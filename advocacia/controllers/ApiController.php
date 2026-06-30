<?php

/**

 * Controller da API REST para autocomplete e operações AJAX.

 */



require_once __DIR__ . '/../models/ProcessoModel.php';
require_once __DIR__ . '/../models/PericiaModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/LogModel.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/log.php';



class ApiController

{

    private ProcessoModel $model;
    private PericiaModel $pericias;
    private UsuarioModel $usuarios;

    public function __construct()
    {
        $this->model = new ProcessoModel();
        $this->pericias = new PericiaModel();
        $this->usuarios = new UsuarioModel();
    }



    public function handle(): void

    {

        $acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

        Auth::iniciarSessao();
        if (!Auth::usuario()) {
            $this->responder(['erro' => 'Não autenticado'], 401);
        }



        if (!in_array($acao, ['upload_foto', 'upload_documento'], true)) {

            header('Content-Type: application/json; charset=utf-8');

        }



        try {

            switch ($acao) {

                case 'buscar':

                    $this->buscar();

                    break;

                case 'registro':

                    $this->registro();

                    break;

                case 'salvar':

                    $this->salvar();

                    break;

                case 'proximo_id':

                    $this->proximoId();

                    break;

                case 'aniversariantes':

                    $this->aniversariantes();

                    break;

                case 'upload_foto':

                    $this->uploadFoto();

                    break;

                case 'upload_documento':

                    $this->uploadDocumento();

                    break;

                case 'pericia':

                    $this->pericia();

                    break;

                case 'pericia_salvar':

                    $this->periciaSalvar();

                    break;

                case 'usuario_salvar':

                    $this->usuarioSalvar();

                    break;

                case 'usuario_excluir':

                    $this->usuarioExcluir();

                    break;

                default:

                    $this->responder(['erro' => 'Ação inválida'], 400);

            }

        } catch (Throwable $e) {

            $this->responder(['erro' => $e->getMessage()], 500);

        }

    }



    private function buscar(): void

    {

        $tipo = $_GET['tipo'] ?? 'geral';
        $this->exigirConsulta($tipo);

        $termo = $_GET['q'] ?? '';



        $resultados = $this->model->autocomplete($termo, $tipo);

        $this->responder(['resultados' => $resultados]);

    }



    private function registro(): void

    {

        if (!Auth::podeVer('cadastro')
            && !Auth::podeVer('consulta_processo')
            && !Auth::podeVer('consulta_reclamante')
            && !Auth::podeVer('consulta_reclamada')) {
            $this->responder(['erro' => 'Sem permissão'], 403);
        }

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {

            $this->responder(['erro' => 'ID inválido'], 400);

        }



        $registro = $this->model->buscarPorId($id);

        if (!$registro) {

            $this->responder(['erro' => 'Registro não encontrado'], 404);

        }

        $pericia = $this->pericias->buscarPorCadastro($id);
        if ($pericia) {
            $registro['pericia'] = $pericia;
        }

        $this->responder(['registro' => $registro]);

    }



    private function salvar(): void

    {

        if (!Auth::podeEditar('cadastro')) {
            $this->responder(['erro' => 'Sem permissão para editar cadastros'], 403);
        }

        $dados = json_decode(file_get_contents('php://input'), true);

        if (!is_array($dados)) {

            $dados = $_POST;

        }

        $periciaDados = null;
        if (isset($dados['pericia']) && is_array($dados['pericia'])) {
            $periciaDados = $dados['pericia'];
            unset($dados['pericia']);
        }

        $forcarNovo = !empty($dados['_forcar_novo']);
        $substituirId = isset($dados['_substituir_id']) ? (int) $dados['_substituir_id'] : 0;
        unset($dados['_forcar_novo'], $dados['_substituir_id']);

        $idForm = isset($dados['CADASTRO']) && $dados['CADASTRO'] !== '' ? (int) $dados['CADASTRO'] : 0;
        $idExcluirDup = ($idForm > 0 && $this->model->registroExiste($idForm)) ? $idForm : 0;

        if ($substituirId > 0) {
            if (!$this->model->registroExiste($substituirId)) {
                $this->responder(['erro' => 'Cadastro para substituir não encontrado.'], 404);
            }
            $dados['CADASTRO'] = $substituirId;
            $idForm = $substituirId;
        } elseif (!$forcarNovo) {
            $duplicados = $this->model->buscarDuplicados(
                $idExcluirDup,
                trim((string) ($dados['RECLAMANTE'] ?? '')),
                $dados['CPF'] ?? null
            );
            if ($duplicados !== []) {
                $this->responder([
                    'duplicado'  => true,
                    'existentes' => $duplicados,
                ]);
                return;
            }
        }

        $idAntes = ($idForm > 0 && $this->model->registroExiste($idForm))
            ? $this->model->buscarPorId($idForm)
            : null;

        $id = $this->model->salvar($dados);

        $registro = $this->model->buscarPorId($id);

        if (is_array($periciaDados)) {
            $periciaSalva = $this->salvarPericiaDoCadastro($id, $registro, $periciaDados);
            if ($periciaSalva) {
                $registro['pericia'] = $periciaSalva;
            }
        } else {
            $periciaExistente = $this->pericias->buscarPorCadastro($id);
            if ($periciaExistente) {
                $registro['pericia'] = $periciaExistente;
            }
        }

        $nome = trim((string) ($registro['RECLAMANTE'] ?? ''));
        $ref = '#' . $id;

        if ($idAntes) {
            $alteracoes = LogModel::diffCampos(
                $idAntes,
                $registro,
                ProcessoModel::colunas(),
                LogModel::rotulosCadastro()
            );
            $desc = 'Alterou cadastro ' . $ref . ($nome !== '' ? ' — ' . $nome : '');
            Log::registrar('cadastro_editar', $desc, 'cadastro', $ref, ['alteracoes' => $alteracoes]);
        } else {
            $desc = 'Criou cadastro ' . $ref . ($nome !== '' ? ' — ' . $nome : '');
            Log::registrar('cadastro_criar', $desc, 'cadastro', $ref);
        }



        $this->responder([

            'sucesso'  => true,

            'id'       => $id,

            'registro' => $registro,

        ]);

    }



    private function salvarPericiaDoCadastro(int $cadastroId, array $registro, array $periciaDados): ?array
    {
        $idPericia = isset($periciaDados['ID']) && $periciaDados['ID'] !== ''
            ? (int) $periciaDados['ID']
            : 0;

        if ($idPericia <= 0) {
            $existente = $this->pericias->buscarPorCadastro($cadastroId);
            if ($existente) {
                $idPericia = (int) ($existente['ID'] ?? 0);
            }
        }

        $payload = [
            'ID'           => $idPericia > 0 ? $idPericia : null,
            'CADASTRO'     => $cadastroId,
            'ORIGEM'       => 'cadastro',
            'DATA_PERICIA' => $periciaDados['DATA_PERICIA'] ?? null,
            'HORA_PERICIA' => $periciaDados['HORA_PERICIA'] ?? null,
            'NOME_PERITO'  => $periciaDados['NOME_PERITO'] ?? null,
            'ENDERECO'     => $periciaDados['ENDERECO'] ?? ($periciaDados['ENDERECO_PERICIA'] ?? null),
            'RECLAMANTE'   => $registro['RECLAMANTE'] ?? null,
            'CPF'          => $registro['CPF'] ?? null,
            'RECLAMADA'    => $registro['RECLAMADA'] ?? null,
            'PROC_NUM'     => $registro['PROC'] ?? null,
        ];

        if (!$this->pericias->temDadosPericia($payload) && $idPericia <= 0) {
            return null;
        }

        $antes = ($idPericia > 0 && $this->pericias->existe($idPericia))
            ? $this->pericias->buscarPorId($idPericia)
            : null;

        $id = $this->pericias->salvar($payload);
        $pericia = $this->pericias->buscarPorId($id);

        $nome = trim((string) ($pericia['RECLAMANTE'] ?? ''));
        $ref = '#' . $id;

        if ($antes) {
            Log::registrar(
                'pericia_editar',
                'Alterou perícia ' . $ref . ($nome !== '' ? ' — ' . $nome : '') . ' (via cadastro)',
                'pericias',
                $ref
            );
        } else {
            Log::registrar(
                'pericia_criar',
                'Criou perícia ' . $ref . ($nome !== '' ? ' — ' . $nome : '') . ' (via cadastro)',
                'pericias',
                $ref
            );
        }

        return $pericia;
    }



    private function proximoId(): void

    {

        if (!Auth::podeEditar('cadastro')) {
            $this->responder(['erro' => 'Sem permissão para editar cadastros'], 403);
        }

        $id = $this->model->proximoId();

        $this->responder(['id' => $id]);

    }



    private function aniversariantes(): void

    {

        if (!Auth::podeVerAniversariantes()) {
            $this->responder(['erro' => 'Sem permissão para visualizar aniversariantes'], 403);
        }

        $lista = $this->model->aniversariantesDoDia();

        $this->responder([

            'total'           => count($lista),

            'aniversariantes' => $lista,

            'data'            => date('d/m/Y'),

            'data_referencia' => date('d/m') . ' (dia/mês de hoje)',

        ]);

    }



    private function uploadFoto(): void

    {

        if (!Auth::podeEditar('cadastro')) {
            $this->responder(['erro' => 'Sem permissão para editar cadastros'], 403);
        }

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || empty($_FILES['arquivo']['tmp_name'])) {

            $this->responder(['erro' => 'Cadastro ou arquivo inválido'], 400);

        }



        $file = $_FILES['arquivo'];

        if ($file['error'] !== UPLOAD_ERR_OK) {

            $this->responder(['erro' => 'Falha no upload da foto'], 400);

        }



        $mime = mime_content_type($file['tmp_name']) ?: $file['type'];

        if (!str_starts_with($mime, 'image/')) {

            $this->responder(['erro' => 'A foto deve ser uma imagem (JPG, PNG, etc.)'], 400);

        }



        if ($file['size'] > 5 * 1024 * 1024) {

            $this->responder(['erro' => 'Foto muito grande (máx. 5 MB)'], 400);

        }



        $conteudo = file_get_contents($file['tmp_name']);

        $this->model->salvarFoto($id, $conteudo, $mime);

        Log::registrar(
            'cadastro_foto',
            'Importou foto no cadastro #' . $id,
            'cadastro',
            '#' . $id
        );



        $this->responder([

            'sucesso'  => true,

            'registro' => $this->model->buscarPorId($id),

        ]);

    }



    private function uploadDocumento(): void

    {

        if (!Auth::podeEditar('cadastro')) {
            $this->responder(['erro' => 'Sem permissão para editar cadastros'], 403);
        }

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || empty($_FILES['arquivo']['tmp_name'])) {

            $this->responder(['erro' => 'Cadastro ou arquivo inválido'], 400);

        }



        $file = $_FILES['arquivo'];

        if ($file['error'] !== UPLOAD_ERR_OK) {

            $this->responder(['erro' => 'Falha no upload do documento'], 400);

        }



        $mime = mime_content_type($file['tmp_name']) ?: $file['type'];

        $permitidos = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if (!in_array($mime, $permitidos, true)) {

            $this->responder(['erro' => 'Formato não permitido. Use PDF ou imagem.'], 400);

        }



        if ($file['size'] > 15 * 1024 * 1024) {

            $this->responder(['erro' => 'Documento muito grande (máx. 15 MB)'], 400);

        }



        $nome = $file['name'] ?: 'documento';

        $conteudo = file_get_contents($file['tmp_name']);

        $this->model->salvarDocumento($id, $conteudo, $mime, $nome);

        Log::registrar(
            'cadastro_documento',
            'Importou documento no cadastro #' . $id . ' — ' . $nome,
            'cadastro',
            '#' . $id,
            ['arquivo' => $nome]
        );



        $this->responder([

            'sucesso'  => true,

            'registro' => $this->model->buscarPorId($id),

        ]);

    }



    private function pericia(): void

    {

        if (!Auth::podeVer('pericias')) {
            $this->responder(['erro' => 'Sem permissão para perícias'], 403);
        }

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {

            $this->responder(['erro' => 'ID inválido'], 400);

        }



        $pericia = $this->pericias->buscarPorId($id);

        if (!$pericia) {

            $this->responder(['erro' => 'Perícia não encontrada'], 404);

        }



        $this->responder(['pericia' => $pericia]);

    }



    private function periciaSalvar(): void

    {

        if (!Auth::podeEditar('pericias')) {
            $this->responder(['erro' => 'Sem permissão para editar perícias'], 403);
        }

        $dados = json_decode(file_get_contents('php://input'), true);

        if (!is_array($dados)) {

            $dados = $_POST;

        }



        $idPericia = isset($dados['ID']) && $dados['ID'] !== '' ? (int) $dados['ID'] : 0;
        $antes = ($idPericia > 0 && $this->pericias->existe($idPericia))
            ? $this->pericias->buscarPorId($idPericia)
            : null;

        $id = $this->pericias->salvar($dados);

        $pericia = $this->pericias->buscarPorId($id);

        $ref = 'Perícia #' . $id;
        $nome = trim((string) ($pericia['RECLAMANTE'] ?? ''));

        if ($antes) {
            $alteracoes = LogModel::diffCampos(
                $antes,
                $pericia,
                PericiaModel::colunas(),
                LogModel::rotulosPericia()
            );
            Log::registrar(
                'pericia_editar',
                'Alterou ' . $ref . ($nome !== '' ? ' — ' . $nome : ''),
                'pericias',
                '#' . $id,
                ['alteracoes' => $alteracoes]
            );
        } else {
            Log::registrar(
                'pericia_criar',
                'Criou ' . $ref . ($nome !== '' ? ' — ' . $nome : ''),
                'pericias',
                '#' . $id
            );
        }



        $this->responder([

            'sucesso' => true,

            'id'      => $id,

            'pericia' => $pericia,

        ]);

    }



    private function usuarioSalvar(): void

    {

        if (!Auth::isAdmin()) {
            $this->responder(['erro' => 'Acesso negado'], 403);
        }

        $dados = json_decode(file_get_contents('php://input'), true);

        if (!is_array($dados)) {
            $dados = $_POST;
        }

        $idEdicao = isset($dados['id']) && $dados['id'] !== '' ? (int) $dados['id'] : 0;
        $antes = $idEdicao > 0 ? $this->usuarios->buscarPorId($idEdicao) : null;

        $id = $this->usuarios->salvar($dados);
        $usuario = $this->usuarios->buscarPorId($id);

        if ($antes) {
            Log::registrar(
                'usuario_editar',
                'Alterou usuário ' . $usuario['login'] . ' — ' . $usuario['nome'],
                'usuarios',
                '@' . $usuario['login']
            );
        } else {
            Log::registrar(
                'usuario_criar',
                'Criou usuário ' . $usuario['login'] . ' — ' . $usuario['nome'],
                'usuarios',
                '@' . $usuario['login']
            );
        }

        $this->responder([
            'sucesso' => true,
            'id'      => $id,
            'usuario' => $this->usuarios->buscarPorId($id),
        ]);

    }



    private function usuarioExcluir(): void

    {

        if (!Auth::isAdmin()) {
            $this->responder(['erro' => 'Acesso negado'], 403);
        }

        $dados = json_decode(file_get_contents('php://input'), true);

        if (!is_array($dados)) {
            $dados = $_POST;
        }

        $id = (int) ($dados['id'] ?? 0);

        if ($id <= 0) {
            $this->responder(['erro' => 'ID inválido'], 400);
        }

        $logado = Auth::usuario();

        if ($logado && (int) $logado['id'] === $id) {
            $this->responder(['erro' => 'Você não pode excluir seu próprio usuário'], 400);
        }

        $usuario = $this->usuarios->buscarPorId($id);

        $this->usuarios->excluir($id);

        if ($usuario) {
            Log::registrar(
                'usuario_excluir',
                'Excluiu usuário ' . $usuario['login'] . ' — ' . $usuario['nome'],
                'usuarios',
                '@' . $usuario['login']
            );
        }

        $this->responder(['sucesso' => true]);

    }



    private function exigirConsulta(string $tipo): void

    {

        $mapa = [
            'geral'               => 'consulta_processo',
            'processo'            => 'consulta_processo',
            'consulta_processo'   => 'consulta_processo',
            'reclamante'          => 'consulta_reclamante',
            'consulta_reclamante' => 'consulta_reclamante',
            'reclamada'           => 'consulta_reclamada',
            'consulta_reclamada'  => 'consulta_reclamada',
        ];

        $modulo = $mapa[$tipo] ?? 'consulta_processo';

        if ($tipo === 'geral') {
            if (Auth::podeVer('cadastro') || Auth::podeVer('consulta_processo')
                || Auth::podeVer('consulta_reclamante') || Auth::podeVer('consulta_reclamada')) {
                return;
            }
            $this->responder(['erro' => 'Sem permissão'], 403);
        }

        if (!Auth::podeVer($modulo)) {
            $this->responder(['erro' => 'Sem permissão'], 403);
        }

    }



    private function responder(array $dados, int $status = 200): void

    {

        http_response_code($status);

        echo json_encode($dados, JSON_UNESCAPED_UNICODE);

        exit;

    }

}


