<?php

namespace App\Filament\Exports;

use App\Models\CallLog;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CallLogExporter extends Exporter
{
    protected static ?string $model = CallLog::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('messages.common.no')),
            ExportColumn::make('name'),
            ExportColumn::make('phone'),
            ExportColumn::make('date')
                ->label(__('messages.enquiry.received_on')),
            ExportColumn::make('follow_up_date'),
            ExportColumn::make('call_type'),
            ExportColumn::make('note'),

        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your call log export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
