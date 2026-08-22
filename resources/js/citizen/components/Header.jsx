import { useEffect, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';

import {
    forgetCitizenSession,
    getRememberedCitizen,
    logoutCitizen,
    rememberCitizenSession,
} from '../api/auth';
import { fetchCitizenProfile } from '../api/profile';
import NotificationMenu from './NotificationMenu';

export default function Header() {
    const { pathname } = useLocation();
    const [citizen, setCitizen] = useState(() => getRememberedCitizen());
    const [isLoggingOut, setIsLoggingOut] = useState(false);

    const navItems = [
        { to: '/', label: 'Home', active: pathname === '/' },
        { to: '/services', label: 'All Services', active: pathname.startsWith('/services') },
        { to: '/applications', label: 'Track Application', active: pathname.startsWith('/applications') },
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
            } catch {
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
        <header className="bg-white border-b border-gray-200 px-10 flex h-20 items-center justify-between shrink-0">
            <Link to="/" className="flex items-center gap-4">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white">
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <div>
                    <h1 className="text-base font-bold text-gray-900 leading-tight">GovServices</h1>
                    <p className="text-xs text-gray-500 leading-tight">Citizen Portal</p>
                </div>
            </Link>
            <nav className="hidden md:flex items-center gap-2">
                {navItems.map((item) => (
                    <Link
                        key={item.to}
                        to={item.to}
                        className={`px-5 py-2.5 text-[16px] font-semibold rounded-xl transition ${
                            item.active ? 'bg-blue-600 text-white' : 'text-gray-500 hover:bg-gray-100'
                        }`}
                    >
                        {item.label}
                    </Link>
                ))}
            </nav>
            <div className="flex items-center gap-3">
                {citizen ? (
                    <>
                        <NotificationMenu enabled={Boolean(citizen)} />
                        <Link className="hidden text-right sm:block" to="/profile">
                            <span className="block max-w-40 truncate text-sm font-semibold text-gray-900">{citizen.name}</span>
                            <span className="block max-w-40 truncate text-xs text-gray-500">{citizen.email}</span>
                        </Link>
                        <Link className="px-5 py-2.5 text-[15px] font-semibold text-gray-700 hover:bg-gray-100 rounded-xl transition" to="/profile">
                            Hồ sơ
                        </Link>
                        <button
                            className="px-5 py-2.5 text-[15px] font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition disabled:opacity-60"
                            disabled={isLoggingOut}
                            onClick={handleLogout}
                            type="button"
                        >
                            {isLoggingOut ? 'Đang đăng xuất...' : 'Đăng xuất'}
                        </button>
                    </>
                ) : (
                    <Link to="/login" className="px-7 py-3 text-[17px] font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition">Đăng nhập</Link>
                )}
            </div>
        </header>
    );
}
