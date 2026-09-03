<?php

namespace App\Filament\Resources\Movimientos\Schemas;

use App\Models\Concepto;
use App\Services\GeneradorCuotas;
use Closure;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class MovimientoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la deuda')
                    ->schema([
                        Select::make('proveedor_id')
                            ->relationship('proveedor', 'nombre')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('concepto_id')
                            ->relationship('concepto', 'nombre')
                            ->searchable()
                            ->preload()
                            ->default(fn () => Concepto::where('codigo', '00')->value('id'))
                            ->required(),
                        TextInput::make('ejercicio')
                            ->numeric()
                            ->default(fn () => now()->year)
                            ->required(),
                        Textarea::make('descripcion')
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('Liquidación y pago')
                    ->schema([
                        Grid::make(4)
                            ->schema(self::camposLiquidacion()),
                    ])
                    ->columnSpanFull(),

                Section::make('Cuotas')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('monto_total')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::regenerarCuotas($get, $set)),
                        TextInput::make('cantidad_cuotas')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(60)
                            ->required()
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::regenerarCuotas($get, $set)),

                        Repeater::make('cuotas')
                            ->relationship()
                            ->reorderable(false)
                            ->addable(false)
                            ->deletable(false)
                            ->columns(4)
                            ->schema([
                                Hidden::make('fecha_vencimiento'),
                                TextInput::make('nro_cuota')->disabled()->dehydrated(),
                                TextInput::make('anio_pago')->disabled()->dehydrated(),
                                TextInput::make('mes_pago')->disabled()->dehydrated(),
                                TextInput::make('importe')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),
                                TextInput::make('observacion')
                                    ->columnSpanFull(),
                            ])
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $suma = collect($get('cuotas'))->sum(fn ($c) => (float) ($c['importe'] ?? 0));
                                    if (round($suma - (float) $get('monto_total'), 2) != 0.0) {
                                        $fail('La suma de las cuotas no coincide con el monto total.');
                                    }
                                },
                            ]),

                        Placeholder::make('control_suma')
                            ->label('Control')
                            ->content(function (Get $get) {
                                $suma = collect($get('cuotas'))->sum(fn ($c) => (float) ($c['importe'] ?? 0));
                                $dif = round($suma - (float) $get('monto_total'), 2);

                                return $dif == 0.0
                                    ? '✓ Suma de cuotas: $'.number_format($suma, 2, ',', '.')
                                    : '✗ Diferencia: $'.number_format($dif, 2, ',', '.');
                            }),
                    ]),
            ]);
    }

    private static function camposLiquidacion(): array
    {
        return [
            Select::make('mes_liquidacion')
                ->options(self::meses())
                ->required()
                ->live()
                ->afterStateUpdated(fn (Get $get, Set $set) => self::regenerarCuotas($get, $set)),
            TextInput::make('anio_liquidacion')
                ->numeric()
                ->default(fn () => now()->year)
                ->required()
                ->live()
                ->afterStateUpdated(fn (Get $get, Set $set) => self::regenerarCuotas($get, $set)),
            TextInput::make('desfasaje_primer_pago')
                ->numeric()
                ->minValue(0)
                ->maxValue(24)
                ->default(1)
                ->required()
                ->live()
                ->helperText('Meses posteriores al mes de liquidación en que se paga la 1ª cuota')
                ->afterStateUpdated(fn (Get $get, Set $set) => self::regenerarCuotas($get, $set)),
            TextInput::make('dia_pago')
                ->numeric()
                ->minValue(1)
                ->maxValue(31)
                ->required()
                ->live()
                ->afterStateUpdated(fn (Get $get, Set $set) => self::regenerarCuotas($get, $set)),
        ];
    }

    private static function regenerarCuotas(Get $get, Set $set): void
    {
        $anio = (int) $get('anio_liquidacion');
        $mes = (int) $get('mes_liquidacion');
        $desfasaje = (int) $get('desfasaje_primer_pago');
        $diaPago = (int) $get('dia_pago');
        $montoTotal = (float) $get('monto_total');
        $cantidadCuotas = (int) $get('cantidad_cuotas');

        if ($anio <= 0 || $mes <= 0 || $diaPago <= 0 || $montoTotal <= 0 || $cantidadCuotas <= 0) {
            return;
        }

        $filas = app(GeneradorCuotas::class)->generar($anio, $mes, $desfasaje, $diaPago, $montoTotal, $cantidadCuotas);

        $set('cuotas', $filas);
    }

    private static function meses(): array
    {
        return [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
    }
}
