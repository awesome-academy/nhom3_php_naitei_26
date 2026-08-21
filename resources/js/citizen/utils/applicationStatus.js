const STATUS_PRESENTATION = {
    received: {
        label: 'Đã tiếp nhận',
        description: 'Hồ sơ đã được ghi nhận và đang chờ cán bộ kiểm tra.',
        tone: 'c-neutral',
    },
    processing: {
        label: 'Đang xử lý',
        description: 'Hồ sơ đang được cán bộ phụ trách xử lý.',
        tone: 'c-info',
    },
    supplement_required: {
        label: 'Cần bổ sung',
        description: 'Hồ sơ cần bổ sung thông tin hoặc tài liệu theo yêu cầu.',
        tone: 'c-warning',
    },
    approved: {
        label: 'Đã duyệt',
        description: 'Hồ sơ đã được duyệt và có thể có tài liệu kết quả.',
        tone: 'c-success',
    },
    rejected: {
        label: 'Bị từ chối',
        description: 'Hồ sơ đã bị từ chối. Vui lòng xem lý do xử lý.',
        tone: 'c-danger',
    },
};

export function statusPresentation(status) {
    return STATUS_PRESENTATION[status] ?? {
        label: status || 'Không xác định',
        description: '',
        tone: 'c-neutral',
    };
}

export function statusLabel(status) {
    return statusPresentation(status).label;
}

export function statusDescription(status) {
    return statusPresentation(status).description;
}

export function statusTone(status) {
    return statusPresentation(status).tone;
}

export function transitionDescription(entry) {
    if (entry?.description) {
        return entry.description;
    }

    if (!entry?.from_status) {
        return 'Hồ sơ được nộp thành công và bắt đầu quy trình xử lý.';
    }

    return statusDescription(entry?.to_status);
}
