<?php

namespace App\Filament\HospitalAdmin\Pages;

use App\Models\NoticeBoard;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class NoticeBoards extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationIcon = 'fas-cog';

    protected static string $view = 'filament.hospital-admin.pages.notice-boards';

    public static function getNavigationLabel(): string
    {
        return __('messages.dashboard.notice_boards');
    }

    public static function getLabel(): string
    {
        return __('messages.dashboard.notice_boards');
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Doctor', 'Accountant', 'Case Manager', 'Pharmacist', 'Lab Technician', 'Nurse', 'Patient']) && ! getModuleAccess('Notice Boards')) {
            return false;
        }

        return ! auth()->user()->hasRole(['Admin', 'Receptionist']);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Doctor', 'Accountant', 'Case Manager', 'Pharmacist', 'Lab Technician', 'Nurse', 'Patient']) && ! getModuleAccess('Notice Boards')) {
            abort(404);
        }

        return $table
            ->query(NoticeBoard::where('tenant_id', auth()->user()->tenant_id))
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label(__('messages.notice_board.title'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('messages.common.created_at'))
                    ->formatStateUsing(
                        fn ($state) => Carbon::parse($state)->format('g:i A').'<br>'.Carbon::parse($state)->format('jS M, Y')
                    )
                    ->html()
                    ->badge()
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make()->iconButton()
                    ->modalCancelAction(false)
                    ->modalHeading(__('messages.notice_board.details'))
                    ->infolist([
                        Group::make()
                            ->schema([
                                TextEntry::make('title')
                                    ->label(__('messages.notice_board.title').':'),

                                TextEntry::make('description')
                                    ->label(__('messages.notice_board.description').':'),

                                TextEntry::make('created_at')
                                    ->label(__('messages.common.created_at').':')
                                    ->getStateUsing(fn ($record) => $record->created_at->diffForHumans() ?? __('messages.common.n/a')),

                                TextEntry::make('updated_at')
                                    ->label(__('messages.common.updated_at').':')
                                    ->getStateUsing(fn ($record) => $record->updated_at->diffForHumans() ?? __('messages.common.n/a')),

                            ])->columns(2),
                    ]),

            ])->actionsColumnLabel(__('messages.common.actions'));
    }
}
