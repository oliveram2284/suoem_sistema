<?php

namespace App\Enums;

enum EstadoMovimiento: string
{
    case Pendiente = 'pendiente';
    case Parcial = 'parcial';
    case Pagado = 'pagado';
    case Rechazado = 'rechazado';
    case Anulado = 'anulado';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Parcial => 'Pago parcial',
            self::Pagado => 'Pagado / Cancelado',
            self::Rechazado => 'Rechazado',
            self::Anulado => 'Anulado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Parcial => 'info',
            self::Pagado => 'success',
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
            self::Parcial => [self::Pagado, self::Anulado],
            self::Rechazado => [self::Pendiente, self::Anulado],
            self::Pagado => [],
            self::Anulado => [],
        };
    }
}
