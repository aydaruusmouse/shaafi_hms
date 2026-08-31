<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings;
use App\Models\SuperAdminSetting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class FooterSettings extends Page
{
    protected static string $view = 'filament.clusters.settings.pages.footer-settings';

    protected static ?string $cluster = Settings::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public ?array $data = [];

    protected static ?string $title = '';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('messages.footer_setting.footer_settings');
    }

    public function mount()
    {
        $keys = ['footer_text', 'email', 'phone', 'address', 'facebook_url', 'twitter_url', 'instagram_url', 'linkedin_url'];
        $settingsData = SuperAdminSetting::select('key', 'value')->whereIn('key', $keys)->get()->keyBy('key')->toArray();
        $this->form->fill($settingsData);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Textarea::make('footer_text.value')
                            ->label(__('messages.footer_setting.footer_text').':')
                            ->placeholder(__('messages.footer_setting.footer_text'))
                            ->validationAttribute(__('messages.footer_setting.footer_text'))
                            ->rows(5)
                            ->columnSpanFull()
                            ->required(),
                        TextInput::make('email.value')
                            ->label(__('messages.user.email').':')
                            ->placeholder(__('messages.user.email'))
                            ->validationAttribute(__('messages.user.email'))
                            ->required()
                            ->email(),
                        PhoneInput::make('phone.value')
                            ->defaultCountry('IN')
                            ->rules(function (Get $get) {
                                return [
                                    'required',
                                    'phone:AUTO,'.strtoupper($get('prefix_code')),
                                ];
                            })
                            ->validationMessages([
                                'phone' => __('messages.common.invalid_number'),
                            ])
                            ->required()
                            ->validationAttribute(__('messages.user.phone'))
                            ->label(__('messages.user.phone').':')
                            ->placeholder(__('messages.user.phone')),
                        TextInput::make('address.value')
                            ->label(__('messages.footer_setting.address').':')
                            ->placeholder(__('messages.footer_setting.address'))
                            ->validationAttribute(__('messages.footer_setting.address'))
                            ->required(),
                        TextInput::make('facebook_url.value')
                            ->label(__('messages.facebook_url').':')
                            ->placeholder(__('messages.facebook_url')),
                        TextInput::make('twitter_url.value')
                            ->label(__('messages.twitter_url').':')
                            ->placeholder(__('messages.twitter_url')),
                        TextInput::make('instagram_url.value')
                            ->label(__('messages.instagram_url').':')
                            ->placeholder(__('messages.instagram_url')),
                        TextInput::make('linkedin_url.value')
                            ->label(__('messages.linkedIn_url').':')
                            ->placeholder(__('messages.linkedIn_url')),

                    ])->columns(2),

            ])->statePath('data');
    }

    public function save()
    {
        $result = $this->form->getState();

        foreach ($result as $key => $value) {
            SuperAdminSetting::updateOrCreate(['key' => $key], ['value' => $value['value']]);
        }
        Notification::make()
            ->success()
            ->title(__('messages.settings').' '.__('messages.common.updated_successfully'))
            ->send();
    }
}
