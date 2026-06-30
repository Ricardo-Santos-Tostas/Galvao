<?php
/**
 * Model de acesso aos processos jurídicos (MySQL).
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/TelefoneBr.php';

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
            'ANDAMENTO', 'CTPS', 'IDENTIDADE', 'CPF', 'AREA',
            'COL_2__RECLAMADA', 'END_RDA_1', 'cxpra_a',
        ];
    }

    public static function areasJuridicas(): array
    {
        return [
            'trabalhista'      => 'Trabalhista',
            'previdenciario'   => 'Previdenciário',
            'consumidor'       => 'Consumidor',
        ];
    }

    public function buscarPorId(int $id): ?array
    {
        $cols = array_map('sqlId', self::colunas());
        $sql = 'SELECT ' . implode(', ', $cols) . ', '
            . sqlId('DOCUMENTO_NOME') . ', ' . sqlId('DOCUMENTO_TIPO') . ', '
            . '(CASE WHEN ' . sqlId('FOTO') . ' IS NOT NULL AND LENGTH(' . sqlId('FOTO') . ') > 0 THEN 1 ELSE 0 END) AS TEM_FOTO, '
            . '(CASE WHEN ' . sqlId('DOCUMENTO') . ' IS NOT NULL AND LENGTH(' . sqlId('DOCUMENTO') . ') > 0 THEN 1 ELSE 0 END) AS TEM_DOCUMENTO '
            . 'FROM ' . $this->tabela . ' WHERE ' . sqlId('CADASTRO') . ' = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $formatado = $this->formatarRegistro($row);
        $formatado['tem_foto'] = (bool) ($row['TEM_FOTO'] ?? false);
        $formatado['tem_documento'] = (bool) ($row['TEM_DOCUMENTO'] ?? false);
        $formatado['documento_nome'] = $row['DOCUMENTO_NOME'] ?? null;

        if ($formatado['tem_foto']) {
            $formatado['foto_url'] = 'midia.php?id=' . $id . '&tipo=foto&t=' . time();
        }
        if ($formatado['tem_documento']) {
            $formatado['documento_url'] = 'midia.php?id=' . $id . '&tipo=documento';
        }

        return $formatado;
    }

    public function registroExiste(int $id): bool
    {
        $sql = 'SELECT 1 FROM ' . $this->tabela . ' WHERE ' . sqlId('CADASTRO') . ' = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    /** CPF com 11 dígitos válidos para comparação (ignora vazio e sequências repetidas). */
    public static function cpfUtilParaComparacao(?string $cpf): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $cpf);
        if (strlen($digits) !== 11) {
            return null;
        }
        if (preg_match('/^(\d)\1{10}$/', $digits)) {
            return null;
        }

        return $digits;
    }

    public static function normalizarNomeComparacao(string $nome): string
    {
        $nome = preg_replace('/\s+/u', ' ', trim($nome));

        return mb_strtoupper($nome, 'UTF-8');
    }

    /**
     * Busca cadastros existentes com o mesmo nome ou CPF.
     *
     * @return list<array{id: int, reclamante: ?string, cpf: ?string, reclamada: ?string, proc: ?string, label: string, por_nome: bool, por_cpf: bool}>
     */
    public function buscarDuplicados(int $excluirId, string $nome, ?string $cpf): array
    {
        $nomeNorm = self::normalizarNomeComparacao($nome);
        $cpfDigits = self::cpfUtilParaComparacao($cpf);

        if ($nomeNorm === '' && $cpfDigits === null) {
            return [];
        }

        $matchParts = [];
        $params = [];

        if ($nomeNorm !== '') {
            $matchParts[] = 'UPPER(TRIM(' . sqlId('RECLAMANTE') . ')) = :nome';
            $params['nome'] = $nomeNorm;
        }
        if ($cpfDigits !== null) {
            $cpfExpr = "REPLACE(REPLACE(REPLACE(" . sqlId('CPF') . ", '.', ''), '-', ''), ' ', '')";
            $matchParts[] = $cpfExpr . ' = :cpf_digits';
            $params['cpf_digits'] = $cpfDigits;
        }

        $sql = 'SELECT ' . sqlId('CADASTRO') . ', ' . sqlId('RECLAMANTE') . ', '
            . sqlId('CPF') . ', ' . sqlId('RECLAMADA') . ', ' . sqlId('PROC') . '
            FROM ' . $this->tabela . '
            WHERE ' . ($excluirId > 0 ? sqlId('CADASTRO') . ' != :excluir AND ' : '')
            . '(' . implode(' OR ', $matchParts) . ')
            ORDER BY ' . sqlId('CADASTRO') . ' ASC
            LIMIT 10';

        if ($excluirId > 0) {
            $params['excluir'] = $excluirId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $resultados = [];
        foreach ($stmt->fetchAll() as $row) {
            $porNome = $nomeNorm !== ''
                && self::normalizarNomeComparacao((string) ($row['RECLAMANTE'] ?? '')) === $nomeNorm;
            $porCpf = $cpfDigits !== null
                && self::cpfUtilParaComparacao($row['CPF'] ?? null) === $cpfDigits;

            $resultados[] = [
                'id'         => (int) $row['CADASTRO'],
                'reclamante' => $row['RECLAMANTE'] ?? null,
                'cpf'        => $row['CPF'] ?? null,
                'reclamada'  => $row['RECLAMADA'] ?? null,
                'proc'       => $row['PROC'] ?? null,
                'label'      => $this->montarLabel($row),
                'por_nome'   => $porNome,
                'por_cpf'    => $porCpf,
            ];
        }

        return $resultados;
    }

    public function salvarFoto(int $id, string $conteudo, string $mime): void
    {
        if (!$this->registroExiste($id)) {
            throw new RuntimeException('Cadastro não encontrado. Salve o registro antes de importar a foto.');
        }

        $sql = 'UPDATE ' . $this->tabela . ' SET ' . sqlId('FOTO') . ' = :foto, '
            . sqlId('FOTO_TIPO') . ' = :tipo WHERE ' . sqlId('CADASTRO') . ' = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':foto', $conteudo, PDO::PARAM_LOB);
        $stmt->bindValue(':tipo', $mime);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function salvarDocumento(int $id, string $conteudo, string $mime, string $nome): void
    {
        if (!$this->registroExiste($id)) {
            throw new RuntimeException('Cadastro não encontrado. Salve o registro antes de importar o documento.');
        }

        $sql = 'UPDATE ' . $this->tabela . ' SET ' . sqlId('DOCUMENTO') . ' = :doc, '
            . sqlId('DOCUMENTO_TIPO') . ' = :tipo, ' . sqlId('DOCUMENTO_NOME') . ' = :nome WHERE '
            . sqlId('CADASTRO') . ' = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':doc', $conteudo, PDO::PARAM_LOB);
        $stmt->bindValue(':tipo', $mime);
        $stmt->bindValue(':nome', $nome);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function excluirDocumento(int $id): void
    {
        if (!$this->registroExiste($id)) {
            throw new RuntimeException('Cadastro não encontrado.');
        }

        $sql = 'UPDATE ' . $this->tabela . ' SET '
            . sqlId('DOCUMENTO') . ' = NULL, '
            . sqlId('DOCUMENTO_TIPO') . ' = NULL, '
            . sqlId('DOCUMENTO_NOME') . ' = NULL WHERE '
            . sqlId('CADASTRO') . ' = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function obterFoto(int $id): ?array
    {
        $sql = 'SELECT ' . sqlId('FOTO') . ', ' . sqlId('FOTO_TIPO') . ' FROM ' . $this->tabela
            . ' WHERE ' . sqlId('CADASTRO') . ' = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row || empty($row['FOTO'])) {
            return null;
        }

        return [
            'conteudo' => $row['FOTO'],
            'tipo'     => $row['FOTO_TIPO'] ?: 'image/jpeg',
        ];
    }

    /** @return array<int, true> IDs de cadastros que possuem foto. */
    public function cadastrosComFoto(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = 'SELECT ' . sqlId('CADASTRO') . ' FROM ' . $this->tabela
            . ' WHERE ' . sqlId('CADASTRO') . " IN ({$placeholders})"
            . ' AND ' . sqlId('FOTO') . ' IS NOT NULL AND LENGTH(' . sqlId('FOTO') . ') > 0';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);

        $mapa = [];
        while ($id = $stmt->fetchColumn()) {
            $mapa[(int) $id] = true;
        }

        return $mapa;
    }

    public function obterDocumento(int $id): ?array
    {
        $sql = 'SELECT ' . sqlId('DOCUMENTO') . ', ' . sqlId('DOCUMENTO_TIPO') . ', '
            . sqlId('DOCUMENTO_NOME') . ' FROM ' . $this->tabela
            . ' WHERE ' . sqlId('CADASTRO') . ' = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row || empty($row['DOCUMENTO'])) {
            return null;
        }

        return [
            'conteudo' => $row['DOCUMENTO'],
            'tipo'     => $row['DOCUMENTO_TIPO'] ?: 'application/octet-stream',
            'nome'     => $row['DOCUMENTO_NOME'] ?: 'documento',
        ];
    }

    public function autocomplete(string $termo, string $tipo = 'geral', int $limite = 20): array
    {
        $termo = trim($termo);
        if ($termo === '') {
            return [];
        }

        $like = '%' . $termo . '%';
        $inicio = $termo . '%';
        $params = [];

        switch ($tipo) {
            case 'processo':
                $where = $this->montarCondicaoBusca([
                    sqlId('PROC') . ' LIKE :w1',
                ], $termo, $params, 'w1', $like);
                break;
            case 'reclamante':
                $where = $this->montarCondicaoBusca([
                    sqlId('RECLAMANTE') . ' LIKE :w1',
                ], $termo, $params, 'w1', $like);
                break;
            case 'reclamada':
                $where = $this->montarCondicaoBusca([
                    sqlId('RECLAMADA') . ' LIKE :w1',
                    sqlId('COL_2__RECLAMADA') . ' LIKE :w2',
                ], $termo, $params, 'w1', $like, ['w2' => $like]);
                break;
            default:
                $where = $this->montarCondicaoBusca([
                    sqlId('RECLAMANTE') . ' LIKE :w1',
                    sqlId('RECLAMADA') . ' LIKE :w2',
                    sqlId('COL_2__RECLAMADA') . ' LIKE :w3',
                    sqlId('PROC') . ' LIKE :w4',
                ], $termo, $params, 'w1', $like, ['w2' => $like, 'w3' => $like, 'w4' => $like]);
                break;
        }

        $sql = '
            SELECT ' . sqlId('CADASTRO') . ', ' . sqlId('RECLAMANTE') . ', '
            . sqlId('RECLAMADA') . ', ' . sqlId('PROC') . ', ' . sqlId('CPF') . '
            FROM ' . $this->tabela . '
            WHERE ' . $where . '
            ORDER BY
                CASE
                    WHEN ' . sqlId('RECLAMANTE') . ' LIKE :o1 THEN 0
                    WHEN ' . sqlId('CPF') . ' LIKE :o7 THEN 1
                    WHEN ' . sqlId('PROC') . ' LIKE :o2 THEN 2
                    WHEN ' . sqlId('RECLAMADA') . ' LIKE :o3 THEN 3
                    WHEN ' . sqlId('RECLAMANTE') . ' LIKE :o4 THEN 4
                    WHEN ' . sqlId('CPF') . ' LIKE :o8 THEN 5
                    WHEN ' . sqlId('PROC') . ' LIKE :o5 THEN 6
                    WHEN ' . sqlId('RECLAMADA') . ' LIKE :o6 THEN 7
                    ELSE 8
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
        $stmt->bindValue('o7', $inicio);
        $stmt->bindValue('o8', $like);
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        $resultados = [];
        foreach ($stmt->fetchAll() as $row) {
            $display = $this->textoExibicao($row, $termo);
            $resultados[] = [
                'id'         => (int) $row['CADASTRO'],
                'label'      => $this->montarLabel($row),
                'display'    => $display,
                'reclamante' => $row['RECLAMANTE'] ?? '',
                'reclamada'  => $row['RECLAMADA'] ?? '',
                'proc'       => $row['PROC'] ?? '',
                'cpf'        => $row['CPF'] ?? '',
            ];
        }

        return $resultados;
    }

    /** Monta condições LIKE incluindo busca por CPF (formatado e só números). */
    private function montarCondicaoBusca(array $condicoes, string $termo, array &$params, string $primeiraChave, string $like, array $extras = []): string
    {
        $params[$primeiraChave] = $like;
        foreach ($extras as $chave => $valor) {
            $params[$chave] = $valor;
        }

        $partes = $condicoes;
        $partes[] = sqlId('CPF') . ' LIKE :cpf_like';
        $params['cpf_like'] = $like;

        $termoDigits = preg_replace('/\D/', '', $termo);
        if ($termoDigits !== '' && strlen($termoDigits) >= 3) {
            $cpfExpr = "REPLACE(REPLACE(REPLACE(" . sqlId('CPF') . ", '.', ''), '-', ''), ' ', '')";
            $partes[] = $cpfExpr . ' LIKE :cpf_digits';
            $params['cpf_digits'] = '%' . $termoDigits . '%';
        }

        return implode(' OR ', $partes);
    }

    public function salvar(array $dados): int
    {
        $id = isset($dados['CADASTRO']) && $dados['CADASTRO'] !== ''
            ? (int) $dados['CADASTRO']
            : 0;

        $campos = self::colunas();
        $valores = [];
        foreach ($campos as $campo) {
            $valor = $dados[$campo] ?? null;
            if (in_array($campo, TelefoneBr::campos(), true)) {
                $valor = TelefoneBr::normalizar($valor);
            }
            if ($campo === 'AREA' && $valor !== null) {
                $valor = trim((string) $valor) ?: null;
            }
            $valores[$campo] = $valor;
        }

        if ($id > 0 && $this->registroExiste($id)) {
            $sets = [];
            $params = ['CADASTRO' => $id];
            foreach ($campos as $campo) {
                if ($campo === 'CADASTRO') {
                    continue;
                }
                if (!array_key_exists($campo, $dados)) {
                    continue;
                }
                $sets[] = sqlId($campo) . ' = :' . $campo;
                $params[$campo] = $valores[$campo];
            }
            if ($sets === []) {
                return $id;
            }
            $sql = 'UPDATE ' . $this->tabela . ' SET ' . implode(', ', $sets)
                . ' WHERE ' . sqlId('CADASTRO') . ' = :CADASTRO';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $id;
        }

        $novoId = ($id > 0 && !$this->registroExiste($id)) ? $id : $this->proximoId();
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
            . sqlId('DATA_NASC') . ', ' . sqlId('CPF') . ', ' . sqlId('FONE_RTE') . ', '
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
            $telefones = [
                $row['FONE_RTE'] ?? '',
                $row['FONE_RTE_2_'] ?? '',
                $row['FONE_RTE_3_'] ?? '',
                $row['FONE_RTE_4_'] ?? '',
            ];
            $telefone = $this->extrairTelefoneWhatsApp($telefones);

            $lista[] = [
                'id'        => (int) $row['CADASTRO'],
                'nome'      => trim($row['RECLAMANTE']),
                'cpf'       => trim($row['CPF'] ?? '') ?: null,
                'data_nasc' => $this->formatarValor('DATA_NASC', $row['DATA_NASC']),
                'idade'     => $idade,
                'telefone'  => $telefone,
                'fone_display' => TelefoneBr::primeiroFormatado($telefones),
            ];
        }

        return $lista;
    }

    private function exprDataNasc(): string
    {
        $col = sqlId('DATA_NASC');
        $parte2 = "CAST(SUBSTRING_INDEX(SUBSTRING_INDEX({$col}, '/', 2), '/', -1) AS UNSIGNED)";

        // Access/Excel costuma gravar m/d/Y (ex: 6/30/1957); legado BR usa d/m/Y (ex: 30/06/1957).
        return "(
            CASE
                WHEN {$col} REGEXP '^[0-9]{4}-' THEN DATE({$col})
                WHEN {$col} REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}'
                    AND CAST(SUBSTRING_INDEX({$col}, '/', 1) AS UNSIGNED) > 12
                    THEN STR_TO_DATE({$col}, '%d/%m/%Y')
                WHEN {$col} REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}'
                    AND {$parte2} > 12
                    THEN STR_TO_DATE({$col}, '%m/%d/%Y')
                WHEN {$col} REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}'
                    THEN STR_TO_DATE({$col}, '%m/%d/%Y')
                WHEN {$col} REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}'
                    THEN STR_TO_DATE(SUBSTRING({$col}, 1, 10), '%d/%m/%Y')
                ELSE NULL
            END
        )";
    }

    private function parseDataNascPhp(?string $dataNasc): ?DateTime
    {
        if ($dataNasc === null || trim($dataNasc) === '') {
            return null;
        }

        $texto = trim($dataNasc);

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $texto, $m)) {
            $dt = DateTime::createFromFormat('Y-m-d', "{$m[1]}-{$m[2]}-{$m[3]}");
            return $dt ?: null;
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $texto, $m)) {
            $a = (int) $m[1];
            $b = (int) $m[2];
            $ano = $m[3];

            if ($a > 12) {
                $dt = DateTime::createFromFormat('d/m/Y', "{$m[1]}/{$m[2]}/{$ano}");
            } elseif ($b > 12) {
                $dt = DateTime::createFromFormat('m/d/Y', "{$m[1]}/{$m[2]}/{$ano}");
            } else {
                $dt = DateTime::createFromFormat('m/d/Y', "{$m[1]}/{$m[2]}/{$ano}");
            }

            return $dt ?: null;
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $texto, $m)) {
            $dt = DateTime::createFromFormat('d/m/Y', $texto);
            if ($dt) {
                return $dt;
            }
        }

        $ts = strtotime($texto);

        return $ts !== false ? (new DateTime())->setTimestamp($ts) : null;
    }

    private function calcularIdade(?string $dataNasc): ?int
    {
        $dt = $this->parseDataNascPhp($dataNasc);
        if (!$dt) {
            return null;
        }

        $hoje = new DateTime('today');
        return (int) $dt->diff($hoje)->y;
    }

    private function extrairTelefoneWhatsApp(array $telefones): ?string
    {
        foreach ($telefones as $tel) {
            $whatsapp = TelefoneBr::paraWhatsApp($tel);
            if ($whatsapp !== null) {
                return $whatsapp;
            }
        }

        return null;
    }

    private function montarLabel(array $row): string
    {
        $partes = array_filter([
            $row['RECLAMANTE'] ?? null,
            !empty(trim((string) ($row['CPF'] ?? ''))) ? 'CPF: ' . trim($row['CPF']) : null,
            $row['PROC'] ?? null,
            $row['RECLAMADA'] ?? null,
        ]);

        return implode(' — ', $partes) ?: 'Registro ' . $row['CADASTRO'];
    }

    /** Texto principal para autocomplete (combo estilo Access). */
    private function textoExibicao(array $row, string $termo = ''): string
    {
        $termoDigits = preg_replace('/\D/', '', $termo);
        if ($termoDigits !== '' && strlen($termoDigits) >= 3) {
            $cpf = trim((string) ($row['CPF'] ?? ''));
            $cpfDigits = preg_replace('/\D/', '', $cpf);
            if ($cpfDigits !== '' && str_contains($cpfDigits, $termoDigits)) {
                return $cpf;
            }
        }

        foreach (['RECLAMANTE', 'PROC', 'RECLAMADA', 'CPF'] as $campo) {
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

        if (in_array($coluna, TelefoneBr::campos(), true)) {
            return TelefoneBr::normalizar((string) $valor);
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
