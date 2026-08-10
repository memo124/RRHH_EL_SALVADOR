<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EncuestaRecordatorioMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $empleadoNombre,
        public string $encuestaTitulo,
        public ?string $encuestaDescripcion,
        public string $linkResponder,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recordatorio: encuesta pendiente — ' . $this->encuestaTitulo,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.encuesta-recordatorio',
        );
    }
}
