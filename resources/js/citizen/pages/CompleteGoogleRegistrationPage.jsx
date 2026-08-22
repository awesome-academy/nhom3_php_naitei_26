import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';

import {
    completeGoogleCitizenRegistration,
    getApiError,
    getPendingGoogleCitizen,
    rememberCitizenSession,
} from '../api/auth';
import BrandIdentity from '../components/BrandIdentity';
import FormField, { FieldError } from '../components/FormField';
import LanguageSwitcher from '../components/LanguageSwitcher';
import { useLanguage } from '../i18n/LanguageContext';

const initialForm = {
    name: '',
    citizen_id: '',
    date_of_birth: '',
    phone: '',
    address: '',
};

export default function CompleteGoogleRegistrationPage() {
    const navigate = useNavigate();
    const { t } = useLanguage();
    const [pendingGoogle, setPendingGoogle] = useState(null);
    const [form, setForm] = useState(initialForm);
    const [errors, setErrors] = useState({});
    const [message, setMessage] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        let isMounted = true;

        async function loadPendingGoogle() {
            try {
                const response = await getPendingGoogleCitizen();

                if (!isMounted) {
                    return;
                }

                setPendingGoogle(response.data);
                setForm((current) => ({
                    ...current,
                    name: response.data.name ?? '',
                }));
            } catch {
                navigate('/login', {
                    replace: true,
                    state: {
                        flash: t('auth.googleSessionExpired'),
                    },
                });
            } finally {
                if (isMounted) {
                    setIsLoading(false);
                }
            }
        }

        loadPendingGoogle();

        return () => {
            isMounted = false;
        };
    }, [navigate]);

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
        setIsSubmitting(true);

        try {
            const response = await completeGoogleCitizenRegistration(form);

            rememberCitizenSession(response.data);
            navigate('/', { replace: true });
        } catch (error) {
            const apiError = getApiError(error);
            setMessage(apiError.message);
            setErrors(apiError.errors);
        } finally {
            setIsSubmitting(false);
        }
    }

    if (isLoading) {
        return (
            <main className="relative min-h-screen bg-surface px-4 py-8 text-gray-900 sm:px-6 lg:px-8">
                <LanguageSwitcher className="right-4 top-4 z-20 sm:right-6 sm:top-6" floating />
                <section className="mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-5xl flex-col items-center justify-center gap-6">
                    <Link className="inline-flex" to="/">
                        <BrandIdentity markClassName="h-14 w-14" />
                    </Link>
                    <p className="rounded-lg border border-border bg-white px-5 py-4 text-sm font-semibold text-gray-600">
                        {t('auth.checkingGoogle')}
                    </p>
                </section>
            </main>
        );
    }

    return (
        <main className="relative min-h-screen bg-surface px-4 py-6 text-gray-900 sm:px-6 lg:px-8">
            <LanguageSwitcher className="right-4 top-4 z-20 sm:right-6 sm:top-6" floating />
            <section className="mx-auto w-full max-w-5xl">
                <header className="mb-6">
                    <Link className="inline-flex" to="/">
                        <BrandIdentity markClassName="h-12 w-12" />
                    </Link>
                </header>

                <div className="grid items-start gap-6 lg:grid-cols-[280px_1fr]">
                    <aside className="rounded-lg border border-border bg-blue-50 p-5">
                        <p className="text-sm font-semibold uppercase text-primary">{t('auth.googleLogin')}</p>
                        <h1 className="mt-3 text-2xl font-bold leading-tight text-gray-950">
                            {t('auth.completeCitizenInfo')}
                        </h1>
                        <p className="mt-3 text-sm leading-6 text-gray-600">
                            {t('auth.googleVerified')}
                        </p>
                        <div className="mt-5 rounded-md bg-white px-3 py-2 text-sm">
                            <p className="font-semibold text-gray-500">{t('auth.googleEmail')}</p>
                            <p className="mt-1 break-all text-gray-950">{pendingGoogle.email}</p>
                        </div>
                    </aside>

                    <form className="card-container rounded-lg p-5 shadow-sm sm:p-6" noValidate onSubmit={submitForm}>
                        <div className="grid gap-x-4 gap-y-4 md:grid-cols-2">
                            <FormField
                                autoComplete="name"
                                errors={errors.name}
                                label={t('auth.fullName')}
                                name="name"
                                onChange={updateField}
                                value={form.name}
                            />
                            <FormField
                                errors={errors.citizen_id}
                                helpText={t('auth.citizenIdHelp')}
                                label={t('auth.citizenId')}
                                name="citizen_id"
                                onChange={updateField}
                                value={form.citizen_id}
                            />
                            <FormField
                                errors={errors.date_of_birth}
                                label={t('auth.dateOfBirth')}
                                name="date_of_birth"
                                onChange={updateField}
                                type="date"
                                value={form.date_of_birth}
                            />
                            <FormField
                                autoComplete="tel"
                                errors={errors.phone}
                                label={t('auth.phone')}
                                name="phone"
                                onChange={updateField}
                                value={form.phone}
                            />
                            <div className="md:col-span-2">
                                <label className="label mb-1.5 normal-case tracking-normal" htmlFor="address">
                                    {t('auth.address')}
                                </label>
                                <textarea
                                    className={`input-field min-h-20 resize-y rounded-lg px-3.5 py-2.5 text-sm ${
                                        errors.address ? 'input-error' : ''
                                    }`}
                                    id="address"
                                    name="address"
                                    onChange={updateField}
                                    value={form.address}
                                />
                                <FieldError errors={errors.address} />
                            </div>
                        </div>

                        {message && (
                            <p className="mt-6 rounded-lg bg-red-50 px-4 py-3 text-sm font-semibold text-danger">
                                {message}
                            </p>
                        )}

                        <div className="mt-6 flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <Link className="text-center text-sm font-semibold text-primary" to="/login">
                                {t('auth.backToLogin')}
                            </Link>
                            <button className="btn-primary rounded-full px-8 py-3 text-base" disabled={isSubmitting}>
                                {isSubmitting ? t('auth.completing') : t('auth.complete')}
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    );
}
