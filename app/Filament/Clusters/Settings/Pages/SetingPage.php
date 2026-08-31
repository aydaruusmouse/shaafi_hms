<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings;
use App\Models\SuperAdminCurrencySetting;
use App\Models\SuperAdminSetting;
use App\Models\User;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tapp\FilamentCountryCodeField\Forms\Components\CountryCodeSelect;
use Throwable;

class SetingPage extends Page
{
    protected static string $view = 'filament.clusters.settings.pages.seting-page';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $cluster = Settings::class;

    protected static ?string $title = '';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public $settingsData = [];

    public static function getNavigationLabel(): string
    {
        return __('messages.settings');
    }

    public function mount()
    {
        $keys = ['app_name', 'plan_expire_notification', 'default_country_code', 'super_admin_currency', 'enable_google_recaptcha', 'default_language', 'manual_instruction', 'app_logo', 'favicon', 'google_captcha_key', 'google_captcha_secret'];
        $settingsData = SuperAdminSetting::select('key', 'value')->whereIn('key', $keys)->get()->keyBy('key')->toArray();
        $this->form->fill([
            'app_name' => $settingsData['app_name']['value'],
            'plan_expire_notification' => $settingsData['plan_expire_notification']['value'],
            'default_country_code' => $settingsData['default_country_code']['value'],
            'super_admin_currency' => $settingsData['super_admin_currency']['value'],
            'enable_google_recaptcha' => $settingsData['enable_google_recaptcha']['value'],
            'default_language' => $settingsData['default_language']['value'],
            'manual_instruction' => $settingsData['manual_instruction']['value'],
            'google_captcha_key' => $settingsData['google_captcha_key']['value'],
            'google_captcha_secret' => $settingsData['google_captcha_secret']['value'],
            'app_logo' => $settingsData['app_logo']['value'],
            'favicon' => $settingsData['favicon']['value'],
        ]);
    }

    public function form(Form $form): Form
    {
        $settingsData = SuperAdminSetting::select('key', 'value')->where('key', 'enable_google_recaptcha')->get()->keyBy('key')->toArray();
        $form->model = SuperAdminSetting::with('media')->first();

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('app_name')
                            ->label(__('messages.setting.app_name').':')
                            ->placeholder(__('messages.setting.app_name'))
                            ->validationAttribute(__('messages.setting.app_name'))
                            ->required(),
                        Group::make()
                            ->schema([
                                TextInput::make('plan_expire_notification')
                                    ->label(__('messages.plan_expire_notifications').':')
                                    ->placeholder(__('messages.plan_expire_notifications'))
                                    ->maxLength(2)
                                    ->validationAttribute(__('messages.plan_expire_notifications'))
                                    ->required(),
                                CountryCodeSelect::make('default_country_code')
                                    ->label(__('messages.common.default_country_code').':')
                                    ->required()
                                    ->validationMessages([
                                        'required' => __('messages.fields.the').' '.__('messages.common.default_country_code').' '.__('messages.fields.required'),
                                    ]),
                            ])->columns(2),

                        Select::make('super_admin_currency')
                            ->label(__('messages.setting.currency').':')
                            ->options(SuperAdminCurrencySetting::all()->mapWithKeys(function ($currency) {
                                return [
                                    $currency->currency_code => $currency->currency_icon.'  '.$currency->currency_name.')',
                                ];
                            })->toArray())
                            ->native(false)
                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.setting.currency').' '.__('messages.fields.required'),
                            ]),
                        Group::make()
                            ->schema([
                                Toggle::make('enable_google_recaptcha')
                                    ->inline(false)
                                    ->label(__('messages.setting.enable_google_reCAPTCHA').':')
                                    ->live()
                                    ->default(function ($record) use ($settingsData) {
                                        if ($settingsData['enable_google_recaptcha']['value'] == 1) {
                                            return true;
                                        }

                                        return false;
                                    })
                                    ->validationAttribute(__('messages.setting.enable_google_reCAPTCHA'))
                                    ->required(),
                                Select::make('default_language')
                                    ->label(__('messages.profile.language').':')
                                    ->options(User::LANGUAGES)
                                    ->required()
                                    ->native(false)
                                    ->validationMessages([
                                        'required' => __('messages.fields.the').' '.__('messages.profile.language').' '.__('messages.fields.required'),
                                    ]),
                            ])->columns(2),
                        TextInput::make('google_captcha_key')
                            ->label(__('messages.new_change.captcha_key').':')
                            ->placeholder(__('messages.new_change.captcha_key'))
                            ->validationAttribute(__('messages.new_change.captcha_key'))
                            ->visible(function (callable $get) {
                                if ($get('enable_google_recaptcha') == 1) {
                                    return true;
                                }

                                return false;
                            })
                            ->required(),
                        TextInput::make('google_captcha_secret')
                            ->label(__('messages.new_change.captcha_secret').':')
                            ->placeholder(__('messages.new_change.captcha_secret'))
                            ->validationAttribute(__('messages.new_change.captcha_secret'))
                            ->visible(function (callable $get) {
                                if ($get('enable_google_recaptcha') == 1) {
                                    return true;
                                }

                                return false;
                            })
                            ->required(),
                        Textarea::make('manual_instruction')
                            ->label(__('messages.custom_field.manual_instruction').':')
                            ->validationAttribute(__('messages.custom_field.manual_instruction'))
                            ->required()
                            ->rows(5),
                        Group::make()
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('app_logo')
                                    ->label(__('messages.setting.app_logo').':')
                                    ->validationAttribute(__('messages.setting.app_logo'))
                                    ->avatar()
                                    ->imageCropAspectRatio(null)
                                    ->disk(config('app.media_disk'))
                                    ->image()
                                    ->collection(SuperAdminSetting::PATH)
                                    ->saveUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, TemporaryUploadedFile $file, ?Model $record): ?string {
                                        $record = SuperAdminSetting::where('key', '=', 'app_logo')->first();
                                        if (! $record) {
                                            $record = SuperAdminSetting::create([
                                                'key' => 'app_logo',
                                                'value' => null,
                                            ]);
                                        }

                                        if (! method_exists($record, 'addMediaFromString')) {
                                            return $file;
                                        }

                                        try {
                                            if (! $file->exists()) {
                                                return null;
                                            }
                                        } catch (UnableToCheckFileExistence $exception) {
                                            return null;
                                        }

                                        $record->getMedia($component->getCollection() ?? 'default')
                                            ->whereNotIn('uuid', array_keys($component->getState() ?? []))
                                            ->when($component->hasMediaFilter(), fn (Collection $media): Collection => $component->filterMedia($media))
                                            ->each(fn (Media $media) => $media->delete());

                                        /** @var FileAdder $mediaAdder */
                                        $mediaAdder = $record->addMediaFromString($file->get());

                                        $filename = $component->getUploadedFileNameForStorage($file);

                                        $media = $mediaAdder
                                            ->addCustomHeaders($component->getCustomHeaders())
                                            ->usingFileName($filename)
                                            ->usingName($component->getMediaName($file) ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                                            ->storingConversionsOnDisk($component->getConversionsDisk() ?? '')
                                            ->withCustomProperties($component->getCustomProperties())
                                            ->withManipulations($component->getManipulations())
                                            ->withResponsiveImagesIf($component->hasResponsiveImages())
                                            ->withProperties($component->getProperties())
                                            ->toMediaCollection($component->getCollection() ?? 'default', $component->getDiskName());

                                        $record->update(['value' => $media->getUrl()]);

                                        return $media->getAttributeValue('uuid');
                                    })
                                    ->loadStateFromRelationshipsUsing(static function (SpatieMediaLibraryFileUpload $component, HasMedia $record): void {
                                        /** @var Model&HasMedia $record */
                                        $record = SuperAdminSetting::with('media')->where('key', '=', 'app_logo')->first();
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
                                    })
                                    ->getUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, string $file): ?array {
                                        if (! $component->getRecord()) {
                                            return null;
                                        }
                                        $record = SuperAdminSetting::with('media')->where('key', '=', 'app_logo')->first();
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
                                    })
                                    ->required(),
                                SpatieMediaLibraryFileUpload::make('favicon')
                                    ->label(__('messages.setting.favicon').':')
                                    ->avatar()
                                    ->imageCropAspectRatio(null)
                                    ->image()
                                    ->disk(config('app.media_disk'))
                                    ->collection(SuperAdminSetting::PATH)
                                    ->required()
                                    ->validationAttribute(__('messages.setting.favicon'))
                                    ->saveUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, TemporaryUploadedFile $file, ?Model $record): ?string {
                                        $record = SuperAdminSetting::where('key', '=', 'favicon')->first();
                                        if (! $record) {
                                            $record = SuperAdminSetting::create([
                                                'key' => 'favicon',
                                                'value' => null,
                                            ]);
                                        }

                                        if (! method_exists($record, 'addMediaFromString')) {
                                            return $file;
                                        }

                                        try {
                                            if (! $file->exists()) {
                                                return null;
                                            }
                                        } catch (UnableToCheckFileExistence $exception) {
                                            return null;
                                        }

                                        $record->getMedia($component->getCollection() ?? 'default')
                                            ->whereNotIn('uuid', array_keys($component->getState() ?? []))
                                            ->when($component->hasMediaFilter(), fn (Collection $media): Collection => $component->filterMedia($media))
                                            ->each(fn (Media $media) => $media->delete());

                                        /** @var FileAdder $mediaAdder */
                                        $mediaAdder = $record->addMediaFromString($file->get());

                                        $filename = $component->getUploadedFileNameForStorage($file);

                                        $media = $mediaAdder
                                            ->addCustomHeaders($component->getCustomHeaders())
                                            ->usingFileName($filename)
                                            ->usingName($component->getMediaName($file) ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                                            ->storingConversionsOnDisk($component->getConversionsDisk() ?? '')
                                            ->withCustomProperties($component->getCustomProperties())
                                            ->withManipulations($component->getManipulations())
                                            ->withResponsiveImagesIf($component->hasResponsiveImages())
                                            ->withProperties($component->getProperties())
                                            ->toMediaCollection($component->getCollection() ?? 'default', $component->getDiskName());

                                        $record->update(['value' => $media->getUrl()]);

                                        return $media->getAttributeValue('uuid');
                                    })
                                    ->loadStateFromRelationshipsUsing(static function (SpatieMediaLibraryFileUpload $component, HasMedia $record): void {
                                        /** @var Model&HasMedia $record */
                                        $record = SuperAdminSetting::with('media')->where('key', '=', 'favicon')->first();
                                        // dd($record);
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
                                    })
                                    ->getUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, string $file): ?array {
                                        if (! $component->getRecord()) {
                                            return null;
                                        }
                                        $record = SuperAdminSetting::with('media')->where('key', '=', 'favicon')->first();
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
                            ])->columns(3),

                    ])->columns(2),
            ])->statePath('data');
    }

    public function save()
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            SuperAdminSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Notification::make()
            ->success()
            ->title(__('messages.settings').' '.__('messages.common.updated_successfully'))
            ->send();
    }
}
