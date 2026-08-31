<?php

namespace App\Filament\HospitalAdmin\Clusters\Settings\Pages;

use App\Filament\HospitalAdmin\Clusters\Settings;
use App\Models\CurrencySetting;
use App\Models\Setting;
use App\Repositories\SettingRepository;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class General extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.hospital-admin.clusters.settings.pages.general';

    protected static ?string $cluster = Settings::class;

    public ?array $data = [];

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('messages.general');
    }

    public function getTitle(): string
    {
        return __('messages.general');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public function mount()
    {
        $keys = ['app_name', 'company_name', 'hospital_email', 'hospital_phone', 'hospital_from_day', 'hospital_from_time', 'hospital_address', 'current_currency', 'about_us', 'app_logo', 'favicon', 'facebook_url', 'twitter_url', 'instagram_url', 'linkedIn_url', 'enable_google_recaptcha', 'open_ai_enable', 'open_ai_key'];
        $data = Setting::whereTenantId((getLoggedInUser()->tenant_id))->select('key', 'value')->whereIn('key', $keys)->get()->keyBy('key')->toArray();
        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        $form->model = Setting::with('media')->whereTenantId((getLoggedInUser()->tenant_id))->first();

        return $form
            ->schema([
                Section::make('')
                    ->schema([
                        TextInput::make('app_name.value')
                            ->label(__('messages.setting.app_name').':')
                            ->required()
                            ->validationAttribute(__('messages.setting.app_name'))
                            ->placeholder(__('messages.setting.app_name'))
                            ->maxLength(255),
                        TextInput::make('company_name.value')
                            ->label(__('messages.setting.company_name').':')
                            ->required()
                            ->validationAttribute(__('messages.setting.company_name'))
                            ->placeholder(__('messages.setting.company_name'))
                            ->maxLength(255),
                        TextInput::make('hospital_email.value')
                            ->label(__('messages.setting.hospital_email').':')
                            ->required()
                            ->validationAttribute(__('messages.setting.hospital_email'))
                            ->placeholder(__('messages.setting.hospital_email'))
                            ->email()
                            ->maxLength(255),
                        PhoneInput::make('hospital_phone.value')
                            ->label(__('messages.user.phone').':')
                            ->defaultCountry('IN')
                            ->required()
                            ->validationAttribute(__('messages.user.phone'))
                            ->rules(function ($get) {
                                return [
                                    'required',
                                    'phone:AUTO,'.strtoupper($get('prefix_code')),
                                ];
                            })
                            ->validationMessages([
                                'phone' => __('messages.common.invalid_number'),
                            ])
                            ->placeholder(__('messages.user.phone')),
                        TextInput::make('hospital_from_day.value')
                            ->label(__('messages.setting.hospital_from_day').':')
                            ->required()
                            ->validationAttribute(__('messages.setting.hospital_from_day'))
                            ->placeholder(__('messages.setting.hospital_from_day'))
                            ->maxLength(255),
                        TextInput::make('hospital_from_time.value')
                            ->label(__('messages.setting.hospital_from_time').':')
                            ->required()
                            ->validationAttribute(__('messages.setting.hospital_from_time'))
                            ->placeholder(__('messages.setting.hospital_from_time'))
                            ->maxLength(255),
                        TextInput::make('hospital_address.value')
                            ->label(__('messages.setting.address').':')
                            ->placeholder(__('messages.setting.address'))
                            ->required()
                            ->validationAttribute(__('messages.setting.address'))
                            ->maxLength(255),

                        Select::make('current_currency.value')
                            ->live()
                            ->required()
                            ->label(__('messages.setting.currency').':')
                            ->options(function () {
                                $currenciesData = CurrencySetting::where('tenant_id', getLoggedInUser()->tenant_id)->get();
                                $currencies = [];

                                foreach ($currenciesData as $currency) {
                                    $convertCode = strtolower($currency['currency_code']);
                                    $currencies[$convertCode] =
                                        $currency['currency_icon'].'  '.$currency['currency_name'];
                                }

                                return $currencies;
                            })
                            ->native(false)
                            ->searchable()
                            ->placeholder(__('messages.setting.currency'))
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.setting.currency').' '.__('messages.fields.required'),
                            ]),

                        Textarea::make('about_us.value')
                            ->label(__('messages.setting.about_us').':')
                            ->required()
                            ->validationAttribute(__('messages.setting.about_us'))
                            ->placeholder(__('messages.setting.about_us'))
                            ->columnSpanFull()
                            ->rows(4)
                            ->maxLength(255),
                        SpatieMediaLibraryFileUpload::make('app_logo')
                            ->required()
                            ->validationAttribute(__('messages.setting.app_logo'))
                            ->label(__('messages.setting.app_logo').':')
                            ->image()
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->collection(Setting::PATH)
                            ->disk(config('app.media_disk'))
                            ->loadStateFromRelationshipsUsing(static function (SpatieMediaLibraryFileUpload $component, HasMedia $record): void {
                                /** @var Model&HasMedia $record */
                                $record = Setting::with('media')->where('tenant_id', getLoggedInUser()->tenant_id)->where('key', '=', 'app_logo')->first();
                                $media = $record->load('media')->getMedia($component->getCollection() ?? 'default')
                                    ->when(
                                        $component->hasMediaFilter(),
                                        fn (Collection $media) => $component->filterMedia($media)
                                    )
                                    ->when(
                                        ! $component->isMultiple(),
                                        fn (Collection $media): Collection => $media->take(1),
                                    )
                                    ->mapWithKeys(function (Media $media): array {
                                        $uuid = $media->getAttributeValue('uuid');

                                        return [$uuid => $uuid];
                                    })
                                    ->toArray();

                                $component->state($media);
                            })->getUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, string $file): ?array {
                                if (! $component->getRecord()) {
                                    return null;
                                }
                                $record = Setting::with('media')->where('tenant_id', getLoggedInUser()->tenant_id)->where('key', '=', 'app_logo')->first();
                                $media = $record->getRelationValue('media')->firstWhere('uuid', $file);

                                $url = null;

                                if ($component->getVisibility() === 'private') {
                                    $conversion = $component->getConversion();

                                    try {
                                        $url = $media?->getTemporaryUrl(
                                            now()->addMinutes(5),
                                            (filled($conversion) && $media->hasGeneratedConversion($conversion)) ? $conversion : '',
                                        );
                                    } catch (Throwable $exception) {
                                        // This driver does not support creating temporary URLs.
                                    }
                                }

                                if ($component->getConversion() && $media?->hasGeneratedConversion($component->getConversion())) {
                                    $url ??= $media->getUrl($component->getConversion());
                                }

                                $url ??= $media?->getUrl();

                                return [
                                    'name' => $media?->getAttributeValue('name') ?? $media?->getAttributeValue('file_name'),
                                    'size' => $media?->getAttributeValue('size'),
                                    'type' => $media?->getAttributeValue('mime_type'),
                                    'url' => $url,
                                ];
                            }),
                        SpatieMediaLibraryFileUpload::make('app_favicon')
                            ->required()
                            ->validationAttribute(__('messages.setting.favicon'))
                            ->label(__('messages.setting.favicon').':')
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->image()
                            ->disk(config('app.media_disk', 'public'))
                            ->collection(Setting::PATH)
                            ->loadStateFromRelationshipsUsing(static function (SpatieMediaLibraryFileUpload $component, HasMedia $record): void {
                                $record = Setting::where('tenant_id', getLoggedInUser()->tenant_id)->where('key', '=', 'favicon')->first();
                                $media = $record->load('media')->getMedia($component->getCollection() ?? 'default')
                                    ->when(
                                        $component->hasMediaFilter(),
                                        fn (Collection $media) => $component->filterMedia($media)
                                    )
                                    ->when(
                                        ! $component->isMultiple(),
                                        fn (Collection $media): Collection => $media->take(1),
                                    )
                                    ->mapWithKeys(function (Media $media): array {
                                        $uuid = $media->getAttributeValue('uuid');

                                        return [$uuid => $uuid];
                                    })
                                    ->toArray();
                                $component->state($media);
                            })->getUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, string $file): ?array {
                                if (! $component->getRecord()) {
                                    return null;
                                }
                                $record = Setting::where('tenant_id', getLoggedInUser()->tenant_id)->where('key', '=', 'favicon')->first();
                                $media = $record->getRelationValue('media')->firstWhere('uuid', $file);

                                $url = null;

                                if ($component->getVisibility() === 'private') {
                                    $conversion = $component->getConversion();

                                    try {
                                        $url = $media?->getTemporaryUrl(
                                            now()->addMinutes(5),
                                            (filled($conversion) && $media->hasGeneratedConversion($conversion)) ? $conversion : '',
                                        );
                                    } catch (Throwable $exception) {
                                        // This driver does not support creating temporary URLs.
                                    }
                                }

                                if ($component->getConversion() && $media?->hasGeneratedConversion($component->getConversion())) {
                                    $url ??= $media->getUrl($component->getConversion());
                                }

                                $url ??= $media?->getUrl();

                                return [
                                    'name' => $media?->getAttributeValue('name') ?? $media?->getAttributeValue('file_name'),
                                    'size' => $media?->getAttributeValue('size'),
                                    'type' => $media?->getAttributeValue('mime_type'),
                                    'url' => $url,
                                ];
                            }),
                        Fieldset::make()
                            ->label(__('messages.setting.social_details'))
                            ->schema([
                                TextInput::make('facebook_url.value')
                                    ->label(__('messages.facebook_url').':')
                                    ->placeholder(__('messages.facebook_url'))
                                    ->url()
                                    ->maxLength(255),
                                TextInput::make('twitter_url.value')
                                    ->label(__('messages.twitter_url').':')
                                    ->placeholder(__('messages.twitter_url'))
                                    ->url()
                                    ->maxLength(255),
                                TextInput::make('instagram_url.value')
                                    ->label(__('messages.instagram_url').':')
                                    ->placeholder(__('messages.instagram_url'))
                                    ->url()
                                    ->maxLength(255),
                                TextInput::make('linkedIn_url.value')
                                    ->label(__('messages.linkedIn_url').':')
                                    ->placeholder(__('messages.linkedIn_url'))
                                    ->url()
                                    ->maxLength(255),
                            ]),
                        Toggle::make('enable_google_recaptcha.value')
                            ->inline()
                            ->label(__('messages.setting.enable_google_reCAPTCHA').': ')
                            ->live(),
                        Group::make()->schema([
                            Toggle::make('open_ai_enable.value')
                                ->inline()
                                ->label(__('messages.open_ai.open_ai').': ')
                                ->live(),
                            TextInput::make('open_ai_key.value')
                                ->label(__('messages.open_ai.open_ai_key').': ')
                                ->required(fn ($get) => $get('open_ai_enable.value'))
                                ->validationAttribute(__('messages.open_ai.open_ai_key'))
                                ->live()
                                ->visible(function ($get) {
                                    $key = 'open_ai_enable.value';
                                    $record = Setting::whereTenantId(getLoggedInUser()->tenant_id)
                                        ->where('key', $key)
                                        ->first();
                                    $storedValue = $record->value ?? 0;

                                    return $storedValue == 0 && $get($key) == 1;
                                })
                                ->maxLength(255)
                                ->placeholder(__('messages.open_ai.open_ai_key')),
                        ]),
                    ])->columns(2),
            ])->statePath('data');
    }

    public function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('messages.common.save'))
                ->submit('save'),

            Action::make('cancel')
                ->label(__('messages.common.cancel'))
                ->color('light-secondary')
                ->outlined()
                ->extraAttributes(['class' => 'border border-gray-500 text-gray-700 hover:bg-gray-100'])
                ->action('resetForm'),
        ];
    }

    public function resetForm()
    {
        $this->js('window.location.reload()');
    }

    public function save()
    {
        $result = $this->data;

        foreach ($result as $key => $value) {
            if (in_array($key, ['app_logo', 'app_favicon'])) {
                continue;
            }

            if (is_array($value) && isset($value['value'])) {
                $result[$key] = $value['value'];
            } else {
                $result[$key] = null;
            }
        }

        $result['favicon'] = isset($result['app_favicon']) ? reset($result['app_favicon']) : null;

        $result = Arr::except($result, ['app_favicon']);

        app(SettingRepository::class)->updateSetting($result);

        Notification::make()
            ->success()
            ->title(__('messages.settings').' '.__('messages.common.updated_successfully'))
            ->send();
        $this->afterSave();
    }

    protected function afterSave()
    {
        $this->js('window.location.reload()');
    }
}
