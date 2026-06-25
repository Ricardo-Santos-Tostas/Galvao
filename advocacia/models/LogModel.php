<?php
/**
 * Model do log de atividades do sistema.
 */

require_once __DIR__ . '/../config/database.php';

class LogModel
{
    private PDO $db;
    private string $tabela;

    public function __construct()
    {
        $this->db = getConnection();
        $this->tabela = sqlId('log_atividades');
    }

    public static function acoes(): array
    {
        return [
            'login'              => 'Login',
            'logout'             => 'Logout',
            'cadastro_criar'     => 'Cadastro criado',
            'cadastro_editar'    => 'Cadastro alterado',
            'cadastro_foto'      => 'Foto importada',
            'cadastro_documento' => 'Documento importado',
            'pericia_criar'      => 'Perícia criada',
            'pericia_editar'     => 'Perícia alterada',
            'usuario_criar'      => 'Usuário criado',
            'usuario_editar'     => 'Usuário alterado',
            'usuario_excluir'    => 'Usuário excluído',
        ];
    }

    public function registrar(
        string $acao,
        string $descricao,
        ?string $modulo = null,
        ?string $referencia = null,
        ?array $detalhes = null,
        ?array $usuario = null
    ): void {
        $usuario = $usuario ?? ($_SESSION['usuario'] ?? null);

        $sql = 'INSERT INTO ' . $this->tabela . ' ('
            . sqlId('USUARIO_ID') . ', ' . sqlId('USUARIO_LOGIN') . ', ' . sqlId('USUARIO_NOME') . ', '
            . sqlId('ACAO') . ', ' . sqlId('MODULO') . ', ' . sqlId('REFERENCIA') . ', '
            . sqlId('DESCRICAO') . ', ' . sqlId('DETALHES') . ', ' . sqlId('IP')
            . ') VALUES (:usuario_id, :login, :nome, :acao, :modulo, :referencia, :descricao, :detalhes, :ip)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'usuario_id' => $usuario['id'] ?? null,
            'login'      => $usuario['login'] ?? 'sistema',
            'nome'       => $usuario['nome'] ?? 'Sistema',
            'acao'       => $acao,
            'modulo'     => $modulo,
            'referencia' => $referencia,
            'descricao'  => $descricao,
            'detalhes'   => $detalhes ? json_encode($detalhes, JSON_UNESCAPED_UNICODE) : null,
            'ip'         => self::ipCliente(),
        ]);
    }

    public function listar(
        ?string $dataInicio = null,
        ?string $dataFim = null,
        ?int $usuarioId = null,
        ?string $acao = null,
        ?string $busca = null,
        int $limite = 500
    ): array {
        [$sql, $params] = $this->montarConsulta(
            'SELECT *',
            $dataInicio,
            $dataFim,
            $usuarioId,
            $acao,
            $busca
        );

        $sql .= ' ORDER BY ' . sqlId('CRIADO_EM') . ' DESC, ' . sqlId('ID') . ' DESC';
        $sql .= ' LIMIT ' . max(1, min($limite, 2000));

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map([$this, 'formatar'], $stmt->fetchAll());
    }

    public function contar(
        ?string $dataInicio = null,
        ?string $dataFim = null,
        ?int $usuarioId = null,
        ?string $acao = null,
        ?string $busca = null
    ): int {
        [$sql, $params] = $this->montarConsulta(
            'SELECT COUNT(*)',
            $dataInicio,
            $dataFim,
            $usuarioId,
            $acao,
            $busca
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function resumoPorUsuario(?string $dataInicio, ?string $dataFim): array
    {
        [$base, $params] = $this->montarConsulta(
            'SELECT ' . sqlId('USUARIO_NOME') . ' AS nome, COUNT(*) AS total',
            $dataInicio,
            $dataFim,
            null,
            null,
            null
        );

        $sql = $base . ' GROUP BY ' . sqlId('USUARIO_NOME')
            . ' ORDER BY total DESC LIMIT 8';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function usuariosNoLog(): array
    {
        $sql = 'SELECT DISTINCT ' . sqlId('USUARIO_ID') . ' AS id, '
            . sqlId('USUARIO_NOME') . ' AS nome, ' . sqlId('USUARIO_LOGIN') . ' AS login '
            . 'FROM ' . $this->tabela
            . ' WHERE ' . sqlId('USUARIO_ID') . ' IS NOT NULL '
            . 'ORDER BY ' . sqlId('USUARIO_NOME');

        return $this->db->query($sql)->fetchAll();
    }

    private function montarConsulta(
        string $select,
        ?string $dataInicio,
        ?string $dataFim,
        ?int $usuarioId,
        ?string $acao,
        ?string $busca
    ): array {
        $sql = $select . ' FROM ' . $this->tabela . ' WHERE 1=1';
        $params = [];

        if ($dataInicio) {
            $sql .= ' AND ' . sqlId('CRIADO_EM') . ' >= :inicio';
            $params['inicio'] = $dataInicio . ' 00:00:00';
        }
        if ($dataFim) {
            $sql .= ' AND ' . sqlId('CRIADO_EM') . ' <= :fim';
            $params['fim'] = $dataFim . ' 23:59:59';
        }
        if ($usuarioId) {
            $sql .= ' AND ' . sqlId('USUARIO_ID') . ' = :usuario_id';
            $params['usuario_id'] = $usuarioId;
        }
        if ($acao) {
            $sql .= ' AND ' . sqlId('ACAO') . ' = :acao';
            $params['acao'] = $acao;
        }
        if ($busca) {
            $sql .= ' AND (' . sqlId('DESCRICAO') . ' LIKE :busca OR '
                . sqlId('REFERENCIA') . ' LIKE :busca OR '
                . sqlId('USUARIO_NOME') . ' LIKE :busca OR '
                . sqlId('DETALHES') . ' LIKE :busca)';
            $params['busca'] = '%' . $busca . '%';
        }

        return [$sql, $params];
    }

    private function formatar(array $row): array
    {
        $detalhes = null;
        if (!empty($row['DETALHES'])) {
            $detalhes = json_decode($row['DETALHES'], true);
        }

        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $row['CRIADO_EM']);

        return [
            'id'          => (int) $row['ID'],
            'usuario_id'  => $row['USUARIO_ID'] !== null ? (int) $row['USUARIO_ID'] : null,
            'usuario'     => $row['USUARIO_NOME'] ?: $row['USUARIO_LOGIN'],
            'login'       => $row['USUARIO_LOGIN'],
            'acao'        => $row['ACAO'],
            'acao_label'  => self::acoes()[$row['ACAO']] ?? $row['ACAO'],
            'modulo'      => $row['MODULO'],
            'referencia'  => $row['REFERENCIA'],
            'descricao'   => $row['DESCRICAO'],
            'detalhes'    => $detalhes,
            'ip'          => $row['IP'],
            'criado_em'   => $dt ? $dt->format('d/m/Y H:i:s') : $row['CRIADO_EM'],
        ];
    }

    public static function ipCliente(): ?string
    {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    public static function diffCampos(?array $antes, array $depois, array $campos, array $rotulos): array
    {
        $alteracoes = [];

        foreach ($campos as $campo) {
            if (in_array($campo, ['CADASTRO', 'ID'], true)) {
                continue;
            }

            $valorAntes = trim((string) ($antes[$campo] ?? ''));
            $valorDepois = trim((string) ($depois[$campo] ?? ''));

            if ($valorAntes === $valorDepois) {
                continue;
            }

            $rotulo = $rotulos[$campo] ?? $campo;
            $alteracoes[] = [
                'campo'  => $campo,
                'rotulo' => $rotulo,
                'antes'  => $valorAntes !== '' ? $valorAntes : '(vazio)',
                'depois' => $valorDepois !== '' ? $valorDepois : '(vazio)',
            ];
        }

        return $alteracoes;
    }

    public static function rotulosCadastro(): array
    {
        return [
            'RECLAMANTE' => 'Reclamante',
            'CPF'        => 'CPF',
            'DATA_NASC'  => 'Data de nascimento',
            'ENDERE_O'   => 'Endereço',
            'FONE_RTE'   => 'Telefone 1',
            'RECLAMADA'  => 'Reclamada',
            'PROC'       => 'Nº processo',
            'DIA_AUD'    => 'Data audiência',
            'HORA_AUD'   => 'Hora audiência',
            'JUNTA'      => 'Junta',
            'ANDAMENTO'  => 'Andamento',
            'AREA'       => 'Área',
            'CTPS'       => 'CTPS',
            'IDENTIDADE' => 'Identidade',
        ];
    }

    public static function rotulosPericia(): array
    {
        return [
            'DATA_PERICIA' => 'Data',
            'HORA_PERICIA' => 'Hora',
            'RECLAMANTE'   => 'Reclamante',
            'CPF'          => 'CPF',
            'RECLAMADA'    => 'Reclamada',
            'PROC_NUM'     => 'Nº processo',
            'NOME_PERITO'  => 'Nome do perito',
            'ENDERECO'     => 'Endereço',
        ];
    }
}
