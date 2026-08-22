import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';

import { fetchApplications } from '../api/applications';
import { forgetCitizenSession } from '../api/auth';
import { fetchCitizenProfile } from '../api/profile';
import Footer from '../components/Footer';
import Header from '../components/Header';
import StatusBadge from '../components/StatusBadge';
import { useLanguage } from '../i18n/LanguageContext';
import { localizeService } from '../i18n/content';
import { formatDate } from '../utils/format';

export default function MyApplicationsPage() {
    const navigate = useNavigate();
    const { language, locale, t } = useLanguage();
    const [applications, setApplications] = useState([]);
    const [meta, setMeta] = useState(null);
    const [loading, setLoading] = useState(true);
    const [loadError, setLoadError] = useState(false);

    async function loadApplications(page = 1) {
        setLoadError(false);
        setLoading(true);

        try {
            const response = await fetchApplications({ page });
            setApplications(response.data.data ?? []);
            setMeta(response.data.meta ?? null);
        } catch (error) {
            if (error?.response?.status === 401) {
                forgetCitizenSession();
                navigate('/login', {
                    replace: true,
                    state: { flash: t('applications.loginRequired') },
                });

                return;
            }

            setLoadError(true);
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        let isMounted = true;

        async function boot() {
            try {
                await fetchCitizenProfile();
            } catch {
                if (!isMounted) {
                    return;
                }

                forgetCitizenSession();
                navigate('/login', {
                    replace: true,
                    state: { flash: t('applications.loginRequired') },
                });

                return;
            }

            if (isMounted) {
                await loadApplications(1);
            }
        }

        boot();

        return () => {
            isMounted = false;
        };
    }, [navigate]);

    const currentPage = meta?.current_page ?? 1;
    const lastPage = meta?.last_page ?? 1;

    useEffect(() => {
        function refreshApplications() {
            loadApplications(currentPage);
        }

        window.addEventListener('citizen-notifications:updated', refreshApplications);

        return () => {
            window.removeEventListener('citizen-notifications:updated', refreshApplications);
        };
    }, [currentPage]);

    return (
        <main className="min-h-screen bg-surface flex flex-col font-sans">
            <Header />

            <div className="flex-1 w-full max-w-[1101px] mx-auto bg-white border-x border-gray-200 flex flex-col">
                <div className="flex items-center justify-between px-10 py-6 border-b border-gray-100">
                    <div>
                        <h1 className="text-[26px] font-bold tracking-tight text-gray-900">{t('applications.title')}</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            {meta?.total ? t('applications.countSummary', { count: meta.total }) : t('applications.listSummary')}
                        </p>
                    </div>
                    <Link className="btn-primary rounded-xl px-6 py-3 text-[15px]" to="/services">
                        {t('applications.new')}
                    </Link>
                </div>

                <div className="flex-1 px-10 py-8">
                    {loading ? (
                        <div className="py-10 text-center text-gray-500">{t('common.loading')}</div>
                    ) : loadError ? (
                        <div className="py-10 text-center">
                            <p className="text-gray-600">{t('applications.loadError')}</p>
                            <button type="button" className="mt-4 text-sm font-semibold text-primary hover:underline" onClick={() => loadApplications(currentPage)}>
                                {t('applications.tryAgain')}
                            </button>
                        </div>
                    ) : applications.length === 0 ? (
                        <div className="py-16 text-center">
                            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 text-gray-400">
                                <svg className="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <h2 className="mt-5 text-lg font-bold text-gray-900">{t('applications.empty')}</h2>
                            <p className="mt-1 text-sm text-gray-500">{t('applications.emptyHelp')}</p>
                            <Link className="btn-primary mt-6 rounded-xl px-7 py-3 text-[15px]" to="/services">
                                {t('applications.viewCatalog')}
                            </Link>
                        </div>
                    ) : (
                        <div className="border-[1.5px] border-gray-200 rounded-2xl bg-white overflow-hidden">
                            {applications.map((application, index) => (
                                <Link
                                    key={application.id}
                                    to={`/applications/${application.id}`}
                                    className={`flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 md:p-6 transition hover:bg-gray-50 group ${index !== 0 ? 'border-t-[1.5px] border-gray-100' : ''}`}
                                >
                                    <div className="min-w-0 flex-1 pr-6">
                                        <h4 className="truncate text-[17px] font-semibold text-gray-900 group-hover:text-blue-600 transition">
                                            {localizeService(application.service_type, language)?.name ?? t('applications.service')}
                                        </h4>
                                        <p className="mt-1 font-consolas text-sm text-gray-500">{application.application_code}</p>
                                    </div>
                                    <div className="flex items-center gap-6 md:gap-8">
                                        <div className="w-36 text-left md:text-right">
                                            <span className="text-[13px] font-semibold text-gray-400 uppercase tracking-widest block">{t('applications.submittedDate')}</span>
                                            <span className="text-[15px] font-semibold text-gray-700">{formatDate(application.submitted_at, locale)}</span>
                                        </div>
                                        <div className="w-32 flex justify-end">
                                            <StatusBadge status={application.status} />
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}

                    {meta && lastPage > 1 && (
                        <div className="mt-6 flex items-center justify-center gap-4">
                            <button
                                type="button"
                                disabled={currentPage <= 1}
                                className="btn-secondary rounded-xl px-5 py-2.5 text-sm disabled:opacity-40"
                                onClick={() => loadApplications(currentPage - 1)}
                            >
                                {t('applications.previousPage')}
                            </button>
                            <span className="text-sm text-gray-500">{t('applications.page', { current: currentPage, last: lastPage })}</span>
                            <button
                                type="button"
                                disabled={currentPage >= lastPage}
                                className="btn-secondary rounded-xl px-5 py-2.5 text-sm disabled:opacity-40"
                                onClick={() => loadApplications(currentPage + 1)}
                            >
                                {t('applications.nextPage')}
                            </button>
                        </div>
                    )}
                </div>

                <Footer />
            </div>
        </main>
    );
}
