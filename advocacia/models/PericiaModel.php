<?php
/**
 * Model de perícias — tabela dedicada no MySQL.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ProcessoModel.php';

class PericiaModel
{
    private PDO $db;
    private string $tabela;

    public function __construct()
    {
        $this->db = getConnection();
        $this->tabela = sqlId('pericias');
    }

    public static function colunas(): array
    {
        return [
            'ID', 'CADASTRO', 'DATA_PERICIA', 'HORA_PERICIA',
            'RECLAMANTE', 'CPF', 'RECLAMADA', 'PROC_NUM',
            'NOME_PERITO', 'ENDERECO', 'ORIGEM',
        ];
    }

    public function listar(?string $dataInicio = null, ?string $dataFim = null): array
    {
        $sql = 'SELECT * FROM ' . $this->tabela
            . " WHERE " . sqlId('ORIGEM') . " = 'cadastro'"
            . ' AND ' . sqlId('DATA_PERICIA') . ' IS NOT NULL AND TRIM(' . sqlId('DATA_PERICIA') . ") != ''";

        $params = [];
        $this->aplicarFiltroData($sql, $params, $dataInicio, $dataFim);

        $sql .= ' ORDER BY ' . $this->exprDataPericia() . ', '
            . sqlId('HORA_PERICIA') . ', ' . sqlId('RECLAMANTE');

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map([$this, 'formatarRegistro'], $stmt->fetchAll());
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = 'SELECT * FROM ' . $this->tabela . ' WHERE ' . sqlId('ID') . ' = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->formatarRegistro($row) : null;
    }

    public function buscarPorCadastro(int $cadastro): ?array
    {
        if ($cadastro <= 0) {
            return null;
        }

        $sql = 'SELECT * FROM ' . $this->tabela
            . ' WHERE ' . sqlId('CADASTRO') . ' = :cadastro'
            . " AND " . sqlId('ORIGEM') . " = 'cadastro'"
            . ' ORDER BY ' . sqlId('ID') . ' DESC LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cadastro' => $cadastro]);
        $row = $stmt->fetch();

        return $row ? $this->formatarRegistro($row) : null;
    }

    public function temDadosPericia(array $dados): bool
    {
        $campos = ['DATA_PERICIA', 'HORA_PERICIA', 'NOME_PERITO', 'ENDERECO'];
        foreach ($campos as $campo) {
            if (isset($dados[$campo]) && trim((string) $dados[$campo]) !== '') {
                return true;
            }
        }

        return false;
    }

    public function salvar(array $dados): int
    {
        $id = isset($dados['ID']) && $dados['ID'] !== '' ? (int) $dados['ID'] : 0;

        $valores = [];
        $isUpdate = $id > 0 && $this->existe($id);

        foreach (self::colunas() as $campo) {
            if ($campo === 'ID') {
                continue;
            }

            if ($isUpdate && $campo === 'ORIGEM') {
                continue;
            }

            if ($campo === 'CADASTRO') {
                $valores[$campo] = isset($dados[$campo]) && $dados[$campo] !== ''
                    ? (int) $dados[$campo]
                    : null;
                continue;
            }

            if ($campo === 'ORIGEM') {
                $origem = trim((string) ($dados['ORIGEM'] ?? 'cadastro'));
                $valores[$campo] = $origem !== '' ? $origem : 'cadastro';
                continue;
            }

            $valor = $dados[$campo] ?? null;
            $valores[$campo] = ($valor === null || trim((string) $valor) === '')
                ? null
                : trim((string) $valor);
        }

        if ($isUpdate) {
            $sets = [];
            foreach ($valores as $campo => $_) {
                $sets[] = sqlId($campo) . ' = :' . $campo;
            }

            $sql = 'UPDATE ' . $this->tabela . ' SET ' . implode(', ', $sets)
                . ' WHERE ' . sqlId('ID') . ' = :ID';
            $valores['ID'] = $id;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($valores);

            return $id;
        }

        $campos = array_keys($valores);
        $cols = implode(', ', array_map('sqlId', $campos));
        $placeholders = implode(', ', array_map(fn($c) => ':' . $c, $campos));

        $sql = 'INSERT INTO ' . $this->tabela . ' (' . $cols . ') VALUES (' . $placeholders . ')';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($valores);

        return (int) $this->db->lastInsertId();
    }

    public function existe(int $id): bool
    {
        $sql = 'SELECT 1 FROM ' . $this->tabela . ' WHERE ' . sqlId('ID') . ' = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);

        return (bool) $stmt->fetchColumn();
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

        if ($coluna === 'DATA_PERICIA') {
            return $this->formatarData($valor);
        }

        if ($coluna === 'HORA_PERICIA') {
            return $this->formatarHora($valor);
        }

        if ($coluna === 'ID' || $coluna === 'CADASTRO') {
            return (string) (int) $valor;
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

        return $texto;
    }

    private function exprDataPericia(): string
    {
        $col = sqlId('DATA_PERICIA');

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
        $expr = $this->exprDataPericia();

        if ($dataInicio) {
            $sql .= ' AND ' . $expr . ' >= :inicio';
            $params['inicio'] = $dataInicio;
        }
        if ($dataFim) {
            $sql .= ' AND ' . $expr . ' <= :fim';
            $params['fim'] = $dataFim;
        }
    }
}
