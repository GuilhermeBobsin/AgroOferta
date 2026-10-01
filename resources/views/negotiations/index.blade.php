<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Minhas negociações</h2></x-slot>

    <div class="max-w-3xl mx-auto p-4 space-y-2">
        @forelse ($negotiations as $n)
            <a href="{{ route('negotiations.show', $n) }}" class="block bg-white rounded-lg shadow p-3 hover:shadow-md">
                <p class="font-semibold">{{ $n->listing->title }}</p>
                <p class="text-sm text-gray-500">
                    {{ auth()->id() === $n->buyer_id ? 'Vendedor: '.$n->seller->name : 'Comprador: '.$n->buyer->name }}
                    · {{ $n->updated_at->diffForHumans() }}
                </p>
            </a>
        @empty
            <p class="text-gray-500">Você ainda não tem negociações.</p>
        @endforelse
    </div>
</x-app-layout>
