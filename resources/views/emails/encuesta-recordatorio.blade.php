<x-mail::message>
# Encuesta pendiente

Hola {{ $empleadoNombre }},

Tiene una encuesta pendiente de respuesta:

**{{ $encuestaTitulo }}**

@if($encuestaDescripcion)
{{ $encuestaDescripcion }}
@endif

<x-mail::button :url="$linkResponder">
Responder encuesta
</x-mail::button>

Si el botón no funciona, copie y pegue este enlace en su navegador:<br>
{{ $linkResponder }}

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
