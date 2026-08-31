<div class="d-flex align-items-center">
    <a target="_blank" href="{{ route('medicine.bill.pdf', [$billId]) }}"
        class="btn btn-success text-white {{ $language == 'ar' ? 'me-auto' : 'ms-auto' }}">
        {{ __('messages.bill.print_bill') }}
    </a>
</div>
