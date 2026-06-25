<?php
/**
 * Autenticação e controle de permissões.
 */

require_once __DIR__ . '/../models/UsuarioModel.php';

class Auth
{
    public static function iniciarSessao(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(string $login, string $senha): bool
    {
        self::iniciarSessao();

        $model = new UsuarioModel();
        $usuario = $model->autenticar($login, $senha);

        if (!$usuario) {
            return false;
        }

        $_SESSION['usuario'] = $usuario;

        return true;
    }

    public static function logout(): void
    {
        self::iniciarSessao();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }

    public static function usuario(): ?array
    {
        self::iniciarSessao();

        return $_SESSION['usuario'] ?? null;
    }

    public static function requerLogin(): void
    {
        if (!self::usuario()) {
            $destino = $_SERVER['REQUEST_URI'] ?? 'index.php';
            header('Location: login.php?redirect=' . urlencode($destino));
            exit;
        }
    }

    public static function requerAdmin(): void
    {
        self::requerLogin();

        if (!self::isAdmin()) {
            http_response_code(403);
            exit('Acesso negado.');
        }
    }

    public static function requerModulo(string $modulo, string $nivel = 'ver'): void
    {
        self::requerLogin();

        if ($nivel === 'editar') {
            if (!self::podeEditar($modulo)) {
                self::negarAcesso();
            }
            return;
        }

        if (!self::podeVer($modulo)) {
            self::negarAcesso();
        }
    }

    public static function isAdmin(): bool
    {
        $usuario = self::usuario();

        return !empty($usuario['is_admin']);
    }

    public static function podeVer(string $modulo): bool
    {
        if (self::isAdmin()) {
            return true;
        }

        $usuario = self::usuario();
        $perm = $usuario['permissoes'][$modulo] ?? null;

        return !empty($perm['ver']) || !empty($perm['editar']);
    }

    public static function podeEditar(string $modulo): bool
    {
        if (self::isAdmin()) {
            return true;
        }

        $usuario = self::usuario();
        $perm = $usuario['permissoes'][$modulo] ?? null;

        return !empty($perm['editar']);
    }

    public static function podeVerAniversariantes(): bool
    {
        return self::podeVer('aniversariantes_ver');
    }

    public static function podeEnviarAniversario(): bool
    {
        if (self::isAdmin()) {
            return true;
        }

        $usuario = self::usuario();
        $perm = $usuario['permissoes']['aniversariantes_enviar'] ?? null;

        return !empty($perm['ver']);
    }

    public static function permissoesJson(): array
    {
        $usuario = self::usuario();

        if (!$usuario) {
            return [];
        }

        return [
            'is_admin'              => self::isAdmin(),
            'aniversariantes_ver'   => self::podeVerAniversariantes(),
            'aniversariantes_enviar'=> self::podeEnviarAniversario(),
            'modulos'               => $usuario['permissoes'] ?? [],
        ];
    }

    public static function linksMenu(): array
    {
        $itens = [
            'cadastro' => [
                'modulo' => 'cadastro',
                'href'   => 'cadastro.php',
                'titulo' => 'Cadastro',
                'desc'   => 'Incluir ou editar clientes e processos',
                'icon'   => 'user-plus',
            ],
            'consulta_processo' => [
                'modulo' => 'consulta_processo',
                'href'   => 'consulta.php?tipo=processo',
                'titulo' => 'Consulta por Processo',
                'desc'   => 'Buscar por processo, nome ou CPF',
                'icon'   => 'search',
            ],
            'consulta_reclamante' => [
                'modulo' => 'consulta_reclamante',
                'href'   => 'consulta.php?tipo=reclamante',
                'titulo' => 'Consulta por Reclamante',
                'desc'   => 'Localizar por nome ou CPF do reclamante',
                'icon'   => 'user',
            ],
            'consulta_reclamada' => [
                'modulo' => 'consulta_reclamada',
                'href'   => 'consulta.php?tipo=reclamada',
                'titulo' => 'Consulta por Reclamada',
                'desc'   => 'Localizar por nome, CPF ou reclamada',
                'icon'   => 'building',
            ],
            'pauta_audiencias' => [
                'modulo' => 'pauta_audiencias',
                'href'   => 'relatorio.php?tipo=audiencias',
                'titulo' => 'Pauta de Audiências',
                'desc'   => 'Relatório de audiências agendadas',
                'icon'   => 'calendar',
            ],
            'pauta_reclamante' => [
                'modulo' => 'pauta_reclamante',
                'href'   => 'relatorio.php?tipo=reclamante',
                'titulo' => 'Pauta Reclamante',
                'desc'   => 'Lista completa por reclamante',
                'icon'   => 'file',
            ],
            'pericias' => [
                'modulo' => 'pericias',
                'href'   => 'pericias.php',
                'titulo' => 'Perícias',
                'desc'   => 'Relatório de perícias agendadas',
                'icon'   => 'check',
            ],
        ];

        $visiveis = [];
        foreach ($itens as $key => $item) {
            if (self::podeVer($item['modulo'])) {
                $visiveis[$key] = $item;
            }
        }

        return $visiveis;
    }

    private static function negarAcesso(): void
    {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Acesso negado</title>'
            . '<link rel="stylesheet" href="assets/css/style.css"></head><body class="page-acesso-negado">'
            . '<div class="acesso-negado-box"><h1>Acesso negado</h1>'
            . '<p>Você não tem permissão para acessar esta área.</p>'
            . '<a href="index.php" class="btn btn-primary">Voltar ao menu</a></div></body></html>';
        exit;
    }
}
