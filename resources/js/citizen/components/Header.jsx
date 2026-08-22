import { useEffect, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';

import {
    forgetCitizenSession,
    getRememberedCitizen,
    logoutCitizen,
    rememberCitizenSession,
} from '../api/auth';
import { fetchCitizenProfile } from '../api/profile';
import { useLanguage } from '../i18n/LanguageContext';
import BrandIdentity from './BrandIdentity';
import LanguageSwitcher from './LanguageSwitcher';
import NotificationMenu from './NotificationMenu';

function logCitizenSessionSyncFailure(error) {
    if (import.meta.env.DEV) {
        console.warn('Không thể đồng bộ phiên công dân.', {
            message: error?.message,
            status: error?.response?.status,
        });
    }
}

export default function Header() {
    const { pathname } = useLocation();
    const { t } = useLanguage();
    const [citizen, setCitizen] = useState(() => getRememberedCitizen());
    const [isLoggingOut, setIsLoggingOut] = useState(false);

    const navItems = [
        { to: '/', label: t('nav.home'), active: pathname === '/' },
        { to: '/services', label: t('nav.services'), active: pathname.startsWith('/services') },
        { to: '/applications', label: t('nav.applications'), active: pathname.startsWith('/applications') },
    ];

    useEffect(() => {
        let isMounted = true;

        async function syncCitizen() {
            try {
                const response = await fetchCitizenProfile();

                if (!isMounted) {
                    return;
                }

                rememberCitizenSession(response.data);
                setCitizen(response.data);
            } catch (error) {
                logCitizenSessionSyncFailure(error);

                if (!isMounted) {
                    return;
                }

                forgetCitizenSession();
                setCitizen(null);
            }
        }

        syncCitizen();

        return () => {
            isMounted = false;
        };
    }, []);

    async function handleLogout() {
        setIsLoggingOut(true);

        try {
            await logoutCitizen();
        } finally {
            forgetCitizenSession();
            setCitizen(null);
            setIsLoggingOut(false);
            window.location.assign('/login');
        }
    }

    return (
        <header className="sticky top-0 z-50 w-full border-b border-slate-200/80 bg-white/95 shadow-sm backdrop-blur-lg">
            <div className="mx-auto flex h-20 max-w-7xl items-center justify-between gap-3 px-4 sm:px-8">
                <Link to="/" className="flex min-w-0 items-center gap-2.5 sm:gap-3">
                    <BrandIdentity
                        className="gap-2.5 sm:gap-3"
                        markClassName="h-10 w-10 sm:h-12 sm:w-12"
                        nameClassName="text-sm text-[#073d7d] sm:text-lg"
                        sloganClassName="text-[9px] text-slate-400 sm:text-[10px]"
                    />
                </Link>

                <nav className="hidden items-center gap-1 lg:flex" aria-label={t('nav.main')}>
                    {navItems.map((item) => (
                        <Link
                            key={item.to}
                            to={item.to}
                            className={`rounded-full px-4 py-2.5 text-sm font-semibold transition ${
                                item.active
                                    ? 'bg-blue-50 text-[#075cca]'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-[#075cca]'
                            }`}
                        >
                            {item.label}
                        </Link>
                    ))}
                </nav>

                <div className="flex shrink-0 items-center gap-1.5 sm:gap-2">
                    {citizen ? (
                        <>
                            <NotificationMenu enabled={Boolean(citizen)} />
                            <LanguageSwitcher />
                            <Link className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-sm font-bold text-[#075cca] ring-1 ring-blue-100 transition hover:bg-blue-100" title={t('nav.profile')} to="/profile">
                                {citizen.name?.charAt(0)?.toUpperCase() || 'C'}
                            </Link>
                            <button
                                className="ml-0.5 inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-2.5 text-sm font-semibold text-red-700 transition hover:border-red-300 hover:bg-red-100 disabled:opacity-60 sm:px-4"
                                disabled={isLoggingOut}
                                onClick={handleLogout}
                                type="button"
                            >
                                <svg aria-hidden="true" className="h-4 w-4" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" viewBox="0 0 24 24">
                                    <path d="M10 17l5-5-5-5m5 5H3m10-9h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6" />
                                </svg>
                                <span className="hidden sm:inline">{isLoggingOut ? t('nav.loggingOut') : t('nav.logout')}</span>
                            </button>
                        </>
                    ) : (
                        <>
                            <LanguageSwitcher />
                            <Link className="hidden rounded-xl px-4 py-2.5 text-sm font-semibold text-[#075cca] transition hover:bg-blue-50 sm:inline-flex" to="/register">
                                {t('nav.register')}
                            </Link>
                            <Link className="rounded-xl bg-[#075cca] px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#064da8] sm:px-5" to="/login">
                                {t('nav.login')}
                            </Link>
                        </>
                    )}
                </div>
            </div>
        </header>
    );
}
