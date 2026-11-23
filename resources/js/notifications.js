export default (feedUrl, readAllUrl, initialCount) => ({
    open: false,
    unreadCount: initialCount,
    notifications: [],
    loaded: false,
    loading: false,
    marking: false,
    error: '',
    timer: null,
    onVisibility: null,
    onRead: null,
    retryAt: 0,
    retryDelay: 5000,
    refreshPending: false,
    stopped: false,

    init() {
        this.load();
        this.timer = window.setInterval(() => this.load(), 5000);
        this.onVisibility = () => { if (!document.hidden) this.load(true); };
        this.onRead = () => this.load(true);
        document.addEventListener('visibilitychange', this.onVisibility);
        window.addEventListener('focus', this.onVisibility);
        window.addEventListener('online', this.onVisibility);
        window.addEventListener('notifications-updated', this.onRead);
    },

    destroy() {
        this.stopped = true;
        window.clearInterval(this.timer);
        document.removeEventListener('visibilitychange', this.onVisibility);
        window.removeEventListener('focus', this.onVisibility);
        window.removeEventListener('online', this.onVisibility);
        window.removeEventListener('notifications-updated', this.onRead);
    },

    toggle() {
        this.open = !this.open;
        if (this.open) this.load(true);
    },

    async load(force = false) {
        if (this.stopped || document.hidden) return;
        if (this.loading || this.marking) {
            if (force) this.refreshPending = true;
            return;
        }
        if (!force && Date.now() < this.retryAt) return;
        this.loading = true;

        try {
            const response = await window.axios.get(feedUrl, {
                params: { summary: this.open ? 0 : 1 },
                timeout: 8000,
            });
            if (typeof response.data?.unread_count !== 'number') throw new Error('Invalid notification response');
            if (!this.marking && !this.stopped) {
                if (Array.isArray(response.data.notifications)) {
                    this.notifications = response.data.notifications;
                    this.loaded = true;
                }
                this.unreadCount = response.data.unread_count;
                this.error = '';
                this.retryAt = 0;
                this.retryDelay = 5000;
            }
        } catch (error) {
            this.retryDelay = Math.min(this.retryDelay * 2, 60000);
            const retryAfter = Number(error.response?.headers?.['retry-after']) || 0;
            this.retryAt = Date.now() + Math.max(this.retryDelay, retryAfter * 1000);
            this.error = 'Não foi possível atualizar. Tentaremos novamente em instantes.';
            if ([401, 419].includes(error.response?.status)) {
                this.stopped = true;
                window.clearInterval(this.timer);
                this.error = 'Sua sessão expirou. Atualize a página para entrar novamente.';
            }
        } finally {
            this.loading = false;
            if (this.refreshPending) {
                this.refreshPending = false;
                this.load(true);
            }
        }
    },

    async readAll() {
        if (this.marking || this.loading) return;
        this.marking = true;

        try {
            await window.axios.patch(readAllUrl, {}, { timeout: 8000 });
            this.notifications.forEach((notification) => { notification.unread = false; });
            this.unreadCount = 0;
            this.error = '';
        } catch (_) {
            this.error = 'Não foi possível marcar como lidas. Tente novamente.';
        } finally {
            this.marking = false;
            if (this.refreshPending) {
                this.refreshPending = false;
                this.load(true);
            }
        }
    },
});
