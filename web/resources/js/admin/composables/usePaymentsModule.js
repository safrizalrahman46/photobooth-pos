import { computed, ref } from 'vue';

export const usePaymentsModule = ({
    props,
    parseRequestError,
    getCsrfToken,
    formatRupiah,
}) => {
    const payments = ref(Array.isArray(props.initialPayments) ? props.initialPayments : []);
    const paymentTransactionOptions = ref(Array.isArray(props.initialPaymentTransactionOptions)
        ? props.initialPaymentTransactionOptions
        : []);
    const paymentLoading = ref(false);
    const paymentSaving = ref(false);
    const paymentError = ref('');

    const applyPaymentsPayload = (payload) => {
        const nextPayments = payload?.data?.payments;
        const nextTransactionOptions = payload?.data?.transaction_options;

        if (Array.isArray(nextPayments)) {
            payments.value = nextPayments;
        }

        if (Array.isArray(nextTransactionOptions)) {
            paymentTransactionOptions.value = nextTransactionOptions;
        }
    };

    const fetchPaymentsData = async ({ silent = false } = {}) => {
        if (!props.paymentsDataUrl) {
            return;
        }

        if (!silent) {
            paymentLoading.value = true;
        }
        paymentError.value = '';

        try {
            const response = await fetch(props.paymentsDataUrl, {
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
            applyPaymentsPayload(payload);
        } catch (error) {
            if (!silent) {
                paymentError.value = error instanceof Error ? error.message : 'Failed to load payments.';
            }
        } finally {
            if (!silent) {
                paymentLoading.value = false;
            }
        }
    };

    const createPayment = async ({ transaction_id, payload }) => {
        const transactionId = Number(transaction_id || 0);
        const baseUrl = String(props.paymentsStoreUrlBase || '').replace(/\/$/, '');

        if (!transactionId || !baseUrl) {
            return;
        }

        paymentSaving.value = true;
        paymentError.value = '';

        try {
            const response = await fetch(`${baseUrl}/${transactionId}/store`, {
                method: 'POST',
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
            applyPaymentsPayload(result);
        } catch (error) {
            paymentError.value = error instanceof Error ? error.message : 'Failed to add payment.';
            throw error;
        } finally {
            paymentSaving.value = false;
        }
    };

    const paymentTransactionRows = computed(() => {
        return (paymentTransactionOptions.value || []).map((item) => {
            const totalAmount = Number(item.total_amount || 0);
            const paidAmount = Number(item.paid_amount || 0);
            const remainingAmount = Number(item.remaining_amount || Math.max(totalAmount - paidAmount, 0));

            return {
                id: Number(item.id || 0),
                transaction_code: String(item.transaction_code || '-'),
                customer_name: String(item.customer_name || '-'),
                remaining_amount: remainingAmount,
                remaining_amount_text: String(item.remaining_amount_text || formatRupiah(remainingAmount)),
            };
        }).filter((item) => item.id > 0);
    });

    const paymentRows = computed(() => {
        return (payments.value || []).map((item) => {
            const amount = Number(item.amount || 0);

            return {
                id: Number(item.id || 0),
                payment_code: String(item.payment_code || '-'),
                transaction_code: String(item.transaction_code || '-'),
                customer_name: String(item.customer_name || '-'),
                branch_name: String(item.branch_name || '-'),
                method: String(item.method || '-'),
                cashier_name: String(item.cashier_name || '-'),
                paid_at_text: String(item.paid_at_text || '-'),
                amount,
                amount_text: String(item.amount_text || formatRupiah(amount)),
            };
        });
    });

    return {
        payments,
        paymentRows,
        paymentTransactionRows,
        paymentLoading,
        paymentSaving,
        paymentError,
        fetchPaymentsData,
        createPayment,
    };
};
