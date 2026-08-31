<?php

namespace App\Models;

use App\Traits\PopulateTenantID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ConsultationPathologyTest extends Model
{
    use BelongsToTenant, PopulateTenantID;

    protected $table = 'consultation_pathology_tests';

    protected $fillable = [
        'patient_id',
        'pathology_category_id',
        'caseable_type',
        'caseable_id',
        'pathology_parameter_id',
        'pathology_test_id',
        'test_name',
        'notes',
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
        'pathology_category_id' => 'integer',
        'caseable_id' => 'integer',
        'pathology_test_id' => 'integer',
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

    public function pathologyCategory(): BelongsTo
    {
        return $this->belongsTo(PathologyCategory::class);
    }

    public function caseable(): MorphTo
    {
        return $this->morphTo();
    }

    // Relationship to PathologyTest (many-to-one)
    public function pathologyTest(): BelongsTo
    {
        return $this->belongsTo(PathologyTest::class, 'pathology_test_id');
    }

    // Remove the old hasOne relationship
    
    public function pathologyParameter(): BelongsTo
    {
        return $this->belongsTo(PathologyParameter::class, 'pathology_parameter_id');
    }

    // Helper method to get doctor name
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

    // Helper method to get section (IPD/OPD)
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

    // Check if test is already processed
    public function getIsProcessedAttribute(): bool
    {
        return !is_null($this->pathology_test_id);
    }

    // Many consultation tests can point to one pathology test
    public function getTestNameAttribute(): string
    {
        return $this->pathologyTest?->test_name
            ?? ($this->attributes['test_name'] ?? 'N/A');
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
                    ->from('consultation_pathology_tests')
                    ->where('tenant_id', $tenantId)
                    ->groupBy('patient_id', 'caseable_type', 'caseable_id');
            });
    }

    public function estimatedCharge(): float
    {
        $categoryIds = $this->groupQuery()
            ->pluck('pathology_category_id')
            ->unique()
            ->filter();

        $total = 0.0;
        foreach ($categoryIds as $categoryId) {
            $charge = PathologyTest::where('tenant_id', $this->tenant_id ?? getLoggedInUser()->tenant_id)
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
            ->with('pathologyCategory')
            ->get()
            ->unique(fn ($test) => $test->pathology_category_id ?: $test->id)
            ->map(function ($test) use ($tenantId, $defaultCharge) {
                $charge = Charge::where('tenant_id', $tenantId)
                    ->where('charge_category_id', $defaultCharge?->charge_category_id)
                    ->first() ?? $defaultCharge;

                $rawName = trim((string) ($test->getAttributes()['test_name'] ?? ''));
                $name = ($rawName !== '' && strcasecmp($rawName, 'N/A') !== 0)
                    ? $rawName
                    : ($test->pathologyCategory?->name ?? __('messages.pathology_tests'));

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
