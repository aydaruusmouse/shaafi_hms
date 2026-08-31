<?php

namespace App\Filament\HospitalAdmin\Clusters\FrontCms\Pages;

use App\Filament\HospitalAdmin\Clusters\FrontCms;
use App\Models\FrontSetting;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
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
use Throwable;

class Cms extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    protected static string $view = 'filament.hospital-admin.clusters.front-cms.pages.cms';

    protected static ?string $cluster = FrontCms::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public function mount()
    {
        $frontSetting = FrontSetting::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('value', 'key')->toArray();
        $this->form->getLivewire()->data = [];
        $this->form->fill($frontSetting);
        $this->form->getLivewire()->data['data'] = $this->data;
        // dd($this->form->getLivewire()->data['data']);
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public static function getNavigationLabel(): string
    {
        return strtoupper(__('messages.cms'));
    }

    public function form(Form $form): Form
    {
        return $form
            ->model(FrontSetting::where('tenant_id', getLoggedInUser()->tenant_id)->first())
            ->schema($this->getFormSchema())
            ->live()
            ->statePath('data');
    }

    protected function getFormSchema(): array
    {
        return [
            Tabs::make('Front CMS Settings')
                ->tabs([
                    Tabs\Tab::make('Home')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    SpatieMediaLibraryFileUpload::make('home_page_image')
                                        ->label(__('messages.front_setting.home_page_image').':')
                                        ->image()
                                        ->avatar()
                                        ->imageCropAspectRatio(null)
                                        ->required()
                                        ->validationAttribute(__('messages.front_setting.home_page_image'))
                                        ->collection(FrontSetting::HOME_IMAGE_PATH)
                                        ->disk(config('app.media_disk'))
                                        ->saveUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, TemporaryUploadedFile $file, ?Model $record): ?string {
                                            $record = FrontSetting::whereTenantId(getLoggedInUser()->tenant_id)->where('key', '=', 'home_page_image')->first();
                                            if (! $record) {
                                                $record = FrontSetting::whereTenantId(getLoggedInUser()->tenant_id)->create([
                                                    'key' => 'home_page_image',
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
                                            $record = FrontSetting::with('media')->whereTenantId(getLoggedInUser()->tenant_id)->where('key', '=', 'home_page_image')->first();
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
                                            $record = FrontSetting::whereTenantId(getLoggedInUser()->tenant_id)->with('media')->where('key', '=', 'home_page_image')->first();

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

                                    SpatieMediaLibraryFileUpload::make('home_page_certified_doctor_image')
                                        ->label(__('messages.front_setting.home_page_certified_doctor_image').':')
                                        ->required()
                                        ->validationAttribute(__('messages.front_setting.home_page_certified_doctor_image'))
                                        ->avatar()
                                        ->imageCropAspectRatio(null)
                                        ->image()
                                        ->collection(FrontSetting::HOME_IMAGE_PATH)
                                        ->disk(config('app.media_disk'))
                                        ->saveUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, TemporaryUploadedFile $file, ?Model $record): ?string {
                                            $record = FrontSetting::whereTenantId(getLoggedInUser()->tenant_id)->where('key', '=', 'home_page_certified_doctor_image')->first();
                                            if (! $record) {
                                                $record = FrontSetting::whereTenantId(getLoggedInUser()->tenant_id)->create([
                                                    'key' => 'home_page_certified_doctor_image',
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
                                            $record = FrontSetting::with('media')->whereTenantId(getLoggedInUser()->tenant_id)->where('key', '=', 'home_page_certified_doctor_image')->first();
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
                                            $record = FrontSetting::whereTenantId(getLoggedInUser()->tenant_id)->with('media')->where('key', '=', 'home_page_certified_doctor_image')->first();

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
                                ]),

                            // Home Experience Field
                            TextInput::make('home_page_experience')
                                ->label(__('messages.front_setting.home_page_experience'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.home_page_experience'))
                                ->placeholder(__('messages.front_setting.home_page_experience'))
                                ->statePath('data.home_page_experience'),

                            // Home Title Field
                            TextInput::make('home_page_title')
                                ->label(__('messages.front_setting.home_page_title'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.home_page_title'))
                                ->placeholder(__('messages.front_setting.home_page_title'))
                                ->statePath('data.home_page_title'),

                            // Home Description Field
                            Textarea::make('home_page_description')
                                ->label(__('messages.front_setting.home_page_description'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.home_page_description'))
                                ->rows(5)
                                ->placeholder(__('messages.front_setting.home_page_description'))
                                ->statePath('data.home_page_description'),

                            // Home Box Title Field
                            TextInput::make('home_page_box_title')
                                ->label(__('messages.front_setting.home_page_box_title'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.home_page_box_title'))
                                ->placeholder(__('messages.front_setting.home_page_box_title'))
                                ->statePath('data.home_page_box_title'),

                            // Home Box Description Field
                            Textarea::make('home_page_box_description')
                                ->label(__('messages.front_setting.home_page_box_description'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.home_page_box_description'))
                                ->rows(5)
                                ->placeholder(__('messages.front_setting.home_page_box_description'))
                                ->statePath('data.home_page_box_description'),

                            // Certified Doctor Text Field
                            TextInput::make('home_page_certified_doctor_text')
                                ->label(__('messages.front_setting.home_page_certified_doctor_text'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.home_page_certified_doctor_text'))
                                ->placeholder(__('messages.front_setting.home_page_certified_doctor_text'))
                                ->statePath('data.home_page_certified_doctor_text'),

                            // Certified Doctor Title Field
                            TextInput::make('home_page_certified_doctor_title')
                                ->label(__('messages.front_setting.home_page_certified_doctor_title'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.home_page_certified_doctor_title'))
                                ->placeholder(__('messages.front_setting.home_page_certified_doctor_title'))
                                ->statePath('data.home_page_certified_doctor_title'),

                            // Certified Doctor Description Field
                            Textarea::make('home_page_certified_doctor_description')
                                ->label(__('messages.front_setting.home_page_certified_doctor_description'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.home_page_certified_doctor_description'))
                                ->rows(5)
                                ->placeholder(__('messages.front_setting.home_page_certified_doctor_description'))
                                ->statePath('data.home_page_certified_doctor_description'),

                            // Certified Box Title Field
                            TextInput::make('home_page_certified_box_title')
                                ->label(__('messages.front_setting.home_page_certified_box_title'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.home_page_certified_box_title'))
                                ->placeholder(__('messages.front_setting.home_page_certified_box_title'))
                                ->statePath('data.home_page_certified_box_title'),

                            // Certified Box Description Field
                            Textarea::make('home_page_certified_box_description')
                                ->label(__('messages.front_setting.home_page_certified_box_description'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.home_page_certified_box_description'))
                                ->rows(5)
                                ->placeholder(__('messages.front_setting.home_page_certified_box_description'))
                                ->statePath('data.home_page_certified_box_description'),

                            // Step 1 Title Field
                            TextInput::make('home_page_step_1_title')
                                ->label(__('messages.front_setting.home_page_step_1_title'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.home_page_step_1_title'))
                                ->placeholder(__('messages.front_setting.home_page_step_1_title'))
                                ->statePath('data.home_page_step_1_title'),

                            // Step 1 Description Field
                            Textarea::make('home_page_step_1_description')
                                ->label(__('messages.front_setting.home_page_step_1_description'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.home_page_step_1_description'))
                                ->rows(5)
                                ->placeholder(__('messages.front_setting.home_page_step_1_description'))
                                ->statePath('data.home_page_step_1_description'),

                            // Step 2 Title Field
                            TextInput::make('home_page_step_2_title')
                                ->label(__('messages.front_setting.home_page_step_2_title'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.home_page_step_2_title'))
                                ->placeholder(__('messages.front_setting.home_page_step_2_title'))
                                ->statePath('data.home_page_step_2_title'),

                            // Step 2 Description Field
                            Textarea::make('home_page_step_2_description')
                                ->label(__('messages.front_setting.home_page_step_2_description'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.home_page_step_2_description'))
                                ->rows(5)
                                ->placeholder(__('messages.front_setting.home_page_step_2_description'))
                                ->statePath('data.home_page_step_2_description'),

                            // Step 3 Title Field
                            TextInput::make('home_page_step_3_title')
                                ->label(__('messages.front_setting.home_page_step_3_title'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.home_page_step_3_title'))
                                ->placeholder(__('messages.front_setting.home_page_step_3_title'))
                                ->statePath('data.home_page_step_3_title'),

                            // Step 3 Description Field
                            Textarea::make('home_page_step_3_description')
                                ->label(__('messages.front_setting.home_page_step_3_description'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.home_page_step_3_description'))
                                ->rows(5)
                                ->placeholder(__('messages.front_setting.home_page_step_3_description'))
                                ->statePath('data.home_page_step_3_description'),

                            // Step 4 Title Field
                            TextInput::make('home_page_step_4_title')
                                ->label(__('messages.front_setting.home_page_step_4_title'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.home_page_step_4_title'))
                                ->placeholder(__('messages.front_setting.home_page_step_4_title'))
                                ->statePath('data.home_page_step_4_title'),

                            // Step 4 Description Field
                            Textarea::make('home_page_step_4_description')
                                ->label(__('messages.front_setting.home_page_step_4_description'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.home_page_step_4_description'))
                                ->rows(5)
                                ->placeholder(__('messages.front_setting.home_page_step_4_description'))
                                ->statePath('data.home_page_step_4_description'), // Bind to $data array
                        ]),
                    Tabs\Tab::make('About Us')
                        ->schema([
                            TextInput::make('about_us_title')
                                ->label(__('messages.front_setting.about_us_title'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.about_us_title'))
                                ->statePath('data.about_us_title'),
                            Textarea::make('about_us_description')
                                ->label(__('messages.front_setting.about_us_description'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.about_us_description'))
                                ->statePath('data.about_us_description'),
                            Textarea::make('about_us_mission')
                                ->label(__('messages.front_setting.about_us_mission'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.about_us_mission'))
                                ->statePath('data.about_us_mission'),
                            SpatieMediaLibraryFileUpload::make('about_us_image')
                                ->label(__('messages.front_setting.about_us_image').':')
                                ->collection(FrontSetting::PATH)
                                ->disk(config('app.media_disk'))
                                ->image()
                                ->avatar()
                                ->imageCropAspectRatio(null)
                                ->saveUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, TemporaryUploadedFile $file, ?Model $record): ?string {
                                    $record = FrontSetting::whereTenantId(getLoggedInUser()->tenant_id)->where('key', '=', 'about_us_image')->first();
                                    if (! $record) {
                                        $record = FrontSetting::whereTenantId(getLoggedInUser()->tenant_id)->create([
                                            'key' => 'about_us_image',
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
                                    $record = FrontSetting::with('media')->whereTenantId(getLoggedInUser()->tenant_id)->where('key', '=', 'about_us_image')->first();
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
                                    $record = FrontSetting::whereTenantId(getLoggedInUser()->tenant_id)->with('media')->where('key', '=', 'about_us_image')->first();

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
                        ]),
                    Tabs\Tab::make('Appointment')
                        ->schema([
                            TextInput::make('appointment_title')
                                ->label(__('messages.front_setting.about_us_title').':')
                                ->placeholder(__('messages.front_setting.about_us_title'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.about_us_title'))
                                ->columnSpan(12)
                                ->statePath('data.appointment_title'),

                            Textarea::make('appointment_description')
                                ->label(__('messages.front_setting.about_us_description').':')
                                ->placeholder(__('messages.front_setting.about_us_description'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.about_us_description'))
                                ->rows(5)
                                ->columnSpan(12)
                                ->statePath('data.appointment_description'),
                        ]),
                    Tabs\Tab::make('Terms & Conditions')
                        ->schema([
                            RichEditor::make('terms_conditions')
                                ->label(__('messages.front_setting.terms_conditions').':')
                                ->placeholder(__('messages.front_setting.terms_conditions'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.terms_conditions'))
                                ->statePath('data.terms_conditions'),
                            RichEditor::make('privacy_policy')
                                ->label(__('messages.front_setting.privacy_policy'))
                                ->placeholder(__('messages.front_setting.privacy_policy'))
                                ->required()
                                ->validationAttribute(__('messages.front_setting.privacy_policy'))
                                ->statePath('data.privacy_policy'),
                        ]),
                ])->persistTabInQueryString()
                ->reactive()
                ->live(),
        ];
    }

    public function saveHomeSettings()
    {
        $this->form->getState();
        $data = $this->data['data'];

        $keys = [
            'home_page_experience',
            'home_page_title',
            'home_page_description',
            'home_page_box_title',
            'home_page_box_description',
            'home_page_certified_doctor_text',
            'home_page_certified_doctor_title',
            'home_page_certified_doctor_description',
            'home_page_certified_box_title',
            'home_page_certified_box_description',
            'home_page_step_1_title',
            'home_page_step_1_description',
            'home_page_step_2_title',
            'home_page_step_2_description',
            'home_page_step_3_title',
            'home_page_step_3_description',
            'home_page_step_4_title',
            'home_page_step_4_description',
        ];

        foreach ($keys as $key) {
            FrontSetting::updateOrCreate(['key' => $key, 'tenant_id' => getLoggedInUser()->tenant_id], ['value' => $data[$key]]);
        }

        Notification::make()
            ->success()
            ->title(__('messages.flash.front_setting_updated'))
            ->send();
    }

    public function saveAboutUsSettings()
    {
        $this->form->getState();
        $data = $this->data['data'];
        foreach (['about_us_title', 'about_us_description', 'about_us_mission'] as $key) {
            FrontSetting::updateOrCreate(['key' => $key, 'tenant_id' => getLoggedInUser()->tenant_id], ['value' => $data[$key]]);
        }

        Notification::make()
            ->success()
            ->title(__('messages.flash.front_setting_updated'))
            ->send();

        // if (!empty($data['about_us_image'])) {
        //     FrontSetting::updateOrCreate(
        //         ['key' => 'about_us_image'],
        //         ['value' => $data['about_us_image']->store('images', 'public')]
        //     );
        // }
    }

    public function saveAppointmentSettings()
    {
        $this->form->getState();
        $data = $this->data['data'];
        foreach (['appointment_title', 'appointment_description'] as $key) {
            FrontSetting::updateOrCreate(['key' => $key, 'tenant_id' => getLoggedInUser()->tenant_id], ['value' => $data[$key]]);
        }

        Notification::make()
            ->success()
            ->title(__('messages.flash.front_setting_updated'))
            ->send();
    }

    public function saveTermsConditionsSettings()
    {
        $this->form->getState();
        $data = $this->data['data'];
        foreach (['terms_conditions', 'privacy_policy'] as $key) {
            FrontSetting::updateOrCreate(['key' => $key, 'tenant_id' => getLoggedInUser()->tenant_id], ['value' => $data[$key]]);
        }

        Notification::make()
            ->success()
            ->title(__('messages.flash.front_setting_updated'))
            ->send();
    }

    public function getFormActions(): array
    {
        $tab = Request()->get('tab');
        // dd($tab);
        if ($tab == '-home-tab') {
            return [
                Action::make('Save Home Settings')
                    ->label(__('messages.common.save'))
                    ->action('saveHomeSettings'),
            ];
        } elseif ($tab == '-about-us-tab') {

            return [
                Action::make('Save About Us Settings')
                    ->label(__('messages.common.save'))
                    ->action('saveAboutUsSettings'),
            ];
        } elseif ($tab == '-appointment-tab') {
            return [
                Action::make('Save Appointment Settings')
                    ->label(__('messages.common.save'))
                    ->action('saveAppointmentSettings'),
            ];
        } elseif ($tab == '-terms-conditions-tab') {

            return [
                Action::make('Save Terms & Conditions')
                    ->label(__('messages.common.save'))
                    ->action('saveTermsConditionsSettings'),
            ];
        } else {
            $previousUrl = url()->previous();
            $parsedUrl = parse_url($previousUrl);

            $tab = null;
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                $tab = $queryParams['tab'] ?? null;
            }
            if ($tab == '-home-tab') {
                return [
                    Action::make('Save Home Settings')
                        ->label(__('messages.common.save'))
                        ->action('saveHomeSettings'),
                ];
            } elseif ($tab == '-about-us-tab') {

                return [
                    Action::make('Save About Us Settings')
                        ->label(__('messages.common.save'))
                        ->action('saveAboutUsSettings'),
                ];
            } elseif ($tab == '-appointment-tab') {
                return [
                    Action::make('Save Appointment Settings')
                        ->label(__('messages.common.save'))
                        ->action('saveAppointmentSettings'),
                ];
            } elseif ($tab == '-terms-conditions-tab') {

                return [
                    Action::make('Save Terms & Conditions')
                        ->label(__('messages.common.save'))
                        ->action('saveTermsConditionsSettings'),
                ];
            }
        }

        return [];
    }
}
