<?php

namespace App\Filament\HospitalAdmin\Widgets;

use App\Filament\HospitalAdmin\Clusters\Pathology\Resources\PathologyTestResource;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\PathologyTest;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingLabOverview extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return auth()->user()->hasRole('Admin') && getModuleAccess('Pathology Tests');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('messages.dashboard.pending_pathology'))
            ->query(
                PathologyTest::query()
                    ->with(['patient.user', 'pathologycategory'])
                    ->where('tenant_id', getLoggedInUser()->tenant_id)
                    ->where('status', 0)
                    ->latest()
            )
            ->columns([
                TextColumn::make('test_name')
                    ->label(__('messages.pathology_test.test_name'))
                    ->color('primary')
                    ->url(fn ($record) => PathologyTestResource::getUrl('view', ['record' => $record->id])),
                TextColumn::make('patient.user.full_name')
                    ->label(__('messages.case.patient'))
                    ->default(__('messages.common.n/a'))
                    ->url(fn ($record) => $record->patient_id ? PatientResource::getUrl('view', ['record' => $record->patient_id]) : null),
                TextColumn::make('pathologycategory.name')
                    ->label(__('messages.pathology_test.category_name'))
                    ->default(__('messages.common.n/a')),
                TextColumn::make('created_at')
                    ->label(__('messages.common.created_on'))
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('jS M, Y') : __('messages.common.n/a'))
                    ->badge(),
            ])
            ->paginated([5])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }
}
