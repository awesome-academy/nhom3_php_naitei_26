import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';

import { fetchCitizenProfile } from '../api/profile';
import { fetchService } from '../api/services';
import { createApplication, uploadApplicationDocument } from '../api/applications';
import { forgetCitizenSession, getApiError } from '../api/auth';
import ApplySteps from '../components/ApplySteps';
import DocumentUploader from '../components/DocumentUploader';
import Footer from '../components/Footer';
import Header from '../components/Header';
import { useLanguage } from '../i18n/LanguageContext';
import { localizeService } from '../i18n/content';
import { formatFee } from '../utils/format';
import { normalizeDocumentRequirements, normalizeSchemaFields } from '../utils/schema';

function FieldInput({ field, value, onChange, hasError }) {
    if (field.type === 'boolean') {
        return (
            <label className="flex items-center gap-3">
                <input
                    type="checkbox"
                    className="checkbox-field"
                    checked={Boolean(value)}
                    onChange={(event) => onChange(event.target.checked)}
                />
                <span className="text-sm font-medium text-gray-700">{field.label}</span>
            </label>
        );
    }

    return (
        <div>
            <label className="label mb-1.5 normal-case tracking-normal" htmlFor={field.name}>
                {field.label}
                {field.required && <span className="ml-0.5 text-danger">*</span>}
            </label>
            <input
                id={field.name}
                name={field.name}
                type={field.type}
                value={typeof value === 'string' ? value : ''}
                onChange={(event) => onChange(event.target.value)}
                className={`input-field rounded-lg px-3.5 py-2.5 text-sm ${hasError ? 'input-error' : ''}`}
            />
        </div>
    );
}

export default function ApplyPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { language, locale, t } = useLanguage();

    const [service, setService] = useState(null);
    const [serviceError, setServiceError] = useState(false);
    const [loading, setLoading] = useState(true);
    const [currentStep, setCurrentStep] = useState(0);
    const [formData, setFormData] = useState({});
    const [clientErrors, setClientErrors] = useState({});
    const [files, setFiles] = useState([]);
    const [message, setMessage] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const localizedService = useMemo(() => localizeService(service, language), [language, service]);
    const fields = useMemo(() => (localizedService ? normalizeSchemaFields(localizedService) : []), [localizedService]);
    const requirements = useMemo(() => (localizedService ? normalizeDocumentRequirements(localizedService) : []), [localizedService]);

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
                    state: { flash: t('apply.loginRequired') },
                });

                return;
            }

            try {
                const response = await fetchService(id);

                if (isMounted) {
                    setService(response.data.data);
                }
            } catch {
                if (isMounted) {
                    setServiceError(true);
                }
            } finally {
                if (isMounted) {
                    setLoading(false);
                }
            }
        }

        boot();

        return () => {
            isMounted = false;
        };
    }, [id, navigate]);

    function updateField(name) {
        return (value) => {
            setFormData((current) => ({ ...current, [name]: value }));
            setClientErrors((current) => ({ ...current, [name]: undefined }));
        };
    }

    function validateStepOne() {
        const errors = {};

        fields.forEach((field) => {
            if (!field.required) {
                return;
            }

            const value = formData[field.name];

            if (field.type === 'boolean') {
                if (!value) {
                    errors[field.name] = t('apply.confirmField', { field: field.label });
                }

                return;
            }

            if (value === undefined || String(value).trim() === '') {
                errors[field.name] = t('apply.enterField', { field: field.label.toLowerCase() });
            }
        });

        setClientErrors(errors);

        return Object.keys(errors).length === 0;
    }

    function goToStepOne() {
        if (validateStepOne()) {
            setCurrentStep(1);
        }
    }

    function goToStepTwo() {
        setCurrentStep(2);
    }

    function filesForCode(code) {
        return files.filter((entry) => entry.requirementCode === code);
    }

    function addFiles(code, fileList) {
        setFiles((current) => [
            ...current,
            ...Array.from(fileList).map((file) => ({ requirementCode: code, file })),
        ]);
    }

    function removeFile(entry) {
        setFiles((current) => current.filter((item) => item !== entry));
    }

    const missingRequired = requirements.filter((requirement) => requirement.required && filesForCode(requirement.code).length === 0);

    async function submitApplication() {
        setMessage('');
        setSubmitting(true);

        try {
            const response = await createApplication(service.id, formData);
            const application = response.data;

            let uploadFailures = 0;

            if (files.length > 0) {
                for (const entry of files) {
                    try {
                        await uploadApplicationDocument(application.id, entry.file, entry.requirementCode || undefined);
                    } catch {
                        uploadFailures += 1;
                    }
                }
            }

            if (uploadFailures > 0) {
                navigate(`/applications/${application.id}`, {
                    state: {
                        flash: t('apply.uploadPartial', { code: application.application_code, count: uploadFailures }),
                    },
                });

                return;
            }

            navigate('/applications', {
                state: { flash: t('apply.success', { code: application.application_code }) },
            });
        } catch (error) {
            const apiError = getApiError(error);

            if (error?.response?.status === 401) {
                forgetCitizenSession();
                navigate('/login', {
                    replace: true,
                    state: { flash: t('auth.sessionExpired') },
                });

                return;
            }

            setMessage(apiError.message);
        } finally {
            setSubmitting(false);
        }
    }

    if (loading) {
        return (
            <main className="min-h-screen bg-surface flex flex-col font-sans">
                <Header />
                <div className="flex-1 w-full max-w-[1101px] mx-auto bg-white border-x border-gray-200 flex items-center justify-center py-20 text-gray-500">
                    {t('common.loading')}
                </div>
                <Footer />
            </main>
        );
    }

    if (serviceError || !localizedService) {
        return (
            <main className="min-h-screen bg-surface flex flex-col font-sans">
                <Header />
                <div className="flex-1 w-full max-w-[1101px] mx-auto bg-white border-x border-gray-200 flex flex-col items-center justify-center py-20">
                    <p className="text-gray-600">{t('apply.notFound')}</p>
                    <Link className="mt-4 text-sm font-semibold text-primary hover:underline" to="/services">
                        {t('services.backToList')}
                    </Link>
                </div>
                <Footer />
            </main>
        );
    }

    return (
        <main className="min-h-screen bg-surface flex flex-col font-sans">
            <Header />

            <div className="flex-1 w-full max-w-[1101px] mx-auto bg-white border-x border-gray-200 flex flex-col">
                <div className="px-10 py-6 border-b border-gray-100">
                    <Link className="flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-blue-600 transition" to={`/services/${localizedService.id}`}>
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" /></svg>
                        {t('apply.backToService')}
                    </Link>
                </div>

                <div className="flex-1 px-10 py-8">
                    <div className="mb-8">
                        <h1 className="text-[26px] font-bold tracking-tight text-gray-900">{localizedService.name}</h1>
                        <p className="mt-1 text-sm text-gray-500">{t('apply.onlineForService')}</p>
                    </div>

                    <div className="mb-10">
                        <ApplySteps currentStep={currentStep} />
                    </div>

                    {!localizedService.is_active && (
                        <div className="mb-8 rounded-xl border border-red-200 bg-red-50 p-4 text-[15px] text-red-700">
                            {t('apply.serviceSuspended')}
                        </div>
                    )}

                    {currentStep === 0 && (
                        <section className="mx-auto max-w-2xl">
                            <div className="grid gap-x-6 gap-y-5">
                                {fields.map((field) => (
                                    <FieldInput
                                        key={field.name}
                                        field={field}
                                        value={formData[field.name]}
                                        onChange={updateField(field.name)}
                                        hasError={Boolean(clientErrors[field.name])}
                                    />
                                ))}
                            </div>

                            {fields.length === 0 && (
                                <p className="rounded-xl bg-gray-50 border border-gray-100 p-5 text-sm text-gray-600">
                                    {t('apply.noExtraFields')}
                                </p>
                            )}

                            <div className="mt-8 flex justify-end">
                                <button
                                    type="button"
                                    disabled={!localizedService.is_active}
                                    className="btn-primary rounded-xl px-8 py-3 text-[15px]"
                                    onClick={goToStepOne}
                                >
                                    {t('apply.continueDocuments')}
                                </button>
                            </div>
                        </section>
                    )}

                    {currentStep === 1 && (
                        <section className="mx-auto max-w-2xl">
                            {requirements.length > 0 ? (
                                <div className="space-y-6">
                                    {requirements.map((requirement) => (
                                        <div key={requirement.code} className="rounded-2xl border border-gray-200 bg-white p-5">
                                            <div className="mb-3 flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="text-[15px] font-semibold text-gray-900">
                                                        {requirement.label}
                                                        {requirement.required && <span className="ml-1 text-danger">*</span>}
                                                    </p>
                                                    <p className="mt-0.5 text-xs text-gray-500">{t('common.code')}: {requirement.code}</p>
                                                </div>
                                                {requirement.required && filesForCode(requirement.code).length === 0 && (
                                                    <span className="shrink-0 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-danger">{t('apply.missing')}</span>
                                                )}
                                            </div>
                                            <DocumentUploader
                                                requirement={requirement}
                                                files={filesForCode(requirement.code)}
                                                onAdd={(next) => addFiles(requirement.code, next)}
                                                onRemove={(file) => removeFile(filesForCode(requirement.code).find((entry) => entry.file === file))}
                                            />
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <DocumentUploader
                                    requirement={null}
                                    files={filesForCode('')}
                                    onAdd={(next) => addFiles('', next)}
                                    onRemove={(file) => removeFile(filesForCode('').find((entry) => entry.file === file))}
                                />
                            )}

                            <div className="mt-8 flex items-center justify-between">
                                <button type="button" className="btn-secondary rounded-xl px-8 py-3 text-[15px]" onClick={() => setCurrentStep(0)}>
                                    {t('common.back')}
                                </button>
                                <button type="button" className="btn-primary rounded-xl px-8 py-3 text-[15px]" onClick={goToStepTwo}>
                                    {t('apply.reviewSubmit')}
                                </button>
                            </div>
                        </section>
                    )}

                    {currentStep === 2 && (
                        <section className="mx-auto max-w-2xl">
                            <div className="rounded-2xl border-[1.5px] border-gray-100 bg-gray-50 p-6">
                                <h3 className="mb-4 text-[17px] font-bold text-gray-900">{t('apply.enteredInformation')}</h3>
                                <dl className="space-y-3">
                                    <div className="flex justify-between gap-6">
                                        <dt className="text-sm text-gray-500">{t('apply.service')}</dt>
                                        <dd className="text-sm font-semibold text-gray-900">{localizedService.name}</dd>
                                    </div>
                                    {fields.map((field) => (
                                        <div key={field.name} className="flex justify-between gap-6">
                                            <dt className="text-sm text-gray-500">{field.label}</dt>
                                            <dd className="text-sm font-semibold text-gray-900">
                                                {field.type === 'boolean'
                                                    ? (formData[field.name] ? t('common.yes') : t('common.no'))
                                                    : String(formData[field.name] ?? '—')}
                                            </dd>
                                        </div>
                                    ))}
                                    <div className="flex justify-between gap-6">
                                        <dt className="text-sm text-gray-500">{t('apply.attachments')}</dt>
                                        <dd className="text-sm font-semibold text-gray-900">{t('apply.attachmentCount', { count: files.length })}</dd>
                                    </div>
                                    <div className="flex justify-between gap-6">
                                        <dt className="text-sm text-gray-500">{t('apply.fee')}</dt>
                                        <dd className="text-sm font-semibold text-gray-900">{formatFee(localizedService.fee, locale, t('common.free'))}</dd>
                                    </div>
                                </dl>
                            </div>

                            {missingRequired.length > 0 && (
                                <div className="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-danger">
                                    {t('apply.missingRequired', { count: missingRequired.length, names: missingRequired.map((req) => req.label).join(', ') })}
                                </div>
                            )}

                            {message && (
                                <p className="mt-6 rounded-lg bg-red-50 px-4 py-3 text-sm font-semibold text-danger">{message}</p>
                            )}

                            <div className="mt-8 flex items-center justify-between">
                                <button type="button" className="btn-secondary rounded-xl px-8 py-3 text-[15px]" onClick={() => setCurrentStep(1)}>
                                    {t('common.back')}
                                </button>
                                <button
                                    type="button"
                                    disabled={!localizedService.is_active || submitting}
                                    className="btn-success rounded-xl px-8 py-3 text-[15px]"
                                    onClick={submitApplication}
                                >
                                    {submitting ? t('apply.submitting') : t('apply.submit')}
                                </button>
                            </div>
                        </section>
                    )}
                </div>

                <Footer />
            </div>
        </main>
    );
}
