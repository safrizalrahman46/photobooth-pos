import { ref } from 'vue';
import { resolveRequestErrorMessage } from '../requestErrors';

export const useAppSettingsModule = ({
    props,
    parseRequestError,
    getCsrfToken,
}) => {
    const appSettingsGroups = ref(props.initialAppSettingsGroups && typeof props.initialAppSettingsGroups === 'object'
        ? props.initialAppSettingsGroups
        : {
            general: {},
            booking: {},
            payment: {},
            ui: {},
        });
    const appSettingsLoading = ref(false);
    const appSettingsSaving = ref(false);
    const appSettingsError = ref('');
    const appSettingsSuccess = ref('');

    const applyAppSettingsPayload = (payload) => {
        const nextGroups = payload?.data?.app_settings;

        if (!nextGroups || typeof nextGroups !== 'object') {
            return;
        }

        appSettingsGroups.value = {
            general: nextGroups.general && typeof nextGroups.general === 'object' ? nextGroups.general : {},
            booking: nextGroups.booking && typeof nextGroups.booking === 'object' ? nextGroups.booking : {},
            payment: nextGroups.payment && typeof nextGroups.payment === 'object' ? nextGroups.payment : {},
            ui: nextGroups.ui && typeof nextGroups.ui === 'object' ? nextGroups.ui : {},
        };
    };

    const fetchAppSettingsData = async ({ silent = false } = {}) => {
        if (!props.appSettingsDataUrl) {
            return;
        }

        if (!silent) {
            appSettingsLoading.value = true;
        }
        appSettingsError.value = '';

        try {
            const response = await fetch(props.appSettingsDataUrl, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(await parseRequestError(response));
            }

            const payload = await response.json();
            applyAppSettingsPayload(payload);
        } catch (error) {
            if (!silent) {
                appSettingsError.value = resolveRequestErrorMessage(error, 'Gagal memuat pengaturan aplikasi.');
            }
        } finally {
            if (!silent) {
                appSettingsLoading.value = false;
            }
        }
    };

    const updateAppSetting = async ({ group, value }) => {
        const groupKey = String(group || '').trim();

        if (!groupKey || !props.appSettingBaseUrl) {
            return;
        }

        appSettingsSaving.value = true;
        appSettingsError.value = '';
        appSettingsSuccess.value = '';

        try {
            const response = await fetch(`${props.appSettingBaseUrl}/${encodeURIComponent(groupKey)}`, {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({ value }),
            });

            if (!response.ok) {
                throw new Error(await parseRequestError(response));
            }

            const result = await response.json();
            applyAppSettingsPayload(result);
            appSettingsSuccess.value = String(result?.message || 'Pengaturan aplikasi berhasil diperbarui.');
        } catch (error) {
            appSettingsError.value = resolveRequestErrorMessage(error, 'Gagal memperbarui pengaturan aplikasi.');
            throw error;
        } finally {
            appSettingsSaving.value = false;
        }
    };

    return {
        appSettingsGroups,
        appSettingsLoading,
        appSettingsSaving,
        appSettingsError,
        appSettingsSuccess,
        fetchAppSettingsData,
        updateAppSetting,
    };
};
