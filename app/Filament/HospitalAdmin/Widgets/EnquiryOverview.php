<?php

namespace App\Filament\HospitalAdmin\Widgets;

use App\Models\Enquiry;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class EnquiryOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        $enriquies = Enquiry::where('status', 0)->where('tenant_id', getLoggedInUser()->tenant_id)->count();

        return $enriquies > 0 && auth()->user()->hasRole('Admin');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('messages.visitor_filter.enquiry'))
            ->query(Enquiry::where('status', 0)->where('tenant_id', getLoggedInUser()->tenant_id)->latest()->take(5))
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('messages.common.name'))
                    ->formatStateUsing(
                        fn ($record) => '<a href="'.route('filament.hospitalAdmin.enquiries.resources.enquiries.view', $record->id).'"class="hoverLink">'.$record->full_name.'</a>'
                    )
                    ->html()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('messages.user.email')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('messages.common.created_on'))
                    ->getStateUsing(fn ($record) => $record->created_at ? \Carbon\Carbon::parse($record->created_at)->translatedFormat('jS M, Y') : __('messages.common.n/a'))
                    ->badge(),
            ])->paginated(false)
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }
}
