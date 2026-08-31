<div>
    @if ($getRecord()->is_default)
        <span style="--c-50:var(--success-50);--c-400:var(--success-400);--c-600:var(--success-600);"
            class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30 fi-color-success">
            <span class="grid"> <span class="truncate"> {{ __('messages.common.default_plan') }} </span> </span> </span>
    @else
        <div type="button" x-on:click="$wire.call('toggleStatus', {{ $getRecord()->id }})"
            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent outline-none transition-colors duration-200 ease-in-out bg-gray-200 dark:bg-gray-700 fi-color-gray"
            style="--c-600:var(--gray-600)"> <span
                class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out translate-x-0">
                <span
                    class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity opacity-100 ease-in duration-200"
                    aria-hidden="true"> </span> <span
                    class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity opacity-0 ease-out duration-100"
                    aria-hidden="true"> </span> </span> </div>
    @endif
</div>
