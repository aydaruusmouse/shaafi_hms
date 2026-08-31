<?php

namespace App\Livewire;

use App\Models\User;
use Exception;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class EditProfile extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public $first_name;

    public $last_name;

    public $email;

    public $phone;

    public function mount()
    {
        $user = Auth::user();
        $this->form->fill($user->toArray());
    }

    public function render()
    {
        return view('livewire.edit-profile');
    }

    public function form(Form $form): Form
    {
        $form->model = Auth::user();

        return $form
            ->schema([
                TextInput::make('first_name')
                    ->label(__('messages.user.first_name').':')
                    ->placeholder(__('messages.user.first_name'))
                    ->required()
                    ->maxLength(500),

                TextInput::make('last_name')
                    ->label(__('messages.user.last_name').':')
                    ->placeholder(__('messages.user.last_name'))
                    ->required()
                    ->maxLength(500),

                TextInput::make('email')
                    ->unique('users', 'email', ignoreRecord: true)
                    ->label(__('messages.user.email').':')
                    ->validationMessages([
                        'unique' => __('messages.user.email').' '.__('messages.common.is_already_exists'),
                    ])
                    ->placeholder(__('messages.user.email'))
                    ->email()
                    ->required()
                    ->maxLength(255),

                PhoneInput::make('phone')
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
                    ->label(__('messages.user.phone').':')
                    ->placeholder(__('messages.user.phone'))
                    ->required(),

                SpatieMediaLibraryFileUpload::make('profile')
                    ->label(__('messages.common.profile').':')
                    ->avatar()
                    ->disk(config('app.media_disk'))
                    ->collection(User::COLLECTION_PROFILE_PICTURES),
            ])->columns(2)
            ->statePath('data');
    }

    public function save()
    {
        try {
            $data = $this->form->getState();
            User::first()->update($data);
            Notification::make()
                ->success()
                ->title(__('messages.flash.profile_update'))
                ->send();
            $this->js('window.location.reload()');
        } catch (Exception $exception) {
            Notification::make()
                ->danger()
                ->title($exception->getMessage())
                ->send();
        }
    }
}
