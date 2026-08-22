import { useEffect, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';

import { forgetCitizenSession, getRememberedCitizen, rememberCitizenSession } from '../api/auth';
import { fetchCitizenProfile } from '../api/profile';
import Footer from '../components/Footer';
import Header from '../components/Header';

export default function HomePage() {
    const location = useLocation();
    const [citizen, setCitizen] = useState(() => getRememberedCitizen());
    const [flash, setFlash] = useState(location.state?.flash ?? '');

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

    useEffect(() => {
        if (!flash) {
            return undefined;
        }

        const timeout = window.setTimeout(() => {
            setFlash('');
            window.history.replaceState({}, document.title);
        }, 4000);

        return () => window.clearTimeout(timeout);
    }, [flash]);

    return (
        <main className="min-h-screen bg-surface flex flex-col text-gray-900">
            <Header />

            <div className="flex-1 w-full max-w-[1101px] mx-auto bg-white border-x border-gray-200 flex flex-col">
                <section className="flex flex-1 flex-col justify-center px-10 py-16">
                    {flash && (
                        <p className="mb-6 w-fit rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-success">
                            {flash}
                        </p>
                    )}

                    <h1 className="mt-4 max-w-3xl text-4xl font-bold tracking-tight text-gray-950 sm:text-6xl">
                        Hệ thống Quản lý Dịch vụ Công
                    </h1>
                    <p className="mt-6 max-w-2xl text-lg leading-8 text-gray-600">
                        Dịch vụ công trực tuyến cho công dân.
                    </p>

                    <div className="mt-8 flex flex-wrap gap-4">
                        <Link className="btn-primary rounded-xl px-6 py-4 text-base shadow-sm" to="/services">
                            Danh mục Dịch vụ
                        </Link>
                        {!citizen && (
                            <>
                                <Link className="btn-secondary rounded-xl px-6 py-4 text-base" to="/login">
                                    Đăng nhập
                                </Link>
                                <Link className="btn-secondary rounded-xl px-6 py-4 text-base" to="/register">
                                    Đăng ký
                                </Link>
                            </>
                        )}
                    </div>
                </section>

                <Footer />
            </div>
        </main>
    );
}
