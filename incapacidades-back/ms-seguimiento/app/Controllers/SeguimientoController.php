<?php

namespace App\Controllers;

use App\Presentation\Repositories\SeguimientoRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SeguimientoController
{
    private SeguimientoRepository $repository;

    public function __construct()
    {
        $this->repository = new SeguimientoRepository();
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $seguimientos = $this->repository->listar($params);

        return $this->jsonResponse($response, [
            'ok' => true,
            'data' => $seguimientos
        ], 200);
    }

    public function porIncapacidad(Request $request, Response $response, array $args): Response
    {
        $incapacidadId = (int)($args['incapacidad_id'] ?? 0);

        if ($incapacidadId <= 0) {
            return $this->jsonResponse($response, [
                'ok' => false,
                'mensaje' => 'El incapacidad_id debe ser un entero positivo'
            ], 400);
        }

        $seguimientos = $this->repository->listarPorIncapacidad($incapacidadId);

        return $this->jsonResponse($response, [
            'ok' => true,
            'data' => $seguimientos
        ], 200);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int)($args['id'] ?? 0);
        $seguimiento = $this->repository->buscarPorId($id);

        if (!$seguimiento) {
            return $this->jsonResponse($response, [
                'ok' => false,
                'mensaje' => 'Registro de seguimiento no encontrado'
            ], 404);
        }

        return $this->jsonResponse($response, [
            'ok' => true,
            'data' => $seguimiento
        ], 200);
    }

    public function store(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody() ?? [];
        $usuario = $request->getAttribute('usuarioAutenticado');

        if ($usuario && empty($body['usuario_id'])) {
            $body['usuario_id'] = $usuario->id;
        }

        $resultado = $this->repository->crear($body);

        if (isset($resultado['error'])) {
            return $this->jsonResponse($response, [
                'ok' => false,
                'mensaje' => $resultado['error']
            ], $resultado['status'] ?? 400);
        }

        return $this->jsonResponse($response, [
            'ok' => true,
            'mensaje' => 'Seguimiento registrado correctamente',
            'data' => $resultado['data']
        ], $resultado['status'] ?? 201);
    }
}