<?php

namespace App\Filament\Clusters\LandingCMS\Pages;

use App\Filament\Clusters\LandingCMS;
use App\Models\SectionOne;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;

class SetionOnePage extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    protected static string $view = 'filament.clusters.landing-c-m-s.pages.setion-one-page';

    protected static ?string $cluster = LandingCMS::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('messages.landing_cms.section_one');
    }

    protected $record;

    public function mount()
    {
        $this->record = SectionOne::with('media')->first();

        if (! $this->record) {
            $this->record = SectionOne::create();
        }

        $this->form->fill([
            'img_url' => $this->record->img_url,
            'text_main' => $this->record->text_main,
            'text_secondary' => $this->record->text_secondary,
        ]);
    }

    public function getTitle(): string
    {
        return '';
    }

    public function form(Form $form): Form
    {
        $form->model = SectionOne::first();

        return $form
            ->schema([
                Section::make()->columns(7)
                    ->schema([
                        Group::make([
                            SpatieMediaLibraryFileUpload::make('img_url')
                                ->label(__('messages.landing_cms.image').':')
                                ->required()
                                ->imageEditor()
                                ->avatar()
                                ->validationAttribute(__('Image'))
                                ->imageCropAspectRatio(null)
                                ->imageEditorAspectRatios([
                                    null,
                                ])
                                ->disk(config('app.media_disk'))
                                ->collection(SectionOne::SECTION_ONE_PATH),
                        ]),
                        Group::make([
                            TextInput::make('text_main')
                                ->label(__('messages.landing_cms.text_main').':')
                                ->required()
                                ->validationAttribute(__('messages.landing_cms.text_main'))
                                ->placeholder(__('messages.landing_cms.text_main')),
                            TextInput::make('text_secondary')
                                ->label(__('messages.landing_cms.text_secondary').':')
                                ->required()
                                ->validationAttribute(__('messages.landing_cms.text_secondary'))
                                ->placeholder(__('messages.landing_cms.text_secondary')),
                        ])->columnSpan(6)->columns(2),
                    ]),
            ])->statePath('data');
    }

    public function save()
    {
        $data = $this->form->getState();
        $sectionOne = SectionOne::first();

        $sectionOne->update([
            'img_url' => $sectionOne->getFirstMediaUrl(SectionOne::SECTION_ONE_PATH),
            'text_main' => $data['text_main'],
            'text_secondary' => $data['text_secondary'],
        ]);

        Notification::make()
            ->success()
            ->title(__('messages.landing_cms.section_one').' '.__('messages.common.updated_successfully'))
            ->send();
    }
}
