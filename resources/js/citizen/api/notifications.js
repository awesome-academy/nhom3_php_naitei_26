import apiClient from './client';

export async function fetchNotifications(params = {}) {
    const { data } = await apiClient.get('/notifications', { params });

    return data;
}

export async function markNotificationAsRead(id) {
    const { data } = await apiClient.patch(`/notifications/${id}/read`);

    return data;
}

export async function markAllNotificationsAsRead() {
    const { data } = await apiClient.patch('/notifications/read-all');

    return data;
}
