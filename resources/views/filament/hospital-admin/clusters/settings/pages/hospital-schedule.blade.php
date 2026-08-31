<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}
        {{-- {{ $this->table }} --}}
        <x-filament-panels::form.actions :actions="$this->getFormActions()" />
    </x-filament-panels::form>
</x-filament-panels::page>

{{-- <x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        <div class="overflow-x-auto">
            <table style="width: 100%; border-collapse: collapse; margin-top: 1rem; border: 1px solid #e5e7eb;">
                <tbody>
                    @foreach ($weekDay as $day => $shortWeekDay)
                        @php($isValid = $hospitalSchedules->where('day_of_week', $day)->count() != 0)
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 0.5rem;">
                                <div style="display: flex; align-items: center;">
                                    <x-filament::input.checkbox name="days[{{ $day }}]"
                                        id="chkShortWeekDay_{{ $shortWeekDay }}" :checked="$isValid"
                                        class="form-checkbox" />
                                </div>
                            </td>
                            <td style="padding: 0.5rem;">
                                <label for="chkShortWeekDay_{{ $shortWeekDay }}"
                                    style="font-size: 1.125rem; font-weight: bold;">
                                    {{ __('messages.hospital_schedule_weekday.' . $shortWeekDay) }}
                                </label>
                            </td>
                            <td style="padding: 0.5rem;">
                                <div class="session-times">
                                    @if ($hospitalSchedule = $hospitalSchedules->where('day_of_week', $day)->first())
                                        @include('hospital_schedule.slot', [
                                            'slot' => $slots,
                                            'day' => $day,
                                            'hospitalSchedule' => $hospitalSchedule,
                                        ])
                                    @else
                                        @include('hospital_schedule.slot', [
                                            'slot' => $slots,
                                            'day' => $day,
                                        ])
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
            <x-filament::button type="submit" id="btnHospitalScheduleSave"
                data-loading-text="<span class='spinner-border spinner-border-sm'></span> Processing..."
                style="background-color: #6571ff; color: white; border: none; padding: 0.5rem 1rem; cursor: pointer;">
                {{ __('messages.common.save') }}
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page> --}}
