import { computed, ref } from 'vue';

export const useTimeSlotsModule = ({
    props,
    parseRequestError,
    getCsrfToken,
}) => {
    const timeSlots = ref(Array.isArray(props.initialTimeSlots) ? props.initialTimeSlots : []);
    const timeSlotLoading = ref(false);
    const timeSlotSaving = ref(false);
    const timeSlotError = ref('');
    const deletingTimeSlotId = ref(null);

    const applyTimeSlotsPayload = (payload) => {
        const nextTimeSlots = payload?.data?.time_slots;

        if (Array.isArray(nextTimeSlots)) {
            timeSlots.value = nextTimeSlots;
        }
    };

    const fetchTimeSlotsData = async ({ silent = false } = {}) => {
        if (!props.timeSlotsDataUrl) {
            return;
        }

        if (!silent) {
            timeSlotLoading.value = true;
        }
        timeSlotError.value = '';

        try {
            const response = await fetch(props.timeSlotsDataUrl, {
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
            applyTimeSlotsPayload(payload);
        } catch (error) {
            if (!silent) {
                timeSlotError.value = error instanceof Error ? error.message : 'Failed to load time slots.';
            }
        } finally {
            if (!silent) {
                timeSlotLoading.value = false;
            }
        }
    };

    const createTimeSlot = async (formPayload) => {
        if (!props.timeSlotStoreUrl) {
            return;
        }

        timeSlotSaving.value = true;
        timeSlotError.value = '';

        try {
            const response = await fetch(props.timeSlotStoreUrl, {
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
            applyTimeSlotsPayload(payload);
        } catch (error) {
            timeSlotError.value = error instanceof Error ? error.message : 'Failed to create time slot.';
            throw error;
        } finally {
            timeSlotSaving.value = false;
        }
    };

    const updateTimeSlot = async ({ id, payload }) => {
        const slotId = Number(id || 0);

        if (!slotId || !props.timeSlotBaseUrl) {
            return;
        }

        timeSlotSaving.value = true;
        timeSlotError.value = '';

        try {
            const response = await fetch(`${props.timeSlotBaseUrl}/${slotId}`, {
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
            applyTimeSlotsPayload(result);
        } catch (error) {
            timeSlotError.value = error instanceof Error ? error.message : 'Failed to update time slot.';
            throw error;
        } finally {
            timeSlotSaving.value = false;
        }
    };

    const deleteTimeSlot = async (id) => {
        const slotId = Number(id || 0);

        if (!slotId || !props.timeSlotBaseUrl) {
            return;
        }

        deletingTimeSlotId.value = slotId;
        timeSlotError.value = '';

        try {
            const response = await fetch(`${props.timeSlotBaseUrl}/${slotId}`, {
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
            applyTimeSlotsPayload(result);
        } catch (error) {
            timeSlotError.value = error instanceof Error ? error.message : 'Failed to delete time slot.';
            throw error;
        } finally {
            deletingTimeSlotId.value = null;
        }
    };

    const generateTimeSlots = async (formPayload) => {
        if (!props.timeSlotGenerateUrl) {
            return;
        }

        timeSlotSaving.value = true;
        timeSlotError.value = '';

        try {
            const response = await fetch(props.timeSlotGenerateUrl, {
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
            applyTimeSlotsPayload(result);
        } catch (error) {
            timeSlotError.value = error instanceof Error ? error.message : 'Failed to generate time slots.';
            throw error;
        } finally {
            timeSlotSaving.value = false;
        }
    };

    const bulkBookableTimeSlots = async (formPayload) => {
        if (!props.timeSlotBulkBookableUrl) {
            return;
        }

        timeSlotSaving.value = true;
        timeSlotError.value = '';

        try {
            const response = await fetch(props.timeSlotBulkBookableUrl, {
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
            applyTimeSlotsPayload(result);
        } catch (error) {
            timeSlotError.value = error instanceof Error ? error.message : 'Failed to update selected time slots.';
            throw error;
        } finally {
            timeSlotSaving.value = false;
        }
    };

    const timeSlotRows = computed(() => {
        return (timeSlots.value || []).map((item) => ({
            id: Number(item.id || 0),
            branch_id: Number(item.branch_id || 0),
            branch_name: String(item.branch_name || '-'),
            slot_date: String(item.slot_date || ''),
            slot_date_text: String(item.slot_date_text || '-'),
            start_time: String(item.start_time || ''),
            start_time_text: String(item.start_time_text || ''),
            end_time: String(item.end_time || ''),
            end_time_text: String(item.end_time_text || ''),
            capacity: Number(item.capacity || 1),
            is_bookable: Boolean(item.is_bookable),
        }));
    });

    return {
        timeSlots,
        timeSlotRows,
        timeSlotLoading,
        timeSlotSaving,
        timeSlotError,
        deletingTimeSlotId,
        fetchTimeSlotsData,
        createTimeSlot,
        updateTimeSlot,
        deleteTimeSlot,
        generateTimeSlots,
        bulkBookableTimeSlots,
    };
};
