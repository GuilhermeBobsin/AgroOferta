<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Obrigado por se cadastrar! Confirme seu e-mail pelo link que enviamos. Se não recebeu, você pode pedir um novo abaixo.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            Um novo link de confirmação foi enviado para o e-mail informado no cadastro.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    Reenviar e-mail de confirmação
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Sair
            </button>
        </form>
    </div>
</x-guest-layout>
