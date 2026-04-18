<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import {
    Activity,
    BarChart3,
    Bell,
    Calendar,
    Camera,
    ChevronLeft,
    ChevronRight,
    ChevronRight as BreadcrumbChevron,
    LayoutDashboard,
    ListOrdered,
    LogOut,
    Menu,
    Package,
    Palette,
    Download,
    Plus,
    Receipt,
    Search,
    Settings,
    Sparkles,
    Users,
    X,
} from 'lucide-vue-next';

const props = defineProps({
    initialStats: {
        type: Array,
        default: () => [],
    },
    summaryCards: {
        type: Array,
        default: () => [],
    },
    revenueOverview: {
        type: Object,
        default: () => ({}),
    },
    queueLive: {
        type: Object,
        default: () => ({}),
    },
    ownerHighlights: {
        type: Array,
        default: () => [],
    },
    ownerModules: {
        type: Array,
        default: () => [],
    },
    recentTransactions: {
        type: Array,
        default: () => [],
    },
    recentActivities: {
        type: Array,
        default: () => [],
    },
    queueSnapshot: {
        type: Array,
        default: () => [],
    },
    initialRows: {
        type: Array,
        default: () => [],
    },
    initialPagination: {
        type: Object,
        default: () => ({
            current_page: 1,
            per_page: 15,
            total: 0,
            last_page: 1,
        }),
    },
    dataUrl: {
        type: String,
        default: '',
    },
    reportUrl: {
        type: String,
        default: '',
    },
    panelUrl: {
        type: String,
        default: '/panel',
    },
});

const search = ref('');
const filterStatus = ref('all');
const activeRevenuePeriod = ref('7d');
const loading = ref(false);
const mobileOpen = ref(false);
const sidebarCollapsed = ref(false);
const showTopSearch = ref(false);
const topSearchValue = ref('');

const rows = ref(Array.isArray(props.initialRows) ? props.initialRows : []);
const pagination = ref({
    current_page: Number(props.initialPagination?.current_page || 1),
    per_page: Number(props.initialPagination?.per_page || 15),
    total: Number(props.initialPagination?.total || rows.value.length || 0),
    last_page: Number(props.initialPagination?.last_page || 1),
});

let debounceTimer = null;
let activeRequestController = null;

const filterTabs = [
    { key: 'all', label: 'All' },
    { key: 'pending', label: 'Pending' },
    { key: 'booked', label: 'Booked' },
    { key: 'used', label: 'Completed' },
    { key: 'expired', label: 'Cancelled' },
];

const bookingStatusMap = {
    pending: { label: 'Pending', bg: '#FFFBEB', color: '#D97706' },
    booked: { label: 'Booked', bg: '#EFF6FF', color: '#2563EB' },
    used: { label: 'Completed', bg: '#F8FAFC', color: '#64748B' },
    expired: { label: 'Cancelled', bg: '#FEF2F2', color: '#EF4444' },
};

const queueStatusMap = {
    waiting: { bg: '#FFFBEB', color: '#D97706' },
    called: { bg: '#EFF6FF', color: '#2563EB' },
    checked_in: { bg: '#F0F9FF', color: '#0284C7' },
    in_session: { bg: '#ECFDF5', color: '#059669' },
    finished: { bg: '#F8FAFC', color: '#64748B' },
};

const transactionStatusMap = {
    paid: { label: 'Paid', bg: '#ECFDF5', color: '#059669' },
    partial: { label: 'Partial', bg: '#FFFBEB', color: '#D97706' },
    unpaid: { label: 'Unpaid', bg: '#FEF2F2', color: '#EF4444' },
    void: { label: 'Void', bg: '#F8FAFC', color: '#64748B' },
};

const methodStyleMap = {
    QRIS: { bg: '#F5F3FF', color: '#7C3AED' },
    CASH: { bg: '#ECFDF5', color: '#059669' },
    TRANSFER: { bg: '#EFF6FF', color: '#2563EB' },
    CARD: { bg: '#FFFBEB', color: '#D97706' },
    '-': { bg: '#F8FAFC', color: '#64748B' },
};

const defaultCardPalette = [
    { accent: '#2563EB', light: '#EFF6FF', border: '#DBEAFE' },
    { accent: '#059669', light: '#ECFDF5', border: '#A7F3D0' },
    { accent: '#D97706', light: '#FFFBEB', border: '#FDE68A' },
    { accent: '#7C3AED', light: '#F5F3FF', border: '#DDD6FE' },
];

const BOOKING_SCALE = 150000;

const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

const toNumberArray = (value) => {
    if (!Array.isArray(value)) {
        return [];
    }

    return value
        .map((item) => Number(item || 0))
        .filter((item) => Number.isFinite(item));
};

const formatRupiah = (amount) => {
    const numeric = Number(amount || 0);

    return `Rp ${numeric.toLocaleString('id-ID')}`;
};

const formatDuration = (seconds) => {
    const safeSeconds = Math.max(0, Number(seconds || 0));
    const minutes = Math.floor(safeSeconds / 60);
    const remainingSeconds = safeSeconds % 60;

    return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
};

const initialsFromName = (name) => {
    const clean = String(name || '').trim();

    if (!clean) {
        return '--';
    }

    const parts = clean.split(/\s+/).filter(Boolean);

    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }

    return `${parts[0][0] || ''}${parts[1][0] || ''}`.toUpperCase();
};

const buildSparklineMeta = (values, width = 72, height = 28) => {
    const points = Array.isArray(values) && values.length > 0 ? values : [0, 0];

    const max = Math.max(...points);
    const min = Math.min(...points);
    const range = max - min || 1;

    const normalized = points.map((value, index) => {
        const x = points.length === 1 ? width : (index / (points.length - 1)) * width;
        const y = height - ((value - min) / range) * (height - 4) - 2;

        return { x, y };
    });

    return {
        width,
        height,
        linePoints: normalized.map((point) => `${point.x},${point.y}`).join(' '),
        fillPoints: [
            `0,${height}`,
            ...normalized.map((point) => `${point.x},${point.y}`),
            `${width},${height}`,
        ].join(' '),
        lastX: normalized[normalized.length - 1]?.x || width,
        lastY: normalized[normalized.length - 1]?.y || height / 2,
    };
};

const buildRevenueChartMeta = (series) => {
    const width = 760;
    const height = 260;
    const left = 50;
    const right = 16;
    const top = 12;
    const bottom = 32;

    const plotWidth = width - left - right;
    const plotHeight = height - top - bottom;

    if (!series.length) {
        return {
            width,
            height,
            left,
            top,
            plotHeight,
            hasData: false,
            points: [],
            revenuePath: '',
            bookingPath: '',
            areaPath: '',
            gridLines: [],
            yTicks: [],
        };
    }

    const maxRevenue = Math.max(...series.map((point) => Number(point.revenue || 0)), 1);
    const yAxisMax = Math.max(1000000, Math.ceil(maxRevenue / 100000) * 100000);

    const linePathFromPoints = (pointsList, ySelector) => {
        if (!pointsList.length) {
            return '';
        }

        if (pointsList.length === 1) {
            return `M ${pointsList[0].x} ${ySelector(pointsList[0])}`;
        }

        let path = `M ${pointsList[0].x} ${ySelector(pointsList[0])}`;

        for (let index = 0; index < pointsList.length - 1; index += 1) {
            const prev = pointsList[index - 1] || pointsList[index];
            const current = pointsList[index];
            const next = pointsList[index + 1];
            const nextNext = pointsList[index + 2] || next;

            const cp1x = current.x + (next.x - prev.x) / 6;
            const cp1y = ySelector(current) + (ySelector(next) - ySelector(prev)) / 6;
            const cp2x = next.x - (nextNext.x - current.x) / 6;
            const cp2y = ySelector(next) - (ySelector(nextNext) - ySelector(current)) / 6;

            path += ` C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${next.x} ${ySelector(next)}`;
        }

        return path;
    };

    const points = series.map((point, index) => {
        const x = series.length === 1 ? left : left + (index / (series.length - 1)) * plotWidth;
        const revenueY = top + (1 - Number(point.revenue || 0) / yAxisMax) * plotHeight;
        const bookingScaled = Number(point.bookings || 0) * BOOKING_SCALE;
        const bookingY = top + (1 - bookingScaled / yAxisMax) * plotHeight;

        return {
            ...point,
            x,
            revenueY,
            bookingY,
            bookingScaled,
        };
    });

    const revenuePath = linePathFromPoints(points, (point) => point.revenueY);
    const bookingPath = linePathFromPoints(points, (point) => point.bookingY);

    const areaPath = revenuePath
        ? `${revenuePath} L ${points[points.length - 1].x} ${top + plotHeight} L ${points[0].x} ${top + plotHeight} Z`
        : '';

    const gridLines = [0, 0.25, 0.5, 0.75, 1].map((ratio) => top + ratio * plotHeight);
    const yTicks = [1, 0.75, 0.5, 0.25, 0].map((ratio) => {
        const value = yAxisMax * ratio;

        return {
            y: top + (1 - ratio) * plotHeight,
            label: `${(value / 1000000).toFixed(1)}M`,
        };
    });

    return {
        width,
        height,
        left,
        top,
        plotHeight,
        hasData: true,
        points,
        revenuePath,
        bookingPath,
        areaPath,
        gridLines,
        yTicks,
    };
};

const panelBaseUrl = computed(() => {
    const value = String(props.panelUrl || '/panel').trim();

    if (!value) {
        return '/panel';
    }

    return value.endsWith('/') ? value.slice(0, -1) : value;
});

const panelBookingsUrl = computed(() => `${panelBaseUrl.value}/bookings`);
const panelTransactionsUrl = computed(() => `${panelBaseUrl.value}/transactions`);
const panelActivitiesUrl = computed(() => `${panelBaseUrl.value}/activity-logs`);

const navItems = computed(() => ([
    { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard, href: '/admin', group: 'overview' },
    { id: 'packages', label: 'Packages', icon: Package, href: `${panelBaseUrl.value}/packages`, group: 'management' },
    { id: 'designs', label: 'Designs', icon: Palette, href: `${panelBaseUrl.value}/design-catalogs`, group: 'management' },
    { id: 'users', label: 'Users', icon: Users, href: `${panelBaseUrl.value}/users`, group: 'management' },
    { id: 'bookings', label: 'Bookings', icon: Calendar, href: panelBookingsUrl.value, group: 'operations' },
    { id: 'queue', label: 'Queue', icon: ListOrdered, href: `${panelBaseUrl.value}/queue-tickets`, group: 'operations' },
    { id: 'transactions', label: 'Transactions', icon: Receipt, href: panelTransactionsUrl.value, group: 'operations' },
    { id: 'reports', label: 'Reports', icon: BarChart3, href: panelTransactionsUrl.value, group: 'analytics' },
    { id: 'activity-logs', label: 'Activity Logs', icon: Activity, href: panelActivitiesUrl.value, group: 'analytics' },
    { id: 'settings', label: 'Settings', icon: Settings, href: panelBaseUrl.value, group: 'system' },
]));

const navGroups = [
    { key: 'overview', label: 'Overview' },
    { key: 'management', label: 'Management' },
    { key: 'operations', label: 'Operations' },
    { key: 'analytics', label: 'Analytics' },
    { key: 'system', label: 'System' },
];

const currentPath = computed(() => {
    if (typeof window === 'undefined') {
        return '/admin';
    }

    return String(window.location.pathname || '/admin');
});

const isNavActive = (item) => {
    const path = currentPath.value;

    if (item.id === 'dashboard') {
        return path === '/admin' || path === '/admin/';
    }

    return path === item.href || path.startsWith(`${item.href}/`);
};

const topbarMetaById = {
    dashboard: { title: 'Dashboard', subtitle: 'Business overview and key metrics' },
    packages: { title: 'Packages', subtitle: 'Manage your photobooth packages' },
    designs: { title: 'Designs', subtitle: 'Photo design templates and themes' },
    users: { title: 'Users', subtitle: 'Manage staff and customer accounts' },
    bookings: { title: 'Bookings', subtitle: 'Track and manage all reservations' },
    queue: { title: 'Queue', subtitle: 'Live session queue management' },
    transactions: { title: 'Transactions', subtitle: 'Payment history and records' },
    reports: { title: 'Reports', subtitle: 'Business analytics and insights' },
    'activity-logs': { title: 'Activity Logs', subtitle: 'System activity and audit trail' },
    settings: { title: 'Settings', subtitle: 'Configure your business preferences' },
};

const activeTopbarItem = computed(() => {
    const active = navItems.value.find((item) => isNavActive(item));

    if (active) {
        return active;
    }

    return navItems.value.find((item) => item.id === 'dashboard') || null;
});

const topbarTitle = computed(() => {
    const id = String(activeTopbarItem.value?.id || 'dashboard');

    return topbarMetaById[id]?.title || 'Dashboard';
});

const navBadgeMap = computed(() => ({
    bookings: Number(pagination.value.total || 0),
    queue: Number(queueStats.value.waiting || 0),
}));

const navBadgeFor = (itemId) => {
    const value = Number(navBadgeMap.value[itemId] || 0);

    if (!value) {
        return null;
    }

    return value > 99 ? '99+' : String(value);
};

const topbarDate = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(new Date());
});

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    if (hour < 17) {
        return 'Good afternoon';
    }

    return 'Good evening';
});

const fallbackSummaryCards = computed(() => {
    const stats = Array.isArray(props.initialStats) ? props.initialStats.slice(0, 4) : [];

    return stats.map((item, index) => ({
        title: String(item.label || `Metric ${index + 1}`),
        value: String(item.value ?? '-'),
        change: '0',
        changeLabel: 'baseline',
        trend: 'neutral',
        accent: defaultCardPalette[index]?.accent || '#2563EB',
        accentLight: defaultCardPalette[index]?.light || '#EFF6FF',
        accentBorder: defaultCardPalette[index]?.border || '#DBEAFE',
        sparkline: [0, 0, 0, 0, 0, 0, 0],
    }));
});

const normalizedSummaryCards = computed(() => {
    const source = Array.isArray(props.summaryCards) && props.summaryCards.length
        ? props.summaryCards
        : fallbackSummaryCards.value;

    return source.map((card, index) => {
        const palette = defaultCardPalette[index % defaultCardPalette.length];
        const sparkline = toNumberArray(card.sparkline);

        return {
            title: String(card.title || card.label || `Metric ${index + 1}`),
            value: String(card.value ?? '-'),
            change: String(card.change ?? '0'),
            changeLabel: String(card.changeLabel || card.helper || 'baseline'),
            trend: String(card.trend || 'neutral'),
            accent: String(card.accent || palette.accent),
            accentLight: String(card.accentLight || palette.light),
            accentBorder: String(card.accentBorder || palette.border),
            sparklineMeta: buildSparklineMeta(sparkline.length ? sparkline : [0, 0]),
            gradientId: `summary-card-gradient-${index}`,
        };
    });
});

const normalizedRevenueSeries = computed(() => {
    const sourceContainer = props.revenueOverview && typeof props.revenueOverview === 'object'
        ? props.revenueOverview
        : {};

    const selected = sourceContainer[activeRevenuePeriod.value];
    const fallback = sourceContainer['7d'];

    const source = Array.isArray(selected) && selected.length
        ? selected
        : Array.isArray(fallback)
            ? fallback
            : [];

    return source.map((item, index) => ({
        key: String(item.key || index),
        label: String(item.label || item.day || '-'),
        revenue: Number(item.revenue || 0),
        bookings: Number(item.bookings || 0),
    }));
});

const revenueChartMeta = computed(() => buildRevenueChartMeta(normalizedRevenueSeries.value));

const revenueTotal = computed(() => {
    const total = normalizedRevenueSeries.value.reduce((sum, item) => sum + Number(item.revenue || 0), 0);

    return formatRupiah(total);
});

const bookingTotal = computed(() => {
    return normalizedRevenueSeries.value.reduce((sum, item) => sum + Number(item.bookings || 0), 0);
});

const queueStats = computed(() => {
    const stats = props.queueLive?.stats || {};

    return {
        in_queue: Number(stats.in_queue || 0),
        in_session: Number(stats.in_session || 0),
        waiting: Number(stats.waiting || 0),
        completed_today: Number(stats.completed_today || 0),
    };
});

const currentQueue = computed(() => {
    const value = props.queueLive?.current;

    if (!value || typeof value !== 'object') {
        return null;
    }

    return {
        queue_code: String(value.queue_code || '-'),
        customer_name: String(value.customer_name || '-'),
        package_name: String(value.package_name || '-'),
        status: String(value.status || 'waiting'),
        progress_percentage: Number(value.progress_percentage || 0),
        remaining_seconds: Number(value.remaining_seconds || 0),
        session_duration_seconds: Number(value.session_duration_seconds || 0),
    };
});

const waitingQueue = computed(() => {
    const list = Array.isArray(props.queueLive?.waiting) ? props.queueLive.waiting : [];

    return list.slice(0, 5).map((item, index) => ({
        queue_code: String(item.queue_code || `Q${String(index + 1).padStart(3, '0')}`),
        customer_name: String(item.customer_name || '-'),
        package_name: String(item.package_name || '-'),
        status: String(item.status || 'waiting'),
    }));
});

const queueProgress = computed(() => {
    return clamp(Number(currentQueue.value?.progress_percentage || 0), 0, 100);
});

const queueProgressStyle = computed(() => ({
    width: `${queueProgress.value}%`,
    background: queueProgress.value > 80
        ? '#EF4444'
        : 'linear-gradient(90deg, #2563EB, #60A5FA)',
}));

const queueRemainingText = computed(() => {
    return formatDuration(Number(currentQueue.value?.remaining_seconds || 0));
});

const queueSessionDurationText = computed(() => {
    return formatDuration(Number(currentQueue.value?.session_duration_seconds || 0));
});

const normalizedRows = computed(() => {
    return (rows.value || []).map((row) => ({
        id: String(row.id || '-'),
        name: String(row.name || '-'),
        pkg: String(row.pkg || '-'),
        date: String(row.date || '-'),
        time: String(row.time || '-'),
        status: String(row.status || 'pending'),
        payment: String(row.payment || '-'),
        amount: Number(row.amount || 0),
        amount_text: String(row.amount_text || formatRupiah(row.amount || 0)),
        add_ons_count: Number(row.add_ons_count || 0),
    }));
});

const hasPagination = computed(() => Number(pagination.value.last_page || 1) > 1);
const canGoPrev = computed(() => Number(pagination.value.current_page || 1) > 1);
const canGoNext = computed(() => Number(pagination.value.current_page || 1) < Number(pagination.value.last_page || 1));

const bookingResultCaption = computed(() => {
    const total = Number(pagination.value.total || 0);

    if (!total || !normalizedRows.value.length) {
        return 'Showing 0 of 0 bookings';
    }

    const page = Number(pagination.value.current_page || 1);
    const perPage = Number(pagination.value.per_page || normalizedRows.value.length);

    const from = (page - 1) * perPage + 1;
    const to = from + normalizedRows.value.length - 1;

    return `Showing ${from}-${to} of ${total} bookings`;
});

const normalizedRecentTransactions = computed(() => {
    const source = Array.isArray(props.recentTransactions) ? props.recentTransactions : [];

    return source.map((item, index) => {
        const customer = String(item.customer || item.code || '-');
        const method = String(item.method || '-').toUpperCase();
        const amount = Number(item.amount || 0);

        return {
            id: String(item.code || `TX-${index + 1}`),
            customer,
            cashier: String(item.cashier || '-'),
            method,
            amount,
            amountText: String(item.paid_text || item.total_text || formatRupiah(amount)),
            status: String(item.status || 'unpaid'),
            time: String(item.time_text || item.time || '-'),
            initials: initialsFromName(customer),
        };
    });
});

const transactionTodayTotal = computed(() => {
    const total = normalizedRecentTransactions.value.reduce((sum, item) => sum + Number(item.amount || 0), 0);

    return formatRupiah(total);
});

const normalizedRecentActivities = computed(() => {
    const source = Array.isArray(props.recentActivities) ? props.recentActivities : [];

    return source.map((item, index) => ({
        id: index,
        actor: String(item.actor || 'System'),
        action: String(item.action || '-'),
        module: String(item.module || 'system'),
        time: String(item.time || '-'),
    }));
});

const resolveBookingStatus = (status) => {
    return bookingStatusMap[status] || {
        label: status || 'Unknown',
        bg: '#F8FAFC',
        color: '#64748B',
    };
};

const resolveQueueStatus = (status) => {
    return queueStatusMap[status] || {
        bg: '#F8FAFC',
        color: '#64748B',
    };
};

const resolveTransactionStatus = (status) => {
    return transactionStatusMap[status] || {
        label: status || 'Unknown',
        bg: '#F8FAFC',
        color: '#64748B',
    };
};

const resolveMethodStyle = (method) => {
    return methodStyleMap[method] || methodStyleMap['-'];
};

const resolveActivityTone = (module) => {
    const normalized = String(module || '').toLowerCase();

    if (normalized.includes('booking')) {
        return { bg: '#EFF6FF', color: '#2563EB' };
    }

    if (normalized.includes('transaction') || normalized.includes('payment')) {
        return { bg: '#ECFDF5', color: '#059669' };
    }

    if (normalized.includes('queue')) {
        return { bg: '#F0F9FF', color: '#0284C7' };
    }

    if (normalized.includes('user')) {
        return { bg: '#F5F3FF', color: '#7C3AED' };
    }

    return { bg: '#F8FAFC', color: '#64748B' };
};

const fetchRows = async (page = 1) => {
    if (!props.dataUrl) {
        return;
    }

    if (activeRequestController) {
        activeRequestController.abort();
    }

    const controller = new AbortController();
    activeRequestController = controller;
    loading.value = true;

    try {
        const params = new URLSearchParams();
        const trimmedSearch = String(search.value || '').trim();

        params.set('page', String(page));
        params.set('per_page', String(pagination.value.per_page || 15));
        params.set('status', String(filterStatus.value || 'all'));

        if (trimmedSearch) {
            params.set('search', trimmedSearch);
        }

        const response = await fetch(`${props.dataUrl}?${params.toString()}`, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: controller.signal,
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const payload = await response.json();
        const data = payload?.data || {};
        const incomingRows = Array.isArray(data.rows) ? data.rows : [];
        const incomingPagination = data.pagination || {};

        rows.value = incomingRows;
        pagination.value = {
            current_page: Number(incomingPagination.current_page || 1),
            per_page: Number(incomingPagination.per_page || 15),
            total: Number(incomingPagination.total || incomingRows.length),
            last_page: Number(incomingPagination.last_page || 1),
        };
    } catch (error) {
        if (error?.name !== 'AbortError') {
            console.error('Failed to fetch dashboard rows:', error);
        }
    } finally {
        if (activeRequestController === controller) {
            activeRequestController = null;
            loading.value = false;
        }
    }
};

const setFilterStatus = (status) => {
    filterStatus.value = status;
    fetchRows(1);
};

const goToPrevPage = () => {
    if (!canGoPrev.value) {
        return;
    }

    fetchRows(Number(pagination.value.current_page || 1) - 1);
};

const goToNextPage = () => {
    if (!canGoNext.value) {
        return;
    }

    fetchRows(Number(pagination.value.current_page || 1) + 1);
};

watch(search, () => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    debounceTimer = setTimeout(() => {
        fetchRows(1);
    }, 350);
});

onBeforeUnmount(() => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    if (activeRequestController) {
        activeRequestController.abort();
    }
});
</script>

<template>
    <div class="min-h-screen bg-[#F8FAFC]" style="font-family: Poppins, sans-serif;">
        <div class="flex h-screen overflow-hidden">
            <div
                v-if="mobileOpen"
                class="fixed inset-0 z-20 lg:hidden"
                style="background: rgba(15,23,42,0.4); backdrop-filter: blur(4px);"
                @click="mobileOpen = false"
            ></div>

            <aside
                class="fixed inset-y-0 left-0 z-30 flex w-[240px] flex-col border-r border-[#EEF2FF] bg-white shadow-[2px_0_16px_rgba(37,99,235,0.06)] transition-all duration-300 lg:relative"
                :class="[
                    mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                    sidebarCollapsed ? 'lg:w-[72px]' : 'lg:w-[240px]',
                ]"
            >
                <div
                    class="pointer-events-none absolute inset-0"
                    style="background-image: radial-gradient(circle, #DBEAFE 1px, transparent 1px); background-size: 24px 24px; opacity: 0.4;"
                ></div>
                <div
                    class="pointer-events-none absolute left-0 right-0 top-0 h-[3px]"
                    style="background: linear-gradient(90deg, #2563EB 0%, #60A5FA 100%);"
                ></div>

                <div class="relative flex items-center px-4 py-5" style="min-height: 72px;">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                        style="background: linear-gradient(135deg, #2563EB 0%, #60A5FA 100%); box-shadow: 0 4px 12px rgba(37,99,235,0.3);"
                    >
                        <Camera class="h-4 w-4 text-white" />
                    </div>

                    <div v-if="!sidebarCollapsed" class="ml-3 min-w-0 flex-1 overflow-hidden">
                        <p class="whitespace-nowrap text-[0.875rem] font-bold tracking-[-0.01em]" style="font-family: Poppins, sans-serif; color: #1E3A8A;">
                            Ready To Pict
                        </p>
                        <span class="whitespace-nowrap text-xs font-medium" style="color: #60A5FA;">Owner Dashboard</span>
                    </div>

                    <button
                        type="button"
                        class="hidden h-6 w-6 items-center justify-center rounded-lg transition-all duration-200 lg:flex"
                        :style="{ background: '#EFF6FF', color: '#2563EB', marginLeft: sidebarCollapsed ? 'auto' : undefined }"
                        @click="sidebarCollapsed = !sidebarCollapsed"
                    >
                        <ChevronRight v-if="sidebarCollapsed" class="h-3.5 w-3.5" />
                        <ChevronLeft v-else class="h-3.5 w-3.5" />
                    </button>

                    <button
                        type="button"
                        class="ml-auto flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 lg:hidden"
                        @click="mobileOpen = false"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="relative mx-4 h-px bg-[#EEF2FF]"></div>

                <nav class="relative flex-1 overflow-y-auto px-3 py-4">
                    <div v-for="group in navGroups" :key="`nav-group-${group.key}`" class="mb-1">
                        <p
                            v-if="!sidebarCollapsed"
                            class="px-3 pb-1.5 pt-3 text-[0.62rem] font-semibold uppercase tracking-widest"
                            style="color: #CBD5E1;"
                        >
                            {{ group.label }}
                        </p>

                        <div v-else-if="group.key !== 'overview'" class="mx-auto my-2 h-px w-6 bg-[#EEF2FF]"></div>

                        <a
                            v-for="item in navItems.filter((entry) => entry.group === group.key)"
                            :key="`nav-item-${item.id}`"
                            :href="item.href"
                            :title="sidebarCollapsed ? item.label : undefined"
                            class="relative mb-[2px] flex w-full items-center rounded-xl transition-all duration-200"
                            :class="sidebarCollapsed ? 'h-10 justify-center px-0 py-2.5' : 'gap-3 px-3 py-2.5'"
                            :style="{
                                background: isNavActive(item) ? '#EFF6FF' : 'transparent',
                                color: isNavActive(item) ? '#2563EB' : '#64748B',
                            }"
                        >
                            <span
                                v-if="isNavActive(item)"
                                class="absolute left-0 top-1/2 h-5 w-[3px] -translate-y-1/2 rounded-r-full bg-[#2563EB]"
                            ></span>

                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                :style="{ background: isNavActive(item) ? '#DBEAFE' : 'transparent' }"
                            >
                                <component
                                    :is="item.icon"
                                    class="h-4 w-4"
                                    :style="{ color: isNavActive(item) ? '#2563EB' : 'inherit' }"
                                />
                            </span>

                            <span
                                v-if="!sidebarCollapsed"
                                class="flex-1 whitespace-nowrap text-left text-[0.8125rem]"
                                :style="{ fontFamily: 'Poppins, sans-serif', fontWeight: isNavActive(item) ? 600 : 400 }"
                            >
                                {{ item.label }}
                            </span>

                            <span
                                v-if="!sidebarCollapsed && navBadgeFor(item.id)"
                                class="flex h-[18px] min-w-[18px] items-center justify-center rounded-full px-1 text-[0.65rem] font-bold"
                                :style="{
                                    background: isNavActive(item) ? '#2563EB' : '#FEE2E2',
                                    color: isNavActive(item) ? '#FFFFFF' : '#EF4444',
                                }"
                            >
                                {{ navBadgeFor(item.id) }}
                            </span>
                        </a>
                    </div>
                </nav>

                <div class="relative border-t border-[#EEF2FF] p-3">
                    <div
                        class="flex items-center"
                        :class="sidebarCollapsed ? 'justify-center' : 'gap-2.5 rounded-xl border border-[#EEF2FF] bg-[#F8FAFC] p-2.5'"
                    >
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-[0.7rem] font-bold text-white"
                            style="background: linear-gradient(135deg, #2563EB, #60A5FA); box-shadow: 0 2px 8px rgba(37,99,235,0.25);"
                        >
                            AO
                        </div>

                        <div v-if="!sidebarCollapsed" class="min-w-0 flex-1">
                            <p class="truncate text-[0.8rem] font-semibold leading-tight" style="font-family: Poppins, sans-serif; color: #1F2937;">Ahmad Owner</p>
                            <p class="truncate text-[0.7rem]" style="color: #94A3B8;">Owner · Super Admin</p>
                        </div>

                        <button
                            v-if="!sidebarCollapsed"
                            type="button"
                            class="rounded-lg p-1.5 transition-colors duration-200"
                            style="color: #64748B;"
                            @mouseenter="$event.currentTarget.style.background = '#FEF2F2'; $event.currentTarget.style.color = '#EF4444'"
                            @mouseleave="$event.currentTarget.style.background = 'transparent'; $event.currentTarget.style.color = '#64748B'"
                            aria-label="Logout"
                        >
                            <LogOut class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
                <header
                    class="sticky top-0 z-10 flex items-center gap-3 px-5 py-0"
                    style="background: #FFFFFF; border-bottom: 1px solid #EEF2FF; backdrop-filter: blur(12px); min-height: 64px; box-shadow: 0 1px 8px rgba(37,99,235,0.05);"
                >
                    <button
                        type="button"
                        class="rounded-xl bg-[#F1F5F9] p-2 text-slate-600 lg:hidden"
                        @click="mobileOpen = true"
                    >
                        <Menu class="h-4 w-4" />
                    </button>

                    <div class="min-w-0 flex-1">
                        <div class="mb-0.5 flex items-center gap-1.5">
                            <span class="text-[0.7rem] text-[#94A3B8]">Ready To Pict</span>
                            <BreadcrumbChevron class="h-3 w-3 text-[#CBD5E1]" />
                            <span class="rounded-md bg-[#EFF6FF] px-1.5 py-0.5 text-[0.7rem] font-semibold text-[#2563EB]">{{ topbarTitle }}</span>
                        </div>
                        <h1 class="truncate text-base font-bold leading-tight text-[#0F172A]" style="font-family: Poppins, sans-serif;">{{ topbarTitle }}</h1>
                    </div>

                    <div class="hidden items-center rounded-xl border border-[#EEF2FF] bg-[#F8FAFC] px-3 py-1.5 lg:flex">
                        <span class="text-[0.7rem] text-[#94A3B8]">{{ topbarDate }}</span>
                    </div>

                    <div class="relative">
                        <div
                            v-if="showTopSearch"
                            class="flex items-center gap-2 rounded-xl border-[1.5px] border-[#2563EB] bg-[#F8FAFC] px-3 py-2"
                            style="width: 200px;"
                        >
                            <Search class="h-3.5 w-3.5 shrink-0 text-[#2563EB]" />
                            <input
                                v-model="topSearchValue"
                                type="text"
                                autofocus
                                placeholder="Search anything..."
                                class="flex-1 bg-transparent text-[0.8rem] text-slate-800 outline-none placeholder:text-slate-400"
                                @blur="showTopSearch = false; topSearchValue = ''"
                            >
                            <button
                                v-if="topSearchValue"
                                type="button"
                                class="text-slate-400"
                                @click="topSearchValue = ''"
                            >
                                <X class="h-3 w-3" />
                            </button>
                        </div>

                        <button
                            v-else
                            type="button"
                            class="flex items-center gap-2 rounded-xl border border-[#EEF2FF] bg-[#F8FAFC] px-3 py-2 text-[#64748B]"
                            @click="showTopSearch = true"
                        >
                            <Search class="h-3.5 w-3.5" />
                            <span class="hidden text-[0.75rem] sm:inline">Search</span>
                        </button>
                    </div>

                    <button
                        type="button"
                        class="relative rounded-xl border border-[#EEF2FF] bg-[#F8FAFC] p-2.5 text-[#64748B]"
                    >
                        <Bell class="h-4 w-4" />
                        <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-[#EF4444]" style="box-shadow: 0 0 0 2px white;"></span>
                    </button>

                    <button
                        type="button"
                        class="hidden rounded-xl border border-[#EEF2FF] bg-[#F8FAFC] p-2.5 text-[#64748B] md:flex"
                    >
                        <Settings class="h-4 w-4" />
                    </button>

                    <div class="hidden h-6 w-px bg-[#E2E8F0] md:block"></div>

                    <div class="flex cursor-pointer items-center gap-2.5">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-xl text-[0.7rem] font-bold text-white"
                            style="background: linear-gradient(135deg, #2563EB, #60A5FA); box-shadow: 0 2px 8px rgba(37,99,235,0.25);"
                        >
                            AO
                        </div>
                        <div class="hidden md:block">
                            <p class="text-[0.8rem] font-semibold leading-tight text-[#0F172A]" style="font-family: Poppins, sans-serif;">Ahmad Owner</p>
                            <p class="text-[0.7rem] text-[#94A3B8]">Owner · Super Admin</p>
                        </div>
                    </div>
                </header>

                <main class="flex-1 overflow-y-auto">
                    <div class="mx-auto w-full max-w-[1600px] p-5 lg:p-7">
                        <div class="mb-6">
                <div
                    class="relative overflow-hidden rounded-2xl px-6 py-5"
                    style="background: linear-gradient(135deg, #1D4ED8 0%, #2563EB 50%, #3B82F6 100%); box-shadow: 0 4px 20px rgba(37,99,235,0.2);"
                >
                    <div class="pointer-events-none absolute inset-0 overflow-hidden">
                        <div class="absolute -right-6 -top-6 h-36 w-36 rounded-full" style="background: rgba(96,165,250,0.15);"></div>
                        <div class="absolute right-28 top-4 h-12 w-12 rounded-full" style="background: rgba(147,197,253,0.12);"></div>
                        <div
                            class="absolute bottom-0 right-0 h-full w-56 opacity-[0.06]"
                            style="background-image: repeating-linear-gradient(-45deg, #FFFFFF 0px, #FFFFFF 1px, transparent 1px, transparent 10px);"
                        ></div>
                        <div
                            class="absolute inset-0 opacity-[0.07]"
                            style="background-image: radial-gradient(circle, #FFFFFF 1px, transparent 1px); background-size: 20px 20px;"
                        ></div>
                    </div>

                    <div class="relative flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <Sparkles class="h-3.5 w-3.5" style="color: #93C5FD;" />
                                <span style="color: #93C5FD; font-size: 0.75rem; font-weight: 500;">{{ greeting }}, Ahmad!</span>
                            </div>
                            <h1 class="mb-1 text-white" style="font-family: Poppins, sans-serif; font-size: 1.4rem; font-weight: 700; line-height: 1.3;">Business Overview</h1>
                            <p style="color: rgba(255,255,255,0.65); font-size: 0.8rem;">Here's what's happening with Ready To Pict today.</p>
                        </div>

                        <div class="flex flex-wrap gap-2.5">
                            <a
                                :href="panelTransactionsUrl"
                                class="flex items-center gap-1.5 rounded-xl px-4 py-2 text-[0.8rem] transition-all duration-200"
                                style="background: rgba(255,255,255,0.12); color: #FFFFFF; border: 1px solid rgba(255,255,255,0.2); font-weight: 500; backdrop-filter: blur(4px);"
                            >
                                <Download class="h-3.5 w-3.5" />
                                Export
                            </a>
                            <a
                                :href="`${panelBookingsUrl}/create`"
                                class="flex items-center gap-1.5 rounded-xl px-4 py-2 text-[0.8rem] transition-all duration-200"
                                style="background: #FFFFFF; color: #2563EB; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.12);"
                            >
                                <Plus class="h-3.5 w-3.5" />
                                New Booking
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="(card, index) in normalizedSummaryCards"
                    :key="`summary-card-${index}-${card.title}`"
                    class="relative cursor-pointer overflow-hidden rounded-2xl p-5 transition-all duration-200"
                    :style="{ background: '#FFFFFF', border: `1px solid ${card.accentBorder}`, boxShadow: '0 1px 4px rgba(37,99,235,0.04), 0 4px 16px rgba(37,99,235,0.04)' }"
                >
                    <div class="mb-4 flex items-start justify-between">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl" :style="{ background: card.accentLight }">
                            <svg
                                v-if="index % 4 === 0"
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4.5 w-4.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                :stroke="card.accent"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M8 2v4" />
                                <path d="M16 2v4" />
                                <rect x="3" y="4" width="18" height="18" rx="2" />
                                <path d="M3 10h18" />
                            </svg>

                            <svg
                                v-else-if="index % 4 === 1"
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4.5 w-4.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                :stroke="card.accent"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M12 1v22" />
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6" />
                            </svg>

                            <svg
                                v-else-if="index % 4 === 2"
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4.5 w-4.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                :stroke="card.accent"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="8.5" cy="7" r="4" />
                                <path d="M20 8v6" />
                                <path d="M23 11h-6" />
                            </svg>

                            <svg
                                v-else
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4.5 w-4.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                :stroke="card.accent"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M3 17l6-6 4 4 7-7" />
                                <path d="M14 8h6v6" />
                            </svg>
                        </div>

                        <div
                            class="flex items-center gap-1 rounded-full px-2 py-0.5"
                            :style="{
                                background: card.trend === 'up' ? '#F0FDF4' : (card.trend === 'down' ? '#FEF2F2' : card.accentLight),
                                color: card.trend === 'up' ? '#16A34A' : (card.trend === 'down' ? '#EF4444' : card.accent),
                            }"
                        >
                            <svg
                                v-if="card.trend === 'up'"
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-3 w-3"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="m7 17 10-10" />
                                <path d="M7 7h10v10" />
                            </svg>
                            <svg
                                v-else-if="card.trend === 'down'"
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-3 w-3"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="m17 7-10 10" />
                                <path d="M7 7h10v10" />
                            </svg>
                            <span style="font-size: 0.7rem; font-weight: 600;">{{ card.change }}</span>
                        </div>
                    </div>

                    <p class="mb-0.5 text-xs" style="color: #94A3B8; font-weight: 500;">{{ card.title }}</p>
                    <p class="mb-0.5" style="color: #0F172A; font-size: 1.5rem; font-weight: 700; line-height: 1.2; font-family: Poppins, sans-serif;">{{ card.value }}</p>
                    <p class="mb-4 text-xs" style="color: #CBD5E1;">{{ card.changeLabel }}</p>

                    <svg :viewBox="`0 0 ${card.sparklineMeta.width} ${card.sparklineMeta.height}`" class="h-[28px] w-[72px] overflow-visible">
                        <defs>
                            <linearGradient :id="card.gradientId" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" :stop-color="card.accent" stop-opacity="0.18"></stop>
                                <stop offset="100%" :stop-color="card.accent" stop-opacity="0"></stop>
                            </linearGradient>
                        </defs>
                        <polygon :points="card.sparklineMeta.fillPoints" :fill="`url(#${card.gradientId})`"></polygon>
                        <polyline :points="card.sparklineMeta.linePoints" fill="none" :stroke="card.accent" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></polyline>
                        <circle :cx="card.sparklineMeta.lastX" :cy="card.sparklineMeta.lastY" r="3" :fill="card.accent" stroke="#FFFFFF" stroke-width="1.5"></circle>
                    </svg>

                    <div class="absolute bottom-0 left-0 right-0 h-0.5 rounded-b-2xl" :style="{ background: `linear-gradient(90deg, ${card.accent}, transparent)`, opacity: 0.4 }"></div>
                </div>
            </div>

            <div class="mb-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
                <div
                    class="rounded-2xl p-6 lg:col-span-2"
                    style="background: #FFFFFF; box-shadow: 0 1px 3px rgba(37,99,235,0.05), 0 4px 16px rgba(37,99,235,0.05); border: 1px solid #EEF2FF;"
                >
                    <div class="mb-6 flex items-start justify-between">
                        <div>
                            <h2 class="mb-1 text-gray-900" style="font-family: Poppins, sans-serif;">Revenue Overview</h2>
                            <p class="text-sm" style="color: #94A3B8;">Revenue and bookings trend</p>
                        </div>
                        <div class="flex items-center gap-1 rounded-lg p-1" style="background: #F8FAFC; border: 1px solid #EEF2FF;">
                            <button
                                type="button"
                                class="rounded-md px-3 py-1.5 text-xs transition-all duration-200"
                                :style="{ background: activeRevenuePeriod === '7d' ? '#2563EB' : 'transparent', color: activeRevenuePeriod === '7d' ? '#FFFFFF' : '#94A3B8', boxShadow: activeRevenuePeriod === '7d' ? '0 1px 4px rgba(37,99,235,0.3)' : 'none' }"
                                @click="activeRevenuePeriod = '7d'"
                            >
                                Last 7 days
                            </button>
                            <button
                                type="button"
                                class="rounded-md px-3 py-1.5 text-xs transition-all duration-200"
                                :style="{ background: activeRevenuePeriod === '30d' ? '#2563EB' : 'transparent', color: activeRevenuePeriod === '30d' ? '#FFFFFF' : '#94A3B8', boxShadow: activeRevenuePeriod === '30d' ? '0 1px 4px rgba(37,99,235,0.3)' : 'none' }"
                                @click="activeRevenuePeriod = '30d'"
                            >
                                Last 30 days
                            </button>
                        </div>
                    </div>

                    <div class="mb-6 flex gap-6">
                        <div>
                            <p class="mb-1 text-xs" style="color: #94A3B8;">Total Revenue</p>
                            <p style="color: #1F2937; font-size: 1.25rem; font-weight: 700;">{{ revenueTotal }}</p>
                        </div>
                        <div class="w-px" style="background: #F1F5F9;"></div>
                        <div>
                            <p class="mb-1 text-xs" style="color: #94A3B8;">Total Bookings</p>
                            <p style="color: #1F2937; font-size: 1.25rem; font-weight: 700;">{{ bookingTotal }}</p>
                        </div>
                    </div>

                    <div v-if="revenueChartMeta.hasData" class="overflow-x-auto">
                        <svg :viewBox="`0 0 ${revenueChartMeta.width} ${revenueChartMeta.height}`" class="h-[260px] min-w-[680px] w-full">
                            <defs>
                                <linearGradient id="revenue-chart-gradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="5%" stop-color="#2563EB" stop-opacity="0.22"></stop>
                                    <stop offset="95%" stop-color="#2563EB" stop-opacity="0"></stop>
                                </linearGradient>
                            </defs>

                            <line
                                v-for="(lineY, lineIndex) in revenueChartMeta.gridLines"
                                :key="`grid-line-${lineIndex}`"
                                :x1="revenueChartMeta.left"
                                :x2="revenueChartMeta.width - 16"
                                :y1="lineY"
                                :y2="lineY"
                                stroke="#F1F5F9"
                                stroke-dasharray="3 3"
                            ></line>

                            <text
                                v-for="(tick, tickIndex) in revenueChartMeta.yTicks"
                                :key="`tick-${tickIndex}`"
                                x="6"
                                :y="tick.y + 3"
                                fill="#94A3B8"
                                font-size="11"
                            >
                                {{ tick.label }}
                            </text>

                            <path :d="revenueChartMeta.areaPath" fill="url(#revenue-chart-gradient)"></path>
                            <path :d="revenueChartMeta.revenuePath" fill="none" stroke="#2563EB" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path :d="revenueChartMeta.bookingPath" fill="none" stroke="#22C55E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>

                            <text
                                v-for="point in revenueChartMeta.points"
                                :key="`label-${point.key}`"
                                :x="point.x"
                                :y="revenueChartMeta.top + revenueChartMeta.plotHeight + 20"
                                text-anchor="middle"
                                fill="#94A3B8"
                                font-size="11"
                            >
                                {{ point.label }}
                            </text>
                        </svg>
                    </div>
                    <div v-else class="flex h-[260px] items-center justify-center text-sm" style="color: #94A3B8;">No revenue data available.</div>

                    <div class="mt-4 flex items-center gap-6">
                        <div class="flex items-center gap-2">
                            <div class="h-3 w-3 rounded-full" style="background: #2563EB;"></div>
                            <span class="text-xs" style="color: #94A3B8;">Revenue</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="h-3 w-3 rounded-full" style="background: #22C55E;"></div>
                            <span class="text-xs" style="color: #94A3B8;">Bookings</span>
                        </div>
                    </div>
                </div>

                <div
                    class="h-full rounded-2xl p-6"
                    style="background: #FFFFFF; box-shadow: 0 1px 3px rgba(37,99,235,0.05), 0 4px 16px rgba(37,99,235,0.05); border: 1px solid #EEF2FF;"
                >
                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <h2 class="text-gray-900" style="font-family: Poppins, sans-serif;">Queue Monitor</h2>
                            <p class="mt-0.5 text-xs" style="color: #94A3B8;">Live session status</p>
                        </div>
                        <span class="flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs" style="background: #ECFDF5; color: #059669;">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-green-500"></span>
                            Live
                        </span>
                    </div>

                    <div class="mb-4 grid grid-cols-2 gap-2">
                        <div class="rounded-xl p-3" style="background: #F8FAFC; border: 1px solid #EEF2FF;">
                            <p class="text-[0.7rem]" style="color: #94A3B8;">In Queue</p>
                            <p style="color: #1F2937; font-size: 1.1rem; font-weight: 700;">{{ queueStats.in_queue }}</p>
                        </div>
                        <div class="rounded-xl p-3" style="background: #F8FAFC; border: 1px solid #EEF2FF;">
                            <p class="text-[0.7rem]" style="color: #94A3B8;">Now Serving</p>
                            <p style="color: #1F2937; font-size: 1.1rem; font-weight: 700;">{{ queueStats.in_session }}</p>
                        </div>
                        <div class="rounded-xl p-3" style="background: #F8FAFC; border: 1px solid #EEF2FF;">
                            <p class="text-[0.7rem]" style="color: #94A3B8;">Waiting</p>
                            <p style="color: #1F2937; font-size: 1.1rem; font-weight: 700;">{{ queueStats.waiting }}</p>
                        </div>
                        <div class="rounded-xl p-3" style="background: #F8FAFC; border: 1px solid #EEF2FF;">
                            <p class="text-[0.7rem]" style="color: #94A3B8;">Completed</p>
                            <p style="color: #1F2937; font-size: 1.1rem; font-weight: 700;">{{ queueStats.completed_today }}</p>
                        </div>
                    </div>

                    <div class="mb-4 rounded-xl p-4" style="background: #EFF6FF; border: 1px solid #DBEAFE;">
                        <div class="mb-3 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 6v6l4 2" />
                            </svg>
                            <span class="text-xs" style="color: #2563EB; font-weight: 600;">Currently Serving</span>
                        </div>

                        <template v-if="currentQueue">
                            <div class="mb-3 flex items-center justify-between">
                                <div>
                                    <p style="color: #1D4ED8; font-size: 2rem; font-weight: 800; line-height: 1;">{{ currentQueue.queue_code }}</p>
                                    <p class="mt-1 text-sm" style="color: #1F2937;">{{ currentQueue.customer_name }}</p>
                                    <span
                                        class="mt-1 inline-block rounded-full px-2 py-0.5 text-xs"
                                        :style="{ background: resolveQueueStatus(currentQueue.status).bg, color: resolveQueueStatus(currentQueue.status).color, fontWeight: 600 }"
                                    >
                                        {{ currentQueue.package_name }}
                                    </span>
                                </div>
                                <div class="text-right">
                                    <p class="mb-1 text-xs" style="color: #64748B;">Time Left</p>
                                    <p style="color: #1F2937; font-size: 1.25rem; font-weight: 700;">{{ queueRemainingText }}</p>
                                </div>
                            </div>

                            <div class="h-1.5 overflow-hidden rounded-full" style="background: #BFDBFE;">
                                <div class="h-full rounded-full transition-all duration-1000" :style="queueProgressStyle"></div>
                            </div>
                            <div class="mt-1 flex justify-between">
                                <span class="text-xs" style="color: #94A3B8;">00:00</span>
                                <span class="text-xs" style="color: #94A3B8;">{{ queueSessionDurationText }}</span>
                            </div>
                        </template>
                        <p v-else class="text-sm" style="color: #64748B;">No active queue session right now.</p>
                    </div>

                    <div class="mb-4 flex gap-2">
                        <button type="button" class="flex-1 rounded-lg py-2 text-sm" style="background: #2563EB; color: #FFFFFF; box-shadow: 0 2px 8px rgba(37,99,235,0.25);">Call Next</button>
                        <button type="button" class="flex-1 rounded-lg border py-2 text-sm" style="background: #ECFDF5; color: #059669; border-color: #A7F3D0;">Complete</button>
                    </div>

                    <div>
                        <div class="mb-3 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="8.5" cy="7" r="4" />
                                <path d="M20 8v6" />
                                <path d="M23 11h-6" />
                            </svg>
                            <span class="text-sm" style="color: #374151;">Waiting ({{ waitingQueue.length }})</span>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="(ticket, index) in waitingQueue"
                                :key="`waiting-ticket-${ticket.queue_code}-${index}`"
                                class="flex items-center justify-between rounded-xl border p-3"
                                style="background: #F8FAFC; border-color: #EEF2FF;"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex h-7 w-7 items-center justify-center rounded-lg text-xs" style="background: #EFF6FF; color: #2563EB;">{{ index + 1 }}</div>
                                    <div>
                                        <p class="text-sm" style="color: #1F2937;">{{ ticket.queue_code }}</p>
                                        <p class="text-xs" style="color: #94A3B8;">{{ ticket.customer_name }}</p>
                                    </div>
                                </div>
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs"
                                    :style="{ background: resolveQueueStatus(ticket.status).bg, color: resolveQueueStatus(ticket.status).color }"
                                >
                                    {{ ticket.package_name }}
                                </span>
                            </div>

                            <p v-if="!waitingQueue.length" class="text-sm" style="color: #94A3B8;">Queue waiting list is currently empty.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="mb-5 overflow-hidden rounded-2xl"
                style="background: #FFFFFF; box-shadow: 0 1px 3px rgba(37,99,235,0.05), 0 4px 16px rgba(37,99,235,0.05); border: 1px solid #EEF2FF;"
            >
                <div class="border-b p-6" style="border-color: #F1F5F9;">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-gray-900" style="font-family: Poppins, sans-serif;">Booking Monitoring</h2>
                            <p class="mt-0.5 text-sm" style="color: #94A3B8;">Manage and track all reservations</p>
                        </div>
                        <a
                            :href="`${panelBookingsUrl}/create`"
                            class="rounded-xl px-4 py-2 text-sm transition-all duration-200"
                            style="background: #2563EB; color: #FFFFFF; box-shadow: 0 2px 8px rgba(37,99,235,0.25);"
                        >
                            New Booking
                        </a>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <div class="relative flex-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.3-4.3" />
                            </svg>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search by customer name or booking ID..."
                                class="w-full rounded-lg py-2 pl-9 pr-4 text-sm outline-none transition-all"
                                style="background: #F8FAFC; border: 1px solid #EEF2FF; color: #374151;"
                            >
                        </div>
                    </div>

                    <div class="mt-3 flex gap-1.5 overflow-x-auto pb-1">
                        <button
                            v-for="tab in filterTabs"
                            :key="`filter-tab-${tab.key}`"
                            type="button"
                            class="whitespace-nowrap rounded-lg px-3 py-1.5 text-xs transition-all duration-200"
                            :style="{
                                background: filterStatus === tab.key ? '#2563EB' : '#F8FAFC',
                                color: filterStatus === tab.key ? '#FFFFFF' : '#64748B',
                                border: `1px solid ${filterStatus === tab.key ? '#2563EB' : '#EEF2FF'}`,
                                boxShadow: filterStatus === tab.key ? '0 2px 6px rgba(37,99,235,0.2)' : 'none',
                            }"
                            @click="setFilterStatus(tab.key)"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr style="border-bottom: 1px solid #F1F5F9;">
                                <th class="px-5 py-3 text-left text-xs uppercase tracking-wider" style="color: #94A3B8;">Booking ID</th>
                                <th class="px-5 py-3 text-left text-xs uppercase tracking-wider" style="color: #94A3B8;">Customer</th>
                                <th class="px-5 py-3 text-left text-xs uppercase tracking-wider" style="color: #94A3B8;">Package</th>
                                <th class="px-5 py-3 text-left text-xs uppercase tracking-wider" style="color: #94A3B8;">Date and Time</th>
                                <th class="px-5 py-3 text-left text-xs uppercase tracking-wider" style="color: #94A3B8;">Amount</th>
                                <th class="px-5 py-3 text-left text-xs uppercase tracking-wider" style="color: #94A3B8;">Payment</th>
                                <th class="px-5 py-3 text-left text-xs uppercase tracking-wider" style="color: #94A3B8;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in normalizedRows"
                                :key="`booking-row-${row.id}`"
                                style="border-bottom: 1px solid #F8FAFC;"
                            >
                                <td class="px-5 py-3.5">
                                    <span class="text-sm" style="color: #2563EB; font-weight: 600;">{{ row.id }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-7 w-7 items-center justify-center rounded-lg text-xs text-white" style="background: linear-gradient(135deg, #2563EB, #60A5FA);">
                                            {{ initialsFromName(row.name).charAt(0) }}
                                        </div>
                                        <span class="text-sm" style="color: #1F2937;">{{ row.name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-sm" style="color: #374151;">{{ row.pkg }}</td>
                                <td class="px-5 py-3.5">
                                    <p class="text-sm" style="color: #1F2937;">{{ row.date }}</p>
                                    <p class="text-xs" style="color: #94A3B8;">{{ row.time }}</p>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="text-sm" style="color: #1F2937; font-weight: 600;">{{ row.amount_text }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-xs" style="color: #64748B;">{{ row.payment }}</td>
                                <td class="px-5 py-3.5">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs"
                                        :style="{
                                            background: resolveBookingStatus(row.status).bg,
                                            color: resolveBookingStatus(row.status).color,
                                            fontWeight: 600,
                                        }"
                                    >
                                        {{ resolveBookingStatus(row.status).label }}
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="loading">
                                <td colspan="7" class="px-4 py-10 text-center text-sm" style="color: #94A3B8;">Loading bookings...</td>
                            </tr>

                            <tr v-else-if="!normalizedRows.length">
                                <td colspan="7" class="px-4 py-10 text-center text-sm" style="color: #94A3B8;">No bookings found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 p-4" style="border-top: 1px solid #F1F5F9;">
                    <p class="text-xs" style="color: #94A3B8;">{{ bookingResultCaption }}</p>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-lg border px-3 py-1.5 text-xs text-gray-600 transition-colors"
                            :class="canGoPrev ? 'hover:bg-gray-50' : 'cursor-not-allowed opacity-50'"
                            :disabled="!canGoPrev || loading"
                            @click="goToPrevPage"
                        >
                            Previous
                        </button>

                        <span class="text-xs" style="color: #94A3B8;">Page {{ pagination.current_page }} / {{ Math.max(pagination.last_page, 1) }}</span>

                        <button
                            type="button"
                            class="rounded-lg border px-3 py-1.5 text-xs text-gray-600 transition-colors"
                            :class="canGoNext ? 'hover:bg-gray-50' : 'cursor-not-allowed opacity-50'"
                            :disabled="!canGoNext || loading"
                            @click="goToNextPage"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div
                    class="overflow-hidden rounded-2xl"
                    style="background: #FFFFFF; box-shadow: 0 1px 3px rgba(37,99,235,0.05), 0 4px 16px rgba(37,99,235,0.05); border: 1px solid #EEF2FF;"
                >
                    <div class="border-b p-5" style="border-color: #F1F5F9;">
                        <div class="flex items-start justify-between">
                            <div>
                                <h2 class="text-gray-900" style="font-family: Poppins, sans-serif;">Recent Transactions</h2>
                                <p class="mt-0.5 text-xs" style="color: #94A3B8;">Latest payment activities</p>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between rounded-xl p-3" style="background: #EFF6FF;">
                            <span class="text-xs" style="color: #2563EB;">Today's Revenue</span>
                            <span style="color: #2563EB; font-weight: 700;">{{ transactionTodayTotal }}</span>
                        </div>
                    </div>

                    <div class="divide-y" style="border-color: #F8FAFC;">
                        <div
                            v-for="(transaction, index) in normalizedRecentTransactions"
                            :key="`recent-transaction-${transaction.id}-${index}`"
                            class="flex items-center gap-3 px-5 py-3.5"
                        >
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl text-xs text-white" style="background: linear-gradient(135deg, #2563EB, #60A5FA);">
                                {{ transaction.initials }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="truncate text-sm" style="color: #1F2937;">{{ transaction.customer }}</p>
                                    <span
                                        class="shrink-0 rounded px-1.5 py-0.5 text-xs"
                                        :style="{ background: resolveMethodStyle(transaction.method).bg, color: resolveMethodStyle(transaction.method).color }"
                                    >
                                        {{ transaction.method }}
                                    </span>
                                </div>
                                <p class="text-xs" style="color: #94A3B8;">{{ transaction.id }} - {{ transaction.cashier }} - {{ transaction.time }}</p>
                            </div>

                            <div class="shrink-0 text-right">
                                <p class="text-sm" style="color: #1F2937; font-weight: 600;">{{ transaction.amountText }}</p>
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs"
                                    :style="{ background: resolveTransactionStatus(transaction.status).bg, color: resolveTransactionStatus(transaction.status).color }"
                                >
                                    {{ resolveTransactionStatus(transaction.status).label }}
                                </span>
                            </div>
                        </div>

                        <p v-if="!normalizedRecentTransactions.length" class="px-5 py-10 text-center text-sm" style="color: #94A3B8;">No recent transactions.</p>
                    </div>

                    <div class="p-4" style="border-top: 1px solid #F1F5F9;">
                        <a :href="panelTransactionsUrl" class="flex w-full items-center justify-center gap-1 rounded-xl py-2 text-sm" style="color: #2563EB;">View All Transactions</a>
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-2xl"
                    style="background: #FFFFFF; box-shadow: 0 1px 3px rgba(37,99,235,0.05), 0 4px 16px rgba(37,99,235,0.05); border: 1px solid #EEF2FF;"
                >
                    <div class="border-b p-5" style="border-color: #F1F5F9;">
                        <h2 class="text-gray-900" style="font-family: Poppins, sans-serif;">Activity Log</h2>
                        <p class="mt-0.5 text-xs" style="color: #94A3B8;">Recent system activities</p>
                    </div>

                    <div class="p-5">
                        <div class="space-y-4">
                            <div
                                v-for="(activity, index) in normalizedRecentActivities"
                                :key="`activity-${activity.id}-${index}`"
                                class="flex gap-3"
                            >
                                <div class="relative shrink-0">
                                    <div
                                        class="flex h-9 w-9 items-center justify-center rounded-xl text-xs"
                                        :style="{ background: resolveActivityTone(activity.module).bg, color: resolveActivityTone(activity.module).color, fontWeight: 700 }"
                                    >
                                        {{ String(activity.module || 'A').charAt(0).toUpperCase() }}
                                    </div>
                                    <div
                                        v-if="index < normalizedRecentActivities.length - 1"
                                        class="absolute left-4 top-9 w-px"
                                        style="height: 28px; background: #F1F5F9;"
                                    ></div>
                                </div>

                                <div class="min-w-0 flex-1 pb-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-sm" style="color: #1F2937;">{{ activity.action }}</p>
                                        <span class="shrink-0 text-xs" style="color: #CBD5E1;">{{ activity.time }}</span>
                                    </div>
                                    <p class="mt-0.5 text-xs" style="color: #94A3B8;">{{ activity.actor }} - {{ activity.module }}</p>
                                </div>
                            </div>

                            <p v-if="!normalizedRecentActivities.length" class="py-8 text-center text-sm" style="color: #94A3B8;">No recent activities.</p>
                        </div>
                    </div>

                    <div class="p-4" style="border-top: 1px solid #F1F5F9;">
                        <a :href="panelActivitiesUrl" class="flex w-full items-center justify-center gap-1 rounded-xl py-2 text-sm" style="color: #2563EB;">View All Activities</a>
                    </div>
                </div>
            </div>

                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
