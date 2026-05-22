const DEFAULT_REQUEST_ERROR_MESSAGE = 'Permintaan gagal diproses. Silakan coba lagi.';

const statusFallbacks = {
    0: 'Tidak dapat terhubung ke server. Periksa koneksi internet lalu coba lagi.',
    401: 'Sesi login sudah berakhir. Silakan login ulang.',
    403: 'Anda tidak memiliki akses untuk melakukan tindakan ini.',
    404: 'Data yang diminta tidak ditemukan.',
    419: 'Sesi halaman sudah berakhir. Muat ulang halaman lalu coba lagi.',
    422: 'Periksa kembali data yang diisi.',
    429: 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.',
    500: 'Server sedang mengalami gangguan. Silakan coba lagi nanti.',
    502: 'Server sedang tidak merespons. Silakan coba lagi nanti.',
    503: 'Layanan sedang tidak tersedia. Silakan coba lagi nanti.',
    504: 'Server membutuhkan waktu terlalu lama. Silakan coba lagi nanti.',
};

const technicalMessagePatterns = [
    /^failed to fetch$/i,
    /^load failed$/i,
    /^networkerror/i,
    /^network error$/i,
    /^network request failed$/i,
    /^typeerror/i,
    /^http\s+\d{3}$/i,
    /csrf token mismatch/i,
    /sqlstate/i,
    /stack trace/i,
    /trace:/i,
    /undefined is not/i,
    /cannot read/i,
];

export const requestErrorFallback = (status, fallback = DEFAULT_REQUEST_ERROR_MESSAGE) => {
    const code = Number(status || 0);

    if (statusFallbacks[code]) {
        return statusFallbacks[code];
    }

    if (code >= 500) {
        return statusFallbacks[500];
    }

    return fallback;
};

export const sanitizeRequestMessage = (message, fallback = DEFAULT_REQUEST_ERROR_MESSAGE) => {
    const text = String(message || '').trim();

    if (!text) {
        return fallback;
    }

    if (technicalMessagePatterns.some((pattern) => pattern.test(text))) {
        return fallback;
    }

    return text;
};

export const parseResponseError = async (response, fallback = DEFAULT_REQUEST_ERROR_MESSAGE) => {
    const statusFallback = requestErrorFallback(response?.status, fallback);

    try {
        const json = await response.json();
        const firstValidationMessage = Object.values(json?.errors || {})?.[0]?.[0];

        return sanitizeRequestMessage(firstValidationMessage || json?.message, statusFallback);
    } catch {
        return statusFallback;
    }
};

export const resolveRequestErrorMessage = (error, fallback = DEFAULT_REQUEST_ERROR_MESSAGE) => {
    const status = Number(error?.status || error?.response?.status || 0);
    const statusFallback = requestErrorFallback(status, fallback);

    return sanitizeRequestMessage(error instanceof Error ? error.message : '', statusFallback);
};
