'use strict';

self.addEventListener('push', (event) => {
    event.waitUntil((async () => {
        let payload;
        try {
            payload = event.data.json();
        } catch {
            return;
        }
        if (!payload || typeof payload.title !== 'string' || typeof payload.body !== 'string'
            || typeof payload.tag !== 'string' || !Number.isFinite(payload.expiresAt)
            || payload.expiresAt <= Date.now()) return;

        await self.registration.showNotification(payload.title, {
            body: payload.body,
            tag: payload.tag,
            renotify: false,
            data: { url: '/consultas', expiresAt: payload.expiresAt },
        });
    })());
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil((async () => {
        const target = new URL('/consultas', self.location.origin);
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        const existing = windows.find((client) => new URL(client.url).origin === target.origin);
        if (existing) {
            if ('navigate' in existing) await existing.navigate(target.href);
            await existing.focus();
        } else {
            await self.clients.openWindow(target.href);
        }
    })());
});
