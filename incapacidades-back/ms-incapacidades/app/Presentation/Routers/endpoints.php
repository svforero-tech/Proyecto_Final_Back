<?php

use App\Controllers\SeguimientoController;
use App\Middleware\AuthMiddleware;
use Slim\App;

return function (App $app) {
    $controller = new SeguimientoController();
    $authMiddleware = new AuthMiddleware();

    $app->get('/api/seguimientos', [$controller, 'index'])->add($authMiddleware);
    $app->get('/api/seguimientos/incapacidad/{incapacidad_id}', [$controller, 'porIncapacidad'])->add($authMiddleware);
    $app->get('/api/seguimientos/{id}', [$controller, 'show'])->add($authMiddleware);
    $app->post('/api/seguimientos', [$controller, 'store'])->add($authMiddleware);
};