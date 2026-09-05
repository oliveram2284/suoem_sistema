<?php

namespace App\Enums;

enum EstadoCuota: string
{
    case Pendiente = 'pendiente';
    case Pagada = 'pagada';
    case Anulada = 'anulada';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Pagada => 'Pagada',
            self::Anulada => 'Anulada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Pagada => 'success',
            self::Anulada => 'danger',
        };
    }
}
