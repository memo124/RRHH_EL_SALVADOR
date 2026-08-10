<?php

namespace App\Http\Controllers;

use App\Services\SetupService;
use Illuminate\Http\Request;

class SetupController extends Controller
{
    public function __construct(protected SetupService $setup) {}

    public function status()
    {
        return response()->json($this->setup->status());
    }

    public function store(Request $request)
    {
        if (!$this->setup->isRequired()) {
            return response()->json(['error' => 'La configuración inicial ya fue completada.'], 403);
        }

        $request->validate([
            'NOMBREEMPRESA' => 'required|string|max:150',
            'ABREVIATURA' => 'nullable|string|max:20',
            'NUMERONIT' => 'nullable|string|max:20',
            'DIRECCION' => 'nullable|string|max:500',
            'TELEFONO' => 'nullable|string|max:25',
            'GIRO' => 'nullable|string|max:500',
            'USUARIO' => 'required|string|max:100',
            'EMAIL' => 'nullable|email|max:150',
            'CONTRASENA' => 'required|string|min:8|confirmed',
        ]);

        try {
            $result = $this->setup->complete($request->all());
            return response()->json($result, 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }
}
