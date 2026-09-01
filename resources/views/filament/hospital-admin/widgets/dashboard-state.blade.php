<x-filament-widgets::widget>
    <div class="space-y-8">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-950 dark:text-white">
                    {{ __('messages.dashboard.welcome_back') }}, {{ $greetingName }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $todayLabel }}</p>
            </div>
        </div>

        <div>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ __('messages.dashboard.today_at_a_glance') }}
            </h3>
            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
                @include('filament.hospital-admin.widgets.partials.dashboard-stat-card', [
                    'url' => $urls['appointments'] ?? null,
                    'label' => __('messages.dashboard.today_appointments'),
                    'value' => $todayAppointments,
                    'iconColor' => 'text-indigo-600 dark:text-indigo-400',
                    'iconBg' => 'bg-indigo-50 dark:bg-indigo-500/10',
                    'show' => getModuleAccess('Appointments'),
                    'icon' => 'calendar',
                ])
                @include('filament.hospital-admin.widgets.partials.dashboard-stat-card', [
                    'url' => $urls['opd'] ?? null,
                    'label' => __('messages.dashboard.today_opd'),
                    'value' => $todayOpd,
                    'iconColor' => 'text-sky-600 dark:text-sky-400',
                    'iconBg' => 'bg-sky-50 dark:bg-sky-500/10',
                    'show' => getModuleAccess('OPD Patients'),
                    'icon' => 'opd',
                ])
                @include('filament.hospital-admin.widgets.partials.dashboard-stat-card', [
                    'url' => $urls['ipd'] ?? null,
                    'label' => __('messages.dashboard.today_ipd'),
                    'value' => $todayIpd,
                    'iconColor' => 'text-orange-600 dark:text-orange-400',
                    'iconBg' => 'bg-orange-50 dark:bg-orange-500/10',
                    'show' => getModuleAccess('IPD Patients'),
                    'icon' => 'ipd',
                ])
                @include('filament.hospital-admin.widgets.partials.dashboard-stat-card', [
                    'url' => $urls['payments'] ?? null,
                    'label' => __('messages.dashboard.today_payments'),
                    'value' => $todayPaymentsFormatted,
                    'iconColor' => 'text-emerald-600 dark:text-emerald-400',
                    'iconBg' => 'bg-emerald-50 dark:bg-emerald-500/10',
                    'show' => getModuleAccess('Payments'),
                    'icon' => 'payments',
                ])
            </div>
        </div>

        <div>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ __('messages.dashboard.hospital_census') }}
            </h3>
            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
                @include('filament.hospital-admin.widgets.partials.dashboard-stat-card', [
                    'url' => $urls['patients'] ?? null,
                    'label' => __('messages.dashboard.patients'),
                    'value' => $patients,
                    'iconColor' => 'text-blue-600 dark:text-blue-400',
                    'iconBg' => 'bg-blue-50 dark:bg-blue-500/10',
                    'show' => getModuleAccess('Patients'),
                    'icon' => 'patients',
                ])
                @include('filament.hospital-admin.widgets.partials.dashboard-stat-card', [
                    'url' => $urls['doctors'] ?? null,
                    'label' => __('messages.dashboard.doctors'),
                    'value' => $doctors,
                    'iconColor' => 'text-violet-600 dark:text-violet-400',
                    'iconBg' => 'bg-violet-50 dark:bg-violet-500/10',
                    'show' => getModuleAccess('Doctors'),
                    'icon' => 'doctors',
                ])
                @include('filament.hospital-admin.widgets.partials.dashboard-stat-card', [
                    'url' => $urls['nurses'] ?? null,
                    'label' => __('messages.nurses'),
                    'value' => $nurses,
                    'iconColor' => 'text-pink-600 dark:text-pink-400',
                    'iconBg' => 'bg-pink-50 dark:bg-pink-500/10',
                    'show' => getModuleAccess('Nurses'),
                    'icon' => 'nurses',
                ])
                @include('filament.hospital-admin.widgets.partials.dashboard-stat-card', [
                    'url' => $urls['beds'] ?? null,
                    'label' => __('messages.dashboard.available_beds'),
                    'value' => $availableBeds.' / '.$totalBeds,
                    'iconColor' => 'text-emerald-600 dark:text-emerald-400',
                    'iconBg' => 'bg-emerald-50 dark:bg-emerald-500/10',
                    'show' => getModuleAccess('Beds'),
                    'icon' => 'beds',
                ])
                @include('filament.hospital-admin.widgets.partials.dashboard-stat-card', [
                    'url' => $urls['opd'] ?? null,
                    'label' => __('messages.dashboard.active_opd'),
                    'value' => $activeOpd,
                    'iconColor' => 'text-sky-600 dark:text-sky-400',
                    'iconBg' => 'bg-sky-50 dark:bg-sky-500/10',
                    'show' => getModuleAccess('OPD Patients'),
                    'icon' => 'opd',
                ])
                @include('filament.hospital-admin.widgets.partials.dashboard-stat-card', [
                    'url' => $urls['ipd'] ?? null,
                    'label' => __('messages.dashboard.active_ipd'),
                    'value' => $activeIpd,
                    'iconColor' => 'text-orange-600 dark:text-orange-400',
                    'iconBg' => 'bg-orange-50 dark:bg-orange-500/10',
                    'show' => getModuleAccess('IPD Patients'),
                    'icon' => 'ipd',
                ])
                @include('filament.hospital-admin.widgets.partials.dashboard-stat-card', [
                    'url' => $urls['beds'] ?? null,
                    'label' => __('messages.dashboard.bed_occupancy'),
                    'value' => $occupancyPercent.'%',
                    'hint' => $occupiedBeds.' '.__('messages.dashboard.occupied_beds'),
                    'iconColor' => 'text-red-600 dark:text-red-400',
                    'iconBg' => 'bg-red-50 dark:bg-red-500/10',
                    'show' => getModuleAccess('Beds'),
                    'icon' => 'beds',
                ])
                @include('filament.hospital-admin.widgets.partials.dashboard-stat-card', [
                    'url' => $urls['pathology'] ?? null,
                    'label' => __('messages.dashboard.pending_lab_tests'),
                    'value' => $pendingLabTests,
                    'iconColor' => 'text-amber-600 dark:text-amber-400',
                    'iconBg' => 'bg-amber-50 dark:bg-amber-500/10',
                    'show' => getModuleAccess('Pathology Tests'),
                    'icon' => 'lab',
                ])
            </div>
        </div>

        <div>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ __('messages.billing') }}
            </h3>
            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
                @include('filament.hospital-admin.widgets.partials.dashboard-stat-card', [
                    'url' => $urls['invoices'] ?? null,
                    'label' => __('messages.dashboard.total_invoices'),
                    'value' => $invoiceAmount,
                    'iconColor' => 'text-indigo-600 dark:text-indigo-400',
                    'iconBg' => 'bg-indigo-50 dark:bg-indigo-500/10',
                    'show' => getModuleAccess('Invoices'),
                    'icon' => 'invoices',
                ])
                @include('filament.hospital-admin.widgets.partials.dashboard-stat-card', [
                    'url' => $urls['bills'] ?? null,
                    'label' => __('messages.dashboard.total_bills'),
                    'value' => $billAmount,
                    'iconColor' => 'text-emerald-600 dark:text-emerald-400',
                    'iconBg' => 'bg-emerald-50 dark:bg-emerald-500/10',
                    'show' => getModuleAccess('Bills'),
                    'icon' => 'bills',
                ])
                @include('filament.hospital-admin.widgets.partials.dashboard-stat-card', [
                    'url' => $urls['payments'] ?? null,
                    'label' => __('messages.dashboard.total_payments'),
                    'value' => $paymentAmount,
                    'iconColor' => 'text-cyan-600 dark:text-cyan-400',
                    'iconBg' => 'bg-cyan-50 dark:bg-cyan-500/10',
                    'show' => getModuleAccess('Payments'),
                    'icon' => 'payments',
                ])
                @include('filament.hospital-admin.widgets.partials.dashboard-stat-card', [
                    'url' => $urls['advancePayments'] ?? null,
                    'label' => __('messages.dashboard.total_advance_payments'),
                    'value' => $advancePaymentAmount,
                    'iconColor' => 'text-teal-600 dark:text-teal-400',
                    'iconBg' => 'bg-teal-50 dark:bg-teal-500/10',
                    'show' => getModuleAccess('Advance Payments'),
                    'icon' => 'advance',
                ])
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
