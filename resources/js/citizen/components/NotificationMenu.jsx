import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';

import {
    fetchNotifications,
    markAllNotificationsAsRead,
    markNotificationAsRead,
} from '../api/notifications';
import { useLanguage } from '../i18n/LanguageContext';
import { formatDateTime } from '../utils/format';

export default function NotificationMenu({ enabled = false }) {
    const navigate = useNavigate();
    const { language, locale, t } = useLanguage();
    const menuRef = useRef(null);
    const notificationFingerprintRef = useRef('');
    const [isOpen, setIsOpen] = useState(false);
    const [notifications, setNotifications] = useState([]);
    const [unreadCount, setUnreadCount] = useState(0);
    const [isLoading, setIsLoading] = useState(false);

    async function loadNotifications({ notifyPages = false, showLoading = true } = {}) {
        if (!enabled) {
            return;
        }

        if (showLoading) {
            setIsLoading(true);
        }

        try {
            const response = await fetchNotifications();
            const payload = response.data;
            const nextNotifications = payload.notifications ?? [];
            const latestNotification = nextNotifications[0] ?? null;
            const fingerprint = `${payload.unread_count ?? 0}|${latestNotification?.id ?? ''}|${latestNotification?.read_at ?? ''}`;
            const shouldNotifyPage = notifyPages
                && notificationFingerprintRef.current !== ''
                && notificationFingerprintRef.current !== fingerprint;

            setNotifications(nextNotifications);
            setUnreadCount(payload.unread_count ?? 0);
            notificationFingerprintRef.current = fingerprint;

            if (shouldNotifyPage) {
                window.dispatchEvent(new CustomEvent('citizen-notifications:updated', { detail: payload }));
            }
        } catch {
            setNotifications([]);
            setUnreadCount(0);
        } finally {
            if (showLoading) {
                setIsLoading(false);
            }
        }
    }

    useEffect(() => {
        loadNotifications();
    }, [enabled]);

    useEffect(() => {
        if (!enabled) {
            return undefined;
        }

        const interval = window.setInterval(() => {
            loadNotifications({ notifyPages: true, showLoading: false });
        }, 5000);

        return () => {
            window.clearInterval(interval);
        };
    }, [enabled]);

    useEffect(() => {
        if (!isOpen) {
            return undefined;
        }

        function closeWhenClickOutside(event) {
            if (menuRef.current && !menuRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        }

        document.addEventListener('mousedown', closeWhenClickOutside);

        return () => {
            document.removeEventListener('mousedown', closeWhenClickOutside);
        };
    }, [isOpen]);

    async function handleOpen() {
        const nextOpen = !isOpen;
        setIsOpen(nextOpen);

        if (nextOpen) {
            await loadNotifications();
        }
    }

    async function handleMarkAllAsRead() {
        await markAllNotificationsAsRead();
        setUnreadCount(0);
        setNotifications((current) => current.map((notification) => ({
            ...notification,
            read_at: notification.read_at ?? new Date().toISOString(),
        })));
    }

    async function handleNotificationClick(event, notification) {
        event.preventDefault();

        if (!notification.read_at) {
            await markNotificationAsRead(notification.id);
            setUnreadCount((current) => Math.max(current - 1, 0));
            setNotifications((current) => current.map((item) => (
                item.id === notification.id ? { ...item, read_at: new Date().toISOString() } : item
            )));
        }

        setIsOpen(false);
        navigate(notification.url || '/applications');
    }

    if (!enabled) {
        return null;
    }

    return (
        <div className="relative" ref={menuRef}>
            <button
                aria-label={t('notification.title')}
                className="relative flex h-11 w-11 items-center justify-center rounded-xl text-gray-600 transition hover:bg-gray-100"
                onClick={handleOpen}
                type="button"
            >
                <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.7" />
                    <path d="M9 17a3 3 0 006 0" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.7" />
                </svg>
                {unreadCount > 0 && (
                    <span className="absolute -right-1 -top-1 min-w-5 rounded-full bg-red-600 px-1.5 py-0.5 text-center text-[11px] font-bold leading-none text-white">
                        {unreadCount > 99 ? '99+' : unreadCount}
                    </span>
                )}
            </button>

            {isOpen && (
                <div className="absolute right-0 z-20 mt-3 w-[min(360px,calc(100vw-2rem))] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">
                    <div className="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                        <div>
                            <p className="text-sm font-bold text-gray-900">{t('notification.title')}</p>
                            <p className="text-xs text-gray-500">{t('notification.unreadCount', { count: unreadCount })}</p>
                        </div>
                        {unreadCount > 0 && (
                            <button
                                className="text-xs font-semibold text-primary hover:underline"
                                onClick={handleMarkAllAsRead}
                                type="button"
                            >
                                {t('notification.markAllRead')}
                            </button>
                        )}
                    </div>

                    <div className="max-h-96 overflow-y-auto">
                        {isLoading ? (
                            <p className="px-4 py-6 text-center text-sm text-gray-500">{t('notification.loading')}</p>
                        ) : notifications.length === 0 ? (
                            <p className="px-4 py-6 text-center text-sm text-gray-500">{t('notification.empty')}</p>
                        ) : notifications.map((notification) => (
                            <Link
                                className={`block border-b border-gray-100 px-4 py-3 transition last:border-b-0 hover:bg-blue-50 ${
                                    notification.read_at ? 'bg-white' : 'bg-blue-50/60'
                                }`}
                                key={notification.id}
                                onClick={(event) => handleNotificationClick(event, notification)}
                                to={notification.url || '/applications'}
                            >
                                <div className="flex gap-3">
                                    <span className={`mt-1 h-2.5 w-2.5 shrink-0 rounded-full ${notification.read_at ? 'bg-gray-300' : 'bg-blue-600'}`}></span>
                                    <span className="min-w-0">
                                        <span className="block text-sm font-semibold text-gray-900">
                                            {language === 'en' && notification.event
                                                ? t(`notification.${notification.event}.title`)
                                                : notification.title}
                                        </span>
                                        <span className="mt-1 block text-sm leading-5 text-gray-600">
                                            {language === 'en' && notification.event
                                                ? t(`notification.${notification.event}.message`, { code: notification.application_code })
                                                : notification.message}
                                        </span>
                                        <span className="mt-1 block text-xs text-gray-400">
                                            {formatDateTime(notification.created_at, locale)}
                                        </span>
                                    </span>
                                </div>
                            </Link>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
