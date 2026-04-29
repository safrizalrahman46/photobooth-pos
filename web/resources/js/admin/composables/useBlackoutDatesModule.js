import { computed, ref } from 'vue';

export const useBlackoutDatesModule = ({
    props,
    parseRequestError,
    getCsrfToken,
}) => {
    const blackoutDates = ref(Array.isArray(props.initialBlackoutDates) ? props.initialBlackoutDates : []);
    const blackoutDateLoading = ref(false);
    const blackoutDateSaving = ref(false);
    const blackoutDateError = ref('');
    const deletingBlackoutDateId = ref(null);

    const applyBlackoutDatesPayload = (payload) => {
        const nextRows = payload?.data?.blackout_dates;

        if (Array.isArray(nextRows)) {
            blackoutDates.value = nextRows;
        }
    };

    const fetchBlackoutDatesData = async ({ silent = false } = {}) => {
        if (!props.blackoutDatesDataUrl) {
            return;
        }

        if (!silent) {
            blackoutDateLoading.value = true;
        }
        blackoutDateError.value = '';

        try {
            const response = await fetch(props.blackoutDatesDataUrl, {
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
            applyBlackoutDatesPayload(payload);
        } catch (error) {
            if (!silent) {
                blackoutDateError.value = error instanceof Error ? error.message : 'Failed to load blackout dates.';
            }
        } finally {
            if (!silent) {
                blackoutDateLoading.value = false;
            }
        }
    };

    const createBlackoutDate = async (formPayload) => {
        if (!props.blackoutDateStoreUrl) {
            return;
        }

        blackoutDateSaving.value = true;
        blackoutDateError.value = '';

        try {
            const response = await fetch(props.blackoutDateStoreUrl, {
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

            const payload = await response.json();
            applyBlackoutDatesPayload(payload);
        } catch (error) {
            blackoutDateError.value = error instanceof Error ? error.message : 'Failed to create blackout date.';
            throw error;
        } finally {
            blackoutDateSaving.value = false;
        }
    };

    const updateBlackoutDate = async ({ id, payload }) => {
        const blackoutId = Number(id || 0);

        if (!blackoutId || !props.blackoutDateBaseUrl) {
            return;
        }

        blackoutDateSaving.value = true;
        blackoutDateError.value = '';

        try {
            const response = await fetch(`${props.blackoutDateBaseUrl}/${blackoutId}`, {
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
            applyBlackoutDatesPayload(result);
        } catch (error) {
            blackoutDateError.value = error instanceof Error ? error.message : 'Failed to update blackout date.';
            throw error;
        } finally {
            blackoutDateSaving.value = false;
        }
    };

    const deleteBlackoutDate = async (id) => {
        const blackoutId = Number(id || 0);

        if (!blackoutId || !props.blackoutDateBaseUrl) {
            return;
        }

        deletingBlackoutDateId.value = blackoutId;
        blackoutDateError.value = '';

        try {
            const response = await fetch(`${props.blackoutDateBaseUrl}/${blackoutId}`, {
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
            applyBlackoutDatesPayload(result);
        } catch (error) {
            blackoutDateError.value = error instanceof Error ? error.message : 'Failed to delete blackout date.';
            throw error;
        } finally {
            deletingBlackoutDateId.value = null;
        }
    };

    const blackoutDateRows = computed(() => {
        return (blackoutDates.value || []).map((item) => ({
            id: Number(item.id || 0),
            branch_id: Number(item.branch_id || 0),
            branch_name: String(item.branch_name || '-'),
            blackout_date: String(item.blackout_date || ''),
            blackout_date_text: String(item.blackout_date_text || '-'),
            reason: String(item.reason || ''),
            is_closed: Boolean(item.is_closed),
        }));
    });

    return {
        blackoutDates,
        blackoutDateRows,
        blackoutDateLoading,
        blackoutDateSaving,
        blackoutDateError,
        deletingBlackoutDateId,
        fetchBlackoutDatesData,
        createBlackoutDate,
        updateBlackoutDate,
        deleteBlackoutDate,
    };
};
