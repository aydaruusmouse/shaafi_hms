<?php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Filament\Resources\AdminResource;
use App\Models\User;
use Filament\Actions;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewAdmin extends ViewRecord
{
    protected static string $resource = AdminResource::class;

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
        return $infolist
            ->schema([
                InfolistSection::make()->schema([
                    SpatieMediaLibraryImageEntry::make('user.profile')
                        ->collection(User::COLLECTION_PROFILE_PICTURES)
                        ->label('')
                        ->columnSpan(2)
                        ->width(100)
                        ->height(100)
                        ->defaultImageUrl(function ($record) {
                            if (! $record->user || ! $record->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                                return getUserImageInitial($record->id, $record->full_name);
                            }
                        })
                        ->circular()
                        ->columnSpan(2),

                    InfolistGroup::make([
                        TextEntry::make('status')
                            ->label('')
                            ->formatStateUsing(fn ($state) => $state ? __('messages.common.active') : __('messages.common.deactive'))
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'danger')
                            ->columnSpan(2),
                        TextEntry::make('full_name')
                            ->label('')
                            ->extraAttributes(['class' => 'font-black'])
                            ->color('primary')
                            ->columnSpan(2),
                        TextEntry::make('email')
                            ->label('')
                            ->icon('fas-envelope')
                            ->formatStateUsing(fn ($state) => "<a href='mailto:{$state}'>{$state}</a>")
                            ->html()
                            ->columnSpan(2),
                    ])->extraAttributes(['class' => 'display-block']),
                ])->columns(10),
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make(__('messages.overview'))
                            ->schema([
                                TextEntry::make('user.phone')
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
                                    ->getStateUsing(fn ($record) => $record->updated_at->diffForHumans()),
                                TextEntry::make('updated_at')
                                    ->label(__('messages.common.last_updated').':')
                                    ->getStateUsing(fn ($record) => $record->updated_at->diffForHumans()),
                            ])->columns(2),
                    ])->columnSpanFull(),
            ]);
    }
}
