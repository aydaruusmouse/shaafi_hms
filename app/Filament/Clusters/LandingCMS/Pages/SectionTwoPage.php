<?php

namespace App\Filament\Clusters\LandingCMS\Pages;

use App\Filament\Clusters\LandingCMS;
use App\Models\SectionTwo;
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

class SectionTwoPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.clusters.landing-c-m-s.pages.section-two-page';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = LandingCMS::class;

    public static function getNavigationLabel(): string
    {
        return __('messages.landing_cms.section_two');
    }

    public ?array $data = [];

    protected $record;

    public function mount()
    {
        $this->record = SectionTwo::with('media')->first();

        if (! $this->record) {
            $this->record = SectionTwo::create();
        }
        $this->form->fill([
            'text_main' => $this->record->text_main,
            'text_secondary' => $this->record->text_secondary,
            'card_one_image' => $this->record->card_one_image,
            'card_one_text' => $this->record->card_one_text,
            'card_one_text_secondary' => $this->record->card_one_text_secondary,
            'card_two_image' => $this->record->card_two_image,
            'card_two_text' => $this->record->card_two_text,
            'card_two_text_secondary' => $this->record->card_two_text_secondary,
            'card_third_image' => $this->record->card_third_image,
            'card_third_text' => $this->record->card_third_text,
            'card_third_text_secondary' => $this->record->card_third_text_secondary,
        ]);
    }

    public function getTitle(): string
    {
        return '';
    }

    public function form(Form $form): Form
    {
        $form->model = SectionTwo::first();

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('text_main')
                            ->label(__('messages.landing_cms.text_main').':')
                            ->validationAttribute(__('messages.landing_cms.text_main'))
                            ->placeholder(__('messages.landing_cms.text_main'))
                            ->required()->columnSpanFull(),
                        TextInput::make('text_secondary')
                            ->label(__('messages.landing_cms.text_secondary').':')
                            ->placeholder(__('messages.landing_cms.text_secondary'))
                            ->required()
                            ->validationAttribute(__('messages.landing_cms.text_secondary'))
                            ->columnSpanFull(),
                        SpatieMediaLibraryFileUpload::make('card_one_image')
                            ->label(__('messages.landing_cms.card_one_image').':')
                            ->validationAttribute(__('messages.landing_cms.card_one_image'))
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->required()
                            ->disk(config('app.media_disk'))
                            ->image()
                            ->collection(SectionTwo::SECTION_TWO_CARD_ONE_PATH)
                            ->avatar()
                            ->imageCropAspectRatio(null),
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

                        SpatieMediaLibraryFileUpload::make('card_two_image')
                            ->label(__('messages.landing_cms.card_two_image').':')
                            ->validationAttribute(__('messages.landing_cms.card_two_image'))
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->required()
                            ->disk(config('app.media_disk'))
                            ->image()
                            ->collection(SectionTwo::SECTION_TWO_CARD_TWO_PATH),

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

                        SpatieMediaLibraryFileUpload::make('card_third_image')
                            ->label(__('messages.landing_cms.card_third_image').':')
                            ->validationAttribute(__('messages.landing_cms.card_third_image'))
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->required()
                            ->image()
                            ->disk(config('app.media_disk'))
                            ->collection(SectionTwo::SECTION_TWO_CARD_THIRD_PATH),

                        Group::make()
                            ->schema([
                                TextInput::make('card_third_text')
                                    ->label(__('messages.landing_cms.card_third_text').':')
                                    ->validationAttribute(__('messages.landing_cms.card_third_text'))
                                    ->placeholder(__('messages.landing_cms.card_third_text'))
                                    ->required(),
                                TextInput::make('card_third_text_secondary')
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
        $sectionTwo = SectionTwo::first();

        $sectionTwo->update([
            'text_main' => $data['text_main'],
            'text_secondary' => $data['text_secondary'],
            'card_one_image' => $sectionTwo->getFirstMediaUrl(SectionTwo::SECTION_TWO_CARD_ONE_PATH),
            'card_one_text' => $data['card_one_text'],
            'card_one_text_secondary' => $data['card_one_text_secondary'],
            'card_two_image' => $sectionTwo->getFirstMediaUrl(SectionTwo::SECTION_TWO_CARD_TWO_PATH),
            'card_two_text' => $data['card_two_text'],
            'card_two_text_secondary' => $data['card_two_text_secondary'],
            'card_third_image' => $sectionTwo->getFirstMediaUrl(SectionTwo::SECTION_TWO_CARD_THIRD_PATH),
            'card_third_text' => $data['card_third_text'],
            'card_third_text_secondary' => $data['card_third_text_secondary'],
        ]);
        Notification::make()
            ->success()
            ->title(__('messages.landing_cms.section_two').' '.__('messages.common.updated_successfully'))
            ->send();
    }
}
