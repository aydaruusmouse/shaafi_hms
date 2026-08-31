<?php

namespace App\Filament\Resources\HospitalResource\Pages;

use App\Filament\Resources\HospitalResource;
use App\Livewire\HospitalBillingTable;
use App\Livewire\HospitalTransactionTable;
use App\Livewire\HospitalUserTable;
use App\Models\User;
use Filament\Actions;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Livewire;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;

class ViewHospital extends ViewRecord
{
    protected static string $resource = HospitalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('back')
                ->url(url()->previous())
                ->outlined()
                ->label(__('messages.common.back')),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $userId = request()->route('record');
        $tenantId = User::where('id', $userId)->value('tenant_id');

        return $infolist
            ->schema([
                Section::make()->schema([
                    SpatieMediaLibraryImageEntry::make('profile')->collection(User::COLLECTION_PROFILE_PICTURES)->label('')->columnSpan(2)->width(100)->height(100)->defaultImageUrl(function ($record) {
                        if (! $record->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->full_name);
                        }
                    })->circular()->columnSpan(1),
                    Group::make([
                        TextEntry::make('status')
                            ->label('')
                            ->formatStateUsing(fn ($state) => $state ? __('messages.common.active') : __('messages.common.deactive'))
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'danger')
                            ->columnSpan(1),
                        TextEntry::make('full_name')
                            ->label('')
                            ->extraAttributes(['class' => 'font-black'])
                            ->size(TextEntry\TextEntrySize::Large)
                            ->weight(FontWeight::Medium)
                            ->color('primary')
                            ->columnSpan(1),
                        TextEntry::make('email')
                            ->label('')
                            ->icon('fas-envelope')
                            // ->extraAttributes(['style' => 'margin: -20px;'])
                            ->formatStateUsing(fn ($state) => "<a href='mailto:{$state}'>{$state}</a>")
                            ->html()
                            ->columnSpan(1),
                    ])->extraAttributes(['class' => 'display-block']),
                    Group::make([]),
                    Group::make([]),
                    TextEntry::make('id')
                        ->label('')
                        ->formatStateUsing(fn ($record) => "<span class='text-2xl font-bold text-primary-600'>".
                            \App\Models\PatientCase::where('tenant_id', $tenantId)->count().'</span> <br> '.__('messages.patient.total_cases'))
                        ->html()->extraAttributes(['class' => 'border p-6 rounded-xl'])
                        ->columnSpan(2),
                    TextEntry::make('id')
                        ->label('')
                        ->formatStateUsing(fn ($record) => "<span class='text-2xl font-bold text-primary-600'>".
                            \App\Models\Patient::where('tenant_id', $tenantId)->count().'</span> <br> '.__('messages.patients'))
                        ->html()->extraAttributes(['class' => 'border p-6 rounded-xl'])->columnSpan(2),
                    TextEntry::make('id')
                        ->label('')
                        ->formatStateUsing(fn ($record) => "<span class='text-2xl font-bold text-primary-600'>".
                            \App\Models\Appointment::where('tenant_id', $tenantId)->count().'</span> <br> '.
                            '<span>'.__('messages.patient.total_appointments').'</span>')
                        ->html()->extraAttributes(['class' => 'border p-6 rounded-xl'])
                        ->columnSpan(2),
                ])->columns(10),
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make(__('messages.overview'))
                            ->schema([
                                TextEntry::make('hospital_name')
                                    ->default(__('messages.common.n/a'))
                                    ->label(__('messages.hospitals_list.hospital_name').':'),
                                TextEntry::make('username')
                                    ->color(fn ($record) => $record->status ? 'primary' : 'secondary')
                                    ->html()
                                    ->formatStateUsing(fn ($state, $record) => $record->status ? '<a href="'.route('front', $state).'" class="hoverLink" target="_blank">'.$state.'</a>' : $state)
                                    ->default(__('messages.common.n/a'))
                                    ->label(__('messages.user.hospital_slug').':'),
                                TextEntry::make('email')
                                    ->default(__('messages.common.n/a'))
                                    ->label(__('messages.user.email').':'),
                                TextEntry::make('roles')
                                    ->default(__('messages.common.n/a'))
                                    ->formatStateUsing(fn ($state) => json_decode($state, true)['name'] ?? __('messages.common.n/a'))
                                    ->label(__('messages.employee_payroll.role').':'),
                                TextEntry::make('phone')
                                    ->label(__('messages.user.phone').':')
                                    ->getStateUsing(function ($record) {
                                        if ($record->region_code && $record->phone) {
                                            return $record->region_code.$record->phone;
                                        } elseif ($record->phone) {
                                            return $record->phone;
                                        } else {
                                            return __('messages.common.n/a');
                                        }
                                    }),
                                TextEntry::make('created_at')
                                    ->label(__('messages.common.created_at').':')
                                    ->getStateUsing(fn ($record) => $record->created_at->diffForHumans()),
                                TextEntry::make('updated_at')
                                    ->label(__('messages.common.last_updated').':')
                                    ->getStateUsing(fn ($record) => $record->updated_at->diffForHumans()),
                            ])->columns(2),
                        Tabs\Tab::make(__('messages.users'))
                            ->schema(
                                function ($record) {
                                    return [
                                        Livewire::make(HospitalUserTable::class, ['record' => $record]),
                                    ];
                                }
                            ),
                        Tabs\Tab::make(__('messages.billings'))
                            ->schema(
                                function ($record) {
                                    return [
                                        Livewire::make(HospitalBillingTable::class, ['record' => $record]),
                                    ];
                                }
                            ),
                        Tabs\Tab::make(__('messages.subscription_plans.transactions'))
                            ->schema(
                                function ($record) {
                                    return [
                                        Livewire::make(HospitalTransactionTable::class, ['record' => $record]),
                                    ];
                                }
                            ),
                    ])->columnSpanFull(),
            ]);
    }
}
