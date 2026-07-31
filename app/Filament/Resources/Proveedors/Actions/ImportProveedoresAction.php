<?php

namespace App\Filament\Resources\Proveedors\Actions;

use App\Models\Proveedor;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportProveedoresAction
{
    /**
     * Columnas que reconoce el importador: clave interna => encabezado esperado.
     */
    private const COLUMNS = [
        'codigo' => 'codigo',
        'nombre' => 'nombre',
        'razon_social' => 'razon_social',
        'cuit' => 'cuit',
        'direccion' => 'direccion',
        'ciudad' => 'ciudad',
        'telefono' => 'telefono',
        'email' => 'email',
    ];

    private const REQUIRED_COLUMNS = ['codigo', 'nombre'];

    public static function make(): Action
    {
        return Action::make('importProveedores')
            ->label('Importar')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->modalHeading('Importar proveedores')
            ->modalDescription(
                'El archivo (.xlsx, .xls o .csv) debe tener una fila de encabezado con estas columnas, en cualquier orden: '
                . implode(', ', array_keys(self::COLUMNS)) . '. '
                . 'Obligatorias: ' . implode(', ', self::REQUIRED_COLUMNS) . '. '
                . 'Si "razon_social" o "cuit" vienen vacíos, se completan automáticamente con el nombre y un CUIT provisorio. '
                . 'Si "codigo" ya existe, se actualiza ese proveedor en vez de duplicarlo.'
            )
            ->modalSubmitActionLabel('Importar')
            ->extraModalFooterActions([
                self::downloadTemplateAction(),
            ])
            ->schema([
                FileUpload::make('file')
                    ->label('Archivo')
                    ->required()
                    ->acceptedFileTypes([
                        'text/csv',
                        'text/plain',
                        'application/csv',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->disk('local')
                    ->directory('imports/proveedores')
                    ->visibility('private'),
            ])
            ->action(function (array $data): void {
                $result = self::import(Storage::disk('local')->path($data['file']));

                Storage::disk('local')->delete($data['file']);

                if ($result['fatalError']) {
                    Notification::make()
                        ->title('No se pudo importar el archivo')
                        ->body($result['fatalError'])
                        ->danger()
                        ->send();

                    return;
                }

                $body = "{$result['created']} creados, {$result['updated']} actualizados.";

                if ($result['errors']) {
                    $body .= "\n" . count($result['errors']) . ' fila(s) con error:' . "\n"
                        . implode("\n", array_slice($result['errors'], 0, 10));
                }

                Notification::make()
                    ->title('Importación de proveedores finalizada')
                    ->body($body)
                    ->color($result['errors'] ? 'warning' : 'success')
                    ->send();
            });
    }

    /**
     * @return array{created: int, updated: int, errors: array<string>, fatalError: ?string}
     */
    private static function import(string $path): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable $exception) {
            return ['created' => 0, 'updated' => 0, 'errors' => [], 'fatalError' => 'El archivo no pudo leerse: ' . $exception->getMessage()];
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (empty($rows)) {
            return ['created' => 0, 'updated' => 0, 'errors' => [], 'fatalError' => 'El archivo está vacío.'];
        }

        $headerRow = array_shift($rows);
        $columnMap = self::mapColumns($headerRow);

        $missing = array_diff(self::REQUIRED_COLUMNS, array_keys($columnMap));

        if ($missing) {
            return [
                'created' => 0,
                'updated' => 0,
                'errors' => [],
                'fatalError' => 'Faltan columnas obligatorias: ' . implode(', ', $missing) . '.',
            ];
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +1 por el header, +1 porque las hojas empiezan en 1

            if (self::isBlankRow($row)) {
                continue;
            }

            $codigo = trim((string) ($row[$columnMap['codigo']] ?? ''));
            $nombre = trim((string) ($row[$columnMap['nombre']] ?? ''));

            if ($codigo === '' || $nombre === '') {
                $errors[] = "Fila {$rowNumber}: código y nombre son obligatorios.";

                continue;
            }

            $razonSocial = self::nullableValue($row, $columnMap, 'razon_social');
            $cuit = self::nullableValue($row, $columnMap, 'cuit');

            $wasExisting = Proveedor::withTrashed()->where('codigo', $codigo)->exists();

            Proveedor::updateOrCreate(
                ['codigo' => $codigo],
                [
                    'nombre' => $nombre,
                    'razon_social' => $razonSocial ?? $nombre,
                    'cuit' => $cuit ?? self::fakeCuit($codigo),
                    'direccion' => self::nullableValue($row, $columnMap, 'direccion'),
                    'ciudad' => self::nullableValue($row, $columnMap, 'ciudad'),
                    'telefono' => self::nullableValue($row, $columnMap, 'telefono'),
                    'email' => self::nullableValue($row, $columnMap, 'email'),
                ]
            );

            $wasExisting ? $updated++ : $created++;
        }

        return ['created' => $created, 'updated' => $updated, 'errors' => $errors, 'fatalError' => null];
    }

    /**
     * @param  array<int, string|null>  $headerRow
     * @return array<string, int>
     */
    private static function mapColumns(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $columnIndex => $header) {
            $normalized = self::normalizeHeader((string) $header);

            foreach (self::COLUMNS as $key => $expected) {
                if ($normalized === $expected && ! isset($map[$key])) {
                    $map[$key] = $columnIndex;
                }
            }
        }

        return $map;
    }

    private static function normalizeHeader(string $header): string
    {
        $header = trim(mb_strtolower($header));
        $header = strtr($header, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);

        return preg_replace('/[^a-z0-9]+/', '_', $header);
    }

    /**
     * @param  array<int, string|null>  $row
     * @param  array<string, int>  $columnMap
     */
    private static function nullableValue(array $row, array $columnMap, string $key): ?string
    {
        if (! isset($columnMap[$key])) {
            return null;
        }

        $value = trim((string) ($row[$columnMap[$key]] ?? ''));

        return $value !== '' ? $value : null;
    }

    private static function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private static function fakeCuit(string $codigo): string
    {
        $digits = preg_replace('/\D/', '', $codigo) ?: '0';

        return sprintf('20-%08d-0', (int) $digits);
    }

    private static function downloadTemplateAction(): Action
    {
        return Action::make('downloadProveedoresTemplate')
            ->label('Descargar plantilla')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(function () {
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                $headers = array_keys(self::COLUMNS);
                $sheet->fromArray($headers, null, 'A1');
                $sheet->fromArray([
                    '14', 'PALACIO FENIX SRL', 'PALACIO FENIX SRL', '20-12345678-9',
                    'Av. Ejemplo 123', 'San Juan', '4231013', 'contacto@ejemplo.com',
                ], null, 'A2');

                $tempPath = tempnam(sys_get_temp_dir(), 'proveedores_template') . '.xlsx';
                (new Xlsx($spreadsheet))->save($tempPath);

                return response()
                    ->download($tempPath, 'plantilla_proveedores.xlsx')
                    ->deleteFileAfterSend();
            });
    }
}
