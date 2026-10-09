import { http } from '@inertiajs/vue3';
import { configureEcho, echo, echoIsConfigured, useConnectionStatus } from '@laravel/echo-vue';
import { onScopeDispose, watch } from 'vue';

let isListening = false;

function xsrfToken() {
    const cookie = document.cookie.split('; ').find((entry) => entry.startsWith('XSRF-TOKEN='));

    return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : undefined;
}

export function configureRealtime() {
    if (!import.meta.env.VITE_REVERB_APP_KEY) {
        return;
    }

    configureEcho({
        broadcaster: 'reverb',
        channelAuthorization: {
            endpoint: '/broadcasting/auth',
            transport: 'ajax',
            headersProvider: () => ({ 'X-XSRF-TOKEN': xsrfToken() }),
        },
    });

    http.onRequest((config) => {
        const socketId = isListening ? echo().socketId() : undefined;

        return socketId ? { ...config, headers: { ...config.headers, 'X-Socket-Id': socketId } } : config;
    });
}

export function startRealtime() {
    isListening = echoIsConfigured();

    return isListening;
}

export function onReconnect(callback: () => void) {
    const connectionStatus = useConnectionStatus();
    let hasDropped = false;

    watch(connectionStatus, (status) => {
        if (status === 'connected' && hasDropped) {
            hasDropped = false;
            callback();
        } else if (status !== 'connected' && status !== 'connecting') {
            hasDropped = true;
        }
    });
}

export type WorkspaceEvent = 'board' | 'workspace';

type WorkspaceEventHandler = (workspaceId: string, event: WorkspaceEvent) => void;

const workspaceEventHandlers = new Set<WorkspaceEventHandler>();

export function notifyWorkspace(workspaceId: string, event: WorkspaceEvent) {
    workspaceEventHandlers.forEach((handler) => handler(workspaceId, event));
}

export function onWorkspaceEvent(handler: WorkspaceEventHandler) {
    workspaceEventHandlers.add(handler);
    onScopeDispose(() => workspaceEventHandlers.delete(handler));
}
