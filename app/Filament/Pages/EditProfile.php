<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class EditProfile extends BaseEditProfile
{
    protected static string $view = 'filament.pages.edit-profile';

    public static function getLabel(): string
    {
        return 'Profile Details';
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        Section::make()
                            ->columns(7)
                            ->schema([
                                Group::make([
                                    SpatieMediaLibraryFileUpload::make('profile')
                                        ->label(__('messages.common.profile').':')
                                        ->collection(User::COLLECTION_PROFILE_PICTURES)
                                        ->image()
                                        ->disk(config('app.media_disk'))
                                        ->imagePreviewHeight(150)
                                        ->imageEditor('cropper')
                                        ->imageCropAspectRatio(null)
                                        ->imageEditorAspectRatios([
                                            null,
                                        ])
                                        ->inlineLabel(false)
                                        ->required()
                                        ->avatar(),
                                ]),
                                Group::make([
                                    TextInput::make('first_name')
                                        ->label(__('messages.user.first_name').':')
                                        ->placeholder(__('messages.user.first_name'))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('last_name')
                                        ->label(__('messages.user.last_name').':')
                                        ->placeholder(__('messages.user.last_name'))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('email')
                                        ->label(__('messages.user.email').':')
                                        ->unique('users', 'email', ignoreRecord: true)
                                        ->placeholder(__('messages.user.email'))
                                        ->email()
                                        ->validationMessages([
                                            'unique' => __('messages.user.email').' '.__('messages.common.is_already_exists'),
                                        ])
                                        ->required()
                                        ->maxLength(255),
                                    PhoneInput::make('phone')
                                        ->defaultCountry('IN')
                                        ->rules(function (Get $get) {
                                            return [
                                                'phone:AUTO,'.strtoupper($get('prefix_code')),
                                            ];
                                        })
                                        ->validationMessages([
                                            'phone' => __('messages.common.invalid_number'),
                                        ])
                                        ->label(__('messages.user.phone').':')
                                        ->placeholder(__('messages.user.phone')),
                                ])->columnSpan(6)->columns(2),
                            ]),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data'),
            ),
        ];
    }

    protected function afterSave(): void
    {
        $this->js('window.location.reload()');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'User profile updated successfully';
    }
}
