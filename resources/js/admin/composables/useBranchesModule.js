import { computed, ref } from 'vue';

export const useBranchesModule = ({
    props,
    bookingOptions,
    parseRequestError,
    getCsrfToken,
}) => {
    const branches = ref(Array.isArray(props.initialBranches) ? props.initialBranches : []);
    const branchLoading = ref(false);
    const branchSaving = ref(false);
    const branchError = ref('');
    const deletingBranchId = ref(null);

    const applyBranchesPayload = (payload) => {
        const nextBranches = payload?.data?.branches;

        if (!Array.isArray(nextBranches)) {
            return;
        }

        branches.value = nextBranches;

        bookingOptions.value = {
            ...bookingOptions.value,
            branches: nextBranches
                .map((branch) => ({
                    id: Number(branch?.id || 0),
                    name: String(branch?.name || '-'),
                }))
                .filter((branch) => branch.id > 0),
        };
    };

    const fetchBranchesData = async ({ silent = false } = {}) => {
        if (!props.branchesDataUrl) {
            return;
        }

        if (!silent) {
            branchLoading.value = true;
        }
        branchError.value = '';

        try {
            const response = await fetch(props.branchesDataUrl, {
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
            applyBranchesPayload(payload);
        } catch (error) {
            if (!silent) {
                branchError.value = error instanceof Error ? error.message : 'Failed to load branches.';
            }
        } finally {
            if (!silent) {
                branchLoading.value = false;
            }
        }
    };

    const createBranch = async (formPayload) => {
        if (!props.branchStoreUrl) {
            return;
        }

        branchSaving.value = true;
        branchError.value = '';

        try {
            const response = await fetch(props.branchStoreUrl, {
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
            applyBranchesPayload(payload);
        } catch (error) {
            branchError.value = error instanceof Error ? error.message : 'Failed to create branch.';
            throw error;
        } finally {
            branchSaving.value = false;
        }
    };

    const updateBranch = async ({ id, payload }) => {
        const branchId = Number(id || 0);

        if (!branchId || !props.branchBaseUrl) {
            return;
        }

        branchSaving.value = true;
        branchError.value = '';

        try {
            const response = await fetch(`${props.branchBaseUrl}/${branchId}`, {
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
            applyBranchesPayload(result);
        } catch (error) {
            branchError.value = error instanceof Error ? error.message : 'Failed to update branch.';
            throw error;
        } finally {
            branchSaving.value = false;
        }
    };

    const deleteBranch = async (id) => {
        const branchId = Number(id || 0);

        if (!branchId || !props.branchBaseUrl) {
            return;
        }

        deletingBranchId.value = branchId;
        branchError.value = '';

        try {
            const response = await fetch(`${props.branchBaseUrl}/${branchId}`, {
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
            applyBranchesPayload(result);
        } catch (error) {
            branchError.value = error instanceof Error ? error.message : 'Failed to delete branch.';
            throw error;
        } finally {
            deletingBranchId.value = null;
        }
    };

    const branchRows = computed(() => {
        return (branches.value || []).map((item) => ({
            id: Number(item.id || 0),
            code: String(item.code || ''),
            name: String(item.name || '-'),
            timezone: String(item.timezone || 'Asia/Jakarta'),
            phone: String(item.phone || ''),
            address: String(item.address || ''),
            is_active: Boolean(item.is_active),
            bookings_count: Number(item.bookings_count || 0),
            time_slots_count: Number(item.time_slots_count || 0),
            transactions_count: Number(item.transactions_count || 0),
            queue_tickets_count: Number(item.queue_tickets_count || 0),
        }));
    });

    return {
        branches,
        branchRows,
        branchLoading,
        branchSaving,
        branchError,
        deletingBranchId,
        fetchBranchesData,
        createBranch,
        updateBranch,
        deleteBranch,
    };
};
