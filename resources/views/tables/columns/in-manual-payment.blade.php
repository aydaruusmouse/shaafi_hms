    @if ($getRecord()->is_manual_payment == 0 && $getRecord()->status == 0)
        <x-filament::input.wrapper>
            <x-filament::input.select searchable
                x-on:change="if ($el.value == '1') {
                $wire.call('changePaymentStatus', {{ $getRecord()->id }}, 1)
            } else {
                $wire.call('changePaymentStatus', {{ $getRecord()->id }}, $el.value)
            }">
                <option selected="selected" value="">{{ __('messages.subscription.waiting_for_approval') }}</option>
                <option value="1">{{ __('messages.subscription.approved') }}</option>
                <option value="2">{{ __('messages.subscription.denied') }}</option>
            </x-filament::input.select>
        </x-filament::input.wrapper>
    @elseif ($getRecord()->is_manual_payment == 1)
        <x-filament::badge color="success">{{ __('messages.subscription.approved') }}</x-filament::badge>
    @elseif ($getRecord()->is_manual_payment == 2)
        <x-filament::badge color="danger">{{ __('messages.subscription.denied') }}</x-filament::badge>
    @else
        <div class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white  ">
            {{ __('messages.common.n/a') }}
        </div>
    @endif
