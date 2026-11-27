<?php

namespace App\Presentation\Repositories;

use App\Models\Seguimiento;
use DateTime;

class SeguimientoRepository
{
    public function esFechaValida(string $fecha): bool
    {
        $dt = DateTime::createFromFormat('Y-m-d', $fecha);
        return $dt && $dt->format('Y-m-d') === $fecha;
    }

    public function listar(array $filtros = [])
    {
        $query = Seguimiento::query();

        if (!empty($filtros['incapacidad_id'])) {
            $query->where('incapacidad_id', (int)$filtros['incapacidad_id']);
        }

        if (!empty($filtros['estado_seguimiento'])) {
            $query->where('estado_seguimiento', trim((string)$filtros['estado_seguimiento']));
        }

        return $query->orderBy('id', 'desc')->get();
    }

    public function listarPorIncapacidad(int $incapacidadId)
    {
        return Seguimiento::where('incapacidad_id', $incapacidadId)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function buscarPorId(int $id): ?Seguimiento
    {
        return Seguimiento::find($id);
    }

    public function crear(array $datos): array
    {
        if (!isset($datos['incapacidad_id']) || trim((string)$datos['incapacidad_id']) === '') {
            return ['error' => 'El campo incapacidad_id es obligatorio', 'status' => 400];
        }

        $incapacidadId = (int)$datos['incapacidad_id'];
        if ($incapacidadId <= 0) {
            return ['error' => 'El incapacidad_id debe ser un entero positivo', 'status' => 400];
        }

        $descripcion = trim((string)($datos['descripcion'] ?? $datos['observacion'] ?? ''));
        if ($descripcion === '') {
            return ['error' => 'El campo descripcion es obligatorio', 'status' => 400];
        }

        $fechaSeguimiento = isset($datos['fecha_seguimiento']) && trim((string)$datos['fecha_seguimiento']) !== ''
            ? trim((string)$datos['fecha_seguimiento'])
            : date('Y-m-d');

        if (!$this->esFechaValida($fechaSeguimiento)) {
            return ['error' => 'La fecha_seguimiento no es válida (use YYYY-MM-DD)', 'status' => 400];
        }

        $estadoSeguimiento = trim((string)($datos['estado_seguimiento'] ?? $datos['estado'] ?? 'en_revision'));

        $usuarioId = isset($datos['usuario_id']) && (int)$datos['usuario_id'] > 0
            ? (int)$datos['usuario_id']
            : null;

        $seguimiento = Seguimiento::create([
            'incapacidad_id'     => $incapacidadId,
            'usuario_id'         => $usuarioId,
            'fecha_seguimiento'  => $fechaSeguimiento,
            'estado_seguimiento' => $estadoSeguimiento,
            'descripcion'        => $descripcion,
        ]);

        return ['data' => $seguimiento, 'status' => 201];
    }
}