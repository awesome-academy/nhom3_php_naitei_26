import { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate, useParams } from 'react-router-dom';

import {
    deleteApplicationDocument,
    downloadApplicationDocument,
    fetchApplication,
    uploadApplicationDocument,
} from '../api/applications';
import { forgetCitizenSession, getApiError } from '../api/auth';
import { fetchCitizenProfile } from '../api/profile';
import DocumentUploader from '../components/DocumentUploader';
import Footer from '../components/Footer';
import Header from '../components/Header';
import StatusBadge from '../components/StatusBadge';
import { useLanguage } from '../i18n/LanguageContext';
import { localizeService } from '../i18n/content';
import { statusDescription, statusLabel, transitionDescription } from '../utils/applicationStatus';
import { formatBytes, formatDateTime } from '../utils/format';
import { normalizeDocumentRequirements } from '../utils/schema';

export default function MyApplicationDetailPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const location = useLocation();
    const { language, locale, t } = useLanguage();

    const [application, setApplication] = useState(null);
    const [loading, setLoading] = useState(true);
    const [loadError, setLoadError] = useState(false);
    const [flash, setFlash] = useState(location.state?.flash ?? '');
    const [message, setMessage] = useState('');
    const [files, setFiles] = useState([]);
    const [uploading, setUploading] = useState(false);
    const [deletingId, setDeletingId] = useState(null);
    const [previewDoc, setPreviewDoc] = useState(null);

    const isEditable = application?.status === 'received';
    const canUpload = application?.status === 'received' || application?.status === 'supplement_required';
    const supplementNote = application?.supplement_note ?? null;
    const timeline = application?.timeline ?? [];
    const resultNote = application?.result_note ?? null;
    const rejectionReason = application?.rejection_reason ?? null;
    const localizedService = localizeService(application?.service_type, language);

    async function loadApplication() {
        setLoadError(false);

        try {
            const response = await fetchApplication(id);
            setApplication(response.data);
        } catch (error) {
            if (error?.response?.status === 401) {
                forgetCitizenSession();
                navigate('/login', {
                    replace: true,
                    state: { flash: t('applications.loginRequired') },
                });

                return;
            }

            if (error?.response?.status === 403 || error?.response?.status === 404) {
                navigate('/applications', { replace: true });

                return;
            }

            setLoadError(true);
        } finally {
            setLoading(false);
        }
    }

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
                    state: { flash: t('applications.loginRequired') },
                });

                return;
            }

            if (isMounted) {
                await loadApplication();
            }
        }

        boot();

        return () => {
            isMounted = false;
        };
    }, [id, navigate]);

    useEffect(() => {
        if (!application || application.status === 'approved' || application.status === 'rejected') {
            return undefined;
        }

        const interval = window.setInterval(() => {
            loadApplication();
        }, 30000);

        const handleVisibility = () => {
            if (document.visibilityState === 'visible') {
                loadApplication();
            }
        };

        const handleFocus = () => loadApplication();

        window.addEventListener('focus', handleFocus);
        document.addEventListener('visibilitychange', handleVisibility);

        return () => {
            window.clearInterval(interval);
            window.removeEventListener('focus', handleFocus);
            document.removeEventListener('visibilitychange', handleVisibility);
        };
    }, [application?.status, id]);

    useEffect(() => {
        function refreshApplication(event) {
            const notifications = event.detail?.notifications ?? [];
            const hasCurrentApplicationUpdate = notifications.some((notification) => (
                String(notification.application_id) === String(id)
            ));

            if (hasCurrentApplicationUpdate) {
                loadApplication();
            }
        }

        window.addEventListener('citizen-notifications:updated', refreshApplication);

        return () => {
            window.removeEventListener('citizen-notifications:updated', refreshApplication);
        };
    }, [id]);

    useEffect(() => {
        if (!flash) {
            return undefined;
        }

        const timeout = window.setTimeout(() => setFlash(''), 6000);

        return () => window.clearTimeout(timeout);
    }, [flash]);

    async function handleUpload() {
        if (files.length === 0) {
            return;
        }

        setMessage('');
        setUploading(true);

        try {
            for (const entry of files) {
                await uploadApplicationDocument(id, entry.file, entry.requirementCode || undefined);
            }

            setFiles([]);
            await loadApplication();
        } catch (error) {
            setMessage(getApiError(error).message);
        } finally {
            setUploading(false);
        }
    }

    async function handleDelete(documentId) {
        setDeletingId(documentId);
        setMessage('');

        try {
            await deleteApplicationDocument(id, documentId);
            await loadApplication();
        } catch (error) {
            setMessage(getApiError(error).message);
        } finally {
            setDeletingId(null);
        }
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

    function isPreviewable(doc) {
        return ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'].includes(doc.mime_type);
    }

    function getPreviewUrl(doc) {
        return `/api/v1/applications/${id}/documents/${doc.id}?inline=1`;
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

    if (loadError || !application) {
        return (
            <main className="min-h-screen bg-surface flex flex-col font-sans">
                <Header />
                <div className="flex-1 w-full max-w-[1101px] mx-auto bg-white border-x border-gray-200 flex flex-col items-center justify-center py-20">
                    <p className="text-gray-600">{t('applications.detailLoadError')}</p>
                    <button type="button" className="mt-4 text-sm font-semibold text-primary hover:underline" onClick={() => { setLoading(true); loadApplication(); }}>
                        {t('applications.tryAgain')}
                    </button>
                </div>
                <Footer />
            </main>
        );
    }

    const formEntries = Object.entries(application.form_data ?? {});
    const documents = application.documents ?? [];
    const missingDocs = application.missing_required_documents ?? [];

    const serviceRequirements = normalizeDocumentRequirements({
        document_requirements: localizedService?.document_requirements,
    });
    const requirementByCode = Object.fromEntries(serviceRequirements.map((requirement) => [requirement.code, requirement]));

    const documentGroups = [];

    documents.forEach((document) => {
        const label = document.requirement_label || t('applications.otherDocument');
        let group = documentGroups.find((item) => item.label === label);

        if (!group) {
            group = { label, code: document.requirement_code, items: [] };
            documentGroups.push(group);
        }

        group.items.push(document);
    });

    const missingSlots = missingDocs.map((missing) => (
        requirementByCode[missing.code] ?? { code: missing.code, label: missing.label, type: 'mixed', required: true }
    ));

    return (
        <main className="min-h-screen bg-surface flex flex-col font-sans">
            <Header />

            <div className="flex-1 w-full max-w-[1101px] mx-auto bg-white border-x border-gray-200 flex flex-col">
                <div className="px-10 py-6 border-b border-gray-100">
                    <Link className="flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-blue-600 transition" to="/applications">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" /></svg>
                        {t('applications.backToMine')}
                    </Link>
                </div>

                <div className="flex-1 w-full max-w-3xl mx-auto px-10 py-8">
                    {flash && (
                        <div className="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-success">
                            {flash}
                        </div>
                    )}

                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="font-consolas text-sm text-gray-500">{application.application_code}</p>
                            <h1 className="mt-1 text-[26px] font-bold tracking-tight text-gray-900">
                                {localizedService?.name ?? t('applications.service')}
                            </h1>
                        </div>
                        <StatusBadge status={application.status} />
                    </div>

                    <div className="mt-8 grid gap-4 sm:grid-cols-2">
                        <div className="rounded-2xl border-[1.5px] border-gray-100 bg-gray-50 p-6">
                            <h3 className="text-[13px] font-semibold text-gray-400 uppercase tracking-widest">{t('applications.submittedDate')}</h3>
                            <p className="mt-1 text-lg font-bold text-gray-900">{formatDateTime(application.submitted_at, locale)}</p>
                        </div>
                        <div className="rounded-2xl border-[1.5px] border-gray-100 bg-gray-50 p-6">
                            <h3 className="text-[13px] font-semibold text-gray-400 uppercase tracking-widest">{t('applications.status')}</h3>
                            <p className="mt-1 text-lg font-bold text-gray-900">{statusLabel(application.status, t)}</p>
                            <p className="mt-1 text-sm leading-6 text-gray-500">{statusDescription(application.status, t)}</p>
                        </div>
                    </div>

                    {formEntries.length > 0 && (
                        <section className="mt-8">
                            <h2 className="mb-4 text-[18px] font-bold text-gray-900">{t('applications.declaredInformation')}</h2>
                            <div className="rounded-2xl border-[1.5px] border-gray-100 bg-white p-6">
                                <dl className="grid gap-x-8 gap-y-4 sm:grid-cols-2">
                                    {formEntries.map(([key, value]) => (
                                        <div key={key}>
                                            <dt className="text-[13px] font-semibold text-gray-400 uppercase tracking-widest">{key}</dt>
                                            <dd className="mt-1 text-[15px] font-medium text-gray-900 break-words">{String(value ?? '—')}</dd>
                                        </div>
                                    ))}
                                </dl>
                            </div>
                        </section>
                    )}

                    {application.status === 'supplement_required' && supplementNote && (
                        <div className="mt-6 rounded-xl border border-amber-300 bg-amber-50 px-4 py-4" role="alert">
                            <p className="text-[13px] font-bold uppercase tracking-wide text-amber-800">{t('applications.supplementRequest')}</p>
                            <p className="mt-2 text-sm leading-6 text-amber-900 whitespace-pre-wrap">{supplementNote}</p>
                            {missingDocs.length > 0 && (
                                <p className="mt-3 text-sm font-semibold text-amber-900">
                                    {t('applications.documentsNeeded', { names: missingDocs.map((doc) => doc.label).join(', ') })}
                                </p>
                            )}
                        </div>
                    )}

                    {missingDocs.length > 0 && (application.status === 'received' || application.status === 'supplement_required') && (
                        <div className="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-danger">
                            {t('applications.missingRequired', { count: missingDocs.length, names: missingDocs.map((doc) => doc.label).join(', ') })}
                        </div>
                    )}

                    <section className="mt-8">
                        <h2 className="mb-4 text-[18px] font-bold text-gray-900">{t('applications.progress')}</h2>
                        {timeline.length > 0 ? (
                            <ol className="relative border-l border-gray-200 pl-6">
                                {timeline.map((entry, idx) => {
                                    const fromLabel = statusLabel(entry.from_status, t);
                                    const toLabel = statusLabel(entry.to_status, t);

                                    return (
                                        <li key={`${entry.from_status}-${entry.to_status}-${entry.created_at}-${idx}`} className="mb-5 last:mb-0">
                                            <div className="absolute -left-[5px] mt-1 h-2.5 w-2.5 rounded-full bg-primary"></div>
                                            <p className="text-sm font-semibold text-gray-900">
                                                {entry.from_status ? `${fromLabel} → ${toLabel}` : toLabel}
                                            </p>
                                            <p className="mt-1 text-sm leading-6 text-gray-600">
                                                {transitionDescription(entry, t, language === 'vi')}
                                            </p>
                                            <p className="mt-1 text-xs text-gray-500">
                                                {entry.changed_by_name ? t('applications.byPerson', { name: entry.changed_by_name }) : t('common.system')} {entry.created_at ? `· ${formatDateTime(entry.created_at, locale)}` : ''}
                                            </p>
                                            {entry.note && (
                                                <p className="mt-2 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700 whitespace-pre-wrap">{entry.note}</p>
                                            )}
                                        </li>
                                    );
                                })}
                            </ol>
                        ) : (
                            <p className="rounded-xl border border-gray-100 bg-gray-50 p-5 text-sm text-gray-600">
                                {t('applications.progressEmpty')}
                            </p>
                        )}
                    </section>

                    {application.status === 'approved' && (
                        <div className="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4">
                            <p className="text-[13px] font-bold uppercase tracking-wide text-emerald-800">{t('applications.approvedResult')}</p>
                            {resultNote && <p className="mt-2 text-sm text-emerald-900 whitespace-pre-wrap">{resultNote}</p>}
                            {application.completed_at && (
                                <p className="mt-1 text-xs text-emerald-700">{t('applications.completedAt', { date: formatDateTime(application.completed_at, locale) })}</p>
                            )}
                            <p className="mt-2 text-sm text-emerald-800">{t('applications.resultHelp')}</p>
                        </div>
                    )}

                    {application.status === 'rejected' && (
                        <div className="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-4">
                            <p className="text-[13px] font-bold uppercase tracking-wide text-red-800">{t('applications.rejectedResult')}</p>
                            {rejectionReason && <p className="mt-2 text-sm text-red-900 whitespace-pre-wrap">{rejectionReason}</p>}
                            {application.completed_at && (
                                <p className="mt-1 text-xs text-red-700">{t('applications.completedAt', { date: formatDateTime(application.completed_at, locale) })}</p>
                            )}
                        </div>
                    )}

                    <section className="mt-8">
                        <h2 className="mb-4 text-[18px] font-bold text-gray-900">{t('applications.attachments')}</h2>

                        {documentGroups.length === 0 ? (
                            <p className="rounded-xl border border-gray-100 bg-gray-50 p-5 text-sm text-gray-600">
                                {t('applications.noAttachments')}
                            </p>
                        ) : (
                            <div className="space-y-6">
                                {documentGroups.map((group) => (
                                    <div key={group.label}>
                                        <p className="mb-2 text-[13px] font-semibold uppercase tracking-widest text-gray-400">
                                            {group.label}
                                            {group.code && <span className="ml-1 normal-case tracking-normal text-gray-400">· {group.code}</span>}
                                        </p>
                                        <ul className="border-[1.5px] border-gray-200 rounded-2xl overflow-hidden">
                                            {group.items.map((document, index) => (
                                                <li key={document.id} className={`flex items-center justify-between gap-4 p-5 ${index !== 0 ? 'border-t-[1.5px] border-gray-100' : ''}`}>
                                                    <div className="flex min-w-0 items-center gap-3">
                                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#E8F0FE] text-primary">
                                                            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                                        </div>
                                                        <div className="min-w-0">
                                                            <p className="truncate text-[15px] font-semibold text-gray-900">{document.original_name}</p>
                                                            <p className="text-xs text-gray-500">
                                                                {formatBytes(document.file_size)} · {formatDateTime(document.created_at, locale)}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div className="flex shrink-0 items-center gap-2">
                                                        {isPreviewable(document) && (
                                                            <button
                                                                type="button"
                                                                className="rounded-lg px-4 py-2 text-sm font-semibold text-primary transition hover:bg-blue-50"
                                                                onClick={() => setPreviewDoc(document)}
                                                            >
                                                                {t('common.preview') ?? 'Xem'}
                                                            </button>
                                                        )}
                                                        <button
                                                            type="button"
                                                            className="rounded-lg px-4 py-2 text-sm font-semibold text-primary transition hover:bg-blue-50"
                                                            onClick={() => downloadApplicationDocument(id, document.id, document.original_name)}
                                                        >
                                                            {t('common.download')}
                                                        </button>
                                                        {isEditable && (
                                                            <button
                                                                type="button"
                                                                disabled={deletingId === document.id}
                                                                className="rounded-lg px-4 py-2 text-sm font-semibold text-danger transition hover:bg-red-50 disabled:opacity-50"
                                                                onClick={() => handleDelete(document.id)}
                                                            >
                                                                {deletingId === document.id ? t('applications.deleting') : t('common.delete')}
                                                            </button>
                                                        )}
                                                    </div>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>

                    {canUpload && (
                        <section className="mt-8">
                            <h2 className="mb-4 text-[18px] font-bold text-gray-900">{t('applications.uploadMore')}</h2>

                            {missingSlots.length > 0 ? (
                                <div className="space-y-6">
                                    {missingSlots.map((requirement) => (
                                        <div key={requirement.code} className="rounded-2xl border border-gray-200 bg-white p-5">
                                            <p className="mb-3 text-[15px] font-semibold text-gray-900">
                                                {requirement.label}
                                                <span className="ml-1 text-danger">*</span>
                                            </p>
                                            <DocumentUploader
                                                requirement={requirement}
                                                files={filesForCode(requirement.code)}
                                                onAdd={(next) => addFiles(requirement.code, next)}
                                                onRemove={(file) => removeFile(filesForCode(requirement.code).find((entry) => entry.file === file))}
                                            />
                                        </div>
                                    ))}
                                    {files.length > 0 && (
                                        <div className="mt-4 flex justify-end">
                                            <button
                                                type="button"
                                                disabled={uploading}
                                                className="btn-primary rounded-xl px-7 py-3 text-[15px]"
                                                onClick={handleUpload}
                                            >
                                                {uploading ? t('applications.uploading') : t('applications.uploadSupplement')}
                                            </button>
                                        </div>
                                    )}
                                    <p className="mt-3 text-xs text-gray-500">{t('applications.supplementPendingHelp')}</p>
                                </div>
                            ) : (
                                <div className="rounded-xl border border-gray-100 bg-gray-50 p-5 text-sm text-gray-600">
                                    {application.status === 'supplement_required'
                                        ? t('applications.supplementComplete')
                                        : t('applications.noSupplementNeeded')}
                                </div>
                            )}
                        </section>
                    )}

                    {message && (
                        <p className="mt-6 rounded-lg bg-red-50 px-4 py-3 text-sm font-semibold text-danger">{message}</p>
                    )}
                </div>

                {previewDoc && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" onClick={() => setPreviewDoc(null)}>
                        <div className="relative flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" onClick={(e) => e.stopPropagation()}>
                            <div className="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                                <div className="min-w-0">
                                    <h3 className="truncate text-lg font-bold text-gray-900">{previewDoc.original_name}</h3>
                                    <p className="text-xs text-gray-500">{previewDoc.requirement_label || previewDoc.mime_type}</p>
                                </div>
                                <button type="button" className="ml-4 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-2xl text-gray-500 hover:bg-gray-100" onClick={() => setPreviewDoc(null)} aria-label="Đóng">×</button>
                            </div>
                            <div className="flex-1 overflow-auto bg-gray-100 p-2">
                                {previewDoc.mime_type === 'application/pdf' ? (
                                    <iframe src={getPreviewUrl(previewDoc)} title={previewDoc.original_name} className="h-[75vh] w-full rounded-lg border border-gray-200 bg-white" />
                                ) : (
                                    <img src={getPreviewUrl(previewDoc)} alt={previewDoc.original_name} className="mx-auto max-h-[75vh] w-auto rounded-lg object-contain" />
                                )}
                            </div>
                            <div className="flex justify-end gap-3 border-t border-gray-200 bg-white px-6 py-4">
                                <button type="button" className="rounded-xl border border-gray-200 bg-white px-6 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50" onClick={() => setPreviewDoc(null)}>{t('common.close') ?? 'Đóng'}</button>
                                <button type="button" className="rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary-hover" onClick={() => downloadApplicationDocument(id, previewDoc.id, previewDoc.original_name)}>{t('common.download')}</button>
                            </div>
                        </div>
                    </div>
                )}

                <Footer />
            </div>
        </main>
    );
}
