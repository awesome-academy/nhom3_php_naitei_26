import { Link, useNavigate } from 'react-router-dom';
import { useEffect, useState } from 'react';

import { getApiError, getRememberedCitizen, registerCitizen } from '../api/auth';
import BrandIdentity from '../components/BrandIdentity';
import FormField, { FieldError } from '../components/FormField';
import LanguageSwitcher from '../components/LanguageSwitcher';
import { useLanguage } from '../i18n/LanguageContext';

const initialForm = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    citizen_id: '',
    date_of_birth: '',
    phone: '',
    address: '',
};

export default function RegisterPage() {
    const navigate = useNavigate();
    const { t } = useLanguage();
    const [form, setForm] = useState(initialForm);
    const [errors, setErrors] = useState({});
    const [message, setMessage] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        if (getRememberedCitizen()) {
            navigate('/', { replace: true });
        }
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
            await registerCitizen(form);
            navigate('/login', {
                replace: true,
                state: {
                    flash: t('auth.registerSuccess'),
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
                        <p className="text-sm font-semibold uppercase text-primary">{t('auth.citizenAccount')}</p>
                        <h1 className="mt-3 text-2xl font-bold leading-tight text-gray-950">
                            {t('auth.registerServiceTitle')}
                        </h1>
                        <p className="mt-3 text-sm leading-6 text-gray-600">
                            {t('auth.registerIdentityHelp')}
                        </p>
                        <ul className="mt-5 space-y-3 text-sm text-gray-700">
                            <li className="rounded-md bg-white px-3 py-2">{t('auth.identityUnique')}</li>
                            <li className="rounded-md bg-white px-3 py-2">{t('auth.emailForLogin')}</li>
                            <li className="rounded-md bg-white px-3 py-2">{t('auth.contactForProcessing')}</li>
                        </ul>
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
                                autoComplete="email"
                                errors={errors.email}
                                label={t('auth.email')}
                                name="email"
                                onChange={updateField}
                                type="email"
                                value={form.email}
                            />
                            <FormField
                                errors={errors.citizen_id}
                                helpText={t('auth.citizenIdRegisterHelp')}
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
                            <FormField
                                autoComplete="new-password"
                                errors={errors.password}
                                helpText={t('auth.passwordHelp')}
                                label={t('auth.password')}
                                name="password"
                                onChange={updateField}
                                type="password"
                                value={form.password}
                            />
                            <FormField
                                autoComplete="new-password"
                                errors={errors.password_confirmation}
                                label={t('auth.passwordConfirmation')}
                                name="password_confirmation"
                                onChange={updateField}
                                type="password"
                                value={form.password_confirmation}
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
                            <p className="text-center text-sm text-gray-600 sm:text-left">
                                {t('auth.hasAccount')}{' '}
                                <Link className="font-semibold text-primary hover:text-primary-hover" to="/login">
                                    {t('auth.loginTitle')}
                                </Link>
                            </p>
                            <button className="btn-primary rounded-full px-8 py-3 text-base" disabled={isSubmitting}>
                                {isSubmitting ? t('auth.registering') : t('nav.register')}
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    );
}
