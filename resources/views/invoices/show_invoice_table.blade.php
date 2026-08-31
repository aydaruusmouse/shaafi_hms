<div>
    <div class="overflow-x-auto mx-auto my-4">
        <table class="min-w-full bg-gray-800 border border-black-700 mt-4">
            <thead>
                <tr class="border-b border-black-700">
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                        {{ __('messages.account.account') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                        {{ __('messages.invoice.description') }}</th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider {{ getCurrentLoginUserLanguageName() == 'ar' ? 'text-left' : 'text-right' }}">
                        {{ __('messages.invoice.qty') }}</th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider {{ getCurrentLoginUserLanguageName() == 'ar' ? 'text-left' : 'text-right' }}">
                        {{ __('messages.invoice.price') }}</th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider {{ getCurrentLoginUserLanguageName() == 'ar' ? 'text-left' : 'text-right' }}">
                        {{ __('messages.invoice.amount') }}</th>
                </tr>
            </thead>
            <tbody class="bg-gray-800 divide-y divide-gray-700">
                @foreach ($invoice->invoiceItems as $index => $invoiceItem)
                    <tr class="hover:bg-gray-700">
                        <td
                            class="px-6 py-4 text-sm  {{ getCurrentLoginUserLanguageName() == 'ar' ? 'text-right' : 'text-left' }}">
                            {{ $invoiceItem->account->name }}</td>
                        <td
                            class="px-6 py-4 text-sm {{ getCurrentLoginUserLanguageName() == 'ar' ? 'text-right' : 'text-left' }}">
                            {!! $invoiceItem->description != '' ? nl2br(e($invoiceItem->description)) : __('messages.common.n/a') !!}</td>
                        <td class="px-6 py-4 text-sm ">{{ $invoiceItem->quantity }}</td>
                        <td class="px-6 py-4 text-sm ">{{ getCurrencyFormat($invoiceItem->price) }}</td>
                        <td class="px-6 py-4 text-sm ">{{ getCurrencyFormat($invoiceItem->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="{{ getCurrentLoginUserLanguageName() == 'ar' ? 'me-lg-auto' : 'ms-lg-auto' }} flex justify-end">
        <div class="border-t">
            <table class="min-w-full bg-black-800  border-gray-700 shadow-none">
                <tbody class="bg-gray-800">
                    {{-- <tr class="border-b border-gray-700"> --}}
                    <td
                        class="px-6 py-2 text-sm  {{ getLoggedInUser()->thememode ? 'text-white' : 'text-900' }} overflow-hidden text-ellipsis whitespace-nowrap">
                        {{ __('messages.invoice.sub_total') . ' :' }}</td>
                    <td
                        class="px-6 py-2 pr-8 text-sm  {{ getCurrentLoginUserLanguageName() == 'ar' ? 'text-start' : 'text-end' }} pe-0 {{ getLoggedInUser()->thememode ? 'text-gray-300' : 'text-gray-900' }} overflow-hidden text-ellipsis whitespace-nowrap">
                        {{ getCurrencyFormat($invoice->amount) }}</td>
                    {{-- </tr> --}}
                    <tr class="border-gray-700">
                        <td
                            class="px-6 py-2 text-sm {{ getLoggedInUser()->thememode ? 'text-white' : 'text-900' }} overflow-hidden text-ellipsis whitespace-nowrap">
                            {{ __('messages.invoice.discount') . ' :' }}</td>
                        <td
                            class="px-6 py-2 pr-8 text-sm {{ getCurrentLoginUserLanguageName() == 'ar' ? 'text-start' : 'text-end' }} pe-0 {{ getLoggedInUser()->thememode ? 'text-gray-300' : 'text-gray-900' }} overflow-hidden text-ellipsis whitespace-nowrap">
                            {{ getCurrencyFormat(($invoice->amount * $invoice->discount) / 100) }}</td>
                    </tr>
                    <tr class="border-gray-700">
                        <td
                            class="px-6 py-2 text-sm {{ getLoggedInUser()->thememode ? 'text-white' : 'text-900' }} overflow-hidden text-ellipsis whitespace-nowrap">
                            {{ __('messages.invoice.total') . ' :' }}</td>
                        <td
                            class="px-6 py-2 pr-8 text-sm {{ getCurrentLoginUserLanguageName() == 'ar' ? 'text-start' : 'text-end' }} pe-0 {{ getLoggedInUser()->thememode ? 'text-gray-300' : 'text-gray-900' }} overflow-hidden text-ellipsis whitespace-nowrap">
                            {{ getCurrencyFormat($invoice->amount - ($invoice->amount * $invoice->discount) / 100) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
