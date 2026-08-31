@php
    $notificationsIcon = count($notifications) > 0 ? 'heroicon-s-bell' : 'heroicon-s-bell-slash';
@endphp
<x-filament::modal class="modal-color" width="md" slide-over icon="{{ $notificationsIcon }}" alignment="center"
    sticky-footer sticky-header badge="{{ count($notifications) }}">
    @if ($notifications->isEmpty())
        <x-slot name="heading">
            {{ __('messages.common.no') . ' ' . __('messages.notification.notifications') }}
        </x-slot>
        <x-slot name="description">
            {{ __('messages.notification.you_don`t_have_any_new_notification') }}
        </x-slot>
    @else
        <x-slot name="heading">
            {{ __('messages.notification.notifications') }}
        </x-slot>
    @endif
    <x-slot name="trigger">
        <x-filament::button icon="heroicon-s-bell" size="" outlined>
            @if (count($notifications) != 0)
                <x-slot name="badge">
                    {{ count($notifications) }}
                </x-slot>
            @endif
        </x-filament::button>
    </x-slot>

    @foreach ($notifications as $notification)
        <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl"
            wire:click="markAsRead({{ $notification->id }})" style="cursor: pointer;">
            <div class="flex-1">
                <div class="flex items-center space-x-2">
                    <x-dynamic-component :component="getNotificationIcon($notification->type)" class="w-4 h-4 text-primary-500 dark:text-primary-400 " />

                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                        {{ $notification->title }}
                    </h3>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 block">
                    {{ Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                </span>
            </div>
        </div>
    @endforeach

    <x-slot name="footer">
        <div class="custom-notify">
            <x-filament::link wire:click="markAllAsRead" :disabled="count($notifications) == 0">
                {{ __('messages.notification.mark_all_as_read') }}
            </x-filament::link>
        </div>
    </x-slot>
</x-filament::modal>
