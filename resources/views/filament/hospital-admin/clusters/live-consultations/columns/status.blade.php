<div>
    @php
        $adminRole = getLoggedInUser()->hasRole('Admin') ? true : false;
        $doctorRole = getLoggedInUser()->hasRole('Doctor') ? true : false;
    @endphp
    @if ($adminRole || $doctorRole)
        @if ($getState() == 0)
            <div
                class="flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 [&amp;:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20 [&amp;:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600 dark:[&amp;:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500">
                <div class="min-w-0 flex-1">
                    <select wire:change="changeStatus($event.target.value, {{ $getRecord()->id }})"
                        class="block w-full border-none bg-transparent py-1.5 pe-8 text-base text-gray-950 transition duration-75 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] dark:text-white dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] sm:text-sm sm:leading-6 [&amp;_optgroup]:bg-white [&amp;_optgroup]:dark:bg-gray-900 [&amp;_option]:bg-white [&amp;_option]:dark:bg-gray-900 ps-3">
                        <option value="0">{{ __('messages.live_consultation_filter.awaited') }}</option>
                        <option value="1">{{ __('messages.live_consultation_filter.cancelled') }}</option>
                        <option value="2">{{ __('messages.live_consultation_filter.finished') }}</option>
                    </select>
                </div>
            </div>
        @elseif ($getState() == 1)
            <span style="--c-50:var(--danger-50);--c-400:var(--danger-400);--c-600:var(--danger-600);"
                class="flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30 fi-color-danger">
                {{ __('messages.live_consultation_filter.cancelled') }}
            </span>
        @elseif ($getState() == 2)
            <span style="--c-50:var(--success-50);--c-400:var(--success-400);--c-600:var(--success-600);"
                class="flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30 fi-color-success">
                {{ __('messages.live_consultation_filter.finished') }}
            </span>
        @endif
    @else
        @if ($getState() == 1)
            <span style="--c-50:var(--danger-50);--c-400:var(--danger-400);--c-600:var(--danger-600);"
                class="flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30 fi-color-danger">{{ $getRecord()->status_text }}</span>
        @elseif($getState() == 0)
            <span style="--c-50:var(--warning-50);--c-400:var(--warning-400);--c-600:var(--warning-600);"
                class="flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30 fi-color-warning">{{ $getRecord()->status_text }}</span>
        @elseif ($getState() == 2)
            <span style="--c-50:var(--success-50);--c-400:var(--success-400);--c-600:var(--success-600);"
                class="flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30 fi-color-success">{{ $getRecord()->status_text }}</span>
        @else
            {{ $getRecord()->status_text }}
        @endif
    @endif
</div>
