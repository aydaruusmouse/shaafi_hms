<?php

namespace App\Models;

use App\Traits\PopulateTenantID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ConsultationRadiologyTest extends Model
{
    use BelongsToTenant, PopulateTenantID;

    protected $table = 'consultation_radiology_tests';

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'radiology_category_id',
        'caseable_type',
        'caseable_id',
        'test_name',
        'notes',
        'radiology_test_id',
        'processed_at',
        'payment_status',
        'payment_mode',
        'paid_amount',
        'paid_at',
        'payment_note',
        'payment_reference',
    ];

    protected $casts = [
        'id' => 'integer',
        'patient_id' => 'integer',
        'radiology_category_id' => 'integer',
        'caseable_id' => 'integer',
        'radiology_test_id' => 'integer',
        'processed_at' => 'datetime',
        'payment_status' => 'integer',
        'payment_mode' => 'integer',
        'paid_amount' => 'double',
        'paid_at' => 'datetime',
    ];

    const PAYMENT_UNPAID = 0;

    const PAYMENT_PAID = 1;

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function radiologyCategory(): BelongsTo
    {
        return $this->belongsTo(RadiologyCategory::class);
    }

    public function chargeCategory(): BelongsTo
    {
        return $this->belongsTo(ChargeCategory::class);
    }

    public function radiologyTest(): HasOne
    {
        return $this->hasOne(RadiologyTest::class, 'consultation_radiology_test_id');
    }

    public function linkedRadiologyTest(): BelongsTo
    {
        return $this->belongsTo(RadiologyTest::class, 'radiology_test_id');
    }

    public function caseable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getDoctorNameAttribute(): string
    {
        $caseable = $this->caseable;
        if (! $caseable) {
            return 'N/A';
        }

        $doctor = $caseable->doctor
            ?? $caseable->patient?->doctor
            ?? $caseable->ipdPatient?->doctor
            ?? null;

        return $doctor?->doctorUser?->full_name
            ?? $doctor?->user?->full_name
            ?? 'N/A';
    }

    public function getDoctorIdAttribute(): ?int
    {
        $caseable = $this->caseable;
        if (! $caseable) {
            return null;
        }

        return $caseable->doctor_id
            ?? $caseable->patient?->doctor_id
            ?? $caseable->ipdPatient?->doctor_id
            ?? null;
    }

    public function getSectionAttribute(): string
    {
        $type = (string) $this->caseable_type;
        if (str_contains($type, 'Ipd')) {
            return 'IPD';
        }
        if (str_contains($type, 'Opd')) {
            return 'OPD';
        }

        return 'N/A';
    }

    public function isPaid(): bool
    {
        return (int) $this->payment_status === self::PAYMENT_PAID;
    }

    public function groupQuery()
    {
        return static::query()
            ->where('tenant_id', $this->tenant_id ?? getLoggedInUser()->tenant_id)
            ->where('patient_id', $this->patient_id)
            ->where('caseable_type', $this->caseable_type)
            ->where('caseable_id', $this->caseable_id);
    }

    public static function groupedIndexQuery($query)
    {
        $tenantId = getLoggedInUser()->tenant_id;

        return $query->where('tenant_id', $tenantId)
            ->whereIn('id', function ($subQuery) use ($tenantId) {
                $subQuery->selectRaw('MIN(id)')
                    ->from('consultation_radiology_tests')
                    ->where('tenant_id', $tenantId)
                    ->groupBy('patient_id', 'caseable_type', 'caseable_id');
            });
    }

    public function estimatedCharge(): float
    {
        $categoryIds = $this->groupQuery()
            ->pluck('radiology_category_id')
            ->unique()
            ->filter();

        $total = 0.0;
        foreach ($categoryIds as $categoryId) {
            $charge = RadiologyTest::where('tenant_id', $this->tenant_id ?? getLoggedInUser()->tenant_id)
                ->where('category_id', $categoryId)
                ->orderByDesc('id')
                ->value('standard_charge');
            $total += (float) ($charge ?? 0);
        }

        if ($total <= 0) {
            $total = (float) (Charge::where('tenant_id', $this->tenant_id ?? getLoggedInUser()->tenant_id)->value('standard_charge') ?? 0);
        }

        return $total;
    }

    public function testLineItems(): array
    {
        $tenantId = $this->tenant_id ?? getLoggedInUser()->tenant_id;
        $defaultCharge = Charge::where('tenant_id', $tenantId)->first();

        return $this->groupQuery()
            ->with('radiologyCategory')
            ->get()
            ->unique(fn ($test) => $test->radiology_category_id ?: $test->id)
            ->map(function ($test) use ($tenantId, $defaultCharge) {
                $charge = Charge::where('tenant_id', $tenantId)
                    ->where('charge_category_id', $defaultCharge?->charge_category_id)
                    ->first() ?? $defaultCharge;

                $rawName = trim((string) ($test->getAttributes()['test_name'] ?? ''));
                $name = ($rawName !== '' && strcasecmp($rawName, 'N/A') !== 0)
                    ? $rawName
                    : ($test->radiologyCategory?->name ?? __('messages.radiology_tests'));

                return [
                    'test_name' => $name,
                    'charge_category_id' => $charge?->charge_category_id,
                    'amount' => (float) ($charge?->standard_charge ?? 0),
                ];
            })
            ->values()
            ->toArray();
    }

    public function markGroupPaid(array $data): void
    {
        $this->groupQuery()->update([
            'payment_status' => self::PAYMENT_PAID,
            'payment_mode' => $data['payment_mode'] ?? null,
            'paid_amount' => $data['paid_amount'] ?? null,
            'paid_at' => $data['paid_at'] ?? now(),
            'payment_note' => $data['payment_note'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
        ]);
    }
}
