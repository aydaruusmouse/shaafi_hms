<?php

namespace App\Livewire;

use App\Models\Notification;
use Filament\Notifications\Notification as FilamentNotification;
use Livewire\Component;

class NotificationRead extends Component
{
    public function markAsRead($notificationId)
    {
        $notification = Notification::find($notificationId);

        if ($notification) {
            $notification->update(['read_at' => now()]);

            FilamentNotification::make()
                ->success()
                ->title(__('messages.flash.notification_read'))
                ->send();
        }
    }

    public function markAllAsRead()
    {

        $notification = Notification::where('user_id', auth()->id())
            ->where('notification_for', Notification::NOTIFICATION_FOR[auth()->user()->roles->pluck('name')->first()] ?? null)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        FilamentNotification::make()
            ->success()
            ->title(__('messages.flash.notification_read'))
            ->send();
    }

    public function render()
    {
        $role = auth()->user()->roles->pluck('name')->first();

        $notifications = Notification::where('user_id', auth()->user()->id)
            ->whereNotificationFor(Notification::NOTIFICATION_FOR[$role])
            ->whereTenantId(auth()->user()->tenant_id)
            ->where('read_at', null)
            ->orderBy('created_at', 'desc')
            ->toBase()
            ->get() ?? collect();

        return view('livewire.notification-read', ['notifications' => $notifications]);
    }
}
