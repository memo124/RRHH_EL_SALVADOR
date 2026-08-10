<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdjuntoService
{
    private const MAX_BYTES = 10485760; // 10MB

    private const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'docx'];

    public function store(
        UploadedFile $file,
        ?int $idEmpleado,
        ?int $idTipoDocumento,
        string $origen,
        ?int $idOrigen,
        ?int $idUsuario
    ): array {
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException('Extensión no permitida. Use: pdf, jpg, png, docx.');
        }
        if ($file->getSize() > self::MAX_BYTES) {
            throw new \InvalidArgumentException('El archivo excede el tamaño máximo de 10 MB.');
        }

        $maxId = DB::table('ADJUNTO')->max('ID_ADJUNTO') ?? 0;
        $id = $maxId + 1;
        $filename = "adjunto_{$id}_" . time() . ".{$ext}";
        $path = "adjuntos/{$filename}";

        Storage::disk('local')->makeDirectory('adjuntos');
        Storage::disk('local')->putFileAs('adjuntos', $file, $filename);

        $row = [
            'ID_ADJUNTO' => $id,
            'ID_EMPLEADO' => $idEmpleado,
            'ID_TIPO_DOCUMENTO_ADJUNTO' => $idTipoDocumento,
            'NOMBRE_ARCHIVO' => $file->getClientOriginalName(),
            'RUTA_STORAGE' => $path,
            'MIME_TYPE' => $file->getMimeType(),
            'TAMANO_BYTES' => $file->getSize(),
            'FECHA_SUBIDA' => now(),
            'ID_USUARIO_SUBIDA' => $idUsuario,
            'ORIGEN' => $origen,
            'ID_ORIGEN' => $idOrigen,
            'ESACTIVO' => true,
        ];
        DB::table('ADJUNTO')->insert($row);

        return $row;
    }

    public function find(int $id): ?object
    {
        return DB::table('ADJUNTO')
            ->leftJoin('TIPO_DOCUMENTO_ADJUNTO', 'ADJUNTO.ID_TIPO_DOCUMENTO_ADJUNTO', '=', 'TIPO_DOCUMENTO_ADJUNTO.ID_TIPO_DOCUMENTO_ADJUNTO')
            ->leftJoin('EMPLEADO', 'ADJUNTO.ID_EMPLEADO', '=', 'EMPLEADO.ID_EMPLEADO')
            ->where('ADJUNTO.ID_ADJUNTO', $id)
            ->where('ADJUNTO.ESACTIVO', true)
            ->select(
                'ADJUNTO.*',
                'TIPO_DOCUMENTO_ADJUNTO.NOMBRE as TIPO_NOMBRE',
                DB::raw("EMPLEADO.NOMBRES || ' ' || EMPLEADO.APELLIDO_1 as EMPLEADO_NOMBRE")
            )
            ->first();
    }

    public function softDelete(int $id): void
    {
        DB::table('ADJUNTO')->where('ID_ADJUNTO', $id)->update(['ESACTIVO' => false]);
    }

    public function download(int $id): StreamedResponse
    {
        $adj = DB::table('ADJUNTO')->where('ID_ADJUNTO', $id)->where('ESACTIVO', true)->first();
        if (!$adj || !Storage::disk('local')->exists($adj->RUTA_STORAGE)) {
            abort(404, 'Archivo no encontrado.');
        }

        return Storage::disk('local')->download($adj->RUTA_STORAGE, $adj->NOMBRE_ARCHIVO);
    }

    public function tiposDocumento(): array
    {
        return DB::table('TIPO_DOCUMENTO_ADJUNTO')
            ->where('ESACTIVO', true)
            ->orderBy('ID_TIPO_DOCUMENTO_ADJUNTO')
            ->get()
            ->all();
    }
}
