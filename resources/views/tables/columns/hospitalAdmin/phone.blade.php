<span class="text-sm">
    {{ empty($getRecord()->user->phone) ? __('messages.common.n/a') : $getRecord()->user->region_code . $getRecord()->user->phone }}
</span>
