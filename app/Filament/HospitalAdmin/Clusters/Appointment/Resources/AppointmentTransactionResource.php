<?php

namespace App\Filament\HospitalAdmin\Clusters\Appointment\Resources;

use App\Filament\HospitalAdmin\Clusters\Appointment;
use App\Filament\HospitalAdmin\Clusters\Appointment\Resources\AppointmentTransactionResource\Pages;
use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\Appointment as AppointmentModel;
use App\Models\AppointmentTransaction;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AppointmentTransactionResource extends Resource
{
    protected static ?string $model = AppointmentTransaction::class;

    protected static ?string $cluster = Appointment::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 3;

    public static function getLabel(): string
    {
        return __('messages.common.appointment_transaction');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Appointments')) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist', 'Patient'])) {
            return true;
        }

        return false;
    }

    public static function table(Table $table): Table
    {
        $table = $table->modifyQueryUsing(function ($query) {
            $query->with([
                'appointment.patient.patientUser.media',
                'appointment.doctor.doctorUser.media',
                'appointment.doctor',
            ])->where('tenant_id', auth()->user()->tenant_id);
            if (! getLoggedinDoctor()) {
                if (getLoggedinPatient()) {
                    $patientId = auth()->user()->patient->id;
                    $query->whereHas('appointment', function ($q) use ($patientId) {
                        $q->where('patient_id', $patientId);
                    });
                }
            } else {
                $doctorId = getLoggedInUser()->owner_id;
                $query->whereHas('appointment', function ($q) use ($doctorId) {
                    $q->where('doctor_id', $doctorId);
                });
            }
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('appointment.patient.patientUser.profile')
                    ->label(__('messages.role.patient'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! $record->appointment->patient->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->appointment->patient->user->full_name);
                        }
                    })
                    ->sortable(['first_name'])
                    ->url(fn ($record) => PatientResource::getUrl('view', ['record' => $record->appointment->patient->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('appointment.patient.patientUser.full_name')
                    ->label('')
                    ->description(fn ($record) => $record->appointment->patient->patientUser->email ?? __('messages.common.n/a'))
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(fn ($record) => '<a href="'.PatientResource::getUrl('view', ['record' => $record->appointment->patient->id]).'" class="hoverLink">'.$record->appointment->patient->patientUser->full_name.'</a>')
                    ->html()
                    ->searchable(['users.first_name', 'users.last_name']),
                SpatieMediaLibraryImageColumn::make('appointment.doctor.doctorUser.profile')
                    ->label(__('messages.role.doctor'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! $record->appointment->doctor->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->appointment->doctor->user->full_name);
                        }
                    })
                    ->sortable(['first_name'])
                    ->url(fn ($record) => DoctorResource::getUrl('view', ['record' => $record->appointment->doctor->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('appointment.doctor.doctorUser.full_name')
                    ->label('')
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn ($record) => $record->appointment->doctor->doctorUser->email ?? __('messages.common.n/a'))
                    ->formatStateUsing(fn ($record) => '<a href="'.DoctorResource::getUrl('view', ['record' => $record->appointment->doctor->id]).'" class="hoverLink">'.$record->appointment->doctor->doctorUser->full_name.'</a>')
                    ->html()
                    ->searchable(['users.first_name', 'users.last_name']),
                TextColumn::make('appointment.opd_date')
                    ->label(__('messages.opd_patient.appointment_date'))
                    ->sortable()
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $time = \Carbon\Carbon::parse($record->appointment->opd_date)->isoFormat('LT');
                        $date = \Carbon\Carbon::parse($record->appointment->opd_date)->translatedFormat('jS M, Y');

                        return "<div class='text-center'><span>{$time}</span><br><span>{$date}</span></div>";
                    })
                    ->html()
                    ->searchable(),
                TextColumn::make('appointment.payment_type')
                    ->label(__('messages.purchase_medicine.payment_mode'))
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $paymentType = $record->transaction_type ?: $record->appointment?->payment_type;

                        return AppointmentModel::paymentModeLabel($paymentType);
                    })
                    ->color(function ($record) {
                        $paymentType = $record->transaction_type ?: $record->appointment?->payment_type;

                        return AppointmentModel::paymentModeColor($paymentType);
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('appointment.doctor.appointment_charge')
                    ->label(__('messages.ambulance_call.amount'))
                    ->sortable()
                    ->formatStateUsing(fn ($record) => getCurrencyFormat($record->appointment->doctor->appointment_charge))
                    ->searchable()
                    ->alignRight(),
                TextColumn::make('created_at')
                    ->label(__('messages.common.created_at'))
                    ->sortable()
                    ->badge()
                    ->getStateUsing(function ($record) {
                        return \Carbon\Carbon::parse($record->created_at)->translatedFormat('jS M, Y');
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->label(__('messages.purchase_medicine.payment_mode'))
                    ->options(AppointmentModel::paymentModeOptions())
                    ->native(false),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')->label(__('messages.appointment.start_date')),
                        \Filament\Forms\Components\DatePicker::make('created_until')->label(__('messages.appointment.end_date')),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            // ->recordAction(null)
            ->recordUrl(null)
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointmentTransactions::route('/'),
            'create' => Pages\CreateAppointmentTransaction::route('/create'),
            'edit' => Pages\EditAppointmentTransaction::route('/{record}/edit'),
        ];
    }
}
