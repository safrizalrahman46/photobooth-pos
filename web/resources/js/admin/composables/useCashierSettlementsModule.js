import { computed, ref } from 'vue';
import { resolveRequestErrorMessage } from '../requestErrors';

export const useCashierSettlementsModule = ({ props, parseRequestError, getCsrfToken }) => {
    const settlements = ref(Array.isArray(props.initialCashierSettlements) ? props.initialCashierSettlements : []);
    const openSessions = ref(Array.isArray(props.initialOpenCashierSessions) ? props.initialOpenCashierSessions : []);
    const settlementLoading = ref(false);
    const settlementSaving = ref(false);
    const settlementError = ref('');

    const applyPayload = (payload) => {
        const data = payload?.data || {};

        if (Array.isArray(data.settlements)) {
            settlements.value = data.settlements;
        }

        if (Array.isArray(data.open_sessions)) {
            openSessions.value = data.open_sessions;
        }
    };

    const fetchCashierSettlements = async ({ silent = false } = {}) => {
        if (!props.cashierSettlementsDataUrl) return;

        if (!silent) settlementLoading.value = true;
        settlementError.value = '';

        try {
            const response = await fetch(props.cashierSettlementsDataUrl, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error(await parseRequestError(response));
            applyPayload(await response.json());
        } catch (error) {
            if (!silent) {
                settlementError.value = resolveRequestErrorMessage(error, 'Gagal memuat setoran kasir.');
            }
        } finally {
            if (!silent) settlementLoading.value = false;
        }
    };

    const postSettlementAction = async (settlementId, action, payload) => {
        const baseUrl = String(props.cashierSettlementBaseUrl || '').replace(/\/$/, '');

        if (!settlementId || !baseUrl) return;

        settlementSaving.value = true;
        settlementError.value = '';

        try {
            const response = await fetch(`${baseUrl}/${settlementId}/${action}`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify(payload || {}),
            });

            if (!response.ok) throw new Error(await parseRequestError(response));
            applyPayload(await response.json());
        } catch (error) {
            settlementError.value = resolveRequestErrorMessage(error, 'Aksi setoran kasir gagal diproses.');
            throw error;
        } finally {
            settlementSaving.value = false;
        }
    };

    const verifySettlement = ({ settlement_id, payload }) => postSettlementAction(settlement_id, 'verify', payload);
    const createSettlementCorrection = ({ settlement_id, payload }) => postSettlementAction(settlement_id, 'correction', payload);

    const cashierSettlementRows = computed(() => settlements.value || []);
    const openCashierSessionRows = computed(() => openSessions.value || []);

    return {
        cashierSettlementRows,
        openCashierSessionRows,
        settlementLoading,
        settlementSaving,
        settlementError,
        fetchCashierSettlements,
        verifySettlement,
        createSettlementCorrection,
    };
};
