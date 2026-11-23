@php $unreadCount = auth()->user()->messageNotifications()->whereNull('read_at')->count(); @endphp

<div class="relative" x-data="notifications(@js(route('notifications.feed', [], false)), @js(route('notifications.read-all', [], false)), {{ $unreadCount }})"
    @click.outside="open = false" @keydown.escape.stop.prevent="open = false; $refs.trigger.focus()">
    <button type="button" x-ref="trigger" @click="toggle()" :aria-expanded="open.toString()"
        aria-controls="notification-panel" :aria-label="unreadCount ? `Notificações: ${unreadCount} não lidas` : 'Notificações'"
        class="relative flex h-10 w-10 items-center justify-center rounded-full text-gray-600 hover:bg-green-50 hover:text-green-800 focus:outline-none focus:ring-2 focus:ring-green-700">
        <svg aria-hidden="true" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 00-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
        </svg>
        <span x-cloak x-show="unreadCount > 0" x-text="unreadCount > 99 ? '99+' : unreadCount" aria-hidden="true"
            class="absolute -right-1 -top-0.5 min-w-5 rounded-full bg-red-600 px-1 text-center text-xs font-bold leading-5 text-white"></span>
    </button>
    <span class="sr-only" role="status" x-text="unreadCount ? `${unreadCount} notificações não lidas` : 'Nenhuma notificação não lida'"></span>

    <section id="notification-panel" x-cloak x-show="open" x-transition.origin.top.right aria-labelledby="notification-title"
        class="fixed left-4 right-4 top-16 z-50 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl sm:absolute sm:left-auto sm:right-0 sm:top-12 sm:w-96">
        <div class="flex items-center justify-between gap-2 border-b border-gray-100 px-4 py-4">
            <h2 id="notification-title" class="font-bold text-gray-900">Notificações</h2>
            <button type="button" x-show="unreadCount > 0" @click="readAll()" :disabled="marking || loading"
                class="text-xs font-semibold text-green-800 hover:underline disabled:opacity-50">Marcar todas como lidas</button>
        </div>
        <p x-show="error" x-text="error" role="alert" class="bg-amber-50 px-4 py-3 text-sm text-amber-900"></p>
        <p x-show="!loaded && !error" class="px-4 py-8 text-center text-sm text-gray-500">Carregando notificações...</p>
        <div x-show="loaded && notifications.length === 0" class="px-6 py-10 text-center">
            <p class="font-semibold text-gray-900">Tudo em dia por aqui</p>
            <p class="mt-2 text-sm text-gray-500">Quando alguém enviar uma mensagem, você será avisado aqui.</p>
        </div>
        <div class="max-h-[60vh] overflow-y-auto divide-y divide-gray-100">
            <template x-for="notification in notifications" :key="notification.id">
                <a :href="notification.url" class="flex gap-3 px-4 py-4 transition hover:bg-gray-100 focus:bg-gray-100"
                    :class="notification.unread ? 'bg-green-50' : 'bg-white'">
                    <span aria-hidden="true" class="mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-100 font-semibold text-green-900" x-text="notification.sender.slice(0, 1).toUpperCase()"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-gray-900"><span x-text="notification.sender"></span> enviou uma mensagem</span>
                        <span class="mt-0.5 block truncate text-xs font-medium text-green-800" x-text="notification.listing"></span>
                        <span class="mt-1 block break-words text-sm text-gray-600" x-text="notification.preview"></span>
                        <span class="mt-2 block text-xs text-gray-500" x-text="notification.time"></span>
                    </span>
                    <span x-show="notification.unread" class="mt-2 h-2 w-2 shrink-0 rounded-full bg-green-700"><span class="sr-only">Não lida</span></span>
                </a>
            </template>
        </div>
        <a href="{{ route('notifications.index') }}" class="block border-t border-gray-100 px-4 py-3 text-center text-sm font-semibold text-green-800 hover:bg-green-50">Ver todas as notificações</a>
    </section>
    <noscript><a href="{{ route('notifications.index') }}" class="text-sm text-green-800">Notificações ({{ $unreadCount }})</a></noscript>
</div>
