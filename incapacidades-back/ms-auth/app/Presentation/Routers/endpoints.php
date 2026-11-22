<?php

use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;
use Slim\App;

return function (App $app) {
    $controller = new AuthController();
    $authMiddleware = new AuthMiddleware();

    $app->post('/api/auth/login', [$controller, 'login']);
    $app->post('/api/auth/logout', [$controller, 'logout'])->add($authMiddleware);
    $app->get('/api/auth/validar', [$controller, 'validar'])->add($authMiddleware);
};