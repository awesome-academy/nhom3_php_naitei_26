const DEFAULT_LOCALE = 'vi-VN';

export function formatDate(value, locale = DEFAULT_LOCALE) {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return date.toLocaleDateString(locale, {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

export function formatDateTime(value, locale = DEFAULT_LOCALE) {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return date.toLocaleString(locale, {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function formatFee(fee, locale = DEFAULT_LOCALE, freeLabel = 'Miễn phí') {
    const amount = Number(fee);

    if (!Number.isFinite(amount) || amount <= 0) {
        return freeLabel;
    }

    return `${new Intl.NumberFormat(locale).format(amount)} ₫`;
}

export function formatBytes(bytes) {
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
