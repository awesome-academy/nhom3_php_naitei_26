import { Link } from 'react-router-dom';

import BrandIdentity from './BrandIdentity';
import { useLanguage } from '../i18n/LanguageContext';

export default function Footer({ className = "" }) {
    const { t } = useLanguage();

    return (
        <footer className={`border-t border-slate-800 bg-[#061e3a] text-blue-50 ${className}`}>
            <div className="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-8 md:grid-cols-[1.4fr_1fr_1fr]">
                <div>
                    <Link className="inline-flex items-center gap-3" to="/">
                        <BrandIdentity
                            markClassName="h-12 w-12"
                            nameClassName="text-lg text-white"
                            sloganClassName="text-[11px] text-blue-200/70"
                        />
                    </Link>
                    <p className="mt-5 max-w-md text-sm leading-6 text-blue-100/70">
                        {t('footer.description')}
                    </p>
                </div>

                <div>
                    <h2 className="text-sm font-bold uppercase tracking-[0.12em] text-white">{t('footer.quickLinks')}</h2>
                    <div className="mt-4 flex flex-col gap-3 text-sm text-blue-100/70">
                        <Link className="transition hover:text-cyan-200" to="/services">{t('footer.serviceCatalog')}</Link>
                        <Link className="transition hover:text-cyan-200" to="/applications">{t('footer.applicationLookup')}</Link>
                        <Link className="transition hover:text-cyan-200" to="/profile">{t('footer.profile')}</Link>
                    </div>
                </div>

                <div>
                    <h2 className="text-sm font-bold uppercase tracking-[0.12em] text-white">{t('footer.support')}</h2>
                    <div className="mt-4 space-y-3 text-sm leading-6 text-blue-100/70">
                        <p>{t('footer.online247')}</p>
                        <p>{t('footer.transparent')}</p>
                    </div>
                </div>
            </div>

            <div className="border-t border-white/10">
                <div className="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-5 text-xs text-blue-100/55 sm:px-8 md:flex-row md:items-center md:justify-between">
                    <p>{t('footer.copyright')}</p>
                    <p>{t('footer.legal')}</p>
                </div>
            </div>
        </footer>
    );
}
