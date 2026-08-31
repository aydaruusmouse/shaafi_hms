<?php

namespace App\Http\Controllers;

use App\Mail\InvoicePatientMail;
use App\Models\Invoice;
use App\Models\Patient;
use App\Repositories\InvoiceRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;

class InvoiceController extends Controller
{
    private $invoiceRepository;

    public function __construct(InvoiceRepository $invoiceRepo)
    {
        $this->invoiceRepository = $invoiceRepo;
    }

    public function convertToPdf(Invoice $invoice)
    {
        if (! canAccessRecord(Invoice::class, $invoice->id)) {
            return Redirect::back();
        }
        // if (getLoggedInUser()->hasRole('Patient')) {
        // if (getLoggedInUser()->owner_id != $invoice->patient_id) {
        //     return Redirect::back();
        // }
        // }
        if (app()->getLocale() == 'zh') {
            app()->setLocale('en');
        }
        $invoice->invoiceItems;
        $data = $this->invoiceRepository->getSyncListForCreate($invoice->id);
        $data['invoice'] = $invoice;
        $data['currencySymbol'] = getCurrencySymbol();
        if (isset($data['setting']['app_logo']) && ! str_starts_with($data['setting']['app_logo'], 'http')) {
            $data['setting']['app_logo'] = url('/').'/'.ltrim($data['setting']['app_logo'], '/');
        }

        $imageData = Http::get($data['setting']['app_logo'])->body();
        $imageType = pathinfo($data['setting']['app_logo'], PATHINFO_EXTENSION);
        $base64Image = 'data:image/'.$imageType.';base64,'.base64_encode($imageData);

        $data['setting']['app_logo'] = $base64Image;

        $pdf = Pdf::loadView('invoices.invoice_pdf', $data);

        return $pdf->stream('invoice.pdf');
    }

    public function sendMail(Invoice $invoice)
    {
        $patient = Patient::with('user')->whereId($invoice->patient_id)->first();

        $mailData = [
            'invoice_id' => $invoice->id,
            'patient_name' => $patient->user->full_name,
            'invoice_number' => $invoice->invoice_id,
            'invoice_date' => Carbon::parse($invoice->invoice_date)->format('d/m/Y'),
            'discount' => $invoice->discount.'%',
            'amount' => getCurrencySymbol().' '.number_format($invoice->amount - ($invoice->amount * $invoice->discount / 100), 2),
            'status' => $invoice->status == 1 ? 'Paid' : 'Pending',
        ];

        Mail::to($patient->user->email)
            ->send(new InvoicePatientMail(
                'emails.invoice_patient_mail',
                __('messages.new_change.patient_invoice_bill'),
                $mailData
            ));

        Notification::make()
            ->title(__('messages.new_change.patient_mail_send'))
            ->success()
            ->send();

        return Redirect::back();
    }
}
