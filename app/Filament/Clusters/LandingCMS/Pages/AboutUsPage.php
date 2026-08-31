<?php

namespace App\Filament\Clusters\LandingCMS\Pages;

use App\Filament\Clusters\LandingCMS;
use App\Models\LandingAboutUs;
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

class AboutUsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.clusters.landing-c-m-s.pages.about-us-page';

    protected static ?string $cluster = LandingCMS::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string
    {
        return __('messages.about_us');
    }

    public ?array $data = [];

    protected static ?string $title = '';

    protected $record;

    public function mount(): void
    {
        $this->form->fill();
        $this->record = LandingAboutUs::with('media')->first();
        if (! $this->record) {
            $this->record = LandingAboutUs::create();
        }

        $this->form->fill([
            'text_main' => $this->record->text_main,
            'card_one_text' => $this->record->card_one_text,
            'card_two_text' => $this->record->card_two_text,
            'card_three_text' => $this->record->card_three_text,
            'card_one_text_secondary' => $this->record->card_one_text_secondary,
            'card_two_text_secondary' => $this->record->card_two_text_secondary,
            'card_three_text_secondary' => $this->record->card_three_text_secondary,
        ]);
    }

    public function form(Form $form): Form
    {
        $form->model = LandingAboutUs::first();

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
                        SpatieMediaLibraryFileUpload::make('main_img_one')
                            ->label(__('messages.landing_cms.main_img_one').': ')
                            ->validationAttribute(__('messages.landing_cms.main_img_one'))
                            ->disk(config('app.media_disk'))
                            ->image()
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->collection(LandingAboutUs::LANDING_ABOUT_US_MAIN_IMG_ONE)
                            ->required(),
                        SpatieMediaLibraryFileUpload::make('main_img_two')
                            ->label(__('messages.landing_cms.main_img_two').': ')
                            ->validationAttribute(__('messages.landing_cms.main_img_two'))
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->image()
                            ->disk(config('app.media_disk'))
                            ->columnSpan(2)
                            ->collection(LandingAboutUs::LANDING_ABOUT_US_MAIN_IMG_TWO)
                            ->required(),
                        SpatieMediaLibraryFileUpload::make('card_img_one')
                            ->label(__('messages.landing_cms.card_one_image').':')
                            ->validationAttribute(__('messages.landing_cms.card_one_image'))
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->disk(config('app.media_disk'))
                            ->image()
                            ->collection(LandingAboutUs::LANDING_ABOUT_US_CARD_IMG_ONE)
                            ->required(),
                        Group::make()
                            ->schema([
                                TextInput::make('card_one_text')
                                    ->label(__('messages.landing_cms.card_one_text').':')
                                    ->validationAttribute(__('messages.landing_cms.card_one_text'))
                                    ->placeholder(__('messages.landing_cms.card_one_text'))
                                    ->required(),
                                TextInput::make('card_one_text_secondary')
                                    ->label(__('messages.landing_cms.card_one_text_secondary').':')
                                    ->validationAttribute(__('messages.landing_cms.card_one_text_secondary'))
                                    ->placeholder(__('messages.landing_cms.card_one_text_secondary'))
                                    ->required(),
                            ])->columnSpan(2),

                        SpatieMediaLibraryFileUpload::make('card_img_two')
                            ->label(__('messages.landing_cms.card_two_image').':')
                            ->validationAttribute(__('messages.landing_cms.card_two_image'))
                            ->avatar()
                            ->image()
                            ->imageCropAspectRatio(null)
                            ->disk(config('app.media_disk'))
                            ->collection(LandingAboutUs::LANDING_ABOUT_US_CARD_IMG_TWO)
                            ->required(),
                        Group::make()
                            ->schema([
                                TextInput::make('card_two_text')
                                    ->label(__('messages.landing_cms.card_two_text').':')
                                    ->validationAttribute(__('messages.landing_cms.card_two_text'))
                                    ->placeholder(__('messages.landing_cms.card_two_text'))
                                    ->required(),
                                TextInput::make('card_two_text_secondary')
                                    ->label(__('messages.landing_cms.card_two_text_secondary').':')
                                    ->validationAttribute(__('messages.landing_cms.card_two_text_secondary'))
                                    ->placeholder(__('messages.landing_cms.card_two_text_secondary'))
                                    ->required(),
                            ])->columnSpan(2),

                        SpatieMediaLibraryFileUpload::make('card_img_three')
                            ->label(__('messages.landing_cms.card_third_image').':')
                            ->validationAttribute(__('messages.landing_cms.card_third_image'))
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->image()
                            ->disk(config('app.media_disk'))
                            ->collection(LandingAboutUs::LANDING_ABOUT_US_CARD_IMG_THREE)
                            ->required(),
                        Group::make()
                            ->schema([
                                TextInput::make('card_three_text')
                                    ->label(__('messages.landing_cms.card_third_text').':')
                                    ->validationAttribute(__('messages.landing_cms.card_third_text'))
                                    ->placeholder(__('messages.landing_cms.card_third_text'))
                                    ->required(),
                                TextInput::make('card_three_text_secondary')
                                    ->label(__('messages.landing_cms.card_third_text_secondary').':')
                                    ->validationAttribute(__('messages.landing_cms.card_third_text_secondary'))
                                    ->placeholder(__('messages.landing_cms.card_third_text_secondary'))
                                    ->required(),
                            ])->columnSpan(2),
                    ])->columns(3),
            ])->statePath('data');
    }

    public function save()
    {
        $data = $this->form->getState();

        $aboutUs = LandingAboutUs::first();

        $aboutUs->update([
            'text_main' => $data['text_main'],
            'card_one_text' => $data['card_one_text'],
            'card_two_text' => $data['card_two_text'],
            'card_three_text' => $data['card_three_text'],
            'card_one_text_secondary' => $data['card_one_text_secondary'],
            'card_two_text_secondary' => $data['card_two_text_secondary'],
            'card_three_text_secondary' => $data['card_three_text_secondary'],
            'main_img_one' => $aboutUs->getFirstMediaUrl(LandingAboutUs::LANDING_ABOUT_US_MAIN_IMG_ONE),
            'main_img_two' => $aboutUs->getFirstMediaUrl(LandingAboutUs::LANDING_ABOUT_US_MAIN_IMG_TWO),
            'card_img_one' => $aboutUs->getFirstMediaUrl(LandingAboutUs::LANDING_ABOUT_US_CARD_IMG_ONE),
            'card_img_two' => $aboutUs->getFirstMediaUrl(LandingAboutUs::LANDING_ABOUT_US_CARD_IMG_TWO),
            'card_img_three' => $aboutUs->getFirstMediaUrl(LandingAboutUs::LANDING_ABOUT_US_CARD_IMG_THREE),
        ]);

        Notification::make()
            ->success()
            ->title(__('messages.landing_cms.about_us').' '.__('messages.common.updated_successfully'))
            ->send();
    }
}
