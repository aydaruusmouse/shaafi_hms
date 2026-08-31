<?php

namespace App\Filament\Clusters\LandingCMS\Pages;

use App\Filament\Clusters\LandingCMS;
use App\Models\SectionThree;
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

class SectionThreePage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.clusters.landing-c-m-s.pages.section-three-page';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = LandingCMS::class;

    public array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('messages.landing_cms.section_three');
    }

    protected static ?string $title = '';

    protected $record;

    public function mount()
    {
        $this->record = SectionThree::with('media')->first();

        if (! $this->record) {
            $this->record = SectionThree::create();
        }

        $this->form->fill([
            'img_url' => $this->record->img_url,
            'text_main' => $this->record->text_main,
            'text_secondary' => $this->record->text_secondary,
            'text_one' => $this->record->text_one,
            'text_two' => $this->record->text_two,
            'text_three' => $this->record->text_three,
            'text_four' => $this->record->text_four,
            'text_five' => $this->record->text_five,
        ]);
    }

    public function form(Form $form): Form
    {
        $form->model = SectionThree::first();

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('img_url')
                            ->label(__('messages.landing_cms.card_one_image').':')
                            ->validationAttribute(__('messages.landing_cms.card_one_image'))
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->required()
                            ->disk(config('app.media_disk'))
                            ->collection(SectionThree::SECTION_THREE_PATH)
                            ->rules(['image', 'mimes:png,jpg,jpeg,svg']),
                        Group::make()
                            ->schema([
                                TextInput::make('text_main')
                                    ->label(__('messages.landing_cms.text_main').':')
                                    ->validationAttribute(__('messages.landing_cms.text_main'))
                                    ->placeholder(__('messages.landing_cms.text_main'))
                                    ->required(),
                                TextInput::make('text_secondary')
                                    ->label(__('messages.landing_cms.text_secondary').':')
                                    ->placeholder(__('messages.landing_cms.text_secondary'))
                                    ->required()
                                    ->required(),
                            ]),
                        TextInput::make('text_one')
                            ->label(__('messages.landing_cms.text_one').':')
                            ->placeholder(__('messages.landing_cms.text_one'))
                            ->validationAttribute(__('messages.landing_cms.text_one'))
                            ->required(),
                        TextInput::make('text_two')
                            ->label(__('messages.landing_cms.text_two').':')
                            ->placeholder(__('messages.landing_cms.text_two'))
                            ->validationAttribute(__('messages.landing_cms.text_two'))
                            ->required(),
                        TextInput::make('text_three')
                            ->label(__('messages.landing_cms.text_three').':')
                            ->placeholder(__('messages.landing_cms.text_three')),
                        TextInput::make('text_four')
                            ->label(__('messages.landing_cms.text_four').':')
                            ->placeholder(__('messages.landing_cms.text_four')),
                        TextInput::make('text_five')
                            ->label(__('messages.landing_cms.text_five').':')
                            ->placeholder(__('messages.landing_cms.text_five')),

                    ])->columns(2),

            ])->statePath('data');
    }

    public function save()
    {
        $data = $this->form->getState();
        $sectionOne = SectionThree::first();

        $sectionOne->update([
            'img_url' => $sectionOne->getFirstMediaUrl(SectionThree::SECTION_THREE_PATH),
            'text_main' => $data['text_main'],
            'text_secondary' => $data['text_secondary'],
            'text_one' => $data['text_one'],
            'text_two' => $data['text_two'],
            'text_three' => $data['text_three'],
            'text_four' => $data['text_four'],
            'text_five' => $data['text_five'],
        ]);

        Notification::make()
            ->success()
            ->title(__('messages.landing_cms.section_three').' '.__('messages.common.updated_successfully'))
            ->send();
    }
}
