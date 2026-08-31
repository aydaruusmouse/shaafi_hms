<?php

namespace App\Filament\HospitalAdmin\Clusters\Users\Resources\PharmacistResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Users\Resources\PharmacistResource;
use App\Models\Pharmacist;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\Database\Query\Builder;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListPharmacists extends ListRecords
{
    protected static string $resource = PharmacistResource::class;

    protected $i = 1;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\CreateAction::make()
                    ->icon('')
                    ->label(__('messages.pharmacist.new_pharmacist')),
                ExportAction::make()->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->disabled(! Pharmacist::whereTenantId(getLoggedInUser()->tenant_id)->exists())
                    ->label(__('messages.common.export_to_excel'))->exports([
                        ExcelExport::make()
                            ->withFilename(__('messages.pharmacists').'-'.now()->format('Y-m-d').'.xlsx')
                            ->modifyQueryUsing(function (Builder $query) {
                                return $query->where('tenant_id', auth()->user()->tenant_id);
                            })->withColumns([
                                Column::make('id')->heading('No')->formatStateUsing(function () {
                                    return $this->i++;
                                }),
                                Column::make('user.full_name')->heading(heading: __('messages.common.name'))
                                    ->formatStateUsing(fn ($record) => $record->user->full_name ?? __('messages.common.n/a')),
                                Column::make('user.email')->heading(heading: __('messages.user.email')),
                                Column::make('user.phone')->heading(heading: __('messages.user.phone')),
                                Column::make('user.designation')->heading(heading: __('messages.user.designation')),
                                Column::make('user.qualification')->heading(heading: __('messages.user.qualification')),
                                Column::make('user.blood_group')->heading(heading: __('messages.user.blood_group')),
                                Column::make('user.gender')->heading(heading: __('messages.user.gender'))
                                    ->formatStateUsing(fn ($state) => $state == 0 ? __('messages.user.male') : __('messages.user.female')),
                                Column::make('user.dob')->heading(heading: __('messages.user.dob'))
                                    ->formatStateUsing(function ($record) {
                                        return $record->user->dob ? \Carbon\Carbon::parse($record->user->dob)->translatedFormat('jS M, Y') : __('messages.common.n/a');
                                    }),
                                Column::make('user.status')->heading(heading: __('messages.common.status'))
                                    ->formatStateUsing(fn ($state) => $state ? __('messages.common.active') : __('messages.common.deactive')),

                            ]),
                    ]),

            ])
                ->icon('fas-angle-down')
                ->iconPosition(IconPosition::After)
                ->label(__('messages.common.actions'))
                ->button(),
        ];
    }
}
