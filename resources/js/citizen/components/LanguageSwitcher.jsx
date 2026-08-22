import { useEffect, useRef, useState } from 'react';

import { useLanguage } from '../i18n/LanguageContext';

const LANGUAGE_OPTIONS = [
    { code: 'vi', labelKey: 'language.vi' },
    { code: 'en', labelKey: 'language.en' },
];

function FlagIcon({ language }) {
    if (language === 'vi') {
        return (
            <svg aria-hidden="true" className="h-[22px] w-8 rounded-sm shadow-sm ring-1 ring-black/10" viewBox="0 0 32 22">
                <rect width="32" height="22" fill="#da251d" />
                <path d="m16 4 1.7 5.1h5.4L18.7 12l1.7 5.1-4.4-3.2-4.4 3.2 1.7-5.1-4.4-2.9h5.4L16 4Z" fill="#ffed00" />
            </svg>
        );
    }

    return (
        <svg aria-hidden="true" className="h-[22px] w-8 rounded-sm shadow-sm ring-1 ring-black/10" viewBox="0 0 32 22">
            <rect width="32" height="22" fill="#012169" />
            <path d="M0 0 32 22M32 0 0 22" stroke="#fff" strokeWidth="5" />
            <path d="M0 0 32 22M32 0 0 22" stroke="#c8102e" strokeWidth="2" />
            <path d="M16 0v22M0 11h32" stroke="#fff" strokeWidth="7" />
            <path d="M16 0v22M0 11h32" stroke="#c8102e" strokeWidth="4" />
        </svg>
    );
}

export default function LanguageSwitcher({ className = '', floating = false }) {
    const { language, setLanguage, t } = useLanguage();
    const [isOpen, setIsOpen] = useState(false);
    const menuRef = useRef(null);
    const currentLanguage = LANGUAGE_OPTIONS.find((item) => item.code === language) ?? LANGUAGE_OPTIONS[0];

    useEffect(() => {
        function closeOnOutsideClick(event) {
            if (!menuRef.current?.contains(event.target)) {
                setIsOpen(false);
            }
        }

        function closeOnEscape(event) {
            if (event.key === 'Escape') {
                setIsOpen(false);
            }
        }

        document.addEventListener('mousedown', closeOnOutsideClick);
        document.addEventListener('keydown', closeOnEscape);

        return () => {
            document.removeEventListener('mousedown', closeOnOutsideClick);
            document.removeEventListener('keydown', closeOnEscape);
        };
    }, []);

    function chooseLanguage(nextLanguage) {
        setLanguage(nextLanguage);
        setIsOpen(false);
    }

    return (
        <div ref={menuRef} className={`${floating ? 'absolute' : 'relative'} ${className}`}>
            <button
                aria-expanded={isOpen}
                aria-haspopup="menu"
                aria-label={t('language.current')}
                className={`inline-flex h-10 min-w-12 items-center justify-center gap-1.5 rounded-xl border px-2.5 text-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-red-200 ${
                    isOpen
                        ? 'border-red-300 bg-red-50 text-red-700'
                        : 'border-slate-200 bg-white text-slate-700 hover:border-red-200 hover:bg-red-50'
                }`}
                onClick={() => setIsOpen((current) => !current)}
                type="button"
            >
                <FlagIcon language={currentLanguage.code} />
                <svg aria-hidden="true" className={`h-3.5 w-3.5 transition ${isOpen ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" viewBox="0 0 24 24">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </button>

            {isOpen && (
                <div
                    aria-label={t('language.choose')}
                    className="absolute right-0 z-[70] mt-2 w-52 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/15"
                    role="menu"
                >
                    {LANGUAGE_OPTIONS.map((option) => {
                        const selected = option.code === language;

                        return (
                            <button
                                key={option.code}
                                aria-checked={selected}
                                className={`flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold transition ${
                                    selected
                                        ? 'bg-red-50 text-red-700'
                                        : 'text-slate-700 hover:bg-slate-100'
                                }`}
                                onClick={() => chooseLanguage(option.code)}
                                role="menuitemradio"
                                type="button"
                            >
                                <FlagIcon language={option.code} />
                                <span className="flex-1">{t(option.labelKey)}</span>
                                {selected && (
                                    <svg aria-hidden="true" className="h-4 w-4" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" viewBox="0 0 24 24">
                                        <path d="m5 12 4 4L19 6" />
                                    </svg>
                                )}
                            </button>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
