import { Link } from 'react-router-dom';

import BrandIdentity from './BrandIdentity';
import LanguageSwitcher from './LanguageSwitcher';
import { useLanguage } from '../i18n/LanguageContext';

export default function AuthShell({ children, description, title }) {
    const { t } = useLanguage();

    return (
        <main className="relative min-h-screen bg-surface px-4 py-8 text-gray-900 sm:px-6 lg:px-8">
            <LanguageSwitcher className="right-4 top-4 z-20 sm:right-6 sm:top-6" floating />
            <section className="mx-auto grid min-h-[calc(100vh-4rem)] w-full max-w-5xl items-center gap-8 md:grid-cols-[1fr_420px]">
                <div className="max-w-xl">
                    <Link className="inline-flex w-fit" to="/">
                        <BrandIdentity markClassName="h-14 w-14" />
                    </Link>
                    <h2 className="mt-8 text-4xl font-bold leading-tight text-gray-950">
                        {t('auth.shellTitle')}
                    </h2>
                    <p className="mt-4 text-base leading-7 text-gray-600">
                        {t('auth.shellDescription')}
                    </p>
                    <div className="mt-7 grid gap-3 text-sm text-gray-700">
                        <p className="rounded-lg border border-border bg-white px-4 py-3">
                            {t('auth.benefitTracking')}
                        </p>
                        <p className="rounded-lg border border-border bg-white px-4 py-3">
                            {t('auth.benefitAccount')}
                        </p>
                    </div>
                </div>

                <div className="card-container rounded-lg p-6 shadow-sm sm:p-7">
                    <div className="mb-5 text-center">
                        <h1 className="text-2xl font-bold text-gray-950">{title}</h1>
                        {description && <p className="mt-2 text-sm leading-6 text-gray-600">{description}</p>}
                    </div>

                    {children}
                </div>
            </section>
        </main>
    );
}
