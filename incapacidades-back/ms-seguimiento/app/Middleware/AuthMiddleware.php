<?php

namespace App\Middleware;

use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware
{
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

        $authDbName = $_ENV['DB_AUTH_DATABASE'] ?? getenv('DB_AUTH_DATABASE') ?: 'ms-auth_db';
        $dbHost = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
        $dbPort = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
        $dbUser = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root';
        $dbPass = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

        $capsule = Capsule::instance();
        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => $dbHost,
            'port'      => $dbPort,
            'database'  => $authDbName,
            'username'  => $dbUser,
            'password'  => $dbPass,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ], 'auth_check');

        $usuario = $capsule->getConnection('auth_check')
            ->table('usuarios')
            ->where('token', $token)
            ->where('sesion_activa', true)
            ->where('estado', 'activo')
            ->first();

        if (!$usuario) {
            return $this->unauthorizedResponse('Token inválido o sesión inactiva');
        }

        $request = $request->withAttribute('usuarioAutenticado', $usuario);

        return $handler->handle($request);
    }

    private function unauthorizedResponse(string $mensaje): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode([
            'ok' => false,
            'mensaje' => $mensaje
        ], JSON_UNESCAPED_UNICODE));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}