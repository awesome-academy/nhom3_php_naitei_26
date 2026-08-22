import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';

import App from './App';
import { LanguageProvider } from './i18n/LanguageContext';

const rootElement = document.getElementById('citizen-app');

if (rootElement) {
    createRoot(rootElement).render(
        <StrictMode>
            <LanguageProvider>
                <BrowserRouter>
                    <App />
                </BrowserRouter>
            </LanguageProvider>
        </StrictMode>,
    );
}
