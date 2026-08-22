import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';

import { messages } from './messages';

const LANGUAGE_STORAGE_KEY = 'citizen_language';
const DEFAULT_LANGUAGE = 'vi';
const SUPPORTED_LANGUAGES = ['vi', 'en'];

const LanguageContext = createContext(null);

function initialLanguage() {
    const savedLanguage = window.localStorage.getItem(LANGUAGE_STORAGE_KEY);

    return SUPPORTED_LANGUAGES.includes(savedLanguage) ? savedLanguage : DEFAULT_LANGUAGE;
}

export function LanguageProvider({ children }) {
    const [language, setLanguage] = useState(initialLanguage);

    useEffect(() => {
        window.localStorage.setItem(LANGUAGE_STORAGE_KEY, language);
        document.documentElement.lang = language;
    }, [language]);

    const t = useCallback((key, replacements = {}) => {
        const template = messages[language]?.[key] ?? messages.vi[key] ?? key;

        return Object.entries(replacements).reduce(
            (translated, [name, value]) => translated.replaceAll(`{${name}}`, String(value)),
            template,
        );
    }, [language]);

    const value = useMemo(() => ({
        language,
        locale: language === 'en' ? 'en-US' : 'vi-VN',
        setLanguage,
        t,
    }), [language, t]);

    return <LanguageContext.Provider value={value}>{children}</LanguageContext.Provider>;
}

export function useLanguage() {
    const context = useContext(LanguageContext);

    if (!context) {
        throw new Error('useLanguage must be used inside LanguageProvider.');
    }

    return context;
}
