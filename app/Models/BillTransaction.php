<?php

namespace App\Models;

use App\Traits\PopulateTenantID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class BillTransaction extends Model
{
    use BelongsToTenant, PopulateTenantID;

    protected $table = 'bill_transactions';

    public $fillable = [
        'transaction_id',
        'payment_type',
        'amount',
        'bill_id',
        'status',
        'meta',
        'is_manual_payment',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'json',
            'status' => 'boolean',
        ];
    }

    const PAID = 1;

    const UNPAID = 0;

    const TYPE_STRIPE = 0;

    const TYPE_CASH = 2;

    const APPROVED = 1;

    const DENIED = 2;

    public const PAYMENT_MODES_PHONEPE = 8;

    const PHONEPE = 1;

    const FLUTTERWAVE = 3;

    const PAYSTACK = 4;

    const RAZORPAY = 5;

    const PAYPAL = 6;

    const PAYMENT_TYPES = [
        self::TYPE_STRIPE => 'Stripe',
        self::TYPE_CASH => 'Manual',
        self::PHONEPE => 'PhonePe',
        self::FLUTTERWAVE => 'Flutterwave',
        self::PAYSTACK => 'Paystack',
        self::RAZORPAY => 'Razorpay',
        self::PAYPAL => 'Paypal',
    ];

    const PAYMENT_STATUS = [
        self::PAID => 'Paid',
        self::UNPAID => 'Unpaid',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }
}
