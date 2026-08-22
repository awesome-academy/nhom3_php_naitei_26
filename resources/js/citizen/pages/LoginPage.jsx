import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useEffect, useState } from 'react';

import { getApiError, getRememberedCitizen, loginCitizen, rememberCitizenSession } from '../api/auth';
import AuthShell from '../components/AuthShell';
import FormField from '../components/FormField';
import { useLanguage } from '../i18n/LanguageContext';

const initialForm = {
    email: '',
    password: '',
};

const googleErrorKeys = {
    google_callback_failed: 'auth.googleCallbackFailed',
    google_login_denied: 'auth.googleLoginDenied',
    google_missing_email: 'auth.googleMissingEmail',
};

export default function LoginPage() {
    const location = useLocation();
    const navigate = useNavigate();
    const { t } = useLanguage();
    const [form, setForm] = useState(initialForm);
    const [errors, setErrors] = useState({});
    const [message, setMessage] = useState(() => {
        const error = new URLSearchParams(location.search).get('auth_error');

        return googleErrorKeys[error] ? t(googleErrorKeys[error]) : '';
    });
    const [flash, setFlash] = useState(location.state?.flash ?? '');
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        if (getRememberedCitizen()) {
            navigate('/', { replace: true });
        }
    }, [navigate]);

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

    function updateField(event) {
        setForm((current) => ({
            ...current,
            [event.target.name]: event.target.value,
        }));
    }

    async function submitForm(event) {
        event.preventDefault();
        setErrors({});
        setMessage('');
        setFlash('');
        setIsSubmitting(true);

        try {
            const response = await loginCitizen(form);
            rememberCitizenSession(response.data);
            navigate('/', {
                replace: true,
                state: {
                    flash: t('auth.loginSuccess'),
                },
            });
        } catch (error) {
            const apiError = getApiError(error);
            setMessage(apiError.message);
            setErrors(apiError.errors);
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <AuthShell
            description={t('auth.loginDescription')}
            title={t('auth.loginTitle')}
        >
            {flash && (
                <p className="mb-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-semibold text-success">
                    {flash}
                </p>
            )}

            <form noValidate onSubmit={submitForm}>
                <div className="space-y-4">
                    <FormField
                        autoComplete="email"
                        errors={errors.email}
                        label={t('auth.email')}
                        name="email"
                        onChange={updateField}
                        type="email"
                        value={form.email}
                    />
                    <FormField
                        autoComplete="current-password"
                        errors={errors.password}
                        label={t('auth.password')}
                        name="password"
                        onChange={updateField}
                        type="password"
                        value={form.password}
                    />

                    {message && (
                        <p className="rounded-lg bg-red-50 px-4 py-3 text-sm font-semibold text-danger">
                            {message}
                        </p>
                    )}

                    <button className="btn-primary w-full rounded-full py-3 text-base" disabled={isSubmitting}>
                        {isSubmitting ? t('auth.loggingIn') : t('auth.loginTitle')}
                    </button>

                    <div className="flex items-center gap-3 text-xs font-semibold uppercase text-gray-400">
                        <span className="h-px flex-1 bg-border" />
                        {t('auth.or')}
                        <span className="h-px flex-1 bg-border" />
                    </div>

                    <a
                        className="btn-secondary flex w-full items-center justify-center gap-3 rounded-full py-3 text-base"
                        href="/api/v1/auth/google/redirect"
                    >
                        <span className="flex size-6 items-center justify-center rounded-full bg-white text-sm font-bold text-primary">
                            G
                        </span>
                        {t('auth.googleLogin')}
                    </a>
                </div>
            </form>

            <p className="mt-6 text-center text-sm text-gray-600">
                {t('auth.noAccount')}{' '}
                <Link className="font-semibold text-primary hover:text-primary-hover" to="/register">
                    {t('auth.registerNow')}
                </Link>
            </p>
        </AuthShell>
    );
}
