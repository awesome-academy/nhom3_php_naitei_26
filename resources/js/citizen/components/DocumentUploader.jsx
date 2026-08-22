import { useRef, useState } from 'react';
import { useLanguage } from '../i18n/LanguageContext';
import { requirementAccept } from '../utils/schema';

const MAX_SIZE = 10 * 1024 * 1024;

const MIME_PDF = 'application/pdf';
const MIME_JPEG = 'image/jpeg';
const MIME_PNG = 'image/png';

function formatBytes(bytes) {
    if (!Number.isFinite(bytes)) {
        return '';
    }

    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function acceptedTypesFor(type) {
    switch (type) {
        case 'pdf':
            return [MIME_PDF];
        case 'image':
            return [MIME_JPEG, MIME_PNG];
        default:
            return [MIME_PDF, MIME_JPEG, MIME_PNG];
    }
}

function typeHint(type, t) {
    switch (type) {
        case 'pdf':
            return t('apply.uploadPdfHint');
        case 'image':
            return t('apply.uploadImageHint');
        default:
            return t('apply.uploadMixedHint');
    }
}

export default function DocumentUploader({ requirement, files = [], onAdd, onRemove }) {
    const { t } = useLanguage();
    const inputRef = useRef(null);
    const [message, setMessage] = useState('');

    const acceptedTypes = acceptedTypesFor(requirement?.type);
    const hint = typeHint(requirement?.type, t);

    function handleFiles(selected) {
        const next = Array.from(selected ?? []);
        const invalid = next.find(
            (file) => !acceptedTypes.includes(file.type) || file.size > MAX_SIZE,
        );

        if (invalid) {
            setMessage(t('apply.invalidFile'));
            return;
        }

        setMessage('');
        onAdd(next);
    }

    return (
        <div>
            <button
                type="button"
                className="mx-auto flex w-full max-w-2xl flex-col items-center justify-center gap-3 rounded-3xl border-2 border-dashed border-gray-300 bg-surface px-6 py-10 text-center transition hover:border-primary hover:bg-blue-50"
                onClick={() => inputRef.current?.click()}
            >
                <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#E8F0FE] text-primary">
                    <svg className="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M12 16V4m0 0l-4 4m4-4l4 4M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2" /></svg>
                </div>
                <div className="text-center">
                    <p className="text-base font-semibold text-gray-900">{t('apply.dropFiles')}</p>
                    <p className="mt-1 text-sm text-gray-500">{hint}</p>
                </div>
            </button>

            <input
                ref={inputRef}
                type="file"
                accept={requirementAccept(requirement)}
                multiple
                className="hidden"
                onChange={(event) => {
                    handleFiles(event.target.files);
                    event.target.value = '';
                }}
            />

            {message && <p className="mt-3 rounded-lg bg-red-50 px-4 py-2.5 text-sm font-semibold text-danger">{message}</p>}

            {files.length > 0 && (
                <ul className="mt-4 space-y-2.5">
                    {files.map((item, index) => {
                        const file = item.file ?? item;

                        return (
                            <li key={`${file.name}-${file.lastModified}`} className="flex items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3">
                                <div className="flex min-w-0 items-center gap-3">
                                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#E8F0FE] text-primary">
                                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    </div>
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-semibold text-gray-900">{file.name}</p>
                                        <p className="text-xs text-gray-500">{formatBytes(file.size)}</p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    className="shrink-0 rounded-lg px-3 py-1.5 text-sm font-semibold text-danger transition hover:bg-red-50"
                                    onClick={() => onRemove(file, index)}
                                >
                                    {t('common.delete')}
                                </button>
                            </li>
                        );
                    })}
                </ul>
            )}
        </div>
    );
}
