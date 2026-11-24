<?php

namespace App\Controllers;

use App\Presentation\Repositories\EmpleadoRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class EmpleadoController
{
    private EmpleadoRepository $repository;

    public function __construct()
    {
        $this->repository = new EmpleadoRepository();
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
        $empleados = $this->repository->listar($params);

        return $this->jsonResponse($response, [
            'ok' => true,
            'data' => $empleados
        ], 200);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int)($args['id'] ?? 0);
        $empleado = $this->repository->buscarPorId($id);

        if (!$empleado) {
            return $this->jsonResponse($response, [
                'ok' => false,
                'mensaje' => 'Empleado no encontrado'
            ], 404);
        }

        return $this->jsonResponse($response, [
            'ok' => true,
            'data' => $empleado
        ], 200);
    }

    public function store(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody() ?? [];
        $resultado = $this->repository->crear($body);

        if (isset($resultado['error'])) {
            return $this->jsonResponse($response, [
                'ok' => false,
                'mensaje' => $resultado['error']
            ], $resultado['status']);
        }

        return $this->jsonResponse($response, [
            'ok' => true,
            'mensaje' => 'Empleado creado correctamente',
            'data' => $resultado['data']
        ], $resultado['status']);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int)($args['id'] ?? 0);
        $body = $request->getParsedBody() ?? [];
        $resultado = $this->repository->editar($id, $body);

        if (isset($resultado['error'])) {
            return $this->jsonResponse($response, [
                'ok' => false,
                'mensaje' => $resultado['error']
            ], $resultado['status']);
        }

        return $this->jsonResponse($response, [
            'ok' => true,
            'mensaje' => 'Empleado actualizado correctamente',
            'data' => $resultado['data']
        ], $resultado['status']);
    }

    public function cambiarEstado(Request $request, Response $response, array $args): Response
    {
        $id = (int)($args['id'] ?? 0);
        $body = $request->getParsedBody() ?? [];
        $estado = trim((string)($body['estado'] ?? ''));

        $resultado = $this->repository->cambiarEstado($id, $estado);

        if (isset($resultado['error'])) {
            return $this->jsonResponse($response, [
                'ok' => false,
                'mensaje' => $resultado['error']
            ], $resultado['status']);
        }

        return $this->jsonResponse($response, [
            'ok' => true,
            'mensaje' => 'Estado del empleado actualizado correctamente',
            'data' => $resultado['data']
        ], $resultado['status']);
    }
}