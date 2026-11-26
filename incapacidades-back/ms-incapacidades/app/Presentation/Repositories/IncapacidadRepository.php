<?php

namespace App\Presentation\Repositories;

use App\Models\Incapacidad;
use DateTime;

class IncapacidadRepository
{
    private array $estadosPermitidos = [
        'registrada',
        'en_revision',
        'aprobada',
        'rechazada',
        'finalizada'
    ];

    public function esFechaValida(string $fecha): bool
    {
        $dt = DateTime::createFromFormat('Y-m-d', $fecha);
        return $dt && $dt->format('Y-m-d') === $fecha;
    }

    private function calcularDias(string $fechaInicio, string $fechaFin): int
    {
        $inicio = new DateTime($fechaInicio);
        $fin = new DateTime($fechaFin);
        $diff = $inicio->diff($fin);
        return (int)$diff->days + 1;
    }

    public function listar(array $filtros = [])
    {
        $query = Incapacidad::query();

        if (!empty($filtros['empleado_id'])) {
            $query->where('empleado_id', (int)$filtros['empleado_id']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', trim((string)$filtros['estado']));
        }

        if (!empty($filtros['tipo'])) {
            $query->where('tipo', 'LIKE', '%' . trim((string)$filtros['tipo']) . '%');
        }

        return $query->orderBy('id', 'desc')->get();
    }

    public function buscarPorId(int $id): ?Incapacidad
    {
        return Incapacidad::find($id);
    }

    public function crear(array $datos): array
    {
        $obligatorios = ['empleado_id', 'fecha_inicio', 'fecha_fin'];
        foreach ($obligatorios as $campo) {
            if (!isset($datos[$campo]) || trim((string)$datos[$campo]) === '') {
                return ['error' => "El campo {$campo} es obligatorio", 'status' => 400];
            }
        }

        $empleadoId = (int)$datos['empleado_id'];
        if ($empleadoId <= 0) {
            return ['error' => 'El empleado_id debe ser un entero positivo', 'status' => 400];
        }

        $fechaInicio = trim((string)$datos['fecha_inicio']);
        $fechaFin = trim((string)$datos['fecha_fin']);

        if (!$this->esFechaValida($fechaInicio) || !$this->esFechaValida($fechaFin)) {
            return ['error' => 'Las fechas deben tener formato válido YYYY-MM-DD', 'status' => 400];
        }

        if ($fechaFin < $fechaInicio) {
            return ['error' => 'La fecha_fin no puede ser anterior a fecha_inicio', 'status' => 400];
        }

        $dias = isset($datos['dias']) && $datos['dias'] !== ''
            ? (int)$datos['dias']
            : $this->calcularDias($fechaInicio, $fechaFin);

        if ($dias <= 0) {
            return ['error' => 'La cantidad de días debe ser mayor a 0', 'status' => 400];
        }

        $estado = trim((string)($datos['estado'] ?? 'registrada'));
        if (!in_array($estado, $this->estadosPermitidos, true)) {
            return ['error' => 'Estado de incapacidad no válido', 'status' => 400];
        }

        $incapacidad = Incapacidad::create([
            'empleado_id'   => $empleadoId,
            'tipo'          => isset($datos['tipo']) ? trim((string)$datos['tipo']) : 'general',
            'diagnostico'   => isset($datos['diagnostico']) ? trim((string)$datos['diagnostico']) : null,
            'fecha_inicio'  => $fechaInicio,
            'fecha_fin'     => $fechaFin,
            'dias'          => $dias,
            'observaciones' => isset($datos['observaciones']) ? trim((string)$datos['observaciones']) : null,
            'estado'        => $estado,
        ]);

        return ['data' => $incapacidad, 'status' => 201];
    }

    public function editar(int $id, array $datos): array
    {
        $incapacidad = Incapacidad::find($id);
        if (!$incapacidad) {
            return ['error' => 'Incapacidad no encontrada', 'status' => 404];
        }

        if (array_key_exists('empleado_id', $datos)) {
            $empleadoId = (int)$datos['empleado_id'];
            if ($empleadoId <= 0) {
                return ['error' => 'El empleado_id debe ser un entero positivo', 'status' => 400];
            }
            $incapacidad->empleado_id = $empleadoId;
        }

        $fechaInicio = array_key_exists('fecha_inicio', $datos)
            ? trim((string)$datos['fecha_inicio'])
            : $incapacidad->fecha_inicio;

        $fechaFin = array_key_exists('fecha_fin', $datos)
            ? trim((string)$datos['fecha_fin'])
            : $incapacidad->fecha_fin;

        if (!$this->esFechaValida($fechaInicio) || !$this->esFechaValida($fechaFin)) {
            return ['error' => 'Las fechas deben tener formato válido YYYY-MM-DD', 'status' => 400];
        }

        if ($fechaFin < $fechaInicio) {
            return ['error' => 'La fecha_fin no puede ser anterior a fecha_inicio', 'status' => 400];
        }

        $incapacidad->fecha_inicio = $fechaInicio;
        $incapacidad->fecha_fin = $fechaFin;

        if (array_key_exists('dias', $datos) && $datos['dias'] !== '') {
            $dias = (int)$datos['dias'];
            if ($dias <= 0) {
                return ['error' => 'La cantidad de días debe ser mayor a 0', 'status' => 400];
            }
            $incapacidad->dias = $dias;
        } elseif (array_key_exists('fecha_inicio', $datos) || array_key_exists('fecha_fin', $datos)) {
            $incapacidad->dias = $this->calcularDias($fechaInicio, $fechaFin);
        }

        foreach (['tipo', 'diagnostico', 'observaciones'] as $campo) {
            if (array_key_exists($campo, $datos)) {
                $val = $datos[$campo];
                $incapacidad->{$campo} = $val !== null ? trim((string)$val) : null;
            }
        }

        if (array_key_exists('estado', $datos)) {
            $estado = trim((string)$datos['estado']);
            if (!in_array($estado, $this->estadosPermitidos, true)) {
                return ['error' => 'Estado de incapacidad no válido', 'status' => 400];
            }
            $incapacidad->estado = $estado;
        }

        $incapacidad->save();

        return ['data' => $incapacidad, 'status' => 200];
    }

    public function cambiarEstado(int $id, string $estado): array
    {
        $incapacidad = Incapacidad::find($id);
        if (!$incapacidad) {
            return ['error' => 'Incapacidad no encontrada', 'status' => 404];
        }

        if (!in_array($estado, $this->estadosPermitidos, true)) {
            return ['error' => 'Estado de incapacidad no válido', 'status' => 400];
        }

        $incapacidad->estado = $estado;
        $incapacidad->save();

        return ['data' => $incapacidad, 'status' => 200];
    }
}