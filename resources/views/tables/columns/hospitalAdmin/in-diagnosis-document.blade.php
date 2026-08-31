@if ($getRecord()->opd_diagnosis_document_url)
    <a href="{{ $getRecord()->opd_diagnosis_document_url }}" class="text-sm text-primary-500 hover:text-primary-500"
        download>Download</a>
@else
    <span class="text-sm">{{ __('messages.common.n/a') }}</span>
@endif
