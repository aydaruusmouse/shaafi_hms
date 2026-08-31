<?php

namespace App\Filament\HospitalAdmin\Clusters\SmsMail\Pages;

use App\Filament\HospitalAdmin\Clusters\SmsMail;
use App\Models\Mail as MailModel;
use App\Models\User;
use App\Repositories\MailRepository;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;

class Mail extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.hospital-admin.clusters.sms-mail.pages.mail';

    protected static ?string $cluster = SmsMail::class;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Mail')) {
            return false;
        } elseif (! auth()->user()->hasRole('Admin') && ! getModuleAccess('Mail')) {
            return false;
        }

        return true;
    }

    public static function canAccess(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Case Manager', 'Receptionist']) && getModuleAccess('Mail')) {
            return true;
        }

        return false;
    }

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    public ?array $data = [
        'to' => '',
        'subject' => '',
        'message' => '',
        'attachments' => null,
    ];

    public static function getNavigationLabel(): string
    {
        return __('messages.mail');
    }

    public static function getLabel(): string
    {
        return __('messages.mail');
    }

    public function form(Form $form): Form
    {
        // $form->model = MailModel::first();
        return $form
            // ->model(MailModel::first())
            ->schema([
                Section::make()->schema([
                    TextInput::make('to')
                        ->label(__('messages.email.to').':')
                        ->required()
                        ->validationAttribute(__('messages.email.to'))
                        ->email()
                        ->placeholder(__('messages.email.to').':'),
                    TextInput::make('subject')
                        ->label(__('messages.email.subject').':')
                        ->required()
                        ->validationAttribute(__('messages.email.subject'))
                        ->placeholder(__('messages.email.subject').':'),
                    Textarea::make('message')
                        ->label(__('messages.email.message').':')
                        ->required()
                        ->validationAttribute(__('messages.email.message'))
                        ->placeholder(__('messages.email.message').':'),
                    SpatieMediaLibraryFileUpload::make('attachments')
                        ->label(__('messages.email.attachment').':')
                        ->disk(config('app.media_disk'))
                        ->avatar()
                        ->collection(User::COLLECTION_MAIL_ATTACHMENTS),
                    Hidden::make('avatar_remove'),
                ])->columns(2),
            ])
            ->statePath('data');
    }

    public function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('messages.common.save'))
                ->submit('save'),

            Action::make('cancel')
                ->label(__('messages.common.cancel'))
                ->color('secondary')
                ->action('resetForm'),
        ];
    }

    public function resetForm()
    {
        $this->js('window.location.reload()');
    }

    public function save(): void
    {
        $input = $this->data;
        if (isset($input['attachments']) && is_array($input['attachments'])) {
            $input['attachments'] = array_values($input['attachments'])[0];
        }

        app(MailRepository::class)->store($input);
        Notification::make()
            ->title(__('messages.flash.mail_sent'))
            ->success()
            ->send();
        $this->afterSave();
    }

    protected function afterSave()
    {
        $this->js('window.location.reload()');
    }
}
