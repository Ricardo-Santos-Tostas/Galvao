<?php
/**
 * Importa audiências da planilha Excel (aba Trabalhista) para o cadastro.
 */
declare(strict_types=1);

require_once __DIR__ . '/../lib/XlsxReader.php';
require_once __DIR__ . '/ProcessoCNJ.php';
require_once __DIR__ . '/ProcessoModel.php';
require_once __DIR__ . '/RelatorioPautaNaoEncontrados.php';
require_once __DIR__ . '/../views/imprimir/pauta_helpers.php';

final class ImportadorPautaTrabalhista
{
    private ProcessoModel $modelo;

    /** @var array<string, list<int>> */
    private array $indiceProcessos = [];

    public function __construct(?ProcessoModel $modelo = null)
    {
        $this->modelo = $modelo ?? new ProcessoModel();
    }

    /**
     * @return array{
     *   sucesso: bool,
     *   atualizados: int,
     *   nao_encontrados: list<array<string, string>>,
     *   erros: list<string>,
     *   relatorio: ?array{arquivo: string, url: string, total: int}
     * }
     */
    public function importarArquivo(string $caminho, string $aba = 'Trabalhista'): array
    {
        $linhas = XlsxReader::readSheet($caminho, $aba);
        $textos = $this->extrairTextos($linhas);
        $registros = $this->parsearBlocos($textos);

        if ($registros === []) {
            throw new RuntimeException('Nenhuma audiência encontrada na planilha Trabalhista.');
        }

        $this->montarIndiceProcessos();

        $atualizados = 0;
        /** @var array<string, array<string, string>> */
        $naoEncontrados = [];
        $erros = [];

        foreach ($registros as $item) {
            $procExtraido = ProcessoCNJ::extrair($item['processo']) ?? $item['processo'];
            $procAbrev = ProcessoCNJ::abreviado($procExtraido);
            if ($procAbrev === null) {
                $erros[] = 'Processo inválido: ' . $item['processo'];
                continue;
            }

            $chave = ProcessoCNJ::normalizar($procExtraido);
            $ids = $chave !== null ? ($this->indiceProcessos[$chave] ?? []) : [];
            if ($ids === []) {
                if ($chave !== null) {
                    $naoEncontrados[$chave] = $this->montarItemNaoEncontrado($item, $procAbrev, $procExtraido);
                }
                continue;
            }

            foreach ($ids as $id) {
                $this->modelo->atualizarPautaTrabalhista(
                    $id,
                    $item['data'],
                    $item['hora'],
                    $item['mensagem'],
                    $procAbrev
                );
                $atualizados++;
            }
        }

        $listaNaoEncontrados = array_values($naoEncontrados);
        $relatorio = RelatorioPautaNaoEncontrados::salvar($listaNaoEncontrados);

        return [
            'sucesso'         => true,
            'atualizados'     => $atualizados,
            'nao_encontrados' => $listaNaoEncontrados,
            'erros'           => $erros,
            'relatorio'       => $relatorio,
        ];
    }

    /**
     * @param array<string, string> $item
     * @return array<string, string>
     */
    private function montarItemNaoEncontrado(array $item, string $procAbrev, string $procExtraido): array
    {
        return [
            'processo'          => $procAbrev,
            'processo_completo' => ProcessoCNJ::completo($procExtraido) ?? $procExtraido,
            'data'              => $item['data'] ?? '',
            'hora'              => $item['hora'] ?? '',
            'mensagem'          => $item['mensagem'] ?? '',
            'reclamante'        => $item['reclamante'] ?? '',
            'reclamada'         => $item['reclamada'] ?? '',
        ];
    }

    /** @param list<list<string>> $linhas */
    private function extrairTextos(array $linhas): array
    {
        $textos = [];
        foreach ($linhas as $linha) {
            foreach ($linha as $celula) {
                $celula = trim((string) $celula);
                if ($celula !== '' && is_numeric($celula)) {
                    $convertido = XlsxReader::serialParaTexto((float) $celula);
                    if ($convertido !== null) {
                        $celula = $convertido;
                    }
                }
                if ($celula !== '') {
                    $textos[] = $celula;
                }
            }
        }

        return $textos;
    }

    /**
     * @param list<string> $textos
     * @return list<array<string, string>>
     */
    private function parsearBlocos(array $textos): array
    {
        $registros = [];
        $bloco = null;

        foreach ($textos as $texto) {
            if (preg_match(
                '/^(\d{1,2}\/\d{1,2}\/\d{4})\s+(\d{1,2}:\d{2})\s*(?:-\s*)?(.+)$/u',
                $texto,
                $m
            )) {
                if ($bloco !== null) {
                    $registros[] = $this->finalizarBloco($bloco);
                }

                $bloco = [
                    'data'      => $this->formatarData($m[1]),
                    'hora'      => $this->formatarHora($m[2]),
                    'mensagem'  => trim($m[3]),
                    'processo'  => '',
                    'extras'    => [],
                ];

                $procNaMesmaLinha = ProcessoCNJ::extrair($texto);
                if ($procNaMesmaLinha !== null) {
                    $bloco['processo'] = $procNaMesmaLinha;
                }
                continue;
            }

            if ($bloco === null) {
                continue;
            }

            if ($bloco['processo'] === '') {
                $extraido = ProcessoCNJ::extrair($texto);
                if ($extraido !== null) {
                    $bloco['processo'] = $extraido;
                    continue;
                }

                if ($bloco['mensagem'] === '' && !ProcessoCNJ::pareceNumeroProcesso($texto)) {
                    $bloco['mensagem'] = trim($texto);
                }
                continue;
            }

            $bloco['extras'][] = trim($texto);
        }

        if ($bloco !== null) {
            $registros[] = $this->finalizarBloco($bloco);
        }

        return array_values(array_filter($registros, static fn(array $r): bool => ($r['processo'] ?? '') !== ''));
    }

    /**
     * @param array<string, mixed> $bloco
     * @return array<string, string>
     */
    private function finalizarBloco(array $bloco): array
    {
        $reclamante = '';
        $reclamada = '';
        $candidatos = [];

        foreach ($bloco['extras'] ?? [] as $linha) {
            $linha = trim((string) $linha);
            if ($linha === '' || ProcessoCNJ::pareceNumeroProcesso($linha)) {
                continue;
            }
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}\s+\d{1,2}:\d{2}/', $linha)) {
                continue;
            }
            if (preg_match('/^A PARTIR DE\b/i', $linha)) {
                continue;
            }

            $candidatos[] = preg_replace('/\s+X\s*$/iu', '', $linha) ?? $linha;
        }

        foreach ($candidatos as $candidato) {
            if ($this->pareceLocalAudiencia($candidato)) {
                continue;
            }
            if ($reclamante === '') {
                $reclamante = $candidato;
                continue;
            }
            if ($reclamada === '') {
                $reclamada = $candidato;
            }
        }

        if ($reclamante === '' && isset($candidatos[0])) {
            $reclamante = $candidatos[0];
        }
        if ($reclamada === '' && isset($candidatos[1])) {
            $reclamada = $candidatos[1];
        }

        if ($bloco['mensagem'] === '' && $candidatos !== []) {
            foreach ($candidatos as $linha) {
                if ($this->pareceLocalAudiencia($linha) || $linha === $reclamante || $linha === $reclamada) {
                    continue;
                }
                if (!preg_match('/^A PARTIR DE\b/i', $linha)) {
                    $bloco['mensagem'] = $linha;
                    break;
                }
            }
        }

        return [
            'data'       => (string) ($bloco['data'] ?? ''),
            'hora'       => (string) ($bloco['hora'] ?? ''),
            'mensagem'   => (string) ($bloco['mensagem'] ?? ''),
            'processo'   => (string) ($bloco['processo'] ?? ''),
            'reclamante' => $reclamante,
            'reclamada'  => $reclamada,
        ];
    }

    private function formatarData(string $data): string
    {
        $dt = DateTime::createFromFormat('d/m/Y', $data)
            ?: DateTime::createFromFormat('j/n/Y', $data);
        if (!$dt) {
            return $data;
        }

        return $dt->format('d/m/Y');
    }

    private function formatarHora(string $hora): string
    {
        return pautaNormalizarHora($hora);
    }

    private function montarIndiceProcessos(): void
    {
        $this->indiceProcessos = [];
        foreach ($this->modelo->listarProcessosParaIndice() as $row) {
            $proc = (string) ($row['PROC'] ?? '');
            $extraido = ProcessoCNJ::extrair($proc) ?? $proc;
            $chave = ProcessoCNJ::normalizar($extraido);
            if ($chave === null) {
                continue;
            }
            $this->indiceProcessos[$chave][] = (int) $row['CADASTRO'];
        }
    }

    private function pareceLocalAudiencia(string $texto): bool
    {
        return (bool) preg_match('/\b(zoom\.us|sala\d*|impar|auxiliar|videoconfer|cejusc)/iu', $texto);
    }
}
