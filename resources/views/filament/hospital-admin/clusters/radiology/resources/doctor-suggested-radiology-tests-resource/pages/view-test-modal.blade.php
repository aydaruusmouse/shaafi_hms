@if($test)
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-900">
        <h3 class="mb-4 border-b pb-2 text-lg font-semibold text-gray-900 dark:text-white">Test Information</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="block text-sm font-medium text-gray-500">{{ __('messages.case.patient') }}</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $test->patient->user->full_name ?? __('messages.common.n/a') }}</p>
                <p class="text-xs text-gray-500">{{ displayPatientEmail($test->patient->user->email ?? null) }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">{{ __('messages.user.phone') }}</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ displayPatientPhone($test->patient->user ?? null) }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">{{ __('messages.user.gender') }}</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ displayPatientGender($test->patient->user ?? null) }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">{{ __('messages.radiology_test.test_name') }}</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $test->test_name ?? __('messages.common.n/a') }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">{{ __('messages.radiology_test.short_name') }}</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $test->short_name ?? __('messages.common.n/a') }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">{{ __('messages.radiology_test.test_type') }}</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $test->test_type ?? __('messages.common.n/a') }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">{{ __('messages.radiology_test.category_name') }}</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $test->radiologycategory->name ?? __('messages.common.n/a') }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">{{ __('messages.radiology_test.charge_category') }}</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $test->chargecategory->name ?? __('messages.common.n/a') }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">{{ __('messages.radiology_test.standard_charge') }}</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ getCurrencyFormat($test->standard_charge) }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">{{ __('messages.radiology_test.result_status') }}</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">
                    @if($test->result_status === 'abnormal')
                        {{ __('messages.radiology_test.abnormal') }}
                    @elseif($test->result_status === 'normal')
                        {{ __('messages.radiology_test.normal') }}
                    @else
                        {{ __('messages.common.n/a') }}
                    @endif
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">{{ __('messages.radiology_test.document_name') }}</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $test->result_document_name ?? __('messages.radiology_test.no_document_uploaded') }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">{{ __('messages.common.created_on') }}</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ optional($test->created_at)->format('d M, Y h:i A') ?? __('messages.common.n/a') }}</p>
            </div>
        </div>
    </div>
</div>
@elseif(isset($suggestions) && $suggestions->count())
<div class="space-y-4">
    <p class="text-sm text-gray-500">{{ __('messages.appointment.pending') }}</p>
    <ul class="list-disc space-y-1 pl-5 text-sm text-gray-900 dark:text-white">
        @foreach($suggestions as $suggestion)
            <li>{{ $suggestion->getAttributes()['test_name'] ?? ($suggestion->radiologyCategory->name ?? __('messages.radiology_tests')) }}</li>
        @endforeach
    </ul>
</div>
@else
    <p class="text-sm text-gray-500">{{ __('messages.common.no_data_found') }}</p>
@endif
