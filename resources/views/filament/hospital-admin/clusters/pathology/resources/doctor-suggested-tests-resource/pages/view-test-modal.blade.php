{{-- resources/views/filament/hospital-admin/clusters/pathology/resources/doctor-suggested-tests-resource/pages/view-test-modal.blade.php --}}
@if($test)
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Test Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-500">{{ __('messages.case.patient') }}</label>
                <p class="mt-1 text-sm text-gray-900">{{ $test->patient->user->full_name ?? __('messages.common.n/a') }}</p>
                <p class="text-xs text-gray-500">{{ displayPatientEmail($test->patient->user->email ?? null) }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">{{ __('messages.user.phone') }}</label>
                <p class="mt-1 text-sm text-gray-900">{{ displayPatientPhone($test->patient->user ?? null) }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">{{ __('messages.user.gender') }}</label>
                <p class="mt-1 text-sm text-gray-900">{{ displayPatientGender($test->patient->user ?? null) }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Test Name</label>
                <p class="mt-1 text-sm text-gray-900">{{ $test->test_name ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Short Name</label>
                <p class="mt-1 text-sm text-gray-900">{{ $test->short_name ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Test Type</label>
                <p class="mt-1 text-sm text-gray-900">{{ $test->test_type ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Category</label>
                <p class="mt-1 text-sm text-gray-900">{{ $test->pathologycategory->name ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Charge Category</label>
                <p class="mt-1 text-sm text-gray-900">{{ $test->chargecategory->name ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Standard Charge</label>
                <p class="mt-1 text-sm text-gray-900">{{ getCurrencyFormat($test->standard_charge) }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Created On</label>
                <p class="mt-1 text-sm text-gray-900">{{ optional($test->created_at)->format('d M, Y h:i A') ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    @if($test->parameterItems && $test->parameterItems->count() > 0)
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Test Parameters</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parameter Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient Result</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference Range</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($test->parameterItems as $parameter)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $parameter->pathologyParameter->parameter_name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $parameter->patient_result ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $parameter->pathologyParameter->reference_range ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $parameter->pathologyParameter->pathologyUnit->name ?? 'N/A' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@elseif(isset($suggestions) && $suggestions->count() && isset($record))
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Test Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-500">{{ __('messages.case.patient') }}</label>
                <p class="mt-1 text-sm text-gray-900">{{ $record->patient->user->full_name ?? __('messages.common.n/a') }}</p>
                <p class="text-xs text-gray-500">{{ displayPatientEmail($record->patient->user->email ?? null) }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">{{ __('messages.user.phone') }}</label>
                <p class="mt-1 text-sm text-gray-900">{{ displayPatientPhone($record->patient->user ?? null) }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">{{ __('messages.user.gender') }}</label>
                <p class="mt-1 text-sm text-gray-900">{{ displayPatientGender($record->patient->user ?? null) }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Doctor</label>
                <p class="mt-1 text-sm text-gray-900">{{ $record->doctor_name ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Status</label>
                <p class="mt-1 text-sm text-gray-900">{{ $record->isPaid() ? __('messages.paid') : __('messages.new_change.pending_payment') }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Total Amount</label>
                <p class="mt-1 text-sm text-gray-900">{{ getCurrencyFormat($record->isPaid() ? $record->paid_amount : $record->estimatedCharge()) }}</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Test</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($suggestions as $suggestion)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $suggestion->getAttributes()['test_name'] ?? ($suggestion->pathologyCategory->name ?? 'N/A') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $suggestion->pathologyCategory->name ?? 'N/A' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@else
    <div class="p-4 text-red-500">
        Test data is not available.
    </div>
@endif
