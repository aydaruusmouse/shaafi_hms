<div>
    <x-filament::input.wrapper x-show="$store.sidebar.isOpen">
        <x-filament::input type="search" class="searchinsidebar" id="sidebar-search"
            placeholder="{{ __('messages.common.search') }}"
            style="
            background-image: url('{{ asset('images/search.svg') }}');
            background-repeat: no-repeat;
            background-position: 10px center;
            background-size: 20px;
            padding-left: 40px;
        "
            onkeyup="attachSearchEvent()" />

    </x-filament::input.wrapper>
    <span id="no-results" style="display: none; padding: 90px;" class="text-yellow"> No results </span>
    <script>
        function attachSearchEvent() {
            const searchInput = document.getElementById('sidebar-search');
            const noResultsDiv = document.getElementById('no-results');
            const menuItems = document.querySelectorAll('.fi-sidebar-item-button');
            const sidebarGroups = document.querySelectorAll('.fi-sidebar-group');
            const groupItems = document.querySelectorAll('.fi-sidebar-group-items');
            searchInput.addEventListener('input', function(event) {
                const query = event.target.value.toLowerCase();
                let found = false;
                menuItems.forEach(function(item) {
                    if (item.textContent.toLowerCase().includes(query)) {
                        item.style.display = '';
                        found = true;
                    } else {
                        item.style.display = 'none';
                    }
                });

                sidebarGroups.forEach(function(group) {
                    const groupLabel = group.getAttribute('data-group-label');
                    const groupItemButtons = group.querySelectorAll('.fi-sidebar-item-button');
                    let groupHasVisibleItems = false;

                    groupItemButtons.forEach(function(item) {
                        if (item.style.display !== 'none') {
                            groupHasVisibleItems = true;
                        }
                    });

                    if (groupHasVisibleItems) {
                        group.style.display = '';
                    } else {
                        group.style.display = 'none';
                    }
                });

                groupItems.forEach(function(groupItem) {
                    if (query !== '') {
                        groupItem.classList.remove('flex');
                    } else {
                        groupItem.classList.add('flex');
                    }
                });

                if (!found) {
                    noResultsDiv.style.display = 'block';
                } else {
                    noResultsDiv.style.display = 'none';
                }

                if (query === '') {
                    menuItems.forEach(function(item) {
                        item.style.display = '';
                    });
                    sidebarGroups.forEach(function(group) {
                        group.style.display = '';
                    });
                    noResultsDiv.style.display = 'none';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            attachSearchEvent();
        });
    </script>
</div>
