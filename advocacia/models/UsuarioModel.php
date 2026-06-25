<?php
/**
 * Model de usuários e permissões.
 */

require_once __DIR__ . '/../config/database.php';

class UsuarioModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getConnection();
    }

    public static function modulosSistema(): array
    {
        return [
            'cadastro'            => 'Cadastro',
            'consulta_processo'   => 'Consulta por Processo',
            'consulta_reclamante' => 'Consulta por Reclamante',
            'consulta_reclamada'  => 'Consulta por Reclamada',
            'pauta_audiencias'    => 'Pauta de Audiências',
            'pauta_reclamante'    => 'Pauta Reclamante',
            'pericias'            => 'Perícias',
        ];
    }

    public static function modulosAniversario(): array
    {
        return [
            'aniversariantes_ver'    => 'Visualizar aniversariantes',
            'aniversariantes_enviar' => 'Enviar mensagens de aniversário',
        ];
    }

    public function autenticar(string $login, string $senha): ?array
    {
        $sql = 'SELECT * FROM ' . sqlId('usuarios')
            . ' WHERE ' . sqlId('LOGIN') . ' = :login AND ' . sqlId('ATIVO') . ' = 1 LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['login' => trim($login)]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($senha, $row['SENHA_HASH'])) {
            return null;
        }

        return $this->montarSessao($row);
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = 'SELECT ' . sqlId('ID') . ', ' . sqlId('LOGIN') . ', ' . sqlId('NOME') . ', '
            . sqlId('IS_ADMIN') . ', ' . sqlId('ATIVO') . ', ' . sqlId('CRIADO_EM')
            . ' FROM ' . sqlId('usuarios') . ' WHERE ' . sqlId('ID') . ' = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $usuario = $this->normalizarUsuario($row);
        $usuario['permissoes'] = $this->carregarPermissoes($id);

        return $usuario;
    }

    public function listar(): array
    {
        $sql = 'SELECT ' . sqlId('ID') . ', ' . sqlId('LOGIN') . ', ' . sqlId('NOME') . ', '
            . sqlId('IS_ADMIN') . ', ' . sqlId('ATIVO') . ', ' . sqlId('CRIADO_EM')
            . ' FROM ' . sqlId('usuarios') . ' ORDER BY ' . sqlId('NOME') . ' ASC';
        $rows = $this->db->query($sql)->fetchAll();

        return array_map(function ($row) {
            $usuario = $this->normalizarUsuario($row);
            $usuario['permissoes'] = $this->carregarPermissoes((int) $row['ID']);
            return $usuario;
        }, $rows);
    }

    public function salvar(array $dados): int
    {
        $id = isset($dados['id']) && $dados['id'] !== '' ? (int) $dados['id'] : 0;
        $login = trim((string) ($dados['login'] ?? ''));
        $nome = trim((string) ($dados['nome'] ?? ''));
        $senha = (string) ($dados['senha'] ?? '');
        $ativo = !empty($dados['ativo']) ? 1 : 0;
        $isAdmin = !empty($dados['is_admin']) ? 1 : 0;

        if ($login === '' || $nome === '') {
            throw new InvalidArgumentException('Login e nome são obrigatórios.');
        }

        if ($id > 0) {
            if ($senha !== '') {
                $sql = 'UPDATE ' . sqlId('usuarios') . ' SET '
                    . sqlId('LOGIN') . ' = :login, '
                    . sqlId('NOME') . ' = :nome, '
                    . sqlId('SENHA_HASH') . ' = :senha, '
                    . sqlId('ATIVO') . ' = :ativo, '
                    . sqlId('IS_ADMIN') . ' = :admin '
                    . 'WHERE ' . sqlId('ID') . ' = :id';
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'login'  => $login,
                    'nome'   => $nome,
                    'senha'  => password_hash($senha, PASSWORD_DEFAULT),
                    'ativo'  => $ativo,
                    'admin'  => $isAdmin,
                    'id'     => $id,
                ]);
            } else {
                $sql = 'UPDATE ' . sqlId('usuarios') . ' SET '
                    . sqlId('LOGIN') . ' = :login, '
                    . sqlId('NOME') . ' = :nome, '
                    . sqlId('ATIVO') . ' = :ativo, '
                    . sqlId('IS_ADMIN') . ' = :admin '
                    . 'WHERE ' . sqlId('ID') . ' = :id';
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'login' => $login,
                    'nome'  => $nome,
                    'ativo' => $ativo,
                    'admin' => $isAdmin,
                    'id'    => $id,
                ]);
            }
        } else {
            if ($senha === '') {
                throw new InvalidArgumentException('Informe uma senha para o novo usuário.');
            }

            $sql = 'INSERT INTO ' . sqlId('usuarios') . ' ('
                . sqlId('LOGIN') . ', ' . sqlId('NOME') . ', ' . sqlId('SENHA_HASH') . ', '
                . sqlId('IS_ADMIN') . ', ' . sqlId('ATIVO')
                . ') VALUES (:login, :nome, :senha, :admin, :ativo)';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'login' => $login,
                'nome'  => $nome,
                'senha' => password_hash($senha, PASSWORD_DEFAULT),
                'admin' => $isAdmin,
                'ativo' => $ativo,
            ]);
            $id = (int) $this->db->lastInsertId();
        }

        if (!$isAdmin) {
            $this->salvarPermissoes($id, $dados['permissoes'] ?? []);
        } else {
            $this->limparPermissoes($id);
        }

        return $id;
    }

    public function excluir(int $id): void
    {
        $sql = 'DELETE FROM ' . sqlId('usuarios') . ' WHERE ' . sqlId('ID') . ' = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
    }

    public function garantirAdminPadrao(): void
    {
        $sql = 'SELECT COUNT(*) FROM ' . sqlId('usuarios') . ' WHERE ' . sqlId('LOGIN') . " = 'ricardo'";
        $existe = (int) $this->db->query($sql)->fetchColumn();

        if ($existe > 0) {
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO ' . sqlId('usuarios') . ' ('
            . sqlId('LOGIN') . ', ' . sqlId('NOME') . ', ' . sqlId('SENHA_HASH') . ', '
            . sqlId('IS_ADMIN') . ', ' . sqlId('ATIVO')
            . ') VALUES (:login, :nome, :senha, 1, 1)'
        );
        $stmt->execute([
            'login' => 'ricardo',
            'nome'  => 'Ricardo',
            'senha' => password_hash('583820sa', PASSWORD_DEFAULT),
        ]);
    }

    private function montarSessao(array $row): array
    {
        $usuario = $this->normalizarUsuario($row);
        $usuario['permissoes'] = !empty($row['IS_ADMIN'])
            ? $this->permissoesAdmin()
            : $this->carregarPermissoes((int) $row['ID']);

        return $usuario;
    }

    private function normalizarUsuario(array $row): array
    {
        return [
            'id'        => (int) $row['ID'],
            'login'     => $row['LOGIN'],
            'nome'      => $row['NOME'],
            'is_admin'  => (bool) ($row['IS_ADMIN'] ?? false),
            'ativo'     => (bool) ($row['ATIVO'] ?? true),
            'criado_em' => $row['CRIADO_EM'] ?? null,
        ];
    }

    private function carregarPermissoes(int $usuarioId): array
    {
        $sql = 'SELECT ' . sqlId('MODULO') . ', ' . sqlId('PODE_VER') . ', ' . sqlId('PODE_EDITAR')
            . ' FROM ' . sqlId('usuario_permissoes')
            . ' WHERE ' . sqlId('USUARIO_ID') . ' = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $usuarioId]);

        $permissoes = [];
        foreach ($stmt->fetchAll() as $row) {
            $permissoes[$row['MODULO']] = [
                'ver'    => (bool) $row['PODE_VER'],
                'editar' => (bool) $row['PODE_EDITAR'],
            ];
        }

        return $permissoes;
    }

    private function salvarPermissoes(int $usuarioId, array $permissoes): void
    {
        $this->limparPermissoes($usuarioId);

        $sql = 'INSERT INTO ' . sqlId('usuario_permissoes') . ' ('
            . sqlId('USUARIO_ID') . ', ' . sqlId('MODULO') . ', '
            . sqlId('PODE_VER') . ', ' . sqlId('PODE_EDITAR')
            . ') VALUES (:usuario_id, :modulo, :ver, :editar)';
        $stmt = $this->db->prepare($sql);

        $todosModulos = array_merge(
            array_keys(self::modulosSistema()),
            array_keys(self::modulosAniversario())
        );

        foreach ($todosModulos as $modulo) {
            $cfg = $permissoes[$modulo] ?? [];
            $ver = !empty($cfg['ver']) ? 1 : 0;
            $editar = !empty($cfg['editar']) ? 1 : 0;

            if (!$ver && !$editar) {
                continue;
            }

            if (str_starts_with($modulo, 'aniversariantes_')) {
                $editar = 0;
            }

            if ($editar && !$ver) {
                $ver = 1;
            }

            $stmt->execute([
                'usuario_id' => $usuarioId,
                'modulo'     => $modulo,
                'ver'        => $ver,
                'editar'     => $editar,
            ]);
        }
    }

    private function limparPermissoes(int $usuarioId): void
    {
        $sql = 'DELETE FROM ' . sqlId('usuario_permissoes')
            . ' WHERE ' . sqlId('USUARIO_ID') . ' = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $usuarioId]);
    }

    private function permissoesAdmin(): array
    {
        $permissoes = [];
        foreach (array_keys(self::modulosSistema()) as $modulo) {
            $permissoes[$modulo] = ['ver' => true, 'editar' => true];
        }
        $permissoes['aniversariantes_ver'] = ['ver' => true, 'editar' => false];
        $permissoes['aniversariantes_enviar'] = ['ver' => true, 'editar' => false];
        $permissoes['usuarios'] = ['ver' => true, 'editar' => true];

        return $permissoes;
    }
}
