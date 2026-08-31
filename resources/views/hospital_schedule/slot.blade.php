@php
    /** @var \App\Models\HospitalSchedule $hospitalSchedule */
@endphp
<div class="flex items-center justify-between mt-10">
    <div class="flex items-center mb-3 add-slot">
        <div class="flex-initial">
            <x-filament::input.wrapper>
                <x-filament::input.select name="startTimes[{{ $day }}]" class="form-select">
                    @foreach ($slots as $key => $value)
                        <option value="{{ $key }}"
                            {{ isset($hospitalSchedule) && $hospitalSchedule->start_time == $key ? 'selected' : '' }}
                            {{ !isset($hospitalSchedule) && $key == array_key_first($slots) ? 'selected' : '' }}>
                            {{ $value }}
                        </option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
        <span class="px-3 text-lg">To</span>
        <div class="flex-initial">
            <x-filament::input.wrapper>
                <x-filament::input.select name="endTimes[{{ $day }}]" class="form-select">
                    @foreach ($slots as $key => $value)
                        <option value="{{ $key }}"
                            {{ isset($hospitalSchedule) && $hospitalSchedule->end_time == $key ? 'selected' : '' }}
                            {{ !isset($hospitalSchedule) && $key == array_key_last($slots) ? 'selected' : '' }}>
                            {{ $value }}
                        </option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
    </div>
</div>
