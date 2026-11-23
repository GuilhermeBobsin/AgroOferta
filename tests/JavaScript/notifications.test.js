import assert from 'node:assert/strict';
import { beforeEach, test } from 'node:test';
import notifications from '../../resources/js/notifications.js';

beforeEach(() => {
    globalThis.document = { hidden: false };
    globalThis.window = { axios: {} };
});

test('loads unread notifications without changing their read state', async () => {
    const items = [{ id: 1, unread: true, preview: 'Olá!' }];
    window.axios.get = async () => ({ data: { unread_count: 1, notifications: items } });
    const bell = notifications('/feed', '/read', 0);

    await bell.load();

    assert.equal(bell.unreadCount, 1);
    assert.deepEqual(bell.notifications, items);
    assert.equal(bell.loaded, true);
});

test('pauses polling in hidden tabs and prevents overlapping requests', async () => {
    let calls = 0;
    let resolveRequest;
    window.axios.get = () => {
        calls++;
        return new Promise((resolve) => { resolveRequest = resolve; });
    };
    const bell = notifications('/feed', '/read', 3);
    document.hidden = true;
    await bell.load();
    assert.equal(calls, 0);

    document.hidden = false;
    const request = bell.load();
    await bell.load();
    assert.equal(calls, 1);
    resolveRequest({ data: { unread_count: 3, notifications: [] } });
    await request;
    assert.equal(bell.loading, false);
});

test('keeps previous notifications on a network failure and recovers on the next poll', async () => {
    const bell = notifications('/feed', '/read', 2);
    bell.notifications = [{ id: 1, unread: true }];
    window.axios.get = async () => { throw new Error('offline'); };

    await bell.load();

    assert.equal(bell.unreadCount, 2);
    assert.equal(bell.notifications.length, 1);
    assert.ok(bell.error);
    assert.equal(bell.loading, false);

    window.axios.get = async () => ({ data: { unread_count: 0, notifications: [] } });
    await bell.load(true);
    assert.equal(bell.unreadCount, 0);
    assert.equal(bell.error, '');
});

test('only clears unread indicators after a successful server response', async () => {
    const bell = notifications('/feed', '/read', 1);
    bell.notifications = [{ id: 1, unread: true }];
    window.axios.patch = async () => { throw new Error('offline'); };

    await bell.readAll();

    assert.equal(bell.unreadCount, 1);
    assert.equal(bell.notifications[0].unread, true);
    assert.equal(bell.marking, false);
    assert.ok(bell.error);

    window.axios.patch = async (url) => { assert.equal(url, '/read'); };
    await bell.readAll();

    assert.equal(bell.unreadCount, 0);
    assert.equal(bell.notifications[0].unread, false);
    assert.equal(bell.error, '');
});

test('does not mark notifications read while a feed request is in flight', async () => {
    let writes = 0;
    const bell = notifications('/feed', '/read', 1);
    window.axios.patch = async () => { writes++; };
    bell.loading = true;

    await bell.readAll();

    assert.equal(writes, 0);
    assert.equal(bell.unreadCount, 1);
});

test('cleans up polling and listeners when the component is removed', () => {
    const listeners = new Map();
    let cleared = false;
    window.setInterval = (_, delay) => { assert.equal(delay, 5000); return 7; };
    window.clearInterval = (id) => { assert.equal(id, 7); cleared = true; };
    window.addEventListener = document.addEventListener = (name, callback) => listeners.set(name, callback);
    window.removeEventListener = document.removeEventListener = (name, callback) => {
        assert.equal(listeners.get(name), callback);
        listeners.delete(name);
    };
    document.hidden = true;
    const bell = notifications('/feed', '/read', 0);

    bell.init();
    assert.equal(listeners.size, 4);
    bell.destroy();
    assert.equal(cleared, true);
    assert.equal(listeners.size, 0);
});

test('updates the badge on the next timer tick without a page reload', async () => {
    let tick;
    let count = 0;
    window.setInterval = (callback) => { tick = callback; return 1; };
    window.addEventListener = document.addEventListener = () => {};
    window.axios.get = async (_, options) => {
        assert.equal(options.params.summary, 1);
        assert.equal(options.timeout, 8000);
        return { data: { unread_count: count } };
    };
    const bell = notifications('/feed', '/read', 0);
    bell.init();
    await Promise.resolve();

    count = 1;
    await tick();

    assert.equal(bell.unreadCount, 1);
    assert.equal(bell.loaded, false);
});

test('loads previews only when the bell is open', async () => {
    const bell = notifications('/feed', '/read', 0);
    window.axios.get = async (_, options) => {
        assert.equal(options.params.summary, 0);
        return { data: { unread_count: 1, notifications: [{ id: 1, unread: true }] } };
    };
    bell.open = true;
    await bell.load();

    assert.equal(bell.loaded, true);
    assert.equal(bell.notifications.length, 1);
});

test('backs off after failures instead of repeatedly hitting the server', async () => {
    let calls = 0;
    window.axios.get = async () => {
        calls++;
        throw { response: { status: 429, headers: { 'retry-after': '60' } } };
    };
    const bell = notifications('/feed', '/read', 2);
    const started = Date.now();
    await bell.load();
    await bell.load();

    assert.equal(calls, 1);
    assert.ok(bell.retryAt >= started + 60000);
    assert.equal(bell.unreadCount, 2);
});

test('opening the panel during a summary request queues a full refresh', async () => {
    let resolveSummary;
    let calls = 0;
    window.axios.get = (_, options) => {
        calls++;
        if (calls === 1) return new Promise((resolve) => { resolveSummary = resolve; });
        assert.equal(options.params.summary, 0);
        return Promise.resolve({ data: { unread_count: 1, notifications: [{ id: 1 }] } });
    };
    const bell = notifications('/feed', '/read', 0);
    const pending = bell.load();
    bell.toggle();
    resolveSummary({ data: { unread_count: 1 } });
    await pending;
    await Promise.resolve();

    assert.equal(calls, 2);
    assert.equal(bell.loaded, true);
    assert.equal(bell.notifications.length, 1);
});

test('stops polling when the login session expires', async () => {
    let cleared = false;
    window.clearInterval = () => { cleared = true; };
    window.axios.get = async () => { throw { response: { status: 401 } }; };
    const bell = notifications('/feed', '/read', 0);
    await bell.load();

    assert.equal(bell.stopped, true);
    assert.equal(cleared, true);
});
