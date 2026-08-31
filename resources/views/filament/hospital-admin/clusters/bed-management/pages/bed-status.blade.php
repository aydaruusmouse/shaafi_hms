<x-filament-panels::page>
    <style>
        .bed-status {
            color: #0ac074;
        }

        .procedures-status {
            color: #f62947;
        }

        @media (min-width: 640px) {
            .sm\:grid-cols-3 {
                grid-template-columns: repeat(6, minmax(0, 1fr)) !important;
            }
        }

        .wi-9 {
            width: 3.25rem !important;
        }

        .hi-9 {
            height: 3.25rem !important;
        }

        .tooltip {
            position: absolute;
            bottom: 2.5rem;
            background-color: rgb(95, 95, 95);
            color: white;
            font-size: 0.75rem;
            border-radius: 0.375rem;
            padding: 0.25rem 0.5rem;
            box-shadow: 0 2px 8px rgb(181, 181, 181);
            display: none;
            z-index: 50;
            max-width: 200px;
            text-align: center;
            word-wrap: break-word;
        }

        .group:hover .tooltip {
            display: block;
        }
    </style>

    <div class="flex flex-col sm:flex-row items-center card">
        <div class="flex items-center gap-3">
            <x-fas-procedures class="h-6 w-6 procedures-status" />
            <label class="text-md font-medium text-gray-950 dark:text-white">
                {{ __('messages.bed_status.assigned_beds') }}
            </label>
        </div>
        <div class="p-4"></div>
        <div class="flex items-center gap-3">
            <x-fas-bed class="h-6 w-6 bed-status" />
            <label class="text-md font-medium text-gray-950 dark:text-white">
                {{ __('messages.bed_status.available_beds') }}
            </label>
        </div>
    </div>
    <x-filament::fieldset>
        <div>
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="poverview" role="tabpanel">
                    <div class="card mb-5 mb-xl-10">
                        <div>
                            <div class="card-body">
                                @foreach ($bedTypes as $bedType)
                                    <div class="mb-5 lg:mb-10">
                                        <h2 class="text-md font-medium text-gray-950 dark:text-white  mb-4">
                                            {{ $bedType->title }}</h2>
                                        <div class="border px-4 lg:px-10 py-2 lg:py-6">
                                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-1">
                                                @if (count($bedType->beds) > 0)
                                                    @foreach ($bedType->beds as $bed)
                                                        <div class="text-center py-4">
                                                            @if (!$bed->bedAssigns->isEmpty() && !$bed->is_available)
                                                                @foreach ($bed->bedAssigns->where('status', 1) as $bedAssign)
                                                                    <div
                                                                        class="relative group flex flex-col items-center">
                                                                        <a href="#" class="text-danger">
                                                                            <!-- Icon for assigned bed -->
                                                                            <x-fas-procedures
                                                                                class="hi-9 wi-9 procedures-status" />
                                                                        </a>
                                                                        <div class="tooltip">
                                                                            <label>{{ __('messages.bed_status.bed_name') }}:</label>
                                                                            {{ !empty($bed->name) ? $bed->name : __('messages.common.n/a') }}
                                                                            <br>
                                                                            <label>{{ __('messages.case.patient') }}:</label>
                                                                            {{ !empty($bedAssign->patient->patientUser->full_name) ? $bedAssign->patient->patientUser->full_name : __('messages.common.n/a') }}
                                                                            <br>
                                                                            <label>{{ __('messages.bed_status.phone') }}:</label>
                                                                            {{ !empty($bedAssign->patient->patientUser->phone) ? $bedAssign->patient->patientUser->region_code . $bedAssign->patient->patientUser->phone : __('messages.common.n/a') }}
                                                                            <br>
                                                                            <label>{{ __('messages.bed_status.admission_date') }}:</label>
                                                                            {{ date('jS M, Y h:i:s A', strtotime($bedAssign->assign_date)) }}
                                                                            <br>
                                                                            <label>{{ __('messages.bed_status.gender') }}:</label>
                                                                            {{ $bedAssign->patient->patientUser->gender === 0 ? 'Male' : 'Female' }}
                                                                        </div>
                                                                    </div>

                                                                    <div class="pt-1">
                                                                        <label
                                                                            class="text-sm font-medium text-gray-950 dark:text-white">
                                                                            {{ $bedAssign->patient->patientUser->full_name }}
                                                                        </label>
                                                                    </div>
                                                                @endforeach
                                                            @else
                                                                @php
                                                                    $isTrue = true;
                                                                @endphp
                                                                @foreach ($patientAdmissions as $patientAdmission)
                                                                    @if (
                                                                        $patientAdmission->bed->id == $bed->id &&
                                                                            !$patientAdmission->bed->is_available &&
                                                                            $patientAdmission->discharge_date == null)
                                                                        @php
                                                                            $isTrue = false;
                                                                        @endphp
                                                                        <div class="text-center">
                                                                            <div
                                                                                class="relative group flex flex-col items-center">
                                                                                <a href="javascript:void(0)"
                                                                                    class="text-danger">
                                                                                    <!-- Icon for assigned bed -->
                                                                                    <x-fas-procedures
                                                                                        class="hi-9 wi-9 procedures-status" />
                                                                                </a>
                                                                                <div class="tooltip">
                                                                                    <label
                                                                                        class="fs-6 text-gray-800">{{ __('messages.bed_status.bed_name') }}
                                                                                        :</label>
                                                                                    {{ $bed->name ?? __('messages.common.n/a') }}
                                                                                    <br>
                                                                                    <label>{{ __('messages.case.patient') }}:</label>
                                                                                    {{ $patientAdmission->patient->patientUser->full_name ?? __('messages.common.n/a') }}
                                                                                    <br>
                                                                                    <label
                                                                                        class="fs-6 text-gray-800">{{ __('messages.bed_status.phone') }}
                                                                                        :</label>
                                                                                    {{ !empty($patientAdmission->patient->patientUser->phone) ? $patientAdmission->patient->patientUser->phone : __('messages.common.n/a') }}
                                                                                    <br>
                                                                                    <label
                                                                                        class="fs-6 text-gray-800">{{ __('messages.bed_status.admission_date') }}
                                                                                        :</label>
                                                                                    {{ date('jS M, Y h:i:s A', strtotime($patientAdmission->admission_date)) }}
                                                                                    <br>
                                                                                    <label
                                                                                        class="fs-6 text-gray-800">{{ __('messages.bed_status.gender') }}
                                                                                        :</label>
                                                                                    {{ $patientAdmission->patient->patientUser->gender === 0 ? 'Male' : 'Female' }}
                                                                                </div>

                                                                            </div>

                                                                            <div class="pt-1">
                                                                                <label
                                                                                    class="text-sm font-medium text-gray-950 dark:text-white">
                                                                                    {{ $patientAdmission->patient->patientUser->full_name }}
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                @endforeach
                                                                @if ($isTrue == true)
                                                                    <a href="{{ route('filament.hospitalAdmin.bed-management.resources.bed-assigns.create', ['bed_id' => $bed->id]) }}"
                                                                        class="text-sm font-medium text-gray-950 dark:text-white">
                                                                        <!-- Icon for available bed -->
                                                                        <div class="text-center">
                                                                            <div class="flex flex-col items-center">
                                                                                <x-fas-bed
                                                                                    class="hi-9 wi-9 bed-status" />
                                                                                <span
                                                                                    class="text-sm font-medium text-gray-950 dark:text-white mt-1">{{ $bed->name }}</span>
                                                                                <!-- Added mt-1 for spacing -->
                                                                            </div>
                                                                        </div>
                                                                    </a>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <div class="text-center">
                                                        <span class="text-md font-medium text-gray-950 dark:text-white">
                                                            {{ __('messages.common.no') . ' ' . __('messages.bed_assign.bed') . ' ' . __('messages.bed.available') }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::fieldset>
</x-filament-panels::page>
