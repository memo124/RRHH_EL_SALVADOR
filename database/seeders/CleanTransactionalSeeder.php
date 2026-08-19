<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Limpia datos operativos (tablas hijas / transaccionales) y conserva catálogos principales.
 * Uso: php artisan db:seed --class=CleanTransactionalSeeder
 */
class CleanTransactionalSeeder extends Seeder
{
    /** Tablas de referencia que no se vacían (catálogos, geografía, RBAC, leyes). */
    private const CATALOG_TABLES = [
        'migrations',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'PAIS',
        'DEPARTAMENTO_PAIS',
        'MUNICIPIO',
        'DISTRITO',
        'TIPO_DOCUMENTO_IDENTIDAD',
        'ACTIVIDAD_ECONOMICA',
        'TIPO_CONTRATACION',
        'TIPO_INCAPACIDAD',
        'HORAS_EXTRAS',
        'TIPO_PLANILLA',
        'PERFIL_PAGO',
        'FRECUENCIA_PAGO',
        'AFP',
        'BANCO',
        'ESTADO_CIVIL',
        'EDUCACION_ACADEMICA',
        'PROFESIONES_OFICIOS',
        'TIPO_DESCUENTO',
        'TIPO_PRESTAMO',
        'TIPO_INGRESO',
        'FRECUENCIA_ISR',
        'RETENCION_ISR',
        'RETENCION_LEY',
        'MODULOS',
        'PERMISO',
        'ROL',
        'ROL_PERMISO',
        'ETAPA_RECLUTAMIENTO',
        'TIPO_PERMISO_LABORAL',
        'TIPO_DOCUMENTO_ADJUNTO',
    ];

    public function run(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'pgsql') {
            $this->command?->error('CleanTransactionalSeeder solo está probado con PostgreSQL.');

            return;
        }

        $existing = collect(DB::select(
            "SELECT tablename FROM pg_tables WHERE schemaname = 'public'"
        ))->pluck('tablename');

        $toTruncate = $existing
            ->diff(self::CATALOG_TABLES)
            ->values()
            ->all();

        if ($toTruncate === []) {
            $this->command?->info('No hay tablas transaccionales que limpiar.');

            return;
        }

        $quoted = implode(', ', array_map(fn (string $t) => '"' . $t . '"', $toTruncate));

        DB::statement('SET session_replication_role = replica');
        try {
            DB::statement("TRUNCATE TABLE {$quoted} RESTART IDENTITY");
        } finally {
            DB::statement('SET session_replication_role = origin');
        }

        $this->call(PlantillaContratoSeeder::class);

        $this->command?->info('Tablas transaccionales limpiadas: ' . count($toTruncate));
        $this->command?->info('Catálogos principales conservados: ' . count(self::CATALOG_TABLES));
    }
}
