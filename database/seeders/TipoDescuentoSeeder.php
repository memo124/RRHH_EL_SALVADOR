<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoDescuentoSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            // Retenciones de ley (calculadas automáticamente en planilla)
            ['ID_TIPODESCUENTO' => 1, 'NOMBRETIPODESC' => 'ISSS', 'DESCRIPCIONTIPODESC' => 'Instituto Salvadoreño del Seguro Social', 'CATEGORIA' => 'LEY', 'ESACTIVO' => true],
            ['ID_TIPODESCUENTO' => 2, 'NOMBRETIPODESC' => 'AFP', 'DESCRIPCIONTIPODESC' => 'Administradora de Fondos de Pensiones', 'CATEGORIA' => 'LEY', 'ESACTIVO' => true],
            ['ID_TIPODESCUENTO' => 3, 'NOMBRETIPODESC' => 'Renta', 'DESCRIPCIONTIPODESC' => 'Impuesto sobre la Renta (ISR)', 'CATEGORIA' => 'LEY', 'ESACTIVO' => true],
            ['ID_TIPODESCUENTO' => 5, 'NOMBRETIPODESC' => 'ISR', 'DESCRIPCIONTIPODESC' => 'Impuesto sobre la Renta', 'CATEGORIA' => 'LEY', 'ESACTIVO' => true],

            // Descuentos voluntarios u otros (asignados por empleado)
            ['ID_TIPODESCUENTO' => 4, 'NOMBRETIPODESC' => 'Descuento Bancario', 'DESCRIPCIONTIPODESC' => 'Descuento por obligación bancaria del empleado', 'CATEGORIA' => 'DESCUENTO', 'ESACTIVO' => true],
            ['ID_TIPODESCUENTO' => 20, 'NOMBRETIPODESC' => 'Descuento por Daños', 'DESCRIPCIONTIPODESC' => 'Recuperación por daños o pérdidas causadas', 'CATEGORIA' => 'DESCUENTO', 'ESACTIVO' => true],
            ['ID_TIPODESCUENTO' => 21, 'NOMBRETIPODESC' => 'Cooperativa', 'DESCRIPCIONTIPODESC' => 'Aporte o cuota de cooperativa', 'CATEGORIA' => 'DESCUENTO', 'ESACTIVO' => true],
            ['ID_TIPODESCUENTO' => 22, 'NOMBRETIPODESC' => 'Uniformes / Equipo', 'DESCRIPCIONTIPODESC' => 'Descuento por uniformes o equipo entregado', 'CATEGORIA' => 'DESCUENTO', 'ESACTIVO' => true],
            ['ID_TIPODESCUENTO' => 23, 'NOMBRETIPODESC' => 'Seguro Privado', 'DESCRIPCIONTIPODESC' => 'Póliza de seguro contratada por el empleado', 'CATEGORIA' => 'DESCUENTO', 'ESACTIVO' => true],
            ['ID_TIPODESCUENTO' => 24, 'NOMBRETIPODESC' => 'Otros Descuentos', 'DESCRIPCIONTIPODESC' => 'Descuentos varios no clasificados', 'CATEGORIA' => 'DESCUENTO', 'ESACTIVO' => true],

            // Préstamos (cuotas descontadas en planilla)
            ['ID_TIPODESCUENTO' => 10, 'NOMBRETIPODESC' => 'Préstamo Personal', 'DESCRIPCIONTIPODESC' => 'Crédito personal otorgado al empleado', 'CATEGORIA' => 'PRESTAMO', 'ESACTIVO' => true],
            ['ID_TIPODESCUENTO' => 11, 'NOMBRETIPODESC' => 'Préstamo de Almuerzo', 'DESCRIPCIONTIPODESC' => 'Descuento por almuerzos financiados', 'CATEGORIA' => 'PRESTAMO', 'ESACTIVO' => true],
            ['ID_TIPODESCUENTO' => 12, 'NOMBRETIPODESC' => 'Préstamo Hipotecario', 'DESCRIPCIONTIPODESC' => 'Cuota de préstamo para vivienda', 'CATEGORIA' => 'PRESTAMO', 'ESACTIVO' => true],
            ['ID_TIPODESCUENTO' => 13, 'NOMBRETIPODESC' => 'Adelanto de Salario', 'DESCRIPCIONTIPODESC' => 'Anticipo de salario descontado en cuotas', 'CATEGORIA' => 'PRESTAMO', 'ESACTIVO' => true],
            ['ID_TIPODESCUENTO' => 14, 'NOMBRETIPODESC' => 'Préstamo Vehículo', 'DESCRIPCIONTIPODESC' => 'Financiamiento de vehículo', 'CATEGORIA' => 'PRESTAMO', 'ESACTIVO' => true],
            ['ID_TIPODESCUENTO' => 15, 'NOMBRETIPODESC' => 'Préstamo Educativo', 'DESCRIPCIONTIPODESC' => 'Financiamiento de estudios del empleado', 'CATEGORIA' => 'PRESTAMO', 'ESACTIVO' => true],
        ];

        foreach ($tipos as $tipo) {
            DB::table('TIPO_DESCUENTO')->updateOrInsert(
                ['ID_TIPODESCUENTO' => $tipo['ID_TIPODESCUENTO']],
                $tipo
            );
        }
    }
}
