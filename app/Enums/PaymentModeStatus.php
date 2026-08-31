<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentModeStatus: int implements HasLabel
{
    // case PENDING = 0;
    // case APPROVED = 1;
    // case REJECTED = 2;
    // case SOLD = 3;

    case CASH = 0;
    case CHEQUE = 1;
    case RAZORPAY = 2;
    case PAYSTACK = 3;
    case PHONEPE = 4;
    case STRIPE = 5;
    case FLUTTERWAVE = 6;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CASH => __('messages.transaction_filter.cash'),
            self::STRIPE => __('messages.transaction_filter.stripe'),
            self::RAZORPAY => __('messages.transaction_filter.razorpay'),
            self::PAYSTACK => __('messages.transaction_filter.paystack'),
            self::PHONEPE => __('messages.phonepe.phonepe'),
            self::CHEQUE => __('messages.transaction_filter.cheque'),
            self::FLUTTERWAVE => __('messages.flutterwave.flutterwave'),
            default => null,
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::CASH => 'info',
            self::STRIPE => 'primary',
            self::RAZORPAY => 'success',
            self::PAYSTACK => 'success',
            self::PHONEPE => 'primary',
            self::CHEQUE => 'info',
            self::FLUTTERWAVE => 'info',
            default => 'secondary',
        };
    }
}
