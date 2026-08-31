<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources;

use App\Filament\HospitalAdmin\Clusters\Billings;
use App\Filament\HospitalAdmin\Clusters\Billings\Resources\EmployeePayrollResource\Pages;
use App\Models\EmployeePayroll;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmployeePayrollResource extends Resource
{
    protected static ?string $model = EmployeePayroll::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Billings::class;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Employee Payrolls')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Employee Payrolls')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.employee_payrolls');
    }

    public static function getLabel(): string
    {
        return __('messages.employee_payrolls');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant']) && getModuleAccess('Employee Payrolls')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant']) && getModuleAccess('Employee Payrolls')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant']) && getModuleAccess('Employee Payrolls')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('sr_no')
                            ->required()
                            ->validationAttribute(__('messages.employee_payroll.sr_no'))
                            ->label(__('messages.employee_payroll.sr_no').':')
                            ->default(function () {
                                $srNo = EmployeePayroll::orderBy('id', 'desc')->value('id');
                                $srNo = (! $srNo) ? 1 : $srNo + 1;

                                return $srNo;
                            })
                            ->numeric(),
                        Forms\Components\TextInput::make('payroll_id')
                            ->required()
                            ->validationAttribute(__('messages.employee_payroll.payroll_id'))
                            ->readonly()
                            ->default(function () {
                                $payrollId = strtoupper(Str::random(8));

                                return $payrollId;
                            })
                            ->label(__('messages.employee_payroll.payroll_id').':')
                            ->maxLength(191),
                        Forms\Components\Select::make('type')
                            ->required()
                            ->label(__('messages.employee_payroll.role').':')
                            ->placeholder(__('messages.sms.select_role'))
                            ->options(EmployeePayroll::TYPES)
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('owner_id', null);
                            })
                            ->native(false)
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.employee_payroll.role').' '.__('messages.fields.required'),
                            ]),
                        Forms\Components\Select::make('owner_id')
                            ->required()
                            ->placeholder(__('messages.employee_payroll.select_employee'))
                            ->disabled(function (callable $get) {
                                return ! $get('type') ? true : false;
                            })
                            ->label(__('messages.employee_payroll.employee').':')
                            ->options(function (callable $get) {
                                if (! $get('type')) {
                                    return null;
                                }

                                return EmployeePayroll::CLASS_TYPES[$get('type')]::with('user')->where('tenant_id', getLoggedInUser()->tenant_id)
                                    ->get()->where('user.status', '=', 1)->pluck('user.full_name', 'id');
                            })->preload()
                            ->native(false)
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.employee_payroll.employee').' '.__('messages.fields.required'),
                            ]),
                        Forms\Components\Select::make('month')
                            ->required()
                            ->placeholder(__('messages.employee_payroll.month'))
                            ->label(__('messages.employee_payroll.month').':')
                            ->options(EmployeePayroll::MONTHS)
                            ->native(false)
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.employee_payroll.month').' '.__('messages.fields.required'),
                            ]),
                        Forms\Components\TextInput::make('year')
                            ->required()
                            ->validationAttribute(__('messages.employee_payroll.year'))
                            ->placeholder(__('messages.employee_payroll.year'))
                            ->label(__('messages.employee_payroll.year').':')
                            ->numeric()
                            ->maxLength(4),
                        Forms\Components\Select::make('status')
                            ->required()
                            ->placeholder(__('messages.common.select_status'))
                            ->label(__('messages.common.status').':')
                            ->options(EmployeePayroll::STATUS)
                            ->native(false)
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.common.status').' '.__('messages.fields.required'),
                            ]),
                        Forms\Components\TextInput::make('basic_salary')
                            ->required()
                            ->validationAttribute(__('messages.employee_payroll.basic_salary'))
                            ->label(__('messages.employee_payroll.basic_salary').':')
                            ->live()
                            ->debounce(350)
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                $set('net_salary', (int) $state + (int) $get('allowance') - (int) $get('deductions'));
                            })
                            ->placeholder(__('messages.employee_payroll.basic_salary'))
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\TextInput::make('allowance')
                            ->label(__('messages.employee_payroll.allowance').':')
                            ->placeholder(__('messages.employee_payroll.allowance'))
                            ->live()
                            ->debounce(350)
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                $set('net_salary', (int) $get('basic_salary') + (int) $state - (int) $get('deductions'));
                            })
                            ->required()
                            ->validationAttribute(__('messages.employee_payroll.allowance'))
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\TextInput::make('deductions')
                            ->required()
                            ->validationAttribute(__('messages.employee_payroll.deductions'))
                            ->live()
                            ->debounce(350)
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                $set('net_salary', (int) $get('basic_salary') + (int) $get('allowance') - (int) $state);
                            })
                            ->label(__('messages.employee_payroll.deductions').':')
                            ->placeholder(__('messages.employee_payroll.deductions'))
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\TextInput::make('net_salary')
                            ->required()
                            ->readonly()
                            ->label(__('messages.employee_payroll.net_salary').':')
                            ->placeholder(__('messages.employee_payroll.net_salary'))
                            ->validationAttribute(__('messages.employee_payroll.net_salary'))
                            ->numeric()
                            ->minValue(1),
                    ])->columns(4),

            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant']) && ! getModuleAccess('Employee Payrolls')) {
            abort(404);
        }
        $table = $table->modifyQueryUsing(function (Builder $query) {
            $query->whereTenantId(auth()->user()->tenant_id);

            return $query;
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('sr_no')
                    ->label(__('messages.employee_payroll.sr_no'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payroll_id')
                    ->label(__('messages.employee_payroll.payroll_id'))
                    ->sortable()
                    ->badge()
                    ->searchable(),
                // SpatieMediaLibraryImageColumn::make('owner.user.profile')
                //     ->rounded()
                //     ->defaultImageUrl(function ($record) {
                //         if (!$record->owner->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                //             return getUserImageInitial($record->id, $record->owner->user->full_name);
                //         }
                //     })
                //     ->collection(User::COLLECTION_PROFILE_PICTURES)
                //     ->label(__('messages.employee_payroll.employee'))
                //     ->width(50)
                //     ->height(50),
                Tables\Columns\TextColumn::make('owner_id')
                    ->formatStateUsing(function (EmployeePayroll $record) {
                        return $record->owner->user->full_name;
                    })
                    ->searchable()
                    ->label(__('messages.employee_payroll.employee'))
                    ->description(function (EmployeePayroll $record) {
                        return $record->owner->user->department->name;
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('month')
                    ->label(__('messages.employee_payroll.month'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('year')
                    ->label(__('messages.employee_payroll.year'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_salary')
                    ->label(__('messages.employee_payroll.net_salary'))
                    ->formatStateUsing(function (EmployeePayroll $record) {
                        return getCurrencyFormat($record->net_salary);
                    })
                    ->sortable()->alignRight(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label(__('messages.common.status'))
                    ->formatStateUsing(function (EmployeePayroll $record) {
                        if ($record->status == 1) {
                            return __('messages.employee_payroll.paid');
                        } else {
                            return __('messages.employee_payroll.unpaid');
                        }
                    })
                    ->color(fn (EmployeePayroll $record) => $record->status == 1 ? 'success' : 'danger')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('messages.user.status').':')
                    ->options([
                        '' => __('messages.filter.all'),
                        1 => __('messages.paid'),
                        0 => __('messages.unpaid'),
                    ])
                    ->native(false),
            ])
            ->recordUrl(null)
            ->actions([
                Tables\Actions\ViewAction::make()->color('info')->iconButton()->extraAttributes(['class' => 'hidden']),
                Tables\Actions\EditAction::make()->iconButton()->successNotificationTitle(__('messages.flash.employee_payroll_updated')),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function (EmployeePayroll $record) {
                        if (! canAccessRecord(EmployeePayroll::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.employee_payroll_not_found'))
                                ->send();
                        }

                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.flash.employee_payroll_deleted'))
                            ->send();
                    })
                    ->successNotificationTitle(__('messages.flash.employee_payroll_deleted')),
            ])->actionsColumnLabel(__('messages.common.action'))
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('sr_no')
                    ->label(__('messages.employee_payroll.sr_no').':'),
                TextEntry::make('payroll_id')
                    ->label(__('messages.employee_payroll.payroll_id').':'),
                TextEntry::make('type_string')
                    ->label(__('messages.employee_payroll.role').':'),
                TextEntry::make('owner.user.full_name')
                    ->label(__('messages.employee_payroll.employee').':'),
                TextEntry::make('month')
                    ->label(__('messages.employee_payroll.month').':'),
                TextEntry::make('year')
                    ->label(__('messages.employee_payroll.year').':'),
                TextEntry::make('status')
                    ->badge()
                    ->formatStateUsing(function ($record) {
                        if ($record->status == 1) {
                            return __('messages.employee_payroll.paid');
                        } else {
                            return __('messages.employee_payroll.unpaid');
                        }
                    })
                    ->color(fn ($record) => $record->status == 1 ? 'success' : 'danger')
                    ->label(__('messages.common.status').':'),
                TextEntry::make('basic_salary')
                    ->formatStateUsing(fn ($state) => getCurrencyFormat($state, 2))
                    ->label(__('messages.employee_payroll.basic_salary').':'),
                TextEntry::make('allowance')
                    ->formatStateUsing(fn ($state) => getCurrencyFormat($state, 2))
                    ->label(__('messages.employee_payroll.allowance').':'),
                TextEntry::make('deductions')
                    ->formatStateUsing(fn ($state) => getCurrencyFormat($state, 2))
                    ->label(__('messages.employee_payroll.deductions').':'),
                TextEntry::make('net_salary')
                    ->formatStateUsing(fn ($state) => getCurrencyFormat($state, 2))
                    ->label(__('messages.employee_payroll.net_salary').':'),
                TextEntry::make('created_at')
                    ->formatStateUsing(fn ($state) => $state->diffForHumans())
                    ->label(__('messages.common.created_at').':'),
                TextEntry::make('updated_at')
                    ->formatStateUsing(fn ($state) => $state->diffForHumans())
                    ->label(__('messages.common.last_updated').':'),
            ])->columns(3);
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
            'index' => Pages\ListEmployeePayrolls::route('/'),
            'create' => Pages\CreateEmployeePayroll::route('/create'),
            'edit' => Pages\EditEmployeePayroll::route('/{record}/edit'),
        ];
    }
}
