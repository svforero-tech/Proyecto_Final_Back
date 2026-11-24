<?php

use App\Controllers\EmpleadoController;
use App\Middleware\AuthMiddleware;
use Slim\App;

return function (App $app) {
    $controller = new EmpleadoController();
    $authMiddleware = new AuthMiddleware();

    $app->get('/api/empleados', [$controller, 'index'])->add($authMiddleware);
    $app->get('/api/empleados/{id}', [$controller, 'show'])->add($authMiddleware);
    $app->post('/api/empleados', [$controller, 'store'])->add($authMiddleware);
    $app->put('/api/empleados/{id}', [$controller, 'update'])->add($authMiddleware);
    $app->patch('/api/empleados/{id}/estado', [$controller, 'cambiarEstado'])->add($authMiddleware);
};