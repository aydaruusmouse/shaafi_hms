<x-filament-widgets::widget>
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        @if (getModuleAccess('Invoices'))
            <a href="{{ route('filament.hospitalAdmin.billings.resources.invoices.index') }}">
                <div
                    class="fi-wi-stats-overview-stat relative items-center rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 flex justify-between">
                    <div
                        class="flex items-center justify-center w-11 h-11 rounded-lg p-1 ring-2 ring-inset ring-gray-200 hover:ring-gray-300 dark:ring-gray-500 hover:dark:ring-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" class="h-7 w-10 dark:text-gray-200">
                            <path fill="currentColor"
                                d="M64 0C28.7 0 0 28.7 0 64L0 448c0 35.3 28.7 64 64 64l256 0c35.3 0 64-28.7 64-64l0-288-128 0c-17.7 0-32-14.3-32-32L224 0 64 0zM256 0l0 128 128 0L256 0zM80 64l64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16L80 96c-8.8 0-16-7.2-16-16s7.2-16 16-16zm0 64l64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-64 0c-8.8 0-16-7.2-16-16s7.2-16 16-16zm16 96l192 0c17.7 0 32 14.3 32 32l0 64c0 17.7-14.3 32-32 32L96 352c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32zm0 32l0 64 192 0 0-64L96 256zM240 416l64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-64 0c-8.8 0-16-7.2-16-16s7.2-16 16-16z" />
                        </svg>

                    </div>
                    <div class="grid gap-y-2">
                        <div class="flex items-center gap-x-2">
                            <span
                                class="fi-wi-stats-overview-stat-label text-sm font-bold text-gray-500 dark:text-gray-400">
                                {{ __('messages.dashboard.total_invoices') }}
                            </span>
                        </div>
                        <div class="text-right text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ $invoiceAmount }}
                        </div>
                    </div>
                </div>
            </a>
        @endif

        @if (getModuleAccess('Bills'))
            <a href="{{ route('filament.hospitalAdmin.billings.resources.bills.index') }}">
                <div
                    class="fi-wi-stats-overview-stat relative items-center rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 flex justify-between">
                    <div
                        class="flex items-center justify-center w-11 h-11 rounded-lg p-1 ring-2 ring-inset ring-gray-200 hover:ring-gray-300 dark:ring-gray-500 hover:dark:ring-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"
                            class="h-7 w-10 dark:text-gray-200">
                            <path fill="currentColor"
                                d="M64 64C28.7 64 0 92.7 0 128L0 384c0 35.3 28.7 64 64 64l448 0c35.3 0 64-28.7 64-64l0-256c0-35.3-28.7-64-64-64L64 64zm64 320l-64 0 0-64c35.3 0 64 28.7 64 64zM64 192l0-64 64 0c0 35.3-28.7 64-64 64zM448 384c0-35.3 28.7-64 64-64l0 64-64 0zm64-192c-35.3 0-64-28.7-64-64l64 0 0 64zM288 160a96 96 0 1 1 0 192 96 96 0 1 1 0-192z" />
                        </svg>
                    </div>
                    <div class="grid gap-y-2">
                        <div class="flex items-center gap-x-2">
                            <span
                                class="fi-wi-stats-overview-stat-label text-sm font-bold text-gray-500 dark:text-gray-400">
                                {{ __('messages.dashboard.total_bills') }}
                            </span>
                        </div>
                        <div class="text-right text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ $billAmount }}
                        </div>
                    </div>
                </div>
            </a>
        @endif
        @if (getModuleAccess('Payments'))
            <a href="{{ route('filament.hospitalAdmin.billings.resources.payments.index') }}">
                <div
                    class="fi-wi-stats-overview-stat relative items-center rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 flex justify-between">
                    <div
                        class="flex items-center justify-center w-11 h-11 rounded-lg p-1 ring-2 ring-inset ring-gray-200 hover:ring-gray-300 dark:ring-gray-500 hover:dark:ring-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
                            class="h-7 w-10 dark:text-gray-200">
                            <path fill="currentColor"
                                d="M512 80c0 18-14.3 34.6-38.4 48c-29.1 16.1-72.5 27.5-122.3 30.9c-3.7-1.8-7.4-3.5-11.3-5C300.6 137.4 248.2 128 192 128c-8.3 0-16.4 .2-24.5 .6l-1.1-.6C142.3 114.6 128 98 128 80c0-44.2 86-80 192-80S512 35.8 512 80zM160.7 161.1c10.2-.7 20.7-1.1 31.3-1.1c62.2 0 117.4 12.3 152.5 31.4C369.3 204.9 384 221.7 384 240c0 4-.7 7.9-2.1 11.7c-4.6 13.2-17 25.3-35 35.5c0 0 0 0 0 0c-.1 .1-.3 .1-.4 .2c0 0 0 0 0 0s0 0 0 0c-.3 .2-.6 .3-.9 .5c-35 19.4-90.8 32-153.6 32c-59.6 0-112.9-11.3-148.2-29.1c-1.9-.9-3.7-1.9-5.5-2.9C14.3 274.6 0 258 0 240c0-34.8 53.4-64.5 128-75.4c10.5-1.5 21.4-2.7 32.7-3.5zM416 240c0-21.9-10.6-39.9-24.1-53.4c28.3-4.4 54.2-11.4 76.2-20.5c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 19.3-16.5 37.1-43.8 50.9c-14.6 7.4-32.4 13.7-52.4 18.5c.1-1.8 .2-3.5 .2-5.3zm-32 96c0 18-14.3 34.6-38.4 48c-1.8 1-3.6 1.9-5.5 2.9C304.9 404.7 251.6 416 192 416c-62.8 0-118.6-12.6-153.6-32C14.3 370.6 0 354 0 336l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 342.6 135.8 352 192 352s108.6-9.4 148.1-25.9c7.8-3.2 15.3-6.9 22.4-10.9c6.1-3.4 11.8-7.2 17.2-11.2c1.5-1.1 2.9-2.3 4.3-3.4l0 3.4 0 5.7 0 26.3zm32 0l0-32 0-25.9c19-4.2 36.5-9.5 52.1-16c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 10.5-5 21-14.9 30.9c-16.3 16.3-45 29.7-81.3 38.4c.1-1.7 .2-3.5 .2-5.3zM192 448c56.2 0 108.6-9.4 148.1-25.9c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 44.2-86 80-192 80S0 476.2 0 432l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 438.6 135.8 448 192 448z" />
                        </svg>
                    </div>
                    <div class="grid gap-y-2">
                        <div class="flex items-center gap-x-2">
                            <span
                                class="fi-wi-stats-overview-stat-label text-sm font-bold text-gray-500 dark:text-gray-400">
                                {{ __('messages.dashboard.total_payments') }}
                            </span>
                        </div>
                        <div class="text-right text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ $paymentAmount }}
                        </div>
                    </div>
                </div>
            </a>
        @endif

        @if (getModuleAccess('Advance Payments'))
            <a href="{{ route('filament.hospitalAdmin.billings.resources.advanced-payments.index') }} ">
                <div
                    class="fi-wi-stats-overview-stat relative items-center rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 flex justify-between">
                    <div
                        class="flex items-center justify-center w-11 h-11 rounded-lg p-1 ring-2 ring-inset ring-gray-200 hover:ring-gray-300 dark:ring-gray-500 hover:dark:ring-gray-400">

                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"
                            class="h-7 w-10 dark:text-gray-200">
                            <path fill="currentColor"
                                d="M64 64C28.7 64 0 92.7 0 128L0 384c0 35.3 28.7 64 64 64l448 0c35.3 0 64-28.7 64-64l0-256c0-35.3-28.7-64-64-64L64 64zm48 160l160 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-160 0c-8.8 0-16-7.2-16-16s7.2-16 16-16zM96 336c0-8.8 7.2-16 16-16l352 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-352 0c-8.8 0-16-7.2-16-16zM376 160l80 0c13.3 0 24 10.7 24 24l0 48c0 13.3-10.7 24-24 24l-80 0c-13.3 0-24-10.7-24-24l0-48c0-13.3 10.7-24 24-24z" />
                        </svg>
                    </div>
                    <div class="grid gap-y-2">
                        <div class="flex items-center gap-x-2">
                            <span
                                class="fi-wi-stats-overview-stat-label text-sm font-bold text-gray-500 dark:text-gray-400">
                                {{ __('messages.dashboard.total_advance_payments') }}
                            </span>
                        </div>
                        <div class="text-right text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ $advancePaymentAmount }}
                        </div>
                    </div>
                </div>
            </a>
        @endif

        @if (getModuleAccess('Doctors'))
            <a href="{{ route('filament.hospitalAdmin.doctors.resources.doctors.index') }}">
                <div
                    class="fi-wi-stats-overview-stat relative items-center rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 flex justify-between">
                    <div
                        class="flex items-center justify-center w-11 h-11 rounded-lg p-1 ring-2 ring-inset ring-gray-200 hover:ring-gray-300 dark:ring-gray-500 hover:dark:ring-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"
                            class="h-7 w-10 dark:text-gray-200">
                            <path fill="currentColor"
                                d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-96 55.2C54 332.9 0 401.3 0 482.3C0 498.7 13.3 512 29.7 512l388.6 0c16.4 0 29.7-13.3 29.7-29.7c0-81-54-149.4-128-171.1l0 50.8c27.6 7.1 48 32.2 48 62l0 40c0 8.8-7.2 16-16 16l-16 0c-8.8 0-16-7.2-16-16s7.2-16 16-16l0-24c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 24c8.8 0 16 7.2 16 16s-7.2 16-16 16l-16 0c-8.8 0-16-7.2-16-16l0-40c0-29.8 20.4-54.9 48-62l0-57.1c-6-.6-12.1-.9-18.3-.9l-91.4 0c-6.2 0-12.3 .3-18.3 .9l0 65.4c23.1 6.9 40 28.3 40 53.7c0 30.9-25.1 56-56 56s-56-25.1-56-56c0-25.4 16.9-46.8 40-53.7l0-59.1zM144 448a24 24 0 1 0 0-48 24 24 0 1 0 0 48z" />
                        </svg>
                    </div>
                    <div class="grid gap-y-2">
                        <div class="flex items-center gap-x-2">
                            <span
                                class="fi-wi-stats-overview-stat-label text-sm font-bold text-gray-500 dark:text-gray-400">
                                {{ __('messages.dashboard.doctors') }}
                            </span>
                        </div>
                        <div class="text-right text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ $doctors }}
                        </div>
                    </div>
                </div>
            </a>
        @endif

        @if (getModuleAccess('Patients'))
            <a href="{{ route('filament.hospitalAdmin.patients.resources.patients.index') }}">
                <div
                    class="fi-wi-stats-overview-stat relative items-center rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 flex justify-between">
                    <div
                        class="flex items-center justify-center w-11 h-11 rounded-lg p-1 ring-2 ring-inset ring-gray-200 hover:ring-gray-300 dark:ring-gray-500 hover:dark:ring-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"
                            class="h-7 w-10 dark:text-gray-200">
                            <path fill="currentColor"
                                d="M240 80l102.7 0c-7.9-19.5-20.4-36.5-36.2-49.9L240 80zm37.7-68.2C261.3 4.2 243.2 0 224 0c-53.7 0-99.7 33.1-118.7 80l81.4 0 91-68.2zM224 256c70.7 0 128-57.3 128-128c0-5.4-.3-10.8-1-16L97 112c-.7 5.2-1 10.6-1 16c0 70.7 57.3 128 128 128zM124 312.4c-9.7 3.1-19.1 7-28 11.7L96 512l147.7 0L181.5 408.2 124 312.4zm33-7.2L204.3 384l67.7 0c44.2 0 80 35.8 80 80c0 18-6 34.6-16 48l82.3 0c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304l-91.4 0c-7.2 0-14.3 .4-21.3 1.3zM0 482.3C0 498.7 13.3 512 29.7 512L64 512l0-166.6C24.9 378.1 0 427.3 0 482.3zM320 464c0-26.5-21.5-48-48-48l-48.5 0 57.1 95.2C303 507.2 320 487.6 320 464z" />
                        </svg>
                    </div>
                    <div class="grid gap-y-2">
                        <div class="flex items-center gap-x-2">
                            <span
                                class="fi-wi-stats-overview-stat-label text-sm font-bold text-gray-500 dark:text-gray-400">
                                {{ __('messages.dashboard.patients') }}
                            </span>
                        </div>
                        <div class="text-right text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ $patients }}
                        </div>
                    </div>
                </div>
            </a>
        @endif

        @if (getModuleAccess('Nurses'))
            <a href="{{ route('filament.hospitalAdmin.users.resources.nurses.index') }}">
                <div
                    class="fi-wi-stats-overview-stat relative items-center rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 flex justify-between">
                    <div
                        class="flex items-center justify-center w-11 h-11 rounded-lg p-1 ring-2 ring-inset ring-gray-200 hover:ring-gray-300 dark:ring-gray-500 hover:dark:ring-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"
                            class="h-7 w-10 dark:text-gray-200">
                            <path fill="currentColor"
                                d="M96 128l0-57.8c0-13.3 8.3-25.3 20.8-30l96-36c7.2-2.7 15.2-2.7 22.5 0l96 36c12.5 4.7 20.8 16.6 20.8 30l0 57.8-.3 0c.2 2.6 .3 5.3 .3 8l0 40c0 70.7-57.3 128-128 128s-128-57.3-128-128l0-40c0-2.7 .1-5.4 .3-8l-.3 0zm48 48c0 44.2 35.8 80 80 80s80-35.8 80-80l0-16-160 0 0 16zM111.9 327.7c10.5-3.4 21.8 .4 29.4 8.5l71 75.5c6.3 6.7 17 6.7 23.3 0l71-75.5c7.6-8.1 18.9-11.9 29.4-8.5C401 348.6 448 409.4 448 481.3c0 17-13.8 30.7-30.7 30.7L30.7 512C13.8 512 0 498.2 0 481.3c0-71.9 47-132.7 111.9-153.6zM208 48l0 16-16 0c-4.4 0-8 3.6-8 8l0 16c0 4.4 3.6 8 8 8l16 0 0 16c0 4.4 3.6 8 8 8l16 0c4.4 0 8-3.6 8-8l0-16 16 0c4.4 0 8-3.6 8-8l0-16c0-4.4-3.6-8-8-8l-16 0 0-16c0-4.4-3.6-8-8-8l-16 0c-4.4 0-8 3.6-8 8z" />
                        </svg>
                    </div>
                    <div class="grid gap-y-2">
                        <div class="flex items-center gap-x-2">
                            <span
                                class="fi-wi-stats-overview-stat-label text-sm font-bold text-gray-500 dark:text-gray-400">
                                {{ __('messages.nurses') }}
                            </span>
                        </div>
                        <div class="text-right text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ $nurses }}
                        </div>
                    </div>
                </div>
            </a>
        @endif

        @if (getModuleAccess('Beds'))
            <a href="{{ route('filament.hospitalAdmin.bed-management.resources.beds.index') }}">
                <div
                    class="fi-wi-stats-overview-stat relative items-center rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 flex justify-between">
                    <div
                        class="flex items-center justify-center w-11 h-11 rounded-lg p-1 ring-2 ring-inset ring-gray-200 hover:ring-gray-300 dark:ring-gray-500 hover:dark:ring-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"
                            class="h-7 w-10 dark:text-gray-200">
                            <path fill="currentColor"
                                d="M32 32c17.7 0 32 14.3 32 32l0 256 224 0 0-160c0-17.7 14.3-32 32-32l224 0c53 0 96 43 96 96l0 224c0 17.7-14.3 32-32 32s-32-14.3-32-32l0-32-224 0-32 0L64 416l0 32c0 17.7-14.3 32-32 32s-32-14.3-32-32L0 64C0 46.3 14.3 32 32 32zm144 96a80 80 0 1 1 0 160 80 80 0 1 1 0-160z" />
                        </svg>
                    </div>
                    <div class="grid gap-y-2">
                        <div class="flex items-center gap-x-2">
                            <span
                                class="fi-wi-stats-overview-stat-label text-sm font-bold text-gray-500 dark:text-gray-400">
                                {{ __('messages.dashboard.available_beds') }}
                            </span>
                        </div>
                        <div class="text-right text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ $availableBeds }}
                        </div>
                    </div>
                </div>
            </a>
        @endif

    </div>
</x-filament-widgets::widget>
