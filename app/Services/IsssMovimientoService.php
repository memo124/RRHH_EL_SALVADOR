<?php

namespace App\Services;

use App\Services\Concerns\BuildsDelimitedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Gestiona las altas y bajas de empleados ante el ISSS.
 *
 * Decisión de diseño: el ISSS salvadoreño no ofrece una API pública de afiliación,
 * por lo que el flujo consiste en llevar una bitácora de movimientos pendientes que
 * el área de RRHH exporta y transcribe manualmente al portal patronal del ISSS,
 * marcando luego el movimiento como "enviado".
 */
class IsssMovimientoService
{
    use BuildsDelimitedFile;

    public function registrarAlta(int $idEmpleado): void
    {
        $empleado = $this->empleadoConTipo($idEmpleado);
        if (!$empleado || !$this->aplicaIsss($empleado)) {
            return;
        }

        if ($this->tieneMovimientoAbierto($idEmpleado, 'alta')) {
            return;
        }

        $this->crearMovimiento($empleado, 'alta', $empleado->FECHAINGRESO ?? now());
    }

    public function registrarBaja(int $idEmpleado, ?string $fecha = null): void
    {
        $empleado = $this->empleadoConTipo($idEmpleado);
        if (!$empleado || !$this->aplicaIsss($empleado)) {
            return;
        }

        if ($this->tieneMovimientoAbierto($idEmpleado, 'baja')) {
            return;
        }

        $this->crearMovimiento($empleado, 'baja', $fecha ?? now());
    }

    protected function empleadoConTipo(int $idEmpleado): ?object
    {
        return DB::table('EMPLEADO')
            ->join('TIPO_CONTRATACION', 'EMPLEADO.ID_TIPOCONTRATACION', '=', 'TIPO_CONTRATACION.ID_TIPOCONTRATACION')
            ->where('EMPLEADO.ID_EMPLEADO', $idEmpleado)
            ->select(
                'EMPLEADO.ID_EMPLEADO',
                'EMPLEADO.NOMBRES',
                'EMPLEADO.APELLIDO_1',
                'EMPLEADO.APELLIDO_2',
                'EMPLEADO.ISSS',
                'EMPLEADO.DUI',
                'EMPLEADO.SALARIOMENSUAL',
                'EMPLEADO.FECHAINGRESO',
                'TIPO_CONTRATACION.APLICA_ISSS'
            )
            ->first();
    }

    protected function aplicaIsss(object $empleado): bool
    {
        return (bool) $empleado->APLICA_ISSS;
    }

    protected function tieneMovimientoAbierto(int $idEmpleado, string $tipo): bool
    {
        return DB::table('ISSS_MOVIMIENTO')
            ->where('ID_EMPLEADO', $idEmpleado)
            ->where('TIPO', $tipo)
            ->where('ESACTIVO', true)
            ->whereDate('FECHA_CREACION', now()->toDateString())
            ->exists();
    }

    protected function crearMovimiento(object $empleado, string $tipo, $fecha): void
    {
        $maxId = DB::table('ISSS_MOVIMIENTO')->max('ID_MOVIMIENTO') ?? 0;
        $nombre = trim($empleado->NOMBRES . ' ' . $empleado->APELLIDO_1 . ' ' . ($empleado->APELLIDO_2 ?? ''));

        DB::table('ISSS_MOVIMIENTO')->insert([
            'ID_MOVIMIENTO' => $maxId + 1,
            'ID_EMPLEADO' => $empleado->ID_EMPLEADO,
            'TIPO' => $tipo,
            'FECHA' => date('Y-m-d', strtotime((string) $fecha)),
            'ESTADO' => 'pendiente',
            'DATOS_JSON' => json_encode([
                'nombre' => $nombre,
                'isss' => $empleado->ISSS,
                'dui' => $empleado->DUI,
                'salario_mensual' => (float) $empleado->SALARIOMENSUAL,
            ], JSON_UNESCAPED_UNICODE),
            'ESACTIVO' => true,
        ]);
    }

    public function listar(?string $tipo = null, ?string $estado = null): Collection
    {
        $query = DB::table('ISSS_MOVIMIENTO')
            ->join('EMPLEADO', 'ISSS_MOVIMIENTO.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('ISSS_MOVIMIENTO.ESACTIVO', true)
            ->select(
                'ISSS_MOVIMIENTO.*',
                'EMPLEADO.CODIGOEMPLEADO',
                'EMPLEADO.NOMBRES',
                'EMPLEADO.APELLIDO_1',
                'EMPLEADO.APELLIDO_2',
                'EMPLEADO.ISSS as ISSS_EMPLEADO'
            )
            ->orderByDesc('ISSS_MOVIMIENTO.FECHA_CREACION');

        if ($tipo) {
            $query->where('ISSS_MOVIMIENTO.TIPO', $tipo);
        }

        if ($estado) {
            $query->where('ISSS_MOVIMIENTO.ESTADO', $estado);
        }

        return $query->get()->map(function ($row) {
            $row->NOMBRE_EMPLEADO = trim($row->NOMBRES . ' ' . $row->APELLIDO_1 . ' ' . ($row->APELLIDO_2 ?? ''));

            return $row;
        });
    }

    public function marcarEnviado(array $ids, ?string $usuario = null): int
    {
        if (empty($ids)) {
            throw new RuntimeException('Debe seleccionar al menos un movimiento.');
        }

        return DB::table('ISSS_MOVIMIENTO')
            ->whereIn('ID_MOVIMIENTO', $ids)
            ->where('ESTADO', 'pendiente')
            ->update([
                'ESTADO' => 'enviado',
                'FECHA_ENVIO' => now(),
                'USUARIO_ENVIO' => $usuario,
            ]);
    }

    /** @return array{content: string, filename: string, mime: string} */
    public function exportarCsv(?string $tipo = null, ?string $estado = 'pendiente'): array
    {
        $movimientos = $this->listar($tipo, $estado);
        if ($movimientos->isEmpty()) {
            throw new RuntimeException('No hay movimientos para exportar con los filtros seleccionados.');
        }

        $headers = ['Tipo', 'N° ISSS', 'DUI', 'Nombre completo', 'Fecha movimiento', 'Estado'];
        $rows = $movimientos->map(fn ($m) => [
            strtoupper($m->TIPO),
            $m->ISSS_EMPLEADO ?? '',
            $this->fromDatosJson($m, 'dui'),
            $m->NOMBRE_EMPLEADO,
            date('d/m/Y', strtotime($m->FECHA)),
            strtoupper($m->ESTADO),
        ]);

        return [
            'content' => $this->buildDelimited($headers, $rows, ';'),
            'filename' => 'isss_movimientos_' . date('Ymd_His') . '.csv',
            'mime' => 'text/csv',
        ];
    }

    protected function fromDatosJson(object $movimiento, string $key): string
    {
        $data = json_decode((string) $movimiento->DATOS_JSON, true);

        return $data[$key] ?? '';
    }
}
