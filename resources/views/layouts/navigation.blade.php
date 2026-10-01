<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-6">
                <a href="{{ route('home') }}" class="font-bold text-green-700 text-lg">AgroOferta</a>
                <div class="hidden sm:flex gap-6 text-sm text-gray-700">
                    <a href="{{ route('home') }}" class="hover:text-green-700">Insumos</a>
                    @auth
                        <a href="{{ route('listings.mine') }}" class="hover:text-green-700">Meus anúncios</a>
                        <a href="{{ route('negotiations.index') }}" class="hover:text-green-700">Negociações</a>
                        <a href="{{ route('listings.create') }}" class="hover:text-green-700">Anunciar</a>
                    @endauth
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center">
                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 text-sm text-gray-600 hover:text-gray-800">
                                {{ Auth::user()->name }}
                                <svg class="ms-1 h-4 w-4 fill-current" viewBox="0 0 20 20"><path d="M5.3 7.3a1 1 0 011.4 0L10 10.6l3.3-3.3a1 1 0 111.4 1.4l-4 4a1 1 0 01-1.4 0l-4-4a1 1 0 010-1.4z"/></svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('account.edit')">Meu contato</x-dropdown-link>
                            <x-dropdown-link :href="route('profile.edit')">Perfil</x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Sair</x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}" class="text-sm text-gray-700 hover:text-green-700 me-4">Entrar</a>
                    <a href="{{ route('register') }}" class="text-sm bg-green-700 text-white px-3 py-2 rounded-md">Cadastrar</a>
                @endauth
            </div>

            <div class="flex items-center sm:hidden">
                <button @click="open = !open" class="p-2 text-gray-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open}" stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{'hidden': !open, 'inline-flex': open}" class="hidden" stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': !open}" class="hidden sm:hidden border-t border-gray-100">
        <div class="py-2 px-4 space-y-1 text-sm">
            <a href="{{ route('home') }}" class="block py-2">Insumos</a>
            @auth
                <a href="{{ route('listings.mine') }}" class="block py-2">Meus anúncios</a>
                <a href="{{ route('negotiations.index') }}" class="block py-2">Negociações</a>
                <a href="{{ route('listings.create') }}" class="block py-2">Anunciar</a>
                <a href="{{ route('account.edit') }}" class="block py-2">Meu contato</a>
                <a href="{{ route('profile.edit') }}" class="block py-2">Perfil ({{ Auth::user()->name }})</a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="block py-2 w-full text-left">Sair</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block py-2">Entrar</a>
                <a href="{{ route('register') }}" class="block py-2">Cadastrar</a>
            @endauth
        </div>
    </div>
</nav>
