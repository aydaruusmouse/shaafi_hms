<?php

namespace App\Filament\HospitalAdmin\Clusters\BedManagement\Pages;

use App\Filament\HospitalAdmin\Clusters\BedManagement;
use App\Models\Bed;
use App\Models\BedType;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;

class BulkBedAddition extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.hospital-admin.clusters.bed-management.pages.bulk-bed-addition';

    protected static ?string $cluster = BedManagement::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public $beds = [];

    protected function getFormSchema(): array
    {
        return [
            Repeater::make('beds')
                ->label(__('messages.bed.new_bulk_bed'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('messages.bed_assign.bed'))
                        ->required()
                        ->validationAttribute(__('messages.bed_assign.bed'))
                        ->columnSpan(1),
                    Select::make('bed_type')
                        ->label(__('messages.bed_types'))
                        ->options(BedType::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('title', 'id'))
                        ->searchable()
                        ->required()
                        ->columnSpan(1)
                        ->validationMessages([
                            'required' => __('messages.fields.the').' '.__('messages.bed_types').' '.__('messages.fields.required'),
                        ]),
                    TextInput::make('charge')
                        ->label(__('messages.bed.charge'))
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->validationAttribute(__('messages.bed.charge'))
                        ->columnSpan(1),
                    Textarea::make('description')
                        ->label(__('messages.common.description'))
                        ->rows(1)
                        ->columnSpan(1),
                ])
                ->minItems(1)
                ->columns(4),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState()['beds'];
        foreach ($data as $bed) {
            Bed::create([
                'name' => $bed['name'],
                'bed_type' => $bed['bed_type'],
                'charge' => $bed['charge'],
                'description' => $bed['description'],
                'tenant_id' => getLoggedInUser()->tenant_id,
                'bed_id' => strtoupper(\Illuminate\Support\Str::random(8)),
                'is_available' => true,
            ]);
        }
        $this->redirect(route('filament.hospitalAdmin.bed-management.resources.beds.index'));
    }

    public function getFormActions(): array
    {
        return [
            Action::make('submit')
                ->label(__('messages.common.save'))
                ->action('save'),
        ];
    }

    protected function Actions(): array
    {
        return [
            Action::make('save')
                ->label(__('messages.save'))
                ->action('save')
                ->button()
                ->icon('heroicon-o-check'),
        ];
    }
}
