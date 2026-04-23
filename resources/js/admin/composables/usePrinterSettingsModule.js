import { computed, ref } from 'vue';

export const usePrinterSettingsModule = ({
    props,
    parseRequestError,
    getCsrfToken,
}) => {
    const printerSettings = ref(Array.isArray(props.initialPrinterSettings) ? props.initialPrinterSettings : []);
    const printerSettingLoading = ref(false);
    const printerSettingSaving = ref(false);
    const printerSettingError = ref('');
    const deletingPrinterSettingId = ref(null);

    const applyPrinterSettingsPayload = (payload) => {
        const nextSettings = payload?.data?.printer_settings;

        if (Array.isArray(nextSettings)) {
            printerSettings.value = nextSettings;
        }
    };

    const fetchPrinterSettingsData = async ({ silent = false } = {}) => {
        if (!props.printerSettingsDataUrl) {
            return;
        }

        if (!silent) {
            printerSettingLoading.value = true;
        }
        printerSettingError.value = '';

        try {
            const response = await fetch(props.printerSettingsDataUrl, {
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
            applyPrinterSettingsPayload(payload);
        } catch (error) {
            if (!silent) {
                printerSettingError.value = error instanceof Error ? error.message : 'Failed to load printer settings.';
            }
        } finally {
            if (!silent) {
                printerSettingLoading.value = false;
            }
        }
    };

    const createPrinterSetting = async (formPayload) => {
        if (!props.printerSettingStoreUrl) {
            return;
        }

        printerSettingSaving.value = true;
        printerSettingError.value = '';

        try {
            const response = await fetch(props.printerSettingStoreUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify(formPayload),
            });

            if (!response.ok) {
                throw new Error(await parseRequestError(response));
            }

            const result = await response.json();
            applyPrinterSettingsPayload(result);
        } catch (error) {
            printerSettingError.value = error instanceof Error ? error.message : 'Failed to create printer setting.';
            throw error;
        } finally {
            printerSettingSaving.value = false;
        }
    };

    const updatePrinterSetting = async ({ id, payload }) => {
        const settingId = Number(id || 0);

        if (!settingId || !props.printerSettingBaseUrl) {
            return;
        }

        printerSettingSaving.value = true;
        printerSettingError.value = '';

        try {
            const response = await fetch(`${props.printerSettingBaseUrl}/${settingId}`, {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                throw new Error(await parseRequestError(response));
            }

            const result = await response.json();
            applyPrinterSettingsPayload(result);
        } catch (error) {
            printerSettingError.value = error instanceof Error ? error.message : 'Failed to update printer setting.';
            throw error;
        } finally {
            printerSettingSaving.value = false;
        }
    };

    const deletePrinterSetting = async (id) => {
        const settingId = Number(id || 0);

        if (!settingId || !props.printerSettingBaseUrl) {
            return;
        }

        deletingPrinterSettingId.value = settingId;
        printerSettingError.value = '';

        try {
            const response = await fetch(`${props.printerSettingBaseUrl}/${settingId}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
            });

            if (!response.ok) {
                throw new Error(await parseRequestError(response));
            }

            const result = await response.json();
            applyPrinterSettingsPayload(result);
        } catch (error) {
            printerSettingError.value = error instanceof Error ? error.message : 'Failed to delete printer setting.';
            throw error;
        } finally {
            deletingPrinterSettingId.value = null;
        }
    };

    const setDefaultPrinterSetting = async (id) => {
        const settingId = Number(id || 0);

        if (!settingId || !props.printerSettingBaseUrl) {
            return;
        }

        printerSettingSaving.value = true;
        printerSettingError.value = '';

        try {
            const response = await fetch(`${props.printerSettingBaseUrl}/${settingId}/default`, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
            });

            if (!response.ok) {
                throw new Error(await parseRequestError(response));
            }

            const result = await response.json();
            applyPrinterSettingsPayload(result);
        } catch (error) {
            printerSettingError.value = error instanceof Error ? error.message : 'Failed to set default printer.';
            throw error;
        } finally {
            printerSettingSaving.value = false;
        }
    };

    const printerSettingRows = computed(() => {
        return (printerSettings.value || []).map((item) => ({
            id: Number(item.id || 0),
            branch_id: Number(item.branch_id || 0),
            branch_name: String(item.branch_name || '-'),
            device_name: String(item.device_name || ''),
            printer_type: String(item.printer_type || 'thermal'),
            paper_width_mm: Number(item.paper_width_mm || 80),
            is_default: Boolean(item.is_default),
            is_active: Boolean(item.is_active),
            connection: item.connection && typeof item.connection === 'object' ? item.connection : {},
        }));
    });

    return {
        printerSettings,
        printerSettingRows,
        printerSettingLoading,
        printerSettingSaving,
        printerSettingError,
        deletingPrinterSettingId,
        fetchPrinterSettingsData,
        createPrinterSetting,
        updatePrinterSetting,
        deletePrinterSetting,
        setDefaultPrinterSetting,
    };
};
