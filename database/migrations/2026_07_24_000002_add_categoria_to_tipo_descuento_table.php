<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('TIPO_DESCUENTO', function (Blueprint $table) {
            $table->string('CATEGORIA', 20)->default('DESCUENTO')->after('DESCRIPCIONTIPODESC');
        });

        DB::table('TIPO_DESCUENTO')->whereIn('ID_TIPODESCUENTO', [1, 2, 3, 5])->update(['CATEGORIA' => 'LEY']);

        DB::table('TIPO_DESCUENTO')->where('ID_TIPODESCUENTO', 4)->update([
            'CATEGORIA' => 'DESCUENTO',
            'NOMBRETIPODESC' => 'Descuento Bancario',
            'DESCRIPCIONTIPODESC' => 'Descuento por obligación bancaria del empleado',
        ]);
    }

    public function down(): void
    {
        Schema::table('TIPO_DESCUENTO', function (Blueprint $table) {
            $table->dropColumn('CATEGORIA');
        });
    }
};
