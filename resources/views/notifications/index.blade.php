<x-app-layout>
    <x-slot name="header">
        <p class="text-sm font-semibold text-green-800">Acompanhe suas conversas</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Notificações</h1>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-4 px-4 py-8">
        @if (session('status'))
            <p role="status" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">{{ session('status') }}</p>
        @endif

        @if ($notifications->isNotEmpty())
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm text-gray-500">Mensagens de compradores e vendedores</p>
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @method('PATCH')
                    <button class="text-sm font-semibold text-green-800 hover:underline">Marcar todas como lidas</button>
                </form>
            </div>
            <div class="divide-y divide-gray-100 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                @foreach ($notifications as $notification)
                    <a href="{{ route('negotiations.show', $notification->message->negotiation_id) }}"
                        @class(['flex gap-4 px-5 py-5 transition hover:bg-gray-100', 'bg-green-50' => ! $notification->read_at])>
                        <span aria-hidden="true" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 font-semibold text-green-900">{{ mb_strtoupper(mb_substr($notification->message->user->name, 0, 1)) }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold text-gray-900">{{ $notification->message->user->name }} enviou uma mensagem</span>
                            <span class="mt-1 block text-sm font-medium text-green-800">{{ $notification->message->negotiation->listing->title }}</span>
                            <span class="mt-1 block break-words text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($notification->message->body, 180) }}</span>
                            <time datetime="{{ $notification->created_at->toIso8601String() }}" class="mt-2 block text-xs text-gray-500">{{ $notification->created_at->diffForHumans() }}</time>
                        </span>
                        @unless ($notification->read_at)
                            <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-green-700"><span class="sr-only">Não lida</span></span>
                        @endunless
                    </a>
                @endforeach
            </div>
            {{ $notifications->links() }}
        @else
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <h2 class="text-lg font-semibold text-gray-900">Tudo em dia por aqui</h2>
                <p class="mt-2 text-sm text-gray-500">As mensagens que você receber nas negociações aparecerão aqui.</p>
                <a href="{{ route('negotiations.index') }}" class="mt-5 inline-flex rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-900">Ver minhas negociações</a>
            </div>
        @endif
    </div>
</x-app-layout>
