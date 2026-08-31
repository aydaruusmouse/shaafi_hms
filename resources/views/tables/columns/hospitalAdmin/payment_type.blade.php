@if ($getRecord())
    @php
        $manualPayment = App\Models\BillTransaction::where('bill_id', $getRecord()->id)
            ->where('tenant_id', $getRecord()->tenant_id)
            ->latest()
            ->first();
    @endphp
    @if ($getRecord()->status == 0 || empty($getRecord()->status))
        <x-filament::input.wrapper>
            <x-filament::input.select
                x-on:change="$wire.call('changePaymentStatus', {{ $getRecord()->id }}, $el.value)">
                <option value="" disabled selected>{{ __('messages.ipd_payments.payment_mode') }}</option>
                @foreach (getBillPaymentType() as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    @else
        <x-filament::badge
            color="primary">{{ App\Models\BillTransaction::PAYMENT_TYPES[$manualPayment->payment_type] }}</x-filament::badge>
    @endif
@endif
