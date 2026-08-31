<x-filament-widgets::widget>
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('filament.superAdmin.resources.hospitals.index') }}">
            <div
                class="fi-wi-stats-overview-stat relative items-center rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 flex justify-between">
                <div
                    class="flex items-center justify-center w-11 h-11 rounded-lg p-1 ring-2 ring-inset ring-gray-200 hover:ring-gray-300 dark:ring-gray-500 hover:dark:ring-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" class="h-7 w-10 dark:text-gray-200">
                        <path fill="currentColor"
                            d="M192 48c0-26.5 21.5-48 48-48L400 0c26.5 0 48 21.5 48 48l0 464-80 0 0-80c0-26.5-21.5-48-48-48s-48 21.5-48 48l0 80-80 0 0-464zM48 96l112 0 0 416L48 512c-26.5 0-48-21.5-48-48L0 320l80 0c8.8 0 16-7.2 16-16s-7.2-16-16-16L0 288l0-64 80 0c8.8 0 16-7.2 16-16s-7.2-16-16-16L0 192l0-48c0-26.5 21.5-48 48-48zm544 0c26.5 0 48 21.5 48 48l0 48-80 0c-8.8 0-16 7.2-16 16s7.2 16 16 16l80 0 0 64-80 0c-8.8 0-16 7.2-16 16s7.2 16 16 16l80 0 0 144c0 26.5-21.5 48-48 48l-112 0 0-416 112 0zM312 64c-8.8 0-16 7.2-16 16l0 24-24 0c-8.8 0-16 7.2-16 16l0 16c0 8.8 7.2 16 16 16l24 0 0 24c0 8.8 7.2 16 16 16l16 0c8.8 0 16-7.2 16-16l0-24 24 0c8.8 0 16-7.2 16-16l0-16c0-8.8-7.2-16-16-16l-24 0 0-24c0-8.8-7.2-16-16-16l-16 0z" />
                    </svg>
                </div>
                <div class="grid gap-y-2">
                    <div class="flex items-center gap-x-2">
                        <span
                            class="fi-wi-stats-overview-stat-label text-sm font-bold text-gray-500 dark:text-gray-400">
                            {{ __('messages.dashboard.total_hospitals') }}
                        </span>
                    </div>
                    <div class="text-right text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ $totalHospitals }}
                    </div>
                </div>
            </div>
        </a>

        <a href="{{ route('filament.superAdmin.billings.resources.transactions.index') }}">
            <div
                class="fi-wi-stats-overview-stat relative items-center rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 flex justify-between">
                <div
                    class="flex items-center justify-center w-11 h-11  rounded-lg p-1 ring-2 ring-inset ring-gray-200 hover:ring-gray-300 dark:ring-gray-500 hover:dark:ring-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" class="h-7 w-10 dark:text-gray-200">
                        <path fill="currentColor"
                            d="M160 0c17.7 0 32 14.3 32 32l0 35.7c1.6 .2 3.1 .4 4.7 .7c.4 .1 .7 .1 1.1 .2l48 8.8c17.4 3.2 28.9 19.9 25.7 37.2s-19.9 28.9-37.2 25.7l-47.5-8.7c-31.3-4.6-58.9-1.5-78.3 6.2s-27.2 18.3-29 28.1c-2 10.7-.5 16.7 1.2 20.4c1.8 3.9 5.5 8.3 12.8 13.2c16.3 10.7 41.3 17.7 73.7 26.3l2.9 .8c28.6 7.6 63.6 16.8 89.6 33.8c14.2 9.3 27.6 21.9 35.9 39.5c8.5 17.9 10.3 37.9 6.4 59.2c-6.9 38-33.1 63.4-65.6 76.7c-13.7 5.6-28.6 9.2-44.4 11l0 33.4c0 17.7-14.3 32-32 32s-32-14.3-32-32l0-34.9c-.4-.1-.9-.1-1.3-.2l-.2 0s0 0 0 0c-24.4-3.8-64.5-14.3-91.5-26.3c-16.1-7.2-23.4-26.1-16.2-42.2s26.1-23.4 42.2-16.2c20.9 9.3 55.3 18.5 75.2 21.6c31.9 4.7 58.2 2 76-5.3c16.9-6.9 24.6-16.9 26.8-28.9c1.9-10.6 .4-16.7-1.3-20.4c-1.9-4-5.6-8.4-13-13.3c-16.4-10.7-41.5-17.7-74-26.3l-2.8-.7s0 0 0 0C119.4 279.3 84.4 270 58.4 253c-14.2-9.3-27.5-22-35.8-39.6c-8.4-17.9-10.1-37.9-6.1-59.2C23.7 116 52.3 91.2 84.8 78.3c13.3-5.3 27.9-8.9 43.2-11L128 32c0-17.7 14.3-32 32-32z" />
                    </svg>
                </div>
                <div class="grid gap-y-2">
                    <div class="flex items-center gap-x-2">
                        <span
                            class="fi-wi-stats-overview-stat-label text-sm font-bold text-gray-500 dark:text-gray-400">
                            {{ __('messages.dashboard.total_revenue') }}
                        </span>
                    </div>
                    <div class="text-right text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ $totalRevenue }}
                    </div>
                </div>
            </div>
        </a>
        <a href="{{ route('filament.superAdmin.billings.resources.subscriptions.index') }}">
            <div
                class="fi-wi-stats-overview-stat relative items-center rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 flex justify-between">
                <div
                    class="flex items-center justify-center w-11 h-11 rounded-lg p-1 ring-2 ring-inset ring-gray-200 hover:ring-gray-300 dark:ring-gray-500 hover:dark:ring-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" class="h-7 w-10 dark:text-gray-200">
                        <path fill="currentColor"
                            d="M192 64C86 64 0 150 0 256S86 448 192 448l192 0c106 0 192-86 192-192s-86-192-192-192L192 64zm192 96a96 96 0 1 1 0 192 96 96 0 1 1 0-192z" />
                    </svg>
                </div>
                <div class="grid gap-y-2">
                    <div class="flex items-center gap-x-2">
                        <span
                            class="fi-wi-stats-overview-stat-label text-sm font-bold text-gray-500 dark:text-gray-400">
                            {{ __('messages.dashboard.total_active_hospital_plan') }}
                        </span>
                    </div>
                    <div class="text-right text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ $totalActivePlan }}
                    </div>
                </div>
            </div>
        </a>

        <a href="{{ route('filament.superAdmin.billings.resources.subscriptions.index') }} ">
            <div
                class="fi-wi-stats-overview-stat relative items-center rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 flex justify-between">
                <div
                    class="flex items-center justify-center w-11 h-11 rounded-lg p-1 ring-2 ring-inset ring-gray-200 hover:ring-gray-300 dark:ring-gray-500 hover:dark:ring-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" class="h-7 w-10 dark:text-gray-200">
                        <path fill="currentColor"
                            d="M384 128c70.7 0 128 57.3 128 128s-57.3 128-128 128l-192 0c-70.7 0-128-57.3-128-128s57.3-128 128-128l192 0zM576 256c0-106-86-192-192-192L192 64C86 64 0 150 0 256S86 448 192 448l192 0c106 0 192-86 192-192zM192 352a96 96 0 1 0 0-192 96 96 0 1 0 0 192z" />
                    </svg>
                </div>
                <div class="grid gap-y-2">
                    <div class="flex items-center gap-x-2">
                        <span fill="currentColor"
                            class="fi-wi-stats-overview-stat-label text-sm font-bold text-gray-500 dark:text-gray-400">
                            {{ __('messages.dashboard.total_expired_hospital_plan') }}
                        </span>
                    </div>
                    <div class="text-right text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ $totalExpiredPlan }}
                    </div>
                </div>
            </div>
        </a>
    </div>
</x-filament-widgets::widget>
