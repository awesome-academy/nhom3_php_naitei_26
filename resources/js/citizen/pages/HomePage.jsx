import { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';

import { forgetCitizenSession, getRememberedCitizen, rememberCitizenSession } from '../api/auth';
import { fetchCitizenProfile } from '../api/profile';
import { fetchServices } from '../api/services';
import Footer from '../components/Footer';
import Header from '../components/Header';
import { useLanguage } from '../i18n/LanguageContext';
import { localizeService } from '../i18n/content';

const DEFAULT_ARROW_CLASS_NAME = 'h-5 w-5';
const FLASH_TIMEOUT_MS = 4000;

function ServiceIcon({ index }) {
    const paths = [
        <path key="home" d="M3 10.5 12 3l9 7.5M5.25 9.25V21h13.5V9.25M9 21v-6h6v6" />,
        <path key="card" d="M4 5h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm2 4h5m-5 4h8m4-4h.01" />,
        <path key="map" d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3V6Zm6-3v15m6-12v15" />,
        <path key="document" d="M6 3h9l4 4v14H6V3Zm8 0v5h5M9 13h7m-7 4h7" />,
    ];

    return (
        <svg aria-hidden="true" className="h-8 w-8" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" viewBox="0 0 24 24">
            {paths[index % paths.length]}
        </svg>
    );
}

function ArrowIcon({ className = DEFAULT_ARROW_CLASS_NAME }) {
    return (
        <svg aria-hidden="true" className={className} fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" viewBox="0 0 24 24">
            <path d="M5 12h14m-6-6 6 6-6 6" />
        </svg>
    );
}

export default function HomePage() {
    const location = useLocation();
    const navigate = useNavigate();
    const { language, t } = useLanguage();
    const [citizen, setCitizen] = useState(() => getRememberedCitizen());
    const [featuredServices, setFeaturedServices] = useState([]);
    const [flash, setFlash] = useState(location.state?.flash ?? '');
    const [search, setSearch] = useState('');
    const fallbackServices = [1, 2, 3, 4].map((number) => ({
        name: t(`home.fallback${number}Name`),
        description: t(`home.fallback${number}Description`),
        category_name: t(`home.fallback${number}Category`),
    }));
    const displayedServices = featuredServices.length > 0
        ? featuredServices.map((service) => localizeService(service, language))
        : fallbackServices;
    const guideItems = [
        { number: '01', title: t('home.step1Title'), description: t('home.step1Description') },
        { number: '02', title: t('home.step2Title'), description: t('home.step2Description') },
        { number: '03', title: t('home.step3Title'), description: t('home.step3Description') },
    ];

    useEffect(() => {
        let isMounted = true;

        async function loadHomepage() {
            const [profileResult, servicesResult] = await Promise.allSettled([
                fetchCitizenProfile(),
                fetchServices({ per_page: 4 }),
            ]);

            if (!isMounted) {
                return;
            }

            if (profileResult.status === 'fulfilled') {
                rememberCitizenSession(profileResult.value.data);
                setCitizen(profileResult.value.data);
            } else {
                forgetCitizenSession();
                setCitizen(null);
            }

            if (servicesResult.status === 'fulfilled' && servicesResult.value.data.data.length > 0) {
                setFeaturedServices(servicesResult.value.data.data.slice(0, 4));
            }
        }

        loadHomepage();

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
        }, FLASH_TIMEOUT_MS);

        return () => window.clearTimeout(timeout);
    }, [flash]);

    function handleSearch(event) {
        event.preventDefault();
        const keyword = search.trim();

        navigate(keyword ? `/services?search=${encodeURIComponent(keyword)}` : '/services');
    }

    return (
        <main className="min-h-screen overflow-x-hidden bg-[#f6f8fc] font-sans text-slate-900">
            <Header />

            <section
                className="relative isolate flex min-h-[590px] items-start overflow-hidden bg-[#073d7d] bg-cover bg-bottom px-4 pb-36 pt-20 text-center sm:min-h-[650px] sm:px-8 sm:pt-24"
                style={{ backgroundImage: "url('/images/homepage-hero.png')" }}
            >
                <div className="absolute inset-0 -z-10 bg-gradient-to-b from-[#053c7a]/55 via-[#073d7d]/45 to-[#092f61]/70" />
                <div className="absolute inset-x-0 top-0 -z-10 h-52 bg-gradient-to-b from-black/15 to-transparent" />

                <div className="mx-auto flex w-full max-w-4xl flex-col items-center">
                    {flash && (
                        <p className="mb-5 rounded-full border border-emerald-200/50 bg-emerald-50/95 px-5 py-2.5 text-sm font-semibold text-emerald-700 shadow-lg">
                            {flash}
                        </p>
                    )}

                    <span className="mb-5 inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-blue-50 backdrop-blur-md sm:text-sm">
                        <span className="h-2 w-2 rounded-full bg-cyan-300 shadow-[0_0_12px_rgba(103,232,249,0.95)]" />
                        {t('home.heroEyebrow')}
                    </span>

                    <h1 className="max-w-4xl text-4xl font-bold leading-[1.12] tracking-[-0.035em] text-white drop-shadow-sm sm:text-5xl lg:text-[58px]">
                        {t('home.heroTitle')}
                        <span className="block text-cyan-200">{t('home.heroAccent')}</span>
                    </h1>
                    <p className="mt-5 max-w-2xl text-base leading-7 text-blue-50/90 sm:text-lg">
                        {t('home.heroDescription')}
                    </p>

                    <form className="mt-8 flex w-full max-w-3xl flex-col gap-2 rounded-2xl bg-white p-2.5 shadow-[0_20px_60px_rgba(3,25,58,0.32)] sm:flex-row sm:rounded-full" onSubmit={handleSearch}>
                        <label className="flex min-w-0 flex-1 items-center gap-3 px-3" htmlFor="homepage-service-search">
                            <svg aria-hidden="true" className="h-6 w-6 shrink-0 text-slate-400" fill="none" stroke="currentColor" strokeLinecap="round" strokeWidth="2" viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="7" />
                                <path d="m20 20-3.5-3.5" />
                            </svg>
                            <span className="sr-only">{t('home.searchAria')}</span>
                            <input
                                id="homepage-service-search"
                                className="min-w-0 flex-1 border-0 bg-transparent py-3 text-base text-slate-900 outline-none placeholder:text-slate-400 sm:text-[17px]"
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder={t('home.searchPlaceholder')}
                                type="search"
                                value={search}
                            />
                        </label>
                        <button className="inline-flex items-center justify-center gap-2 rounded-xl bg-[#075cca] px-7 py-3.5 text-sm font-bold text-white transition hover:bg-[#064da8] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:rounded-full sm:text-base" type="submit">
                            {t('home.search')}
                            <ArrowIcon className="h-4 w-4" />
                        </button>
                    </form>

                    <div className="mt-6 flex flex-wrap justify-center gap-3">
                        <Link className="inline-flex items-center gap-2 rounded-xl bg-cyan-300 px-6 py-3.5 text-sm font-bold text-[#073d7d] shadow-lg transition hover:-translate-y-0.5 hover:bg-cyan-200" to="/services">
                            <svg aria-hidden="true" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path d="M4 4h6v6H4zm10 0h6v6h-6zM4 14h6v6H4zm10 0h6v6h-6z" />
                            </svg>
                            {t('home.serviceCatalog')}
                        </Link>
                        <Link className="inline-flex items-center gap-2 rounded-xl border border-white/40 bg-white/95 px-6 py-3.5 text-sm font-bold text-[#075cca] shadow-lg transition hover:-translate-y-0.5 hover:bg-white" to={citizen ? '/applications' : '/login'}>
                            <svg aria-hidden="true" className="h-5 w-5" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" viewBox="0 0 24 24">
                                <path d="M4 5h16v14H4zM8 9h8m-8 4h5" />
                            </svg>
                            {t('home.trackApplication')}
                        </Link>
                    </div>
                </div>
            </section>

            <section className="mx-auto w-full max-w-7xl px-4 py-16 sm:px-8 sm:py-20" aria-labelledby="featured-services-title">
                <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm font-bold uppercase tracking-[0.14em] text-[#075cca]">{t('home.featured')}</p>
                        <h2 id="featured-services-title" className="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{t('home.popular')}</h2>
                    </div>
                    <Link className="group inline-flex w-fit items-center gap-2 rounded-full border border-blue-200 bg-white px-4 py-2 text-sm font-bold text-[#075cca] shadow-sm transition hover:border-blue-300 hover:bg-blue-50" to="/services">
                        {t('home.allServices')}
                        <ArrowIcon className="h-4 w-4 transition group-hover:translate-x-1" />
                    </Link>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {displayedServices.map((service, index) => (
                        <Link
                            key={service.id ?? service.name}
                            className="group flex min-h-64 flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-[0_12px_35px_rgba(15,45,80,0.08)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_20px_45px_rgba(7,92,202,0.14)]"
                            to={service.id ? `/services/${service.id}` : '/services'}
                        >
                            <div className="flex items-start justify-between gap-4">
                                <span className="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-[#075cca] transition group-hover:bg-[#075cca] group-hover:text-white">
                                    <ServiceIcon index={index} />
                                </span>
                                <span className="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                    {service.category_name || t('home.onlineCategory')}
                                </span>
                            </div>
                            <h3 className="mt-6 text-lg font-bold leading-6 text-slate-900 transition group-hover:text-[#075cca]">{service.name}</h3>
                            <p className="mt-3 line-clamp-3 text-sm leading-6 text-slate-500">{service.description || t('home.serviceFallback')}</p>
                            <span className="mt-auto inline-flex items-center gap-2 pt-5 text-sm font-bold text-[#075cca]">
                                {t('common.viewDetails')}
                                <ArrowIcon className="h-4 w-4 transition group-hover:translate-x-1" />
                            </span>
                        </Link>
                    ))}
                </div>
            </section>

            <section className="mt-20 border-y border-slate-200 bg-white" aria-label={t('home.benefitsAria')}>
                <div className="mx-auto grid max-w-7xl grid-cols-1 px-4 py-8 sm:px-8 md:grid-cols-3 md:py-10">
                    <div className="flex items-center justify-center gap-4 py-5 text-center md:py-0">
                        <strong className="text-4xl font-bold tracking-tight text-[#075cca]">24/7</strong>
                        <span className="max-w-32 text-left text-sm font-semibold leading-5 text-slate-600">{t('home.onlineSubmission')}</span>
                    </div>
                    <div className="flex items-center justify-center gap-4 border-y border-slate-200 py-5 text-center md:border-x md:border-y-0 md:py-0">
                        <strong className="text-4xl font-bold tracking-tight text-cyan-600">01</strong>
                        <span className="max-w-36 text-left text-sm font-semibold leading-5 text-slate-600">{t('home.oneAccount')}</span>
                    </div>
                    <div className="flex items-center justify-center gap-4 py-5 text-center md:py-0">
                        <svg aria-hidden="true" className="h-10 w-10 text-[#075cca]" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" viewBox="0 0 24 24">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
                            <path d="m9 12 2 2 4-4" />
                        </svg>
                        <span className="max-w-36 text-left text-sm font-semibold leading-5 text-slate-600">{t('home.secureInformation')}</span>
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-4 py-20 sm:px-8" aria-labelledby="guide-title">
                <div className="mx-auto max-w-2xl text-center">
                    <p className="text-sm font-bold uppercase tracking-[0.14em] text-[#075cca]">{t('home.simpleProcess')}</p>
                    <h2 id="guide-title" className="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{t('home.threeSteps')}</h2>
                    <p className="mt-4 text-base leading-7 text-slate-500">{t('home.guideDescription')}</p>
                </div>

                <div className="mt-10 grid grid-cols-1 gap-5 md:grid-cols-3">
                    {guideItems.map((item) => (
                        <article key={item.number} className="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                            <span className="absolute -right-2 -top-7 text-8xl font-black text-blue-50">{item.number}</span>
                            <span className="relative inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#075cca] text-sm font-bold text-white">{item.number}</span>
                            <h3 className="relative mt-6 text-xl font-bold text-slate-900">{item.title}</h3>
                            <p className="relative mt-3 text-sm leading-6 text-slate-500">{item.description}</p>
                        </article>
                    ))}
                </div>

                {!citizen && (
                    <div className="mt-12 flex flex-col items-center justify-between gap-6 rounded-3xl bg-[#072f62] px-7 py-8 text-white shadow-xl sm:flex-row sm:px-10">
                        <div>
                            <h2 className="text-2xl font-bold">{t('home.readyTitle')}</h2>
                            <p className="mt-2 text-sm leading-6 text-blue-100">{t('home.readyDescription')}</p>
                        </div>
                        <Link className="inline-flex shrink-0 items-center gap-2 rounded-xl bg-cyan-300 px-6 py-3.5 text-sm font-bold text-[#073d7d] transition hover:bg-cyan-200" to="/register">
                            {t('home.registerAccount')}
                            <ArrowIcon className="h-4 w-4" />
                        </Link>
                    </div>
                )}
            </section>

            <Footer />
        </main>
    );
}
