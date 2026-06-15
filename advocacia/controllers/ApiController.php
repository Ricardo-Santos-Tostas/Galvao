<?php
/**
 * Controller da API REST para autocomplete e operações AJAX.
 */

require_once __DIR__ . '/../models/ProcessoModel.php';

class ApiController
{
    private ProcessoModel $model;

    public function __construct()
    {
        $this->model = new ProcessoModel();
    }

    public function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

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
                default:
                    $this->responder(['erro' => 'Ação inválida'], 400);
            }
        } catch (Throwable $e) {
            $this->responder(['erro' => $e->getMessage()], 500);
        }
    }

    private function buscar(): void
    {
        $termo = $_GET['q'] ?? '';
        $tipo  = $_GET['tipo'] ?? 'geral';

        $resultados = $this->model->autocomplete($termo, $tipo);
        $this->responder(['resultados' => $resultados]);
    }

    private function registro(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->responder(['erro' => 'ID inválido'], 400);
        }

        $registro = $this->model->buscarPorId($id);
        if (!$registro) {
            $this->responder(['erro' => 'Registro não encontrado'], 404);
        }

        $this->responder(['registro' => $registro]);
    }

    private function salvar(): void
    {
        $dados = json_decode(file_get_contents('php://input'), true);
        if (!is_array($dados)) {
            $dados = $_POST;
        }

        $id = $this->model->salvar($dados);
        $registro = $this->model->buscarPorId($id);

        $this->responder([
            'sucesso'  => true,
            'id'       => $id,
            'registro' => $registro,
        ]);
    }

    private function proximoId(): void
    {
        $id = $this->model->proximoId();
        $this->responder(['id' => $id]);
    }

    private function aniversariantes(): void
    {
        $lista = $this->model->aniversariantesDoDia();
        $this->responder([
            'total'           => count($lista),
            'aniversariantes' => $lista,
            'data'            => date('d/m/Y'),
            'data_referencia' => date('d/m') . ' (dia/mês de hoje)',
        ]);
    }

    private function responder(array $dados, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
