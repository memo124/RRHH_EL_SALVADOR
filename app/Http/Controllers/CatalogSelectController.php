<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogSelectController extends Controller
{
    public function select(Request $request, string $type)
    {
        $config = $this->resolveType($type);
        if (!$config) {
            return response()->json(['error' => 'Catálogo no soportado.'], 404);
        }

        $query = DB::table($config['table']);

        if (!empty($config['where'])) {
            foreach ($config['where'] as $column => $value) {
                $query->where($column, $value);
            }
        }

        foreach ($config['filters'] ?? [] as $param => $column) {
            if ($request->filled($param)) {
                $query->where($column, $request->input($param));
            }
        }

        if ($search = trim($request->input('q', $request->input('search', '')))) {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($config, $like) {
                foreach ($config['search'] as $column) {
                    $q->orWhere($column, 'like', $like);
                }
            });
        }

        $idColumn = $config['id'];
        $selectColumns = array_unique(array_merge([$idColumn], $config['columns'] ?? [$config['label']]));
        $query->select($selectColumns);

        $orderBy = $config['order'] ?? $config['label'];
        if (is_array($orderBy)) {
            if (count($orderBy) === 2 && strtolower($orderBy[1]) === 'desc') {
                $query->orderBy($orderBy[0], 'desc');
            } else {
                foreach ($orderBy as $col) {
                    $query->orderBy($col);
                }
            }
        } else {
            $query->orderBy($orderBy);
        }

        $perPage = min(50, max(10, (int) $request->input('per_page', 30)));
        $paginated = $query->paginate($perPage);

        $data = collect($paginated->items())->map(function ($row) use ($config, $type) {
            return [
                'value' => $row->{$config['id']},
                'label' => $this->formatLabel($row, $config, $type),
            ];
        })->values();

        if ($request->filled('id')) {
            $selectedId = $request->input('id');
            if (!$data->contains('value', $selectedId) && !$data->contains('value', (int) $selectedId)) {
                $selectedQuery = DB::table($config['table'])->where($config['id'], $selectedId);
                foreach ($config['where'] ?? [] as $column => $value) {
                    $selectedQuery->where($column, $value);
                }
                $selected = $selectedQuery->select($selectColumns)->first();
                if ($selected) {
                    $data->prepend([
                        'value' => $selected->{$config['id']},
                        'label' => $this->formatLabel($selected, $config, $type),
                    ]);
                }
            }
        }

        return response()->json([
            'data' => $data->values(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ]);
    }

    private function resolveType(string $type): ?array
    {
        $types = [
            'empresas' => [
                'table' => 'EMPRESA',
                'id' => 'ID_EMPRESA',
                'label' => 'NOMBREEMPRESA',
                'columns' => ['NOMBREEMPRESA', 'ABREVIATURA'],
                'search' => ['NOMBREEMPRESA', 'ABREVIATURA', 'NUMERONIT'],
                'where' => ['EMPRESAACTIVA' => true],
            ],
            'departamentos' => [
                'table' => 'DEPARTAMENTO',
                'id' => 'ID_DEPARTAMENTO',
                'label' => 'NOMBREDEPARTAMENTO',
                'search' => ['NOMBREDEPARTAMENTO'],
                'filters' => ['ID_EMPRESA' => 'ID_EMPRESA'],
            ],
            'cargos' => [
                'table' => 'CARGO',
                'id' => 'ID_CARGO',
                'label' => 'NOMBRECARGO',
                'search' => ['NOMBRECARGO'],
                'where' => ['CARGOESTADO' => true],
                'filters' => ['ID_DEPARTAMENTO' => 'ID_DEPARTAMENTO'],
            ],
            'areas' => [
                'table' => 'AREA',
                'id' => 'ID_AREA',
                'label' => 'NOMBREAREA',
                'search' => ['NOMBREAREA'],
                'where' => ['ACTIVA' => true],
                'filters' => ['ID_EMPRESA' => 'ID_EMPRESA'],
            ],
            'centros-costo' => [
                'table' => 'CENTRO_COSTO',
                'id' => 'ID_CENTROCOSTO',
                'label' => 'NOMBRE_CENTROCOSTO',
                'columns' => ['NOMBRE_CENTROCOSTO', 'CODIGO_CENTROCOSTO'],
                'search' => ['NOMBRE_CENTROCOSTO', 'CODIGO_CENTROCOSTO'],
                'where' => ['ESACTIVO' => true],
                'filters' => ['ID_EMPRESA' => 'ID_EMPRESA'],
            ],
            'tipos-contratacion' => [
                'table' => 'TIPO_CONTRATACION',
                'id' => 'ID_TIPOCONTRATACION',
                'label' => 'TIPOCONTRATACION',
                'columns' => ['TIPOCONTRATACION', 'GRUPO_NOMINA'],
                'search' => ['TIPOCONTRATACION', 'GRUPO_NOMINA'],
                'where' => ['ESACTIVO' => true],
            ],
            'tipos-planilla' => [
                'table' => 'TIPO_PLANILLA',
                'id' => 'ID_TIPOPLANILLA',
                'label' => 'TIPOPLANILLA',
                'columns' => ['TIPOPLANILLA', 'GRUPO_NOMINA'],
                'search' => ['TIPOPLANILLA', 'GRUPO_NOMINA'],
                'where' => ['ESACTIVO' => true],
            ],
            'periodos-laborales' => [
                'table' => 'PERIODO_LABORAL',
                'id' => 'ID_PERIODO',
                'label' => 'CALPERIODO',
                'search' => ['CALPERIODO'],
                'where' => ['ESACTIVO' => true],
                'order' => ['FECHAINICIO', 'desc'],
            ],
            'frecuencias-pago' => [
                'table' => 'FRECUENCIA_PAGO',
                'id' => 'ID_FRECUENCIAPAGO',
                'label' => 'NOMBREFRECUENCIA',
                'search' => ['NOMBREFRECUENCIA'],
            ],
            'cuentas' => [
                'table' => 'CUENTA',
                'id' => 'ID_CUENTA',
                'label' => 'CONCEPTOCUENTA',
                'columns' => ['CONCEPTOCUENTA', 'NUMEROCUENTA'],
                'search' => ['CONCEPTOCUENTA', 'NUMEROCUENTA'],
                'where' => ['ESACTIVO' => true],
            ],
            'horas-extras' => [
                'table' => 'HORAS_EXTRAS',
                'id' => 'ID_HORASEXTRAS',
                'label' => 'TIPOHORAEXTRA',
                'columns' => ['TIPOHORAEXTRA'],
                'search' => ['TIPOHORAEXTRA'],
            ],
            'afps' => [
                'table' => 'AFP',
                'id' => 'ID_AFP',
                'label' => 'NOMBREAFP',
                'search' => ['NOMBREAFP', 'CODIGOPREVISIONAL'],
                'where' => ['ESACTIVO' => true],
            ],
            'bancos' => [
                'table' => 'BANCO',
                'id' => 'ID_BANCO',
                'label' => 'NOMBREBANCO',
                'columns' => ['NOMBREBANCO', 'ALIAS'],
                'search' => ['NOMBREBANCO', 'ALIAS'],
                'where' => ['BANCOACTIVO' => true],
            ],
            'departamentos-pais' => [
                'table' => 'DEPARTAMENTO_PAIS',
                'id' => 'ID_DEPARTAMENTOPAIS',
                'label' => 'NOMBREDEPARTAMENTO',
                'search' => ['NOMBREDEPARTAMENTO'],
            ],
            'municipios' => [
                'table' => 'MUNICIPIO',
                'id' => 'ID_MUNICIPIO',
                'label' => 'NOMBREMUNICIPIO',
                'search' => ['NOMBREMUNICIPIO'],
                'filters' => ['ID_DEPARTAMENTOPAIS' => 'ID_DEPARTAMENTOPAIS'],
            ],
            'distritos' => [
                'table' => 'DISTRITO',
                'id' => 'ID_DISTRITO',
                'label' => 'NOMBREDISTRITO',
                'search' => ['NOMBREDISTRITO'],
                'filters' => ['ID_MUNICIPIO' => 'ID_MUNICIPIO'],
            ],
            'tipos-incapacidad' => [
                'table' => 'TIPO_INCAPACIDAD',
                'id' => 'ID_TIPOINCAPACIDAD',
                'label' => 'NOMBRE_TIPO',
                'search' => ['NOMBRE_TIPO'],
                'where' => ['ESACTIVO' => true],
            ],
            'tipos-prestamo' => [
                'table' => 'TIPO_PRESTAMO',
                'id' => 'ID_TIPOPRESTAMO',
                'label' => 'NOMBREPRESTAMO',
                'search' => ['NOMBREPRESTAMO'],
            ],
            'tipos-descuento' => [
                'table' => 'TIPO_DESCUENTO',
                'id' => 'ID_TIPODESCUENTO',
                'label' => 'NOMBRETIPODESC',
                'search' => ['NOMBRETIPODESC'],
                'where' => ['ESACTIVO' => true, 'CATEGORIA' => 'DESCUENTO'],
            ],
            'tipos-descuento-prestamo' => [
                'table' => 'TIPO_DESCUENTO',
                'id' => 'ID_TIPODESCUENTO',
                'label' => 'NOMBRETIPODESC',
                'search' => ['NOMBRETIPODESC'],
                'where' => ['ESACTIVO' => true, 'CATEGORIA' => 'PRESTAMO'],
            ],
            'tipos-ingreso' => [
                'table' => 'TIPO_INGRESO',
                'id' => 'ID_TIPOINGRESO',
                'label' => 'TIPOINGRESO',
                'search' => ['TIPOINGRESO'],
                'where' => ['ESACTIVO' => true],
            ],
        ];

        return $types[$type] ?? null;
    }

    private function formatLabel(object $row, array $config, string $type): string
    {
        if ($type === 'cuentas') {
            $concepto = trim($row->CONCEPTOCUENTA ?? '');
            $numero = trim($row->NUMEROCUENTA ?? '');
            if ($concepto && $numero) {
                return $concepto . ' (' . $numero . ')';
            }
            return $concepto ?: ($numero ?: ('Cuenta #' . $row->{$config['id']}));
        }

        if ($type === 'centros-costo') {
            $codigo = trim($row->CODIGO_CENTROCOSTO ?? '');
            $nombre = trim($row->NOMBRE_CENTROCOSTO ?? '');
            return $codigo ? ($codigo . ' — ' . $nombre) : $nombre;
        }

        if ($type === 'tipos-planilla') {
            $label = trim($row->TIPOPLANILLA ?? '');
            if (!empty($row->GRUPO_NOMINA)) {
                $label .= ' (' . $row->GRUPO_NOMINA . ')';
            }
            return $label;
        }

        if ($type === 'tipos-contratacion') {
            $label = trim($row->TIPOCONTRATACION ?? '');
            if (!empty($row->GRUPO_NOMINA)) {
                $label .= ' (' . $row->GRUPO_NOMINA . ')';
            }
            return $label;
        }

        if ($type === 'horas-extras') {
            return trim($row->TIPOHORAEXTRA ?? '') ?: ('Tipo #' . $row->{$config['id']});
        }

        if ($type === 'bancos' && !empty($row->ALIAS)) {
            return trim($row->NOMBREBANCO ?? '') . ' (' . $row->ALIAS . ')';
        }

        $labelColumn = $config['label'];
        return trim($row->{$labelColumn} ?? '') ?: ('#' . $row->{$config['id']});
    }
}
