<?php
/**
 * Gera relatório Excel dos processos não encontrados na importação da pauta.
 */
declare(strict_types=1);

require_once __DIR__ . '/../lib/XlsxWriter.php';

final class RelatorioPautaNaoEncontrados
{
    private const PASTA = __DIR__ . '/../import/nao_encontrados';

    /**
     * @param list<array<string, string>> $registros
     * @return array{arquivo: string, caminho: string, url: string, total: int}|null
     */
    public static function salvar(array $registros): ?array
    {
        if ($registros === []) {
            return null;
        }

        if (!is_dir(self::PASTA) && !mkdir(self::PASTA, 0755, true) && !is_dir(self::PASTA)) {
            throw new RuntimeException('Não foi possível criar a pasta de relatórios.');
        }

        $nome = 'pauta_nao_encontrados_' . date('Y-m-d_His') . '.xlsx';
        $caminho = self::PASTA . DIRECTORY_SEPARATOR . $nome;

        $cabecalhos = ['Data', 'Hora', 'Nº Processo', 'Reclamante', 'Reclamado'];
        $linhas = [];

        foreach ($registros as $item) {
            $linhas[] = [
                $item['data'] ?? '',
                $item['hora'] ?? '',
                $item['processo'] ?? '',
                self::nomeReclamante($item),
                trim($item['reclamada'] ?? ''),
            ];
        }

        XlsxWriter::escrever($caminho, 'Não encontrados', $cabecalhos, $linhas);

        return [
            'arquivo' => $nome,
            'caminho' => $caminho,
            'url'     => 'api/?acao=baixar_relatorio_pauta&arquivo=' . rawurlencode($nome),
            'total'   => count($registros),
        ];
    }

    /** @param array<string, string> $item */
    private static function nomeReclamante(array $item): string
    {
        $reclamante = trim($item['reclamante'] ?? '');
        $reclamada = trim($item['reclamada'] ?? '');

        if ($reclamante !== '' && self::pareceNomePessoa($reclamante)) {
            return $reclamante;
        }

        if ($reclamada !== '' && self::pareceNomePessoa($reclamada)) {
            return $reclamada;
        }

        return $reclamante !== '' ? $reclamante : $reclamada;
    }

    private static function pareceNomePessoa(string $texto): bool
    {
        if ($texto === '') {
            return false;
        }

        if (preg_match('/\b(zoom\.us|sala\d*|impar|auxiliar|videoconfer)/iu', $texto)) {
            return false;
        }

        return (bool) preg_match('/[A-ZÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛÇ]{2,}/u', $texto);
    }

    public static function caminhoSeguro(string $arquivo): ?string
    {
        $arquivo = basename($arquivo);
        if (!preg_match('/^pauta_nao_encontrados_\d{4}-\d{2}-\d{2}_\d{6}\.xlsx$/', $arquivo)) {
            return null;
        }

        $caminho = self::PASTA . DIRECTORY_SEPARATOR . $arquivo;

        return is_file($caminho) ? $caminho : null;
    }
}
