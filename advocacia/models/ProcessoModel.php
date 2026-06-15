<?php
/**
 * Model de acesso aos processos jurídicos (MySQL).
 */

require_once __DIR__ . '/../config/database.php';

class ProcessoModel
{
    private PDO $db;
    private string $tabela;

    public function __construct()
    {
        $this->db = getConnection();
        $this->tabela = sqlId(TABELA);
    }

    public static function colunas(): array
    {
        return [
            'CADASTRO', 'RECLAMANTE', 'DATA_NASC', 'ENDERE_O',
            'FONE_RTE', 'FONE_RTE_2_', 'FONE_RTE_3_', 'FONE_RTE_4_',
            'FALAR_COM_FONE_1_', 'FALAR_COM_FONE_2_', 'FALAR_COM_FONE_3_', 'FALAR_COM_FONE_4_',
            'RECLAMADA', 'END_RDA', 'JUNTA', 'PROC',
            'DIA_AUD', 'HORA_AUD', 'PRA_A_DIA', 'PRA_A_HORA',
            'ANDAMENTO', 'CTPS', 'IDENTIDADE', 'CPF',
            'COL_2__RECLAMADA', 'END_RDA_1', 'cxpra_a',
        ];
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = 'SELECT * FROM ' . $this->tabela . ' WHERE ' . sqlId('CADASTRO') . ' = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->formatarRegistro($row) : null;
    }

    public function autocomplete(string $termo, string $tipo = 'geral', int $limite = 20): array
    {
        $termo = trim($termo);
        if ($termo === '') {
            return [];
        }

        $like = '%' . $termo . '%';
        $inicio = $termo . '%';

        switch ($tipo) {
            case 'processo':
                $where = sqlId('PROC') . ' LIKE :w1';
                $params = ['w1' => $like];
                break;
            case 'reclamante':
                $where = sqlId('RECLAMANTE') . ' LIKE :w1';
                $params = ['w1' => $like];
                break;
            case 'reclamada':
                $where = sqlId('RECLAMADA') . ' LIKE :w1 OR ' . sqlId('COL_2__RECLAMADA') . ' LIKE :w2';
                $params = ['w1' => $like, 'w2' => $like];
                break;
            default:
                $where = sqlId('RECLAMANTE') . ' LIKE :w1 OR ' . sqlId('RECLAMADA') . ' LIKE :w2'
                    . ' OR ' . sqlId('COL_2__RECLAMADA') . ' LIKE :w3 OR ' . sqlId('PROC') . ' LIKE :w4';
                $params = ['w1' => $like, 'w2' => $like, 'w3' => $like, 'w4' => $like];
                break;
        }

        $sql = '
            SELECT ' . sqlId('CADASTRO') . ', ' . sqlId('RECLAMANTE') . ', '
            . sqlId('RECLAMADA') . ', ' . sqlId('PROC') . '
            FROM ' . $this->tabela . '
            WHERE ' . $where . '
            ORDER BY
                CASE
                    WHEN ' . sqlId('RECLAMANTE') . ' LIKE :o1 THEN 0
                    WHEN ' . sqlId('PROC') . ' LIKE :o2 THEN 1
                    WHEN ' . sqlId('RECLAMADA') . ' LIKE :o3 THEN 2
                    WHEN ' . sqlId('RECLAMANTE') . ' LIKE :o4 THEN 3
                    WHEN ' . sqlId('PROC') . ' LIKE :o5 THEN 4
                    WHEN ' . sqlId('RECLAMADA') . ' LIKE :o6 THEN 5
                    ELSE 6
                END,
                ' . sqlId('RECLAMANTE') . ' ASC
            LIMIT :limite
        ';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue('o1', $inicio);
        $stmt->bindValue('o2', $inicio);
        $stmt->bindValue('o3', $inicio);
        $stmt->bindValue('o4', $like);
        $stmt->bindValue('o5', $like);
        $stmt->bindValue('o6', $like);
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        $resultados = [];
        foreach ($stmt->fetchAll() as $row) {
            $display = $this->textoExibicao($row);
            $resultados[] = [
                'id'         => (int) $row['CADASTRO'],
                'label'      => $this->montarLabel($row),
                'display'    => $display,
                'reclamante' => $row['RECLAMANTE'] ?? '',
                'reclamada'  => $row['RECLAMADA'] ?? '',
                'proc'       => $row['PROC'] ?? '',
            ];
        }

        return $resultados;
    }

    public function salvar(array $dados): int
    {
        $id = isset($dados['CADASTRO']) && $dados['CADASTRO'] !== ''
            ? (int) $dados['CADASTRO']
            : 0;

        $campos = self::colunas();
        $valores = [];
        foreach ($campos as $campo) {
            $valores[$campo] = $dados[$campo] ?? null;
        }

        if ($id > 0 && $this->buscarPorId($id)) {
            $sets = [];
            foreach ($campos as $campo) {
                if ($campo === 'CADASTRO') {
                    continue;
                }
                $sets[] = sqlId($campo) . ' = :' . $campo;
            }
            $sql = 'UPDATE ' . $this->tabela . ' SET ' . implode(', ', $sets)
                . ' WHERE ' . sqlId('CADASTRO') . ' = :CADASTRO';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($valores);
            return $id;
        }

        $novoId = $this->proximoId();
        $valores['CADASTRO'] = $novoId;

        $cols = implode(', ', array_map('sqlId', $campos));
        $placeholders = implode(', ', array_map(fn($c) => ':' . $c, $campos));

        $sql = 'INSERT INTO ' . $this->tabela . ' (' . $cols . ') VALUES (' . $placeholders . ')';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($valores);

        return $novoId;
    }

    public function pautaAudiencias(?string $dataInicio = null, ?string $dataFim = null): array
    {
        $sql = 'SELECT * FROM ' . $this->tabela
            . ' WHERE ' . sqlId('DIA_AUD') . ' IS NOT NULL AND TRIM(' . sqlId('DIA_AUD') . ") != ''";
        $params = [];
        $this->aplicarFiltroData($sql, $params, $dataInicio, $dataFim);

        $sql .= ' ORDER BY ' . $this->exprDataAud() . ', ' . sqlId('HORA_AUD') . ', ' . sqlId('RECLAMANTE');

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map([$this, 'formatarRegistro'], $stmt->fetchAll());
    }

    public function pautaReclamante(?string $dataInicio = null, ?string $dataFim = null): array
    {
        $sql = 'SELECT * FROM ' . $this->tabela
            . ' WHERE ' . sqlId('RECLAMANTE') . ' IS NOT NULL AND TRIM(' . sqlId('RECLAMANTE') . ") != ''";

        $params = [];

        if ($dataInicio || $dataFim) {
            $sql .= ' AND ' . sqlId('DIA_AUD') . ' IS NOT NULL AND TRIM(' . sqlId('DIA_AUD') . ") != ''";
            $this->aplicarFiltroData($sql, $params, $dataInicio, $dataFim);
            $sql .= ' ORDER BY ' . $this->exprDataAud() . ', ' . sqlId('RECLAMANTE') . ' ASC';
        } else {
            $sql .= ' ORDER BY ' . sqlId('RECLAMANTE') . ' ASC';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map([$this, 'formatarRegistro'], $stmt->fetchAll());
    }

    /** Converte data do formulário (Y-m-d ou d/m/Y) para Y-m-d. */
    public static function parseDataFiltro(?string $data): ?string
    {
        if ($data === null || trim($data) === '') {
            return null;
        }

        $data = trim($data);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return $data;
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $data, $m)) {
            return sprintf('%s-%s-%s', $m[3], $m[2], $m[1]);
        }

        return null;
    }

    /** Formata Y-m-d para exibição d/m/Y. */
    public static function formatarDataFiltro(?string $data): ?string
    {
        if (!$data) {
            return null;
        }

        $dt = DateTime::createFromFormat('Y-m-d', $data);
        return $dt ? $dt->format('d/m/Y') : $data;
    }

    private function exprDataAud(): string
    {
        $col = sqlId('DIA_AUD');

        return "(
            CASE
                WHEN {$col} REGEXP '^[0-9]{4}-' THEN DATE({$col})
                WHEN {$col} REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}' THEN STR_TO_DATE(SUBSTRING({$col}, 1, 10), '%d/%m/%Y')
                ELSE NULL
            END
        )";
    }

    private function aplicarFiltroData(string &$sql, array &$params, ?string $dataInicio, ?string $dataFim): void
    {
        $expr = $this->exprDataAud();

        if ($dataInicio) {
            $sql .= ' AND ' . $expr . ' >= :inicio';
            $params['inicio'] = $dataInicio;
        }
        if ($dataFim) {
            $sql .= ' AND ' . $expr . ' <= :fim';
            $params['fim'] = $dataFim;
        }
    }

    public function proximoId(): int
    {
        $sql = 'SELECT COALESCE(MAX(' . sqlId('CADASTRO') . '), 0) + 1 FROM ' . $this->tabela;
        return (int) $this->db->query($sql)->fetchColumn();
    }

    public function contarProcessos(): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->tabela;
        return (int) $this->db->query($sql)->fetchColumn();
    }

    public function aniversariantesDoDia(): array
    {
        $hoje = new DateTime('today');
        $mes = (int) $hoje->format('m');
        $dia = (int) $hoje->format('d');
        $expr = $this->exprDataNasc();

        $sql = '
            SELECT ' . sqlId('CADASTRO') . ', ' . sqlId('RECLAMANTE') . ', '
            . sqlId('DATA_NASC') . ', ' . sqlId('FONE_RTE') . ', '
            . sqlId('FONE_RTE_2_') . ', ' . sqlId('FONE_RTE_3_') . ', ' . sqlId('FONE_RTE_4_') . '
            FROM ' . $this->tabela . '
            WHERE ' . sqlId('RECLAMANTE') . ' IS NOT NULL AND TRIM(' . sqlId('RECLAMANTE') . ") != ''"
            . ' AND ' . $expr . ' IS NOT NULL
            AND MONTH(' . $expr . ') = :mes
            AND DAY(' . $expr . ') = :dia
            ORDER BY ' . sqlId('RECLAMANTE') . ' ASC
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['mes' => $mes, 'dia' => $dia]);

        $lista = [];
        foreach ($stmt->fetchAll() as $row) {
            $idade = $this->calcularIdade($row['DATA_NASC']);
            $telefone = $this->extrairTelefoneWhatsApp([
                $row['FONE_RTE'] ?? '',
                $row['FONE_RTE_2_'] ?? '',
                $row['FONE_RTE_3_'] ?? '',
                $row['FONE_RTE_4_'] ?? '',
            ]);

            $lista[] = [
                'id'        => (int) $row['CADASTRO'],
                'nome'      => trim($row['RECLAMANTE']),
                'data_nasc' => $this->formatarValor('DATA_NASC', $row['DATA_NASC']),
                'idade'     => $idade,
                'telefone'  => $telefone,
                'fone_display' => trim($row['FONE_RTE'] ?? '') ?: null,
            ];
        }

        return $lista;
    }

    private function exprDataNasc(): string
    {
        $col = sqlId('DATA_NASC');

        return "(
            CASE
                WHEN {$col} REGEXP '^[0-9]{4}-' THEN DATE({$col})
                WHEN {$col} REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}' THEN STR_TO_DATE(SUBSTRING({$col}, 1, 10), '%d/%m/%Y')
                ELSE NULL
            END
        )";
    }

    private function calcularIdade(?string $dataNasc): ?int
    {
        if (!$dataNasc) {
            return null;
        }

        $texto = trim($dataNasc);
        $dt = DateTime::createFromFormat('Y-m-d', substr($texto, 0, 10));
        if (!$dt && preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $texto, $m)) {
            $dt = DateTime::createFromFormat('Y-m-d', "{$m[3]}-{$m[2]}-{$m[1]}");
        }
        if (!$dt) {
            $ts = strtotime($texto);
            if ($ts !== false) {
                $dt = (new DateTime())->setTimestamp($ts);
            }
        }
        if (!$dt) {
            return null;
        }

        $hoje = new DateTime('today');
        return (int) $dt->diff($hoje)->y;
    }

    private function extrairTelefoneWhatsApp(array $telefones): ?string
    {
        foreach ($telefones as $tel) {
            $tel = trim((string) $tel);
            if ($tel === '' || $tel === '(0 ) - 0') {
                continue;
            }

            $digits = preg_replace('/\D/', '', $tel);
            if (strlen($digits) < 10) {
                continue;
            }

            if (strlen($digits) === 10 || strlen($digits) === 11) {
                $digits = '55' . $digits;
            }

            if (strlen($digits) >= 12) {
                return $digits;
            }
        }

        return null;
    }

    private function montarLabel(array $row): string
    {
        $partes = array_filter([
            $row['RECLAMANTE'] ?? null,
            $row['PROC'] ?? null,
            $row['RECLAMADA'] ?? null,
        ]);

        return implode(' — ', $partes) ?: 'Registro ' . $row['CADASTRO'];
    }

    /** Texto principal para autocomplete (combo estilo Access). */
    private function textoExibicao(array $row): string
    {
        foreach (['RECLAMANTE', 'PROC', 'RECLAMADA'] as $campo) {
            $valor = trim((string) ($row[$campo] ?? ''));
            if ($valor !== '') {
                return $valor;
            }
        }

        return 'Registro ' . $row['CADASTRO'];
    }

    public function formatarRegistro(array $row): array
    {
        $formatado = [];
        foreach (self::colunas() as $coluna) {
            $formatado[$coluna] = $this->formatarValor($coluna, $row[$coluna] ?? null);
        }
        return $formatado;
    }

    private function formatarValor(string $coluna, $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (in_array($coluna, ['DIA_AUD', 'DATA_NASC', 'PRA_A_DIA'], true)) {
            return $this->formatarData($valor);
        }

        if (in_array($coluna, ['HORA_AUD', 'PRA_A_HORA'], true)) {
            return $this->formatarHora($valor);
        }

        return (string) $valor;
    }

    private function formatarData($valor): string
    {
        $texto = trim((string) $valor);

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $texto)) {
            return $texto;
        }

        $timestamp = strtotime($texto);
        if ($timestamp !== false) {
            return date('d/m/Y', $timestamp);
        }

        return $texto;
    }

    private function formatarHora($valor): string
    {
        $texto = trim((string) $valor);

        if (preg_match('/^\d{2}:\d{2}/', $texto)) {
            return substr($texto, 0, 5);
        }

        $timestamp = strtotime($texto);
        if ($timestamp !== false) {
            return date('H:i', $timestamp);
        }

        return $texto;
    }
}
