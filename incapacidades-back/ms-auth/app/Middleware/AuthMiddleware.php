<?php

namespace App\Middleware;

use App\Presentation\Repositories\AuthRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware
{
    private AuthRepository $authRepository;

    public function __construct()
    {
        $this->authRepository = new AuthRepository();
    }

    public function __invoke(Request $request, Handler $handler): Response
    {
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return $handler->handle($request);
        }

        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_starts_with($authHeader, 'Bearer ')
            ? trim(substr($authHeader, 7))
            : trim($authHeader);

        if ($token === '') {
            return $this->unauthorizedResponse('Token de autenticación requerido');
        }

        $usuario = $this->authRepository->validarToken($token);

        if (!$usuario) {
            return $this->unauthorizedResponse('Token inválido o sesión inactiva');
        }

        $request = $request->withAttribute('usuarioAutenticado', $usuario);

        return $handler->handle($request);
    }

    private function unauthorizedResponse(string $mensaje): Response
    {
        $response = new SlimResponse();
        $payload = json_encode([
            'ok' => false,
            'mensaje' => $mensaje
        ], JSON_UNESCAPED_UNICODE);

        $response->getBody()->write($payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}