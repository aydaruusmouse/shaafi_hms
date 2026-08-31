<?php

namespace App\Filament\Clusters\LandingCMS\Pages;

use App\Filament\Clusters\LandingCMS;
use App\Models\SectionFour;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;

class SectionFourPage extends Page
{
    protected static string $view = 'filament.clusters.landing-c-m-s.pages.section-four-page';

    protected static ?string $cluster = LandingCMS::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    public array $data = [];

    protected static ?string $title = '';

    public static function getNavigationLabel(): string
    {
        return __('messages.landing_cms.section_four');
    }

    protected $record;

    public function mount()
    {
        $this->record = SectionFour::with('media')->first();

        if (! $this->record) {
            $this->record = SectionFour::create();
        }

        $this->form->fill([
            'text_main' => $this->record->text_main,
            'text_secondary' => $this->record->text_secondary,
            'img_url_one' => $this->record->img_url_one,
            'card_text_one' => $this->record->card_text_one,
            'card_text_one_secondary' => $this->record->card_text_one_secondary,
            'img_url_two' => $this->record->img_url_two,
            'card_text_two' => $this->record->card_text_two,
            'card_text_two_secondary' => $this->record->card_text_two_secondary,
            'img_url_three' => $this->record->img_url_three,
            'card_text_three' => $this->record->card_text_three,
            'card_text_three_secondary' => $this->record->card_text_three_secondary,
            'img_url_four' => $this->record->img_url_four,
            'card_text_four' => $this->record->card_text_four,
            'card_text_four_secondary' => $this->record->card_text_four_secondary,
            'img_url_five' => $this->record->img_url_five,
            'card_text_five' => $this->record->card_text_five,
            'card_text_five_secondary' => $this->record->card_text_five_secondary,
            'img_url_six' => $this->record->img_url_six,
            'card_text_six' => $this->record->card_text_six,
            'card_text_six_secondary' => $this->record->card_text_six_secondary,
        ]);
    }

    public function form(Form $form): Form
    {
        $form->model = SectionFour::first();

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('text_main')
                            ->label(__('messages.landing_cms.text_main').':')
                            ->validationAttribute(__('messages.landing_cms.text_main'))
                            ->placeholder(__('messages.landing_cms.text_main'))
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('text_secondary')
                            ->label(__('messages.landing_cms.text_secondary').':')
                            ->placeholder(__('messages.landing_cms.text_secondary'))
                            ->validationAttribute(__('messages.landing_cms.text_secondary'))
                            ->required()
                            ->columnSpanFull(),
                        SpatieMediaLibraryFileUpload::make('img_url_one')
                            ->label(__('messages.landing_cms.card_one_image').':')
                            ->validationAttribute(__('messages.landing_cms.card_one_image'))
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->required()
                            ->disk(config('app.media_disk'))
                            ->image()
                            ->collection(SectionFour::SECTION_FOUR_CARD_ONE_PATH),
                        Group::make()
                            ->schema([
                                TextInput::make('card_text_one')
                                    ->label(__('messages.landing_cms.card_one_text').':')
                                    ->validationAttribute(__('messages.landing_cms.card_one_text'))
                                    ->placeholder(__('messages.landing_cms.card_one_text'))
                                    ->required(),
                                TextInput::make('card_text_one_secondary')
                                    ->label(__('messages.landing_cms.card_one_text_secondary').':')
                                    ->validationAttribute(__('messages.landing_cms.card_one_text_secondary'))
                                    ->placeholder(__('messages.landing_cms.card_one_text_secondary'))
                                    ->required(),
                            ])->columnSpan(2),
                        SpatieMediaLibraryFileUpload::make('img_url_two')
                            ->label(__('messages.landing_cms.card_two_image').':')
                            ->validationAttribute(__('messages.landing_cms.card_two_image'))
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->required()
                            ->image()
                            ->disk(config('app.media_disk'))
                            ->collection(SectionFour::SECTION_FOUR_CARD_TWO_PATH),
                        Group::make()
                            ->schema([
                                TextInput::make('card_text_two')
                                    ->label(__('messages.landing_cms.card_two_text').':')
                                    ->validationAttribute(__('messages.landing_cms.card_two_text'))
                                    ->placeholder(__('messages.landing_cms.card_two_text'))
                                    ->required(),
                                TextInput::make('card_text_two_secondary')
                                    ->label(__('messages.landing_cms.card_two_text_secondary').':')
                                    ->validationAttribute(__('messages.landing_cms.card_two_text_secondary'))
                                    ->placeholder(__('messages.landing_cms.card_two_text_secondary'))
                                    ->required(),
                            ])->columnSpan(2),
                        SpatieMediaLibraryFileUpload::make('img_url_three')
                            ->label(__('messages.landing_cms.card_third_image').':')
                            ->validationAttribute(__('messages.landing_cms.card_third_image'))
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->required()
                            ->image()
                            ->disk(config('app.media_disk'))
                            ->collection(SectionFour::SECTION_FOUR_CARD_THREE_PATH),
                        Group::make()
                            ->schema([
                                TextInput::make('card_text_three')
                                    ->label(__('messages.landing_cms.card_third_text').':')
                                    ->validationAttribute(__('messages.landing_cms.card_third_text'))
                                    ->placeholder(__('messages.landing_cms.card_third_text'))
                                    ->required(),
                                TextInput::make('card_text_three_secondary')
                                    ->label(__('messages.landing_cms.card_third_text_secondary').':')
                                    ->validationAttribute(__('messages.landing_cms.card_third_text_secondary'))
                                    ->placeholder(__('messages.landing_cms.card_third_text_secondary'))
                                    ->required(),
                            ])->columnSpan(2),
                        SpatieMediaLibraryFileUpload::make('img_url_four')
                            ->label(__('messages.landing_cms.card_four_image').':')
                            ->validationAttribute(__('messages.landing_cms.card_four_image'))
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->required()
                            ->disk(config('app.media_disk'))
                            ->image()
                            ->collection(SectionFour::SECTION_FOUR_CARD_FOUR_PATH),
                        Group::make()
                            ->schema([
                                TextInput::make('card_text_four')
                                    ->label(__('messages.landing_cms.card_four_text').':')
                                    ->placeholder(__('messages.landing_cms.card_four_text'))
                                    ->validationAttribute(__('messages.landing_cms.card_four_text'))
                                    ->required(),
                                TextInput::make('card_text_four_secondary')
                                    ->label(__('messages.landing_cms.card_four_text_secondary').':')
                                    ->validationAttribute(__('messages.landing_cms.card_four_text_secondary'))
                                    ->placeholder(__('messages.landing_cms.card_four_text_secondary'))
                                    ->required(),
                            ])->columnSpan(2),
                        SpatieMediaLibraryFileUpload::make('img_url_five')
                            ->label(__('messages.landing_cms.card_five_image').':')
                            ->validationAttribute(__('messages.landing_cms.card_five_image'))
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->required()
                            ->image()
                            ->disk(config('app.media_disk'))
                            ->collection(SectionFour::SECTION_FOUR_CARD_FIVE_PATH),
                        Group::make()
                            ->schema([
                                TextInput::make('card_text_five')
                                    ->label(__('messages.landing_cms.card_five_text').':')
                                    ->validationAttribute(__('messages.landing_cms.card_five_text'))
                                    ->placeholder(__('messages.landing_cms.card_five_text'))
                                    ->required(),
                                TextInput::make('card_text_five_secondary')
                                    ->label(__('messages.landing_cms.card_five_text_secondary').':')
                                    ->placeholder(__('messages.landing_cms.card_five_text_secondary'))
                                    ->validationAttribute(__('messages.landing_cms.card_five_text_secondary'))
                                    ->required(),
                            ])->columnSpan(2),
                        SpatieMediaLibraryFileUpload::make('img_url_six')
                            ->label(__('messages.landing_cms.card_six_image').':')
                            ->validationAttribute(__('messages.landing_cms.card_six_image'))
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->required()
                            ->disk(config('app.media_disk'))
                            ->image()
                            ->collection(SectionFour::SECTION_FOUR_CARD_SIX_PATH),
                        Group::make()
                            ->schema([
                                TextInput::make('card_text_six')
                                    ->label(__('messages.landing_cms.card_six_text').':')
                                    ->placeholder(__('messages.landing_cms.card_six_text'))
                                    ->validationAttribute(__('messages.landing_cms.card_six_text'))
                                    ->required(),
                                TextInput::make('card_text_six_secondary')
                                    ->label(__('messages.landing_cms.card_six_text_secondary').':')
                                    ->placeholder(__('messages.landing_cms.card_six_text_secondary'))
                                    ->validationAttribute(__('messages.landing_cms.card_six_text_secondary'))
                                    ->required(),
                            ])->columnSpan(2),
                    ])->columns(3),

            ])->statePath('data');
    }

    public function save()
    {
        $data = $this->form->getState();
        $sectionOne = SectionFour::first();

        $sectionOne->update([
            'text_main' => $data['text_main'],
            'text_secondary' => $data['text_secondary'],
            'img_url_one' => $sectionOne->getFirstMediaUrl(SectionFour::SECTION_FOUR_CARD_ONE_PATH),
            'card_text_one' => $data['card_text_one'],
            'card_text_one_secondary' => $data['card_text_one_secondary'],
            'img_url_two' => $sectionOne->getFirstMediaUrl(SectionFour::SECTION_FOUR_CARD_TWO_PATH),
            'card_text_two' => $data['card_text_two'],
            'card_text_two_secondary' => $data['card_text_two_secondary'],
            'img_url_three' => $sectionOne->getFirstMediaUrl(SectionFour::SECTION_FOUR_CARD_THREE_PATH),
            'card_text_three' => $data['card_text_three'],
            'card_text_three_secondary' => $data['card_text_three_secondary'],
            'img_url_four' => $sectionOne->getFirstMediaUrl(SectionFour::SECTION_FOUR_CARD_FOUR_PATH),
            'card_text_four' => $data['card_text_four'],
            'card_text_four_secondary' => $data['card_text_four_secondary'],
            'img_url_five' => $sectionOne->getFirstMediaUrl(SectionFour::SECTION_FOUR_CARD_FIVE_PATH),
            'card_text_five' => $data['card_text_five'],
            'card_text_five_secondary' => $data['card_text_five_secondary'],
            'img_url_six' => $sectionOne->getFirstMediaUrl(SectionFour::SECTION_FOUR_CARD_SIX_PATH),
            'card_text_six' => $data['card_text_six'],
            'card_text_six_secondary' => $data['card_text_six_secondary'],
        ]);

        Notification::make()
            ->success()
            ->title(__('messages.landing_cms.section_four').' '.__('messages.common.updated_successfully'))
            ->send();
    }
}
