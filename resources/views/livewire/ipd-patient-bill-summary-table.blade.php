<div class="px-2">
    {{ $this->form }}
    <div class="flex items-center my-4 flex-wrap gap-3">
        <x-filament::button href="{{ url('ipd-bills/' . $this->id . '/pdf') }}" target="_blank" tag="a">
            {{ __('messages.bill.print_bill') }}
        </x-filament::button>
        <x-filament::button href="{{ url('ipd-discharge-patient/' . $this->id . '/pdf') }}" target="_blank" tag="a">
            {{ __('messages.lunch_break.print_discharge_slip') }}
        </x-filament::button>
        @if (!getLoggedinPatient())
            @if (!$this->record->bill_status)
                <x-filament::button wire:click="submitBill">
                    {{ __('messages.bill.generate_bill') . ' & ' . __('messages.ipd_patient.discharge_patient') }}
                </x-filament::button>
            @endif
        @endif
    </div>
</div>
