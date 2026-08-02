<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlanillaReportController;

Route::get('/reportes/planillas/{id}/imprimir', [PlanillaReportController::class, 'imprimirPlanilla']);
Route::get('/reportes/planillas/{id}/pdf', [PlanillaReportController::class, 'pdfPlanilla']);
Route::get('/reportes/planillas/{id}/boletas', [PlanillaReportController::class, 'imprimirBoletas']);
Route::get('/reportes/planillas/{id}/boletas/pdf', [PlanillaReportController::class, 'pdfBoletas']);
Route::get('/reportes/planillas/{id}/boletas/{detalleId}', [PlanillaReportController::class, 'imprimirBoleta']);
Route::get('/reportes/planillas/{id}/boletas/{detalleId}/pdf', [PlanillaReportController::class, 'pdfBoleta']);

Route::get('/reportes/contratos/lote/pdf', [App\Http\Controllers\ContratoReportController::class, 'pdfLote']);
Route::get('/reportes/contratos/lote/zip', [App\Http\Controllers\ContratoReportController::class, 'zipLote']);
Route::get('/reportes/contratos/lote/imprimir', [App\Http\Controllers\ContratoReportController::class, 'imprimirLote']);
Route::get('/reportes/contratos/{id}', [App\Http\Controllers\ContratoReportController::class, 'imprimir']);
Route::get('/reportes/contratos/{id}/pdf', [App\Http\Controllers\ContratoReportController::class, 'pdf']);

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
