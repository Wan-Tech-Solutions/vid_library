<nav x-data="{ open: false }" class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 shadow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center gap-4">
            <!-- Brand + Primary Links -->
            <div class="flex items-center gap-3">
                <a href="/" class="text-xl font-bold text-gray-800 dark:text-white">VideoLibrary</a>

                <button type="button" onclick="toggleDarkMode()"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 lg:hidden">
                    <i class="ri-contrast-line mr-1"></i>
                    Theme
                </button>

                <div class="hidden lg:flex items-center gap-4">
                    <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                        <i class="ri-home-line mr-1"></i> Dashboard
                    </x-nav-link>

                    @if (in_array(auth()->user()->role, ['admin', 'superadmin']))
                        <div class="relative group">
                            <button type="button"
                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                                <i class="ri-shield-user-line mr-1"></i> Admin
                                <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div
                                class="absolute z-50 left-0 mt-2 w-52 rounded-md border border-gray-200 bg-white shadow-lg transition-all duration-200 group-hover:visible group-hover:opacity-100 dark:border-gray-700 dark:bg-gray-800 invisible opacity-0">
                                <a href="{{ route('admin.dashboard') }}"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                    <i class="ri-dashboard-line mr-2"></i> Dashboard
                                </a>
                                <a href="{{ route('admin.logs.index') }}"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                    <i class="ri-file-list-3-line mr-2"></i> Activity Logs
                                </a>
                                <a href="{{ route('admin.users.index') }}"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                    <i class="ri-user-settings-line mr-2"></i> Admin Users
                                </a>
                                <a href="{{ route('account.password.edit') }}"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                    <i class="ri-lock-password-line mr-2"></i> Change Password
                                </a>
                            </div>
                        </div>
                    @endif

                    <button type="button" onclick="toggleDarkMode()"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        <i class="ri-contrast-line mr-1"></i> Toggle Theme
                    </button>

                    <a href="{{ route('landing') }}" target="_blank"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Home Page
                    </a>
                </div>
            </div>

            <!-- Right -->
            <div class="hidden md:flex items-center gap-4">
                <div id="navbarCountdown" class="hidden text-sm font-semibold text-yellow-400"></div>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 hover:text-gray-900 dark:hover:text-white transition">
                            <span class="mr-2">
                                @auth {{ Auth::user()->name }} @endauth
                                @guest Guest @endguest
                            </span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 12h14M12 5l7 7-7 7" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Mobile Menu Button -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="p-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Nav Dropdown -->
    <div :class="{ 'block': open, 'hidden': !open }"
        class="hidden sm:hidden bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @if (in_array(auth()->user()->role, ['admin', 'superadmin']))
                <x-responsive-nav-link :href="route('admin.logs.index')" :active="request()->routeIs('admin.logs.index')">
                    {{ __('Activity Logs') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                    {{ __('Admin Users') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('account.password.edit')" :active="request()->routeIs('account.password.*')">
                    {{ __('Change Password') }}
                </x-responsive-nav-link>
            @endif

            <a href="{{ route('landing') }}" target="_blank"
                class="block px-4 py-2 text-base font-medium text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                {{ __('Home Page') }}
            </a>

            <button type="button" onclick="toggleDarkMode()"
                class="mx-4 mt-2 block w-full rounded-md border border-gray-300 bg-white px-4 py-2 text-left text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                <i class="ri-contrast-line mr-2"></i>{{ __('Toggle Theme') }}
            </button>
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200">
                    @auth {{ Auth::user()->name }} @endauth
                    @guest Guest @endguest
                </div>
                <div class="font-medium text-sm text-gray-500 dark:text-gray-400">
                    @if (Auth::check())
                        {{ Auth::user()->email }}
                    @endif
                </div>
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

<script>
    function toggleDarkMode() {
        const html = document.querySelector('html');
        html.classList.toggle('dark');
        localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (localStorage.getItem('theme') === 'dark') {
            document.querySelector('html').classList.add('dark');
        }
    });
</script>
