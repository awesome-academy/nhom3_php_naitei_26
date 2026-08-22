const STATUS_PRESENTATION = {
    received: {
        labelKey: 'status.received.label',
        descriptionKey: 'status.received.description',
        tone: 'c-neutral',
    },
    processing: {
        labelKey: 'status.processing.label',
        descriptionKey: 'status.processing.description',
        tone: 'c-info',
    },
    supplement_required: {
        labelKey: 'status.supplement_required.label',
        descriptionKey: 'status.supplement_required.description',
        tone: 'c-warning',
    },
    approved: {
        labelKey: 'status.approved.label',
        descriptionKey: 'status.approved.description',
        tone: 'c-success',
    },
    rejected: {
        labelKey: 'status.rejected.label',
        descriptionKey: 'status.rejected.description',
        tone: 'c-danger',
    },
};

export function statusPresentation(status) {
    return STATUS_PRESENTATION[status] ?? {
        labelKey: null,
        descriptionKey: null,
        tone: 'c-neutral',
    };
}

export function statusLabel(status, t = (key) => key) {
    const presentation = statusPresentation(status);

    return presentation.labelKey ? t(presentation.labelKey) : (status || t('status.unknown'));
}

export function statusDescription(status, t = (key) => key) {
    const descriptionKey = statusPresentation(status).descriptionKey;

    return descriptionKey ? t(descriptionKey) : '';
}

export function statusTone(status) {
    return statusPresentation(status).tone;
}

export function transitionDescription(entry, t = (key) => key, useServerDescription = true) {
    if (useServerDescription && entry?.description) {
        return entry.description;
    }

    if (!entry?.from_status) {
        return t('status.submittedDescription');
    }

    return statusDescription(entry?.to_status, t);
}
