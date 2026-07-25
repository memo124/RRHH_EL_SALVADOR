<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IsrFrecuenciaSeeder extends Seeder
{
    public function run(): void
    {
        // Registrar Tipo Descuento 5 si es requerido por FK
        DB::table('TIPO_DESCUENTO')->updateOrInsert(
            ['ID_TIPODESCUENTO' => 5],
            ['NOMBRETIPODESC' => 'ISR', 'DESCRIPCIONTIPODESC' => 'Impuesto sobre la Renta', 'CATEGORIA' => 'LEY', 'ESACTIVO' => true]
        );

        // 1. Frecuencias de Pago base
        $frecuenciasPago = [
            ['ID_FRECUENCIAPAGO' => 1, 'NOMBREFRECUENCIA' => 'Mensual', 'NUMERODIAS' => 30],
            ['ID_FRECUENCIAPAGO' => 2, 'NOMBREFRECUENCIA' => 'Quincenal', 'NUMERODIAS' => 15],
            ['ID_FRECUENCIAPAGO' => 3, 'NOMBREFRECUENCIA' => 'Semanal', 'NUMERODIAS' => 7]
        ];
        foreach ($frecuenciasPago as $fp) {
            DB::table('FRECUENCIA_PAGO')->updateOrInsert(['ID_FRECUENCIAPAGO' => $fp['ID_FRECUENCIAPAGO']], $fp);
        }

        // 2. Frecuencias de ISR
        $frecuenciasIsr = [
            ['ID_FRECUENCIAISR' => 1, 'FRECUENCIAISR' => 'Retención Mensual', 'NUMERODIAS' => 30],
            ['ID_FRECUENCIAISR' => 2, 'FRECUENCIAISR' => 'Retención Quincenal', 'NUMERODIAS' => 15],
            ['ID_FRECUENCIAISR' => 3, 'FRECUENCIAISR' => 'Retención Semanal', 'NUMERODIAS' => 7],
            ['ID_FRECUENCIAISR' => 4, 'FRECUENCIAISR' => 'Recálculo de Junio', 'NUMERODIAS' => 180],
            ['ID_FRECUENCIAISR' => 5, 'FRECUENCIAISR' => 'Recálculo de Diciembre', 'NUMERODIAS' => 365],
            ['ID_FRECUENCIAISR' => 6, 'FRECUENCIAISR' => 'Cálculo del Impuesto sobre la Renta', 'NUMERODIAS' => 0]
        ];
        foreach ($frecuenciasIsr as $fi) {
            DB::table('FRECUENCIA_ISR')->updateOrInsert(['ID_FRECUENCIAISR' => $fi['ID_FRECUENCIAISR']], [
                'FRECUENCIAISR' => $fi['FRECUENCIAISR'],
                'NUMERODIAS' => $fi['NUMERODIAS']
            ]);
        }

        // 3. Registrar País 9 y 503
        DB::table('PAIS')->updateOrInsert(['ID_PAIS' => 9], ['CODIGO_MH' => 'SV', 'NOMBREPAIS' => 'El Salvador']);

        // 4. Retención ISR
        $retenciones = [
            // Frecuencia 1 (Mensual)
            ['ID_RETENCIONISR' => 1, 'ID_FRECUENCIAISR' => 1, 'ID_PAIS' => 9, 'NUMEROTRAMO' => 'I Tramo', 'MONTOINICIAL' => 0.01, 'MONTOFINA' => 550.00, 'PORCENTAJEAPLICA' => 0.00, 'SOBREEXCESO' => 0.00, 'CUOTAFIJA' => 0.00, 'FECHAINICIAL' => '2016-01-01', 'FECHAFINAL' => '2026-12-31'],
            ['ID_RETENCIONISR' => 2, 'ID_FRECUENCIAISR' => 1, 'ID_PAIS' => 9, 'NUMEROTRAMO' => 'II Tramo', 'MONTOINICIAL' => 550.01, 'MONTOFINA' => 895.24, 'PORCENTAJEAPLICA' => 10.00, 'SOBREEXCESO' => 550.00, 'CUOTAFIJA' => 17.67, 'FECHAINICIAL' => '2016-01-01', 'FECHAFINAL' => '2026-12-31'],
            ['ID_RETENCIONISR' => 3, 'ID_FRECUENCIAISR' => 1, 'ID_PAIS' => 9, 'NUMEROTRAMO' => 'III Tramo', 'MONTOINICIAL' => 895.25, 'MONTOFINA' => 2038.10, 'PORCENTAJEAPLICA' => 20.00, 'SOBREEXCESO' => 895.24, 'CUOTAFIJA' => 60.00, 'FECHAINICIAL' => '2016-01-01', 'FECHAFINAL' => '2026-12-31'],
            ['ID_RETENCIONISR' => 4, 'ID_FRECUENCIAISR' => 1, 'ID_PAIS' => 9, 'NUMEROTRAMO' => 'IV Tramo', 'MONTOINICIAL' => 2038.11, 'MONTOFINA' => 999999.00, 'PORCENTAJEAPLICA' => 30.00, 'SOBREEXCESO' => 2038.10, 'CUOTAFIJA' => 288.57, 'FECHAINICIAL' => '2016-01-01', 'FECHAFINAL' => '2026-12-31'],
            // Frecuencia 2 (Quincenal)
            ['ID_RETENCIONISR' => 5, 'ID_FRECUENCIAISR' => 2, 'ID_PAIS' => 9, 'NUMEROTRAMO' => 'I Tramo', 'MONTOINICIAL' => 0.01, 'MONTOFINA' => 275.00, 'PORCENTAJEAPLICA' => 0.00, 'SOBREEXCESO' => 0.00, 'CUOTAFIJA' => 0.00, 'FECHAINICIAL' => '2016-01-01', 'FECHAFINAL' => '2026-12-31'],
            ['ID_RETENCIONISR' => 6, 'ID_FRECUENCIAISR' => 2, 'ID_PAIS' => 9, 'NUMEROTRAMO' => 'II Tramo', 'MONTOINICIAL' => 275.01, 'MONTOFINA' => 447.62, 'PORCENTAJEAPLICA' => 10.00, 'SOBREEXCESO' => 275.00, 'CUOTAFIJA' => 8.83, 'FECHAINICIAL' => '2016-01-01', 'FECHAFINAL' => '2026-12-31'],
            ['ID_RETENCIONISR' => 7, 'ID_FRECUENCIAISR' => 2, 'ID_PAIS' => 9, 'NUMEROTRAMO' => 'III Tramo', 'MONTOINICIAL' => 447.63, 'MONTOFINA' => 1019.05, 'PORCENTAJEAPLICA' => 20.00, 'SOBREEXCESO' => 447.62, 'CUOTAFIJA' => 30.00, 'FECHAINICIAL' => '2016-01-01', 'FECHAFINAL' => '2026-12-31'],
            ['ID_RETENCIONISR' => 8, 'ID_FRECUENCIAISR' => 2, 'ID_PAIS' => 9, 'NUMEROTRAMO' => 'IV Tramo', 'MONTOINICIAL' => 1019.06, 'MONTOFINA' => 999999.00, 'PORCENTAJEAPLICA' => 30.00, 'SOBREEXCESO' => 1019.05, 'CUOTAFIJA' => 144.28, 'FECHAINICIAL' => '2016-01-01', 'FECHAFINAL' => '2026-12-31'],
            // Frecuencia 3 (Semanal)
            ['ID_RETENCIONISR' => 9, 'ID_FRECUENCIAISR' => 3, 'ID_PAIS' => 9, 'NUMEROTRAMO' => 'I Tramo', 'MONTOINICIAL' => 0.01, 'MONTOFINA' => 137.50, 'PORCENTAJEAPLICA' => 0.00, 'SOBREEXCESO' => 0.00, 'CUOTAFIJA' => 0.00, 'FECHAINICIAL' => '2016-01-01', 'FECHAFINAL' => '2026-12-31'],
            ['ID_RETENCIONISR' => 10, 'ID_FRECUENCIAISR' => 3, 'ID_PAIS' => 9, 'NUMEROTRAMO' => 'II Tramo', 'MONTOINICIAL' => 137.51, 'MONTOFINA' => 223.81, 'PORCENTAJEAPLICA' => 10.00, 'SOBREEXCESO' => 118.00, 'CUOTAFIJA' => 137.50, 'FECHAINICIAL' => '2016-01-01', 'FECHAFINAL' => '2026-12-31'],
            ['ID_RETENCIONISR' => 11, 'ID_FRECUENCIAISR' => 3, 'ID_PAIS' => 9, 'NUMEROTRAMO' => 'III Tramo', 'MONTOINICIAL' => 223.82, 'MONTOFINA' => 509.52, 'PORCENTAJEAPLICA' => 20.00, 'SOBREEXCESO' => 223.81, 'CUOTAFIJA' => 15.00, 'FECHAINICIAL' => '2016-01-01', 'FECHAFINAL' => '2026-12-31'],
            ['ID_RETENCIONISR' => 12, 'ID_FRECUENCIAISR' => 3, 'ID_PAIS' => 9, 'NUMEROTRAMO' => 'IV Tramo', 'MONTOINICIAL' => 509.53, 'MONTOFINA' => 999999.00, 'PORCENTAJEAPLICA' => 30.00, 'SOBREEXCESO' => 509.52, 'CUOTAFIJA' => 72.14, 'FECHAINICIAL' => '2016-01-01', 'FECHAFINAL' => '2026-12-31'],
            // Frecuencia 4 (Recálculo Junio)
            ['ID_RETENCIONISR' => 13, 'ID_FRECUENCIAISR' => 4, 'ID_PAIS' => 9, 'NUMEROTRAMO' => 'I Tramo', 'MONTOINICIAL' => 0.01, 'MONTOFINA' => 3300.00, 'PORCENTAJEAPLICA' => 0.00, 'SOBREEXCESO' => 0.00, 'CUOTAFIJA' => 0.00, 'FECHAINICIAL' => '2016-01-01', 'FECHAFINAL' => '2026-12-31'],
            ['ID_RETENCIONISR' => 14, 'ID_FRECUENCIAISR' => 4, 'ID_PAIS' => 9, 'NUMEROTRAMO' => 'II Tramo', 'MONTOINICIAL' => 3300.01, 'MONTOFINA' => 5371.44, 'PORCENTAJEAPLICA' => 10.00, 'SOBREEXCESO' => 3300.00, 'CUOTAFIJA' => 106.20, 'FECHAINICIAL' => '2016-01-01', 'FECHAFINAL' => '2026-12-31'],
            ['ID_RETENCIONISR' => 15, 'ID_FRECUENCIAISR' => 4, 'ID_PAIS' => 9, 'NUMEROTRAMO' => 'III Tramo', 'MONTOINICIAL' => 5371.45, 'MONTOFINA' => 12228.60, 'PORCENTAJEAPLICA' => 20.00, 'SOBREEXCESO' => 5371.44, 'CUOTAFIJA' => 360.00, 'FECHAINICIAL' => '2016-01-01', 'FECHAFINAL' => '2026-12-31'],
            ['ID_RETENCIONISR' => 16, 'ID_FRECUENCIAISR' => 4, 'ID_PAIS' => 9, 'NUMEROTRAMO' => 'IV Tramo', 'MONTOINICIAL' => 1228.61, 'MONTOFINA' => 999999.00, 'PORCENTAJEAPLICA' => 30.00, 'SOBREEXCESO' => 12228.60, 'CUOTAFIJA' => 1731.42, 'FECHAINICIAL' => '2016-01-01', 'FECHAFINAL' => '2026-12-31'],
            // Frecuencia 5 (Recálculo Diciembre)
            ['ID_RETENCIONISR' => 17, 'ID_FRECUENCIAISR' => 5, 'ID_PAIS' => 9, 'NUMEROTRAMO' => 'I Tramo', 'MONTOINICIAL' => 0.01, 'MONTOFINA' => 6600.00, 'PORCENTAJEAPLICA' => 0.00, 'SOBREEXCESO' => 0.00, 'CUOTAFIJA' => 0.00, 'FECHAINICIAL' => '2016-01-01', 'FECHAFINAL' => '2026-12-31'],
            ['ID_RETENCIONISR' => 18, 'ID_FRECUENCIAISR' => 5, 'ID_PAIS' => 9, 'NUMEROTRAMO' => 'II Tramo', 'MONTOINICIAL' => 6600.01, 'MONTOFINA' => 10742.86, 'PORCENTAJEAPLICA' => 10.00, 'SOBREEXCESO' => 6600.00, 'CUOTAFIJA' => 212.12, 'FECHAINICIAL' => '2016-01-01', 'FECHAFINAL' => '2026-12-31'],
            ['ID_RETENCIONISR' => 19, 'ID_FRECUENCIAISR' => 5, 'ID_PAIS' => 9, 'NUMEROTRAMO' => 'III Tramo', 'MONTOINICIAL' => 10742.87, 'MONTOFINA' => 24457.14, 'PORCENTAJEAPLICA' => 20.00, 'SOBREEXCESO' => 10742.86, 'CUOTAFIJA' => 720.00, 'FECHAINICIAL' => '2016-01-01', 'FECHAFINAL' => '2026-12-31'],
            ['ID_RETENCIONISR' => 20, 'ID_FRECUENCIAISR' => 5, 'ID_PAIS' => 9, 'NUMEROTRAMO' => 'IV Tramo', 'MONTOINICIAL' => 24457.15, 'MONTOFINA' => 999999.00, 'PORCENTAJEAPLICA' => 30.00, 'SOBREEXCESO' => 24457.14, 'CUOTAFIJA' => 3462.86, 'FECHAINICIAL' => '2016-01-01', 'FECHAFINAL' => '2026-12-31']
        ];

        foreach ($retenciones as $ret) {
            DB::table('RETENCION_ISR')->updateOrInsert(['ID_RETENCIONISR' => $ret['ID_RETENCIONISR']], [
                'ID_FRECUENCIAISR' => $ret['ID_FRECUENCIAISR'],
                'ID_PAIS' => $ret['ID_PAIS'],
                'NUMEROTRAMO' => $ret['NUMEROTRAMO'],
                'MONTOINICIAL' => $ret['MONTOINICIAL'],
                'MONTOFINA' => $ret['MONTOFINA'],
                'PORCENTAJEAPLICA' => $ret['PORCENTAJEAPLICA'],
                'SOBREEXCESO' => $ret['SOBREEXCESO'],
                'CUOTAFIJA' => $ret['CUOTAFIJA'],
                'FECHAINICIAL' => $ret['FECHAINICIAL'],
                'FECHAFINAL' => $ret['FECHAFINAL']
            ]);
        }
    }
}
