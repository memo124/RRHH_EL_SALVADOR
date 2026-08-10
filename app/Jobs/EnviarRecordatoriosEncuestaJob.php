<?php

namespace App\Jobs;

use App\Mail\EncuestaRecordatorioMail;
use App\Services\EncuestaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EnviarRecordatoriosEncuestaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public int $idEncuesta) {}

    public function handle(EncuestaService $encuestas): void
    {
        $enc = $encuestas->find($this->idEncuesta);
        if (!$enc || !$enc->ENVIAR_RECORDATORIOS) {
            return;
        }

        $mailer = config('mail.default');
        if (!$mailer) {
            Log::warning('Encuesta recordatorios: mailer no configurado.', ['ID_ENCUESTA' => $this->idEncuesta]);
            return;
        }

        $pendientes = $encuestas->empleadosPendientesRespuesta($this->idEncuesta);
        $link = rtrim(config('app.url'), '/') . '/encuestas';

        foreach ($pendientes as $emp) {
            $email = $emp->CORREOELECTRONICO ?? null;
            if (!$email) {
                continue;
            }

            try {
                Mail::to($email)->send(new EncuestaRecordatorioMail(
                    trim(($emp->NOMBRES ?? '') . ' ' . ($emp->APELLIDO_1 ?? '')),
                    $enc->TITULO,
                    $enc->DESCRIPCION,
                    $link,
                ));
            } catch (Throwable $e) {
                Log::warning('Fallo envío recordatorio encuesta', [
                    'ID_ENCUESTA' => $this->idEncuesta,
                    'ID_EMPLEADO' => $emp->ID_EMPLEADO,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function failed(?Throwable $e): void
    {
        Log::warning('Job recordatorios encuesta falló', [
            'ID_ENCUESTA' => $this->idEncuesta,
            'error' => $e?->getMessage(),
        ]);
    }
}
