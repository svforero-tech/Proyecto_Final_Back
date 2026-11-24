<?php

namespace App\Presentation\Repositories;

use App\Models\Empleado;
use DateTime;

class EmpleadoRepository
{
    public function esFechaValida(string $fecha): bool
    {
        $dt = DateTime::createFromFormat('Y-m-d', $fecha);
        return $dt && $dt->format('Y-m-d') === $fecha;
    }

    public function listar(array $filtros = [])
    {
        $query = Empleado::query();

        if (!empty($filtros['documento'])) {
            $query->where('documento', 'LIKE', '%' . trim($filtros['documento']) . '%');
        }

        if (!empty($filtros['area'])) {
            $query->where('area', 'LIKE', '%' . trim($filtros['area']) . '%');
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', trim($filtros['estado']));
        }

        return $query->orderBy('id', 'desc')->get();
    }

    public function buscarPorId(int $id): ?Empleado
    {
        return Empleado::find($id);
    }

    public function crear(array $datos): array
    {
        $camposObligatorios = [
            'nombres', 'apellidos', 'documento', 'correo',
            'telefono', 'cargo', 'area', 'fecha_ingreso'
        ];

        foreach ($camposObligatorios as $campo) {
            if (!isset($datos[$campo]) || trim((string)$datos[$campo]) === '') {
                return ['error' => "El campo {$campo} es obligatorio", 'status' => 400];
            }
        }

        $fechaIngreso = trim((string)$datos['fecha_ingreso']);
        if (!$this->esFechaValida($fechaIngreso)) {
            return ['error' => 'La fecha_ingreso no es válida (use YYYY-MM-DD)', 'status' => 400];
        }

        $documento = trim((string)$datos['documento']);
        $correo = strtolower(trim((string)$datos['correo']));

        if (Empleado::where('documento', $documento)->exists()) {
            return ['error' => 'Ya existe un empleado con ese documento', 'status' => 409];
        }

        if (Empleado::where('correo', $correo)->exists()) {
            return ['error' => 'Ya existe un empleado con ese correo electrónico', 'status' => 409];
        }

        $estado = $datos['estado'] ?? 'activo';
        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            return ['error' => 'El estado debe ser activo o inactivo', 'status' => 400];
        }

        $empleado = Empleado::create([
            'nombres'       => trim((string)$datos['nombres']),
            'apellidos'     => trim((string)$datos['apellidos']),
            'documento'     => $documento,
            'correo'        => $correo,
            'telefono'      => trim((string)$datos['telefono']),
            'cargo'         => trim((string)$datos['cargo']),
            'area'          => trim((string)$datos['area']),
            'fecha_ingreso' => $fechaIngreso,
            'estado'        => $estado,
        ]);

        return ['data' => $empleado, 'status' => 201];
    }

    public function editar(int $id, array $datos): array
    {
        $empleado = Empleado::find($id);
        if (!$empleado) {
            return ['error' => 'Empleado no encontrado', 'status' => 404];
        }

        if (array_key_exists('documento', $datos)) {
            $documento = trim((string)$datos['documento']);
            if ($documento === '') {
                return ['error' => 'El documento no puede estar vacío', 'status' => 400];
            }
            $duplicadoDoc = Empleado::where('documento', $documento)
                ->where('id', '!=', $id)
                ->exists();
            if ($duplicadoDoc) {
                return ['error' => 'Ya existe otro empleado con ese documento', 'status' => 409];
            }
            $empleado->documento = $documento;
        }

        if (array_key_exists('correo', $datos)) {
            $correo = strtolower(trim((string)$datos['correo']));
            if ($correo === '') {
                return ['error' => 'El correo no puede estar vacío', 'status' => 400];
            }
            $duplicadoCorreo = Empleado::where('correo', $correo)
                ->where('id', '!=', $id)
                ->exists();
            if ($duplicadoCorreo) {
                return ['error' => 'Ya existe otro empleado con ese correo electrónico', 'status' => 409];
            }
            $empleado->correo = $correo;
        }

        if (array_key_exists('fecha_ingreso', $datos)) {
            $fechaIngreso = trim((string)$datos['fecha_ingreso']);
            if (!$this->esFechaValida($fechaIngreso)) {
                return ['error' => 'La fecha_ingreso no es válida (use YYYY-MM-DD)', 'status' => 400];
            }
            $empleado->fecha_ingreso = $fechaIngreso;
        }

        foreach (['nombres', 'apellidos', 'telefono', 'cargo', 'area'] as $campo) {
            if (array_key_exists($campo, $datos)) {
                $valor = trim((string)$datos[$campo]);
                if ($valor === '') {
                    return ['error' => "El campo {$campo} no puede estar vacío", 'status' => 400];
                }
                $empleado->{$campo} = $valor;
            }
        }

        if (array_key_exists('estado', $datos)) {
            $estado = trim((string)$datos['estado']);
            if (!in_array($estado, ['activo', 'inactivo'], true)) {
                return ['error' => 'El estado debe ser activo o inactivo', 'status' => 400];
            }
            $empleado->estado = $estado;
        }

        $empleado->save();

        return ['data' => $empleado, 'status' => 200];
    }

    public function cambiarEstado(int $id, string $estado): array
    {
        $empleado = Empleado::find($id);
        if (!$empleado) {
            return ['error' => 'Empleado no encontrado', 'status' => 404];
        }

        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            return ['error' => 'Estado inválido. Use activo o inactivo', 'status' => 400];
        }

        $empleado->estado = $estado;
        $empleado->save();

        return ['data' => $empleado, 'status' => 200];
    }
}