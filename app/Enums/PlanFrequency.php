<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Facades\Lang;

enum PlanFrequency: int implements HasColor, HasLabel
{
    case MONTHLY = 1;
    case YEARLY = 2;

    public function getLabel(): string
    {
        return Lang::get('messages.plan_frequency.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::MONTHLY => 'danger',
            self::YEARLY => 'primary',
        };
    }
}
