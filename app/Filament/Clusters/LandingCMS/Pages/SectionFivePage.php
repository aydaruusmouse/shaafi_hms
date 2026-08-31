<?php

namespace App\Filament\Clusters\LandingCMS\Pages;

use App\Filament\Clusters\LandingCMS;
use App\Models\SectionFive;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;

class SectionFivePage extends Page
{
    protected static string $view = 'filament.clusters.landing-c-m-s.pages.section-five-page';

    protected static ?string $cluster = LandingCMS::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 5;

    public array $data = [];

    protected static ?string $title = '';

    protected $record;

    public static function getNavigationLabel(): string
    {
        return __('messages.landing_cms.section_five');
    }

    public function mount(): void
    {
        $this->record = SectionFive::first();

        if (! $this->record) {
            $this->record = SectionFive::create();
        }

        $this->form->fill([
            'main_img_url' => $this->record->main_img_url,
            'card_img_url_one' => $this->record->card_img_url_one,
            'card_img_url_two' => $this->record->card_img_url_two,
            'card_img_url_three' => $this->record->card_img_url_three,
            'card_img_url_four' => $this->record->card_img_url_four,
            'card_one_number' => $this->record->card_one_number,
            'card_one_text' => $this->record->card_one_text,
            'card_two_number' => $this->record->card_two_number,
            'card_two_text' => $this->record->card_two_text,
            'card_three_number' => $this->record->card_three_number,
            'card_three_text' => $this->record->card_three_text,
            'card_four_number' => $this->record->card_four_number,
            'card_four_text' => $this->record->card_four_text,
        ]);
    }

    public function form(Form $form): Form
    {
        $form->model = SectionFive::first();

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('main_img_url')
                            ->label(__('messages.landing_cms.main_image').':')
                            ->validationAttribute(__('messages.landing_cms.main_image'))
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->image()
                            ->acceptedFileTypes([
                                'image/*',
                                'image/svg',
                            ])
                            ->disk(config('app.media_disk'))
                            ->collection(SectionFive::SECTION_FIVE_MAIN_IMAGE_PATH)
                            ->columnSpan(3)
                            ->required(),
                        SpatieMediaLibraryFileUpload::make('card_img_url_one')
                            ->label(__('messages.landing_cms.card_one_image').':')
                            ->validationAttribute(__('messages.landing_cms.card_one_image'))
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->disk(config('app.media_disk'))
                            ->image()
                            ->collection(SectionFive::SECTION_FIVE_CARD_ONE_PATH)
                            ->required(),
                        Group::make()
                            ->schema([
                                TextInput::make('card_one_number')
                                    ->label(__('messages.landing_cms.card_one_number').':')
                                    ->placeholder(__('messages.landing_cms.card_one_number'))
                                    ->validationAttribute(__('messages.landing_cms.card_one_number'))
                                    ->numeric()
                                    ->required(),
                                TextInput::make('card_one_text')
                                    ->label(__('messages.landing_cms.card_one_text').':')
                                    ->placeholder(__('messages.landing_cms.card_one_text'))
                                    ->validationAttribute(__('messages.landing_cms.card_one_text'))
                                    ->maxLength(15)
                                    ->required(),
                            ])->columnSpan(2),

                        SpatieMediaLibraryFileUpload::make('card_img_url_two')
                            ->label(__('messages.landing_cms.card_two_image').':')
                            ->validationAttribute(__('messages.landing_cms.card_two_image'))
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->image()
                            ->disk(config('app.media_disk'))
                            ->collection(SectionFive::SECTION_FIVE_CARD_TWO_PATH)
                            ->required(),
                        Group::make()
                            ->schema([
                                TextInput::make('card_two_number')
                                    ->label(__('messages.landing_cms.card_two_number').':')
                                    ->validationAttribute(__('messages.landing_cms.card_two_number'))
                                    ->placeholder(__('messages.landing_cms.card_two_number'))
                                    ->numeric()
                                    ->required(),
                                TextInput::make('card_two_text')
                                    ->label(__('messages.landing_cms.card_two_text').':')
                                    ->placeholder(__('messages.landing_cms.card_two_text'))
                                    ->validationAttribute(__('messages.landing_cms.card_two_text'))
                                    ->maxLength(15)
                                    ->required(),
                            ])->columnSpan(2),

                        SpatieMediaLibraryFileUpload::make('card_img_url_three')
                            ->label(__('messages.landing_cms.card_three_image').':')
                            ->validationAttribute(__('messages.landing_cms.card_three_image'))
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->image()
                            ->disk(config('app.media_disk'))
                            ->collection(SectionFive::SECTION_FIVE_CARD_THREE_PATH)
                            ->required(),
                        Group::make()
                            ->schema([
                                TextInput::make('card_three_number')
                                    ->label(__('messages.landing_cms.card_three_number').':')
                                    ->placeholder(__('messages.landing_cms.card_three_number'))
                                    ->validationAttribute(__('messages.landing_cms.card_three_number'))
                                    ->numeric()
                                    ->required(),
                                TextInput::make('card_three_text')
                                    ->label(__('messages.landing_cms.card_three_text').':')
                                    ->placeholder(__('messages.landing_cms.card_three_text'))
                                    ->validationAttribute(__('messages.landing_cms.card_three_text'))
                                    ->maxLength(15)
                                    ->required(),
                            ])->columnSpan(2),

                        SpatieMediaLibraryFileUpload::make('card_img_url_four')
                            ->label(__('messages.landing_cms.card_four_image').':')
                            ->validationAttribute(__('messages.landing_cms.card_four_image'))
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->disk(config('app.media_disk'))
                            ->image()
                            ->collection(SectionFive::SECTION_FIVE_CARD_FOUR_PATH)
                            ->required(),
                        Group::make()
                            ->schema([
                                TextInput::make('card_four_number')
                                    ->label(__('messages.landing_cms.card_four_number').':')
                                    ->placeholder(__('messages.landing_cms.card_four_number'))
                                    ->validationAttribute(__('messages.landing_cms.card_four_number'))
                                    ->numeric()
                                    ->required(),
                                TextInput::make('card_four_text')
                                    ->label(__('messages.landing_cms.card_five_text').':')
                                    ->placeholder(__('messages.landing_cms.card_five_text'))
                                    ->validationAttribute(__('messages.landing_cms.card_five_text'))
                                    ->maxLength(15)
                                    ->required(),
                            ])->columnSpan(2),
                    ])->columns(3),
            ])->statePath('data');
    }

    public function save()
    {
        $data = $this->form->getState();
        $sectionFive = SectionFive::first();

        $sectionFive->update([
            'main_img_url' => $sectionFive->getFirstMediaUrl(SectionFive::SECTION_FIVE_MAIN_IMAGE_PATH),
            'card_img_url_one' => $sectionFive->getFirstMediaUrl(SectionFive::SECTION_FIVE_CARD_ONE_PATH),
            'card_img_url_two' => $sectionFive->getFirstMediaUrl(SectionFive::SECTION_FIVE_CARD_TWO_PATH),
            'card_img_url_three' => $sectionFive->getFirstMediaUrl(SectionFive::SECTION_FIVE_CARD_THREE_PATH),
            'card_img_url_four' => $sectionFive->getFirstMediaUrl(SectionFive::SECTION_FIVE_CARD_FOUR_PATH),
            'card_one_number' => $data['card_one_number'],
            'card_one_text' => $data['card_one_text'],
            'card_two_number' => $data['card_two_number'],
            'card_two_text' => $data['card_two_text'],
            'card_three_number' => $data['card_three_number'],
            'card_three_text' => $data['card_three_text'],
            'card_four_number' => $data['card_four_number'],
            'card_four_text' => $data['card_four_text'],
        ]);

        Notification::make()
            ->success()
            ->title(__('messages.landing_cms.section_five').' '.__('messages.common.updated_successfully'))
            ->send();
    }
}
