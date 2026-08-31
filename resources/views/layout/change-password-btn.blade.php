<div class="fi-dropdown-header flex w-full gap-2 p-1 text-sm  fi-dropdown-header-color-gray fi-color-gray">
    <button
        class="fi-dropdown-list-item flex w-full items-center gap-2 whitespace-nowrap rounded-md p-2 text-sm transition-colors duration-75 outline-none disabled:pointer-events-none disabled:opacity-70 hover:bg-gray-50 focus-visible:bg-gray-50 dark:hover:bg-white/5 dark:focus-visible:bg-white/5 fi-dropdown-list-item-color-gray fi-color-gray"
        x-on:click ="$dispatch('open-modal', {id: 'change-password-modal'})">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
            class="w-5 h-5 text-gray-400 fi-dropdown-list-item-icon dark:text-gray-500">
            <path fill-rule="evenodd"
                d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z"
                clip-rule="evenodd" />
        </svg>
        <span class="fi-dropdown-list-item-label flex-1 truncate text-start text-gray-700 dark:text-gray-200"
            style="">
            Change Password
        </span>
    </button>
</div>
