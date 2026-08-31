<div>
    <footer class="bottom-0 left-0 z-20 w-full p-4 flex items-center justify-between md:p-6">
        <span class="text-sm text-gray-500 sm:text-center dark:text-gray-400">
            {{ __('messages.web_menu.all_rights_reserved') }} © <strong>{{ date('Y') }}</strong> <a
                class="underline text-primary-500" href="{{ config('app.url') }}"
                class="hover:underline">{{ config('app.name') }}</a>
        </span>
        <span class="text-sm text-gray-500 sm:text-center dark:text-gray-400">
            v{{ getCurrentVersion() }}
        </span>
    </footer>
</div>
<style>
    .fi-main-ctn {
        min-height: 100vh;

        .fi-main {
            flex-grow: 1;
        }
    }
</style>
