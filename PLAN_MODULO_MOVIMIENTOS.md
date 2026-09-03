# Plan de implementación — Módulo Movimientos (deudas a proveedores)

Repo: `oliveram2284/suoem_sistema`
Stack: Laravel 12 · Filament 4 · PHP 8.4 · PostgreSQL 13

---

## 0. Contexto del negocio

El SUOEM (sindicato) tiene convenios con comercios/proveedores. Los afiliados compran
con órdenes de compra en esos comercios; cada mes el comercio presenta una rendición y
el sindicato le queda debiendo un monto total, que se le paga en cuotas.

**Este módulo NO registra afiliados, ni órdenes individuales, ni cómo se descuenta al
afiliado.** Registra únicamente la **deuda agregada contra el comercio** y su cronograma
de pago.

Referencia funcional: sistema legacy "MUTUALIS", pantalla *Carga detalle de prov. órdenes
de compra*. Campos del legacy: proveedor, código de operación, día de pago, total a cargar,
mes/año de liquidación, meses 1er pago, cantidad de cuotas, y una grilla año/mes/importe/
observaciones cuyo total debe coincidir con el total a cargar.

### Concepto clave: desfasaje del primer pago

`desfasaje_primer_pago` = cantidad de meses **posteriores** al mes de liquidación en que
se paga la primera cuota. No es un mes calendario.

```
periodo_cuota[n] = (anio_liquidacion, mes_liquidacion) + desfasaje_primer_pago + (n - 1)
```

Ejemplo del legacy: liquidación 10/2025, desfasaje 1, 4 cuotas → 11/2025, 12/2025, 01/2026, 02/2026.

---

## Fase 1 — Correcciones previas (bugs existentes)

- [ ] **`app/Models/Movimiento.php`**: el `$fillable` dice `'usuer_id'`. Corregir a `'user_id'`.
- [ ] **`app/Models/Proveedor.php`**: eliminar `protected static ?string $modelLabel = 'Proveedor';`.
      Es una propiedad de Filament `Resource`, en un modelo Eloquent no hace nada.
      Si se quiere el label, va en `ProveedorResource` como `protected static ?string $modelLabel`.
- [ ] **`database/migrations/2025_11_26_121257_create_movimientos_table.php`**: la FK está mal.
      `foreignId('proveedor_id')->table('proveedores')->constrained()` — `->table()` no es un
      modificador válido de columna y se ignora; `constrained()` sin argumento infiere la tabla
      `proveedors` (pluralización inglesa) que no existe. Debe ser `->constrained('proveedores')`.
      Se resuelve al reescribir la migración en la Fase 2.

---

## Fase 2 — Base de datos

> **Supuesto:** el proyecto está en desarrollo y no hay datos productivos en `movimientos`.
> Reescribir la migración `2025_11_26_121257_create_movimientos_table.php` in-place y correr
> `php artisan migrate:fresh --seed`.
> Si hubiera datos productivos, NO reescribir: crear migraciones nuevas incrementales.

### 2.1 Reescribir `movimientos`

```php
Schema::create('movimientos', function (Blueprint $table) {
    $table->id();

    $table->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnDelete();
    $table->foreignId('concepto_id')->constrained('conceptos');   // código de operación

    $table->smallInteger('ejercicio');                 // ej. 2025
    $table->smallInteger('anio_liquidacion');
    $table->smallInteger('mes_liquidacion');           // 1-12
    $table->unsignedTinyInteger('desfasaje_primer_pago')->default(1);
    $table->unsignedTinyInteger('dia_pago');           // 1-31

    $table->decimal('monto_total', 14, 2);
    $table->unsignedSmallInteger('cantidad_cuotas');

    $table->text('descripcion')->nullable();

    $table->string('estado', 20)->default('pendiente')->index();

    $table->foreignId('user_id')->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    $table->index(['proveedor_id', 'anio_liquidacion', 'mes_liquidacion']);
});
```

Notas obligatorias:

- `decimal(14,2)`, **no** `10,2`. El `10,2` topea en 99.999.999,99 y ya hay rendiciones
  reales de $14.083.231,95. Se queda corto.
- `estado` como `string`, **no** `enum()` de Postgres: agregar un estado después con `enum()`
  obliga a recrear el tipo.
- Sin campos de comprobante (fuera de alcance por ahora).

### 2.2 Nueva tabla `movimiento_cuotas`

```php
Schema::create('movimiento_cuotas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('movimiento_id')->constrained('movimientos')->cascadeOnDelete();

    $table->unsignedSmallInteger('nro_cuota');
    $table->smallInteger('anio_pago');
    $table->smallInteger('mes_pago');                  // 1-12
    $table->date('fecha_vencimiento');                 // anio-mes-dia_pago (con clamp)

    $table->decimal('importe', 14, 2);
    $table->string('estado', 20)->default('pendiente'); // pendiente | pagada | anulada
    $table->date('fecha_pago')->nullable();
    $table->string('observacion')->nullable();

    $table->timestamps();

    $table->unique(['movimiento_id', 'nro_cuota']);
    $table->index(['anio_pago', 'mes_pago']);
    $table->index('fecha_vencimiento');
});
```

**Clamp de `fecha_vencimiento`:** si `dia_pago = 31` y la cuota cae en un mes de 30 días
(o febrero), usar el último día del mes. Resolverlo al generar la cuota, no en los reportes.

```php
$fecha = Carbon::create($anio, $mes, 1)->endOfMonth();
$dia   = min($diaPago, $fecha->day);
$vencimiento = Carbon::create($anio, $mes, $dia);
```

### 2.3 Nueva tabla `movimiento_estados` (historial)

```php
Schema::create('movimiento_estados', function (Blueprint $table) {
    $table->id();
    $table->foreignId('movimiento_id')->constrained('movimientos')->cascadeOnDelete();

    $table->string('estado_anterior', 20)->nullable();  // null = alta del movimiento
    $table->string('estado_nuevo', 20);
    $table->date('fecha_efectiva');                     // cuándo ocurrió el hecho
    $table->text('motivo')->nullable();

    $table->foreignId('user_id')->constrained('users');
    $table->timestamp('created_at')->useCurrent();      // cuándo se cargó al sistema

    $table->index(['movimiento_id', 'created_at']);
});
```

**No** meter `fecha_pago` / `fecha_rechazo` / `motivo_rechazo` como columnas en `movimientos`.
Si un movimiento se rechaza, se corrige y se vuelve a rechazar, el segundo pisa al primero y
se pierde la traza. El sello físico *"sujeto a revisión posterior"* indica que las idas y
vueltas son habituales.

`fecha_efectiva` separada de `created_at` es requisito: el pago se hace el día 17 y se carga
el 20; con un solo timestamp todos los reportes por período salen corridos.

---

## Fase 3 — Enums PHP

### `app/Enums/EstadoMovimiento.php`

```php
<?php

namespace App\Enums;

enum EstadoMovimiento: string
{
    case Pendiente = 'pendiente';
    case Parcial   = 'parcial';
    case Pagado    = 'pagado';
    case Rechazado = 'rechazado';
    case Anulado   = 'anulado';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Parcial   => 'Pago parcial',
            self::Pagado    => 'Pagado / Cancelado',
            self::Rechazado => 'Rechazado',
            self::Anulado   => 'Anulado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Parcial   => 'info',
            self::Pagado    => 'success',
            self::Rechazado, self::Anulado => 'danger',
        };
    }

    public function requiereMotivo(): bool
    {
        return in_array($this, [self::Rechazado, self::Anulado], true);
    }

    public function esFinal(): bool
    {
        return in_array($this, [self::Pagado, self::Anulado], true);
    }

    /** @return array<int, self> */
    public function siguientes(): array
    {
        return match ($this) {
            self::Pendiente => [self::Parcial, self::Pagado, self::Rechazado, self::Anulado],
            self::Parcial   => [self::Pagado, self::Anulado],
            self::Rechazado => [self::Pendiente, self::Anulado],
            self::Pagado    => [],
            self::Anulado   => [],
        };
    }
}
```

### `app/Enums/EstadoCuota.php`

```php
enum EstadoCuota: string
{
    case Pendiente = 'pendiente';
    case Pagada    = 'pagada';
    case Anulada   = 'anulada';
    // + label() y color() equivalentes
}
```

---

## Fase 4 — Modelos

### `Movimiento`

```php
protected $fillable = [
    'proveedor_id', 'concepto_id', 'ejercicio',
    'anio_liquidacion', 'mes_liquidacion',
    'desfasaje_primer_pago', 'dia_pago',
    'monto_total', 'cantidad_cuotas', 'descripcion',
    'estado', 'user_id',
];

protected function casts(): array
{
    return [
        'monto_total' => 'decimal:2',
        'estado'      => EstadoMovimiento::class,
    ];
}

public function proveedor()  { return $this->belongsTo(Proveedor::class); }
public function concepto()   { return $this->belongsTo(Concepto::class); }
public function user()       { return $this->belongsTo(User::class); }
public function cuotas()     { return $this->hasMany(MovimientoCuota::class)->orderBy('nro_cuota'); }
public function estados()    { return $this->hasMany(MovimientoEstado::class)->latest('created_at'); }

// Derivados
public function getTotalCuotasAttribute(): float;      // suma de cuotas
public function getTotalPagadoAttribute(): float;      // suma de cuotas pagadas
public function getSaldoAttribute(): float;            // monto_total - total_pagado
public function getPeriodoLiquidacionAttribute(): string; // "10/2025"
public function cuadra(): bool;                        // abs(total_cuotas - monto_total) < 0.01
```

Mantener `SoftDeletes`.

### `MovimientoCuota`

`$fillable`: `movimiento_id, nro_cuota, anio_pago, mes_pago, fecha_vencimiento, importe, estado, fecha_pago, observacion`.
Casts: `importe => decimal:2`, `fecha_vencimiento => date`, `fecha_pago => date`, `estado => EstadoCuota::class`.
Relación `belongsTo(Movimiento::class)`.

### `MovimientoEstado`

`$fillable`: `movimiento_id, estado_anterior, estado_nuevo, fecha_efectiva, motivo, user_id`.
Casts a los enums y `fecha_efectiva => date`. `public $timestamps = false;` (solo `created_at`, con `useCurrent`).

---

## Fase 5 — Servicios

### `app/Services/GeneradorCuotas.php`

```php
/**
 * @return array<int, array{nro_cuota:int, anio_pago:int, mes_pago:int,
 *                          fecha_vencimiento:string, importe:float}>
 */
public function generar(
    int $anioLiquidacion,
    int $mesLiquidacion,
    int $desfasaje,
    int $diaPago,
    float $montoTotal,
    int $cantidadCuotas,
): array;
```

Reglas:
- Base = `Carbon::create($anioLiquidacion, $mesLiquidacion, 1)->addMonths($desfasaje)`.
- Cuota base = `round($montoTotal / $cantidadCuotas, 2)`.
- **La última cuota absorbe el redondeo**: `round($montoTotal - $cuotaBase * ($n - 1), 2)`.
  (Verificado contra las planillas reales: Gaillez declara 3 × 38.333,33 = 115.000,00.)
- `fecha_vencimiento` con clamp al último día del mes si `dia_pago` excede.

### `app/Services/CambiarEstadoMovimiento.php`

```php
public function cambiar(
    Movimiento $mov,
    EstadoMovimiento $nuevo,
    ?string $motivo = null,
    ?string $fechaEfectiva = null,
): void
```

- Valida transición contra `$mov->estado->siguientes()`; si no está permitida lanza
  `App\Exceptions\TransicionInvalida`.
- Valida motivo obligatorio si `$nuevo->requiereMotivo()`.
- Todo dentro de `DB::transaction()`: actualiza `movimientos.estado` + inserta fila en
  `movimiento_estados`.
- `fecha_efectiva` default `now()->toDateString()`.
- `user_id` de `auth()->id()`.

### Recalculo automático de estado (observer o método)

Al marcar una cuota como pagada:
- Si **todas** las cuotas no anuladas están pagadas → movimiento a `Pagado`.
- Si **alguna pero no todas** → movimiento a `Parcial`.

Registrar la transición vía `CambiarEstadoMovimiento` para que quede en el historial.

### Alta del movimiento

Al crear un movimiento, insertar la primera fila del historial con
`estado_anterior = null`, `estado_nuevo = 'pendiente'`, `fecha_efectiva = hoy`.
Así la traza arranca completa.

---

## Fase 6 — Filament (`MovimientoResource`)

> **Verificar los namespaces contra la versión instalada de Filament 4** antes de escribir
> (`Filament\Schemas\Components\Section`, `Filament\Schemas\Components\Utilities\Get|Set`,
> `Filament\Forms\Components\Repeater`, etc. cambiaron respecto de v3).
> Generar con `php artisan make:filament-resource Movimiento --generate` y adaptar,
> respetando el patrón del repo: `Schemas/MovimientoForm.php` + `Tables/MovimientosTable.php`.

Propiedades del Resource, siguiendo el estilo de `ProveedorResource`:

```php
protected static ?string $navigationLabel = 'Movimientos';
protected static ?string $modelLabel = 'Movimiento';
protected static ?string $pluralModelLabel = 'Movimientos';
protected static string|\UnitEnum|null $navigationGroup = 'Administración';
protected static ?string $breadcrumb = 'Movimientos';
protected static ?string $recordTitleAttribute = 'id';
```

### 6.1 Formulario

**Sección "Datos de la deuda"**
- `proveedor_id` — Select con `->relationship('proveedor', 'nombre')`, `->searchable()`, `->preload()`, required.
- `concepto_id` — Select `->relationship('concepto', 'nombre')`, required, default = concepto código `00`.
- `ejercicio` — numeric, default `now()->year`.
- `descripcion` — Textarea, nullable.

**Sección "Liquidación y pago"** (grid 4 columnas)
- `mes_liquidacion` — Select 1..12 con nombres de mes, required, `->live()`.
- `anio_liquidacion` — numeric, default `now()->year`, required, `->live()`.
- `desfasaje_primer_pago` — numeric, min 0, max 24, default 1, required, `->live()`,
  helper: *"Meses posteriores al mes de liquidación en que se paga la 1ª cuota"*.
- `dia_pago` — numeric, min 1, max 31, required.

**Sección "Cuotas"**
- `monto_total` — numeric, prefix `$`, required, `->live(debounce: 500)`.
- `cantidad_cuotas` — numeric, min 1, max 60, required, `->live(debounce: 500)`.
- Repeater `cuotas` con `->relationship()`, `->reorderable(false)`, `->addable(false)`,
  `->deletable(false)`, `->columns(4)`:
  - `nro_cuota` — disabled
  - `anio_pago` / `mes_pago` — disabled (se recalculan)
  - `importe` — numeric, editable (permite cuotas desiguales)
  - `observacion` — nullable
- Placeholder de control con la suma vs el total.

**Regeneración de la grilla.** `afterStateUpdated` en `monto_total`, `cantidad_cuotas`,
`mes_liquidacion`, `anio_liquidacion` y `desfasaje_primer_pago` → llamar a `GeneradorCuotas`
y `$set('cuotas', $filas)`.

```php
TextInput::make('cantidad_cuotas')
    ->numeric()->minValue(1)->maxValue(60)
    ->live(debounce: 500)
    ->afterStateUpdated(function (Set $set, Get $get) {
        $filas = app(GeneradorCuotas::class)->generar(
            (int) $get('anio_liquidacion'),
            (int) $get('mes_liquidacion'),
            (int) $get('desfasaje_primer_pago'),
            (int) $get('dia_pago'),
            (float) $get('monto_total'),
            (int) $get('cantidad_cuotas'),
        );
        $set('cuotas', $filas);
    }),
```

**Control de cuadratura (equivalente al "IMPORTE TOTAL" del legacy):**

```php
Placeholder::make('control_suma')
    ->label('Control')
    ->content(function (Get $get) {
        $suma = collect($get('cuotas'))->sum(fn ($c) => (float) ($c['importe'] ?? 0));
        $dif  = round($suma - (float) $get('monto_total'), 2);

        return $dif == 0.0
            ? '✓ Suma de cuotas: $' . number_format($suma, 2, ',', '.')
            : '✗ Diferencia: $' . number_format($dif, 2, ',', '.');
    }),
```

**Y además validación server-side que bloquee el guardado** (el placeholder es solo visual;
replicar el botón `Graba` deshabilitado del legacy, pero nunca confiar solo en el front):

```php
->rules([
    fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
        $suma = collect($get('cuotas'))->sum(fn ($c) => (float) ($c['importe'] ?? 0));
        if (round($suma - (float) $get('monto_total'), 2) != 0.0) {
            $fail('La suma de las cuotas no coincide con el monto total.');
        }
    },
])
```

En edición, si el movimiento está en estado final (`Pagado` / `Anulado`), el formulario va
`->disabled()`.

### 6.2 Tabla

Columnas: proveedor, concepto, período de liquidación (`mes/año`), monto total,
cantidad de cuotas, total pagado, saldo, estado (`TextColumn` con `->badge()` y
`->color(fn ($state) => $state->color())`), fecha de creación (toggleable).

Filtros: por proveedor, por concepto, por estado (`SelectFilter` sobre el enum),
por año de liquidación, `TrashedFilter`.

Acciones de fila: `ViewAction`, `EditAction`, más las tres acciones de estado (6.3).

### 6.3 Acciones de estado

**No usar un `Select` de estado en el formulario.** Tres `Action` separadas, cada una con
su modal, visibles según `$record->estado->siguientes()`:

| Acción | Estado destino | Campos del modal |
|---|---|---|
| **Registrar pago total** | `Pagado` | fecha efectiva |
| **Rechazar** | `Rechazado` | fecha efectiva + **motivo obligatorio** |
| **Anular** | `Anulado` | fecha efectiva + **motivo obligatorio** |

```php
Action::make('rechazar')
    ->label('Rechazar')
    ->icon('heroicon-o-x-circle')
    ->color('danger')
    ->visible(fn (Movimiento $r) => in_array(
        EstadoMovimiento::Rechazado, $r->estado->siguientes(), true
    ))
    ->schema([
        DatePicker::make('fecha_efectiva')->label('Fecha')->default(now())->required(),
        Textarea::make('motivo')->label('Motivo del rechazo')->required(),
    ])
    ->action(fn (Movimiento $r, array $data) =>
        app(CambiarEstadoMovimiento::class)->cambiar(
            $r, EstadoMovimiento::Rechazado, $data['motivo'], $data['fecha_efectiva']
        )
    ),
```

Sobre las cuotas, en el `RelationManager`: acción *Marcar como pagada* (pide fecha de pago),
que dispara el recalculo automático del estado del movimiento.

### 6.4 Relation Managers

- **`CuotasRelationManager`** — tabla de cuotas: nro, período, vencimiento, importe, estado,
  fecha de pago, observación. Acción de marcar pagada. No permitir alta/baja manual de filas
  (se generan desde la cabecera).
- **`EstadosRelationManager`** — historial, **solo lectura**: fecha efectiva, estado anterior →
  nuevo, motivo, usuario, fecha de carga. Sin acciones de creación ni edición.

---

## Fase 7 — Seeders y factories

- [ ] **`ConceptoSeeder`**: crear el concepto `00` (código de operación provisorio).
      Dejar comentado un array con la estructura para cargar el resto cuando el sindicato
      entregue el listado real de códigos de operación (ej. `01 - Gastos Generales`).
- [ ] **`MovimientoFactory`**: hoy devuelve `[]`. Completar con datos coherentes
      (proveedor y concepto por factory, montos realistas de 6 a 8 dígitos, 1 a 24 cuotas).
- [ ] **`MovimientoSeeder`**: hoy está vacío. Generar ~20 movimientos con sus cuotas
      generadas por `GeneradorCuotas`, repartidos entre los distintos estados.
- [ ] Registrar los seeders en `DatabaseSeeder` (hoy solo crea el usuario de prueba).

---

## Fase 8 — Tests

`tests/Feature/`:

- [ ] `GeneradorCuotasTest`
  - genera la cantidad correcta de filas
  - la suma de las cuotas es exactamente igual al monto total (probar con montos que no
    dividen exacto: 100.000 / 3, 115.000 / 3)
  - los períodos respetan el desfasaje y hacen rollover de año correctamente
    (liquidación 11/2025, desfasaje 2, 4 cuotas → 01/2026 … 04/2026)
  - clamp de `dia_pago = 31` en febrero y en meses de 30 días
- [ ] `CambiarEstadoMovimientoTest`
  - transición válida actualiza el estado y crea la fila de historial
  - transición inválida (`Pagado` → `Pendiente`) lanza `TransicionInvalida`
  - `Rechazado` / `Anulado` sin motivo lanzan excepción
  - `fecha_efectiva` se persiste distinta de `created_at`
- [ ] `MovimientoResourceTest`
  - no se puede guardar si la suma de cuotas ≠ monto total
  - al crear un movimiento se generan N cuotas y la fila inicial de historial
  - marcar todas las cuotas pagadas lleva el movimiento a `Pagado`
  - marcar una sola lo lleva a `Parcial`

---

## Fase 9 — Higiene (opcional, si sobra tiempo)

- [ ] `ConceptoResource`: cambiar `$navigationLabel` a "Códigos de operación" — es lo que
      representa funcionalmente. Agregar página `view` (hoy solo tiene index/create/edit).
- [ ] `.env.example`: `DB_HOST=db` (hoy `127.0.0.1`, no funciona con docker-compose) y
      `DB_DATABASE` unificado con `POSTGRES_DB` del compose (hoy dice `suoem_base` vs `suoem_db`).
- [ ] `APP_LOCALE=es` — la UI está toda en español pero el locale es `en`.
- [ ] `User`: implementar `FilamentUser::canAccessPanel()`. Hoy, con `APP_ENV=production`,
      cualquier usuario autenticado accede al panel completo.

---

## Decisiones tomadas por defecto (revisar con el usuario)

Estas quedaron sin confirmar. Implementar según lo indicado y dejarlas marcadas con `// REVISAR:`
en el código para cambiarlas rápido:

1. **No existe estado `Observado`.** El sello físico dice *"sujeto a revisión posterior"*, lo
   que sugiere una instancia de revisión intermedia. Si se confirma que existe, agregar
   `Observado` entre `Pendiente` y el resto. El enum está diseñado para que sumarlo sea trivial.
2. **Se permite anular desde `Parcial`.** Queda plata pagada contra una deuda anulada. Si el
   negocio lo prohíbe, quitar `Anulado` de `EstadoMovimiento::Parcial->siguientes()`.
3. **`Parcial` es automático**, derivado del avance de las cuotas. Si se prefiere manual,
   sacar el recalculo automático de la Fase 5.

---

## Criterios de aceptación

- [ ] `php artisan migrate:fresh --seed` corre sin errores contra PostgreSQL.
- [ ] Se puede dar de alta un movimiento: al tipear cantidad de cuotas la grilla se llena
      sola con los períodos correctos, igual que en MUTUALIS.
- [ ] Editar el importe de una cuota rompe la cuadratura y el sistema **impide guardar**.
- [ ] La suma de las cuotas siempre es exactamente igual al monto total, incluso con
      divisiones no exactas.
- [ ] Rechazar o anular sin motivo es imposible.
- [ ] El historial de estados muestra toda la traza desde el alta, con fecha efectiva,
      motivo y usuario.
- [ ] `php artisan test` en verde.
- [ ] `./vendor/bin/pint` sin diferencias.
