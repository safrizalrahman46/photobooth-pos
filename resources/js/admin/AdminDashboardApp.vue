<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import {
    Activity,
    BarChart3,
    Calendar,
    LayoutDashboard,
    ListOrdered,
    Package,
    Palette,
    Receipt,
    Settings,
    Users,
} from 'lucide-vue-next';

import AdminSidebar from './components/AdminSidebar.vue';
import AdminTopBar from './components/AdminTopBar.vue';
import ActivityLogsPage from './pages/ActivityLogsPage.vue';
import BookingsPage from './pages/BookingsPage.vue';
import DashboardPage from './pages/DashboardPage.vue';
import DesignsPage from './pages/DesignsPage.vue';
import PackagesPage from './pages/PackagesPage.vue';
import QueuePage from './pages/QueuePage.vue';
import ReportsPage from './pages/ReportsPage.vue';
import SettingsPage from './pages/SettingsPage.vue';
import TransactionsPage from './pages/TransactionsPage.vue';
import UsersPage from './pages/UsersPage.vue';

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
        default: '/admin',
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
let reportDebounceTimer = null;
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

const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

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

const panelBaseUrl = computed(() => {
    const value = String(props.panelUrl || '/admin').trim();

    if (!value) {
        return '/admin';
    }

    return value.endsWith('/') ? value.slice(0, -1) : value;
});

const toPathname = (value, fallback = '/admin') => {
    const text = String(value || '').trim();

    if (!text) {
        return fallback;
    }

    if (text.startsWith('/')) {
        return text.endsWith('/') && text !== '/' ? text.slice(0, -1) : text;
    }

    try {
        const url = new URL(text, typeof window !== 'undefined' ? window.location.origin : 'http://localhost');
        const pathname = String(url.pathname || fallback);

        return pathname.endsWith('/') && pathname !== '/' ? pathname.slice(0, -1) : pathname;
    } catch {
        return fallback;
    }
};

const panelBasePath = computed(() => toPathname(panelBaseUrl.value, '/admin'));

const panelBookingsUrl = computed(() => `${panelBaseUrl.value}/bookings`);
const panelTransactionsUrl = computed(() => `${panelBaseUrl.value}/transactions`);
const panelActivitiesUrl = computed(() => `${panelBaseUrl.value}/activity-logs`);
const panelReportsUrl = computed(() => `${panelBaseUrl.value}/reports`);
const panelSettingsUrl = computed(() => `${panelBaseUrl.value}/settings`);

const navGroups = [
    { key: 'overview', label: 'Overview' },
    { key: 'management', label: 'Management' },
    { key: 'operations', label: 'Operations' },
    { key: 'analytics', label: 'Analytics' },
    { key: 'system', label: 'System' },
];

const queueStats = computed(() => {
    const stats = props.queueLive?.stats || {};

    return {
        in_queue: Number(stats.in_queue || 0),
        in_session: Number(stats.in_session || 0),
        waiting: Number(stats.waiting || 0),
        completed_today: Number(stats.completed_today || 0),
    };
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

const navItems = computed(() => {
    const items = [
        { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard, href: '/admin', group: 'overview' },
        { id: 'packages', label: 'Packages', icon: Package, href: `${panelBaseUrl.value}/packages`, group: 'management' },
        { id: 'designs', label: 'Designs', icon: Palette, href: `${panelBaseUrl.value}/design-catalogs`, group: 'management' },
        { id: 'users', label: 'Users', icon: Users, href: `${panelBaseUrl.value}/users`, group: 'management' },
        { id: 'bookings', label: 'Bookings', icon: Calendar, href: panelBookingsUrl.value, group: 'operations' },
        { id: 'queue', label: 'Queue', icon: ListOrdered, href: `${panelBaseUrl.value}/queue-tickets`, group: 'operations' },
        { id: 'transactions', label: 'Transactions', icon: Receipt, href: panelTransactionsUrl.value, group: 'operations' },
        { id: 'reports', label: 'Reports', icon: BarChart3, href: panelReportsUrl.value, group: 'analytics' },
        { id: 'activity-logs', label: 'Activity Logs', icon: Activity, href: panelActivitiesUrl.value, group: 'analytics' },
        { id: 'settings', label: 'Settings', icon: Settings, href: panelSettingsUrl.value, group: 'system' },
    ];

    return items.map((item) => ({
        ...item,
        badge: navBadgeFor(item.id),
    }));
});

const currentPath = computed(() => {
    if (typeof window === 'undefined') {
        return '/admin';
    }

    return String(window.location.pathname || '/admin');
});

const normalizedCurrentPath = computed(() => {
    const path = String(currentPath.value || '/admin');

    if (path === '/') {
        return '/';
    }

    return path.endsWith('/') ? path.slice(0, -1) : path;
});

const activeModuleId = computed(() => {
    const base = panelBasePath.value;
    const path = normalizedCurrentPath.value;

    if (path === base) {
        return 'dashboard';
    }

    const map = [
        { id: 'packages', path: `${base}/packages` },
        { id: 'designs', path: `${base}/design-catalogs` },
        { id: 'users', path: `${base}/users` },
        { id: 'bookings', path: `${base}/bookings` },
        { id: 'queue', path: `${base}/queue-tickets` },
        { id: 'transactions', path: `${base}/transactions` },
        { id: 'reports', path: `${base}/reports` },
        { id: 'activity-logs', path: `${base}/activity-logs` },
        { id: 'settings', path: `${base}/settings` },
    ];

    const matched = map.find((entry) => path === entry.path || path.startsWith(`${entry.path}/`));

    return matched ? matched.id : 'dashboard';
});

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

const topbarTitle = computed(() => {
    const id = String(activeModuleId.value || 'dashboard');

    return topbarMetaById[id]?.title || 'Dashboard';
});

const topbarDate = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(new Date());
});

const fallbackSummaryCards = computed(() => {
    const stats = Array.isArray(props.initialStats) ? props.initialStats.slice(0, 4) : [];

    return stats.map((item, index) => ({
        title: String(item.label || `Metric ${index + 1}`),
        value: String(item.value ?? '-'),
        change: '0',
        changeLabel: 'baseline',
    }));
});

const normalizedSummaryCards = computed(() => {
    const source = Array.isArray(props.summaryCards) && props.summaryCards.length
        ? props.summaryCards
        : fallbackSummaryCards.value;

    return source.map((card, index) => ({
        title: String(card.title || card.label || `Metric ${index + 1}`),
        value: String(card.value ?? '-'),
        change: String(card.change ?? '0'),
        changeLabel: String(card.changeLabel || card.helper || 'baseline'),
        tone: defaultCardPalette[index % defaultCardPalette.length],
    }));
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

const revenueTotal = computed(() => {
    const total = normalizedRevenueSeries.value.reduce((sum, item) => sum + Number(item.revenue || 0), 0);

    return formatRupiah(total);
});

const bookingTotal = computed(() => {
    return normalizedRevenueSeries.value.reduce((sum, item) => sum + Number(item.bookings || 0), 0);
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

const packageCards = computed(() => {
    const grouped = normalizedRows.value.reduce((accumulator, row) => {
        const key = String(row.pkg || '-');

        if (!accumulator[key]) {
            accumulator[key] = {
                name: key,
                bookings: 0,
                revenue: 0,
                pending: 0,
                completed: 0,
            };
        }

        accumulator[key].bookings += 1;
        accumulator[key].revenue += Number(row.amount || 0);

        if (row.status === 'pending' || row.status === 'booked') {
            accumulator[key].pending += 1;
        }

        if (row.status === 'used') {
            accumulator[key].completed += 1;
        }

        return accumulator;
    }, {});

    return Object.values(grouped)
        .sort((left, right) => right.bookings - left.bookings)
        .map((item, index) => ({
            ...item,
            revenueText: formatRupiah(item.revenue),
            tone: defaultCardPalette[index % defaultCardPalette.length],
        }));
});

const designCards = computed(() => {
    const source = packageCards.value.length
        ? packageCards.value
        : [
            { name: 'Blue Elegance', bookings: 0, revenue: 0, pending: 0, completed: 0 },
            { name: 'Golden Hour', bookings: 0, revenue: 0, pending: 0, completed: 0 },
            { name: 'Vintage Rose', bookings: 0, revenue: 0, pending: 0, completed: 0 },
        ];

    return source.slice(0, 6).map((item, index) => ({
        id: `${item.name}-${index}`,
        name: `${item.name} Theme`,
        bookings: item.bookings,
        status: index % 3 === 0 ? 'draft' : 'active',
        updated: index % 2 === 0 ? '2 days ago' : 'today',
        tone: defaultCardPalette[index % defaultCardPalette.length],
    }));
});

const userRows = computed(() => {
    const registry = new Map();

    normalizedRecentActivities.value.forEach((activity) => {
        const name = String(activity.actor || '').trim();

        if (!name || name === 'System') {
            return;
        }

        if (!registry.has(name)) {
            registry.set(name, {
                name,
                role: name.toLowerCase().includes('owner') ? 'Owner' : 'Staff',
                status: 'active',
                source: 'activity',
            });
        }
    });

    normalizedRecentTransactions.value.forEach((transaction) => {
        const cashierName = String(transaction.cashier || '').trim();

        if (!cashierName || cashierName === '-') {
            return;
        }

        if (!registry.has(cashierName)) {
            registry.set(cashierName, {
                name: cashierName,
                role: 'Cashier',
                status: 'active',
                source: 'transaction',
            });
        }
    });

    const list = Array.from(registry.values());

    if (!list.length) {
        return [
            { name: 'Ahmad Owner', role: 'Owner', status: 'active', source: 'seed' },
            { name: 'Cashier Team', role: 'Cashier', status: 'active', source: 'seed' },
        ];
    }

    return list;
});

const activitySearch = ref('');
const activityModuleFilter = ref('all');

const activityModuleOptions = computed(() => {
    const modules = normalizedRecentActivities.value.map((item) => String(item.module || '').toLowerCase());
    const uniques = Array.from(new Set(modules.filter(Boolean))).sort();

    return ['all', ...uniques];
});

const filteredActivityRows = computed(() => {
    const term = String(activitySearch.value || '').toLowerCase().trim();

    return normalizedRecentActivities.value.filter((activity) => {
        const moduleName = String(activity.module || '').toLowerCase();
        const passesModule = activityModuleFilter.value === 'all' || moduleName === activityModuleFilter.value;

        if (!passesModule) {
            return false;
        }

        if (!term) {
            return true;
        }

        return (
            String(activity.actor || '').toLowerCase().includes(term)
            || String(activity.action || '').toLowerCase().includes(term)
            || moduleName.includes(term)
        );
    });
});

const settingsTab = ref('business');
const settingsTabs = [
    { id: 'business', label: 'Business Info' },
    { id: 'hours', label: 'Operating Hours' },
    { id: 'payment', label: 'Payment' },
    { id: 'notifications', label: 'Notifications' },
    { id: 'security', label: 'Security' },
];

const reportFilters = ref({
    from: '',
    to: '',
    package_id: '',
    cashier_id: '',
});
const reportLoading = ref(false);
const reportError = ref('');
const reportData = ref(null);

const setDefaultReportRange = () => {
    const now = new Date();
    const end = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const start = new Date(end);
    start.setDate(end.getDate() - 6);

    const toIso = (value) => {
        const year = value.getFullYear();
        const month = String(value.getMonth() + 1).padStart(2, '0');
        const day = String(value.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    };

    reportFilters.value.from = toIso(start);
    reportFilters.value.to = toIso(end);
};

const fetchReportSummary = async () => {
    if (!props.reportUrl) {
        return;
    }

    reportLoading.value = true;
    reportError.value = '';

    try {
        const params = new URLSearchParams();

        if (reportFilters.value.from) {
            params.set('from', reportFilters.value.from);
        }

        if (reportFilters.value.to) {
            params.set('to', reportFilters.value.to);
        }

        if (reportFilters.value.package_id) {
            params.set('package_id', String(reportFilters.value.package_id));
        }

        if (reportFilters.value.cashier_id) {
            params.set('cashier_id', String(reportFilters.value.cashier_id));
        }

        const response = await fetch(`${props.reportUrl}?${params.toString()}`, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const payload = await response.json();
        reportData.value = payload?.data?.report || null;
    } catch (error) {
        reportError.value = 'Failed to load report data.';
        if (error?.name !== 'AbortError') {
            console.error('Failed to fetch dashboard report:', error);
        }
    } finally {
        reportLoading.value = false;
    }
};

const scheduleReportFetch = () => {
    if (reportDebounceTimer) {
        clearTimeout(reportDebounceTimer);
    }

    reportDebounceTimer = setTimeout(() => {
        fetchReportSummary();
    }, 250);
};

const reportSummaryCards = computed(() => {
    const revenue = reportData.value?.revenue_summary || {};
    const booking = reportData.value?.booking_summary || {};

    return [
        {
            label: 'Total Revenue',
            value: String(revenue.total_revenue_text || formatRupiah(0)),
            helper: `${Number(revenue.transaction_count || 0)} transactions`,
            tone: defaultCardPalette[1],
        },
        {
            label: 'Average Ticket',
            value: String(revenue.average_transaction_text || formatRupiah(0)),
            helper: 'Per successful transaction',
            tone: defaultCardPalette[0],
        },
        {
            label: 'Total Bookings',
            value: String(booking.total_bookings || 0),
            helper: `${Number(booking.converted_bookings || 0)} converted`,
            tone: defaultCardPalette[2],
        },
        {
            label: 'Conversion Rate',
            value: String(booking.conversion_rate_text || '0%'),
            helper: 'Paid + Done bookings',
            tone: defaultCardPalette[3],
        },
    ];
});

const reportDailyRows = computed(() => {
    return Array.isArray(reportData.value?.daily_summary) ? reportData.value.daily_summary : [];
});

const reportDailyMaxRevenue = computed(() => {
    const max = reportDailyRows.value.reduce((highest, row) => Math.max(highest, Number(row.revenue || 0)), 0);

    return max > 0 ? max : 1;
});

const reportPackageRows = computed(() => {
    return Array.isArray(reportData.value?.package_popularity) ? reportData.value.package_popularity : [];
});

const reportCashierRows = computed(() => {
    return Array.isArray(reportData.value?.cashier_performance) ? reportData.value.cashier_performance : [];
});

const reportStatusRows = computed(() => {
    const rowsValue = reportData.value?.booking_summary?.statuses;

    return Array.isArray(rowsValue) ? rowsValue : [];
});

watch(activeModuleId, (nextValue) => {
    if (nextValue !== 'reports') {
        return;
    }

    if (!reportFilters.value.from || !reportFilters.value.to) {
        setDefaultReportRange();
    }

    fetchReportSummary();
}, { immediate: true });

watch(
    () => [
        reportFilters.value.from,
        reportFilters.value.to,
        reportFilters.value.package_id,
        reportFilters.value.cashier_id,
    ],
    () => {
        if (activeModuleId.value !== 'reports') {
            return;
        }

        scheduleReportFetch();
    },
);

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
    if (activeModuleId.value !== 'bookings') {
        return;
    }

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

    if (reportDebounceTimer) {
        clearTimeout(reportDebounceTimer);
    }

    if (activeRequestController) {
        activeRequestController.abort();
    }
});
</script>

<template>
    <div class="min-h-screen bg-[#F8FAFC]" style="font-family: Poppins, sans-serif;">
        <div class="flex h-screen overflow-hidden">
            <AdminSidebar
                :nav-items="navItems"
                :nav-groups="navGroups"
                :active-module-id="activeModuleId"
                :mobile-open="mobileOpen"
                :sidebar-collapsed="sidebarCollapsed"
                @toggle-mobile="mobileOpen = !mobileOpen"
                @toggle-collapse="sidebarCollapsed = !sidebarCollapsed"
            />

            <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
                <AdminTopBar
                    :title="topbarTitle"
                    :date-label="topbarDate"
                    :show-top-search="showTopSearch"
                    :top-search-value="topSearchValue"
                    @open-mobile="mobileOpen = true"
                    @toggle-top-search="showTopSearch = $event"
                    @update:top-search-value="topSearchValue = $event"
                />

                <main class="flex-1 overflow-y-auto">
                    <div class="mx-auto w-full max-w-[1600px] p-5 lg:p-7">
                        <DashboardPage
                            v-if="activeModuleId === 'dashboard'"
                            :summary-cards="normalizedSummaryCards"
                            :revenue-series="normalizedRevenueSeries"
                            :active-revenue-period="activeRevenuePeriod"
                            :revenue-total="revenueTotal"
                            :booking-total="bookingTotal"
                            :queue-stats="queueStats"
                            :current-queue="currentQueue"
                            :waiting-queue="waitingQueue"
                            :recent-transactions="normalizedRecentTransactions"
                            :recent-activities="normalizedRecentActivities"
                            :panel-bookings-url="panelBookingsUrl"
                            :panel-transactions-url="panelTransactionsUrl"
                            :format-rupiah="formatRupiah"
                            @set-revenue-period="activeRevenuePeriod = $event"
                        />

                        <PackagesPage
                            v-else-if="activeModuleId === 'packages'"
                            :package-cards="packageCards"
                            :panel-base-url="panelBaseUrl"
                            :format-rupiah="formatRupiah"
                        />

                        <DesignsPage
                            v-else-if="activeModuleId === 'designs'"
                            :design-cards="designCards"
                            :panel-base-url="panelBaseUrl"
                        />

                        <UsersPage
                            v-else-if="activeModuleId === 'users'"
                            :user-rows="userRows"
                            :initials-from-name="initialsFromName"
                            :panel-base-url="panelBaseUrl"
                        />

                        <BookingsPage
                            v-else-if="activeModuleId === 'bookings'"
                            :search="search"
                            :filter-status="filterStatus"
                            :filter-tabs="filterTabs"
                            :panel-bookings-url="panelBookingsUrl"
                            :normalized-rows="normalizedRows"
                            :loading="loading"
                            :booking-result-caption="bookingResultCaption"
                            :can-go-prev="canGoPrev"
                            :can-go-next="canGoNext"
                            :pagination="pagination"
                            :resolve-booking-status="resolveBookingStatus"
                            @update:search="search = $event"
                            @set-filter-status="setFilterStatus"
                            @go-prev-page="goToPrevPage"
                            @go-next-page="goToNextPage"
                        />

                        <QueuePage
                            v-else-if="activeModuleId === 'queue'"
                            :queue-stats="queueStats"
                            :current-queue="currentQueue"
                            :waiting-queue="waitingQueue"
                            :queue-progress-style="queueProgressStyle"
                            :queue-remaining-text="queueRemainingText"
                            :queue-session-duration-text="queueSessionDurationText"
                            :resolve-queue-status="resolveQueueStatus"
                        />

                        <TransactionsPage
                            v-else-if="activeModuleId === 'transactions'"
                            :panel-transactions-url="panelTransactionsUrl"
                            :normalized-recent-transactions="normalizedRecentTransactions"
                            :transaction-today-total="transactionTodayTotal"
                            :resolve-method-style="resolveMethodStyle"
                            :resolve-transaction-status="resolveTransactionStatus"
                        />

                        <ReportsPage
                            v-else-if="activeModuleId === 'reports'"
                            :report-filters="reportFilters"
                            :report-error="reportError"
                            :report-loading="reportLoading"
                            :report-summary-cards="reportSummaryCards"
                            :report-daily-rows="reportDailyRows"
                            :report-daily-max-revenue="reportDailyMaxRevenue"
                            :report-status-rows="reportStatusRows"
                            :report-package-rows="reportPackageRows"
                            :report-cashier-rows="reportCashierRows"
                        />

                        <ActivityLogsPage
                            v-else-if="activeModuleId === 'activity-logs'"
                            :activity-search="activitySearch"
                            :activity-module-filter="activityModuleFilter"
                            :activity-module-options="activityModuleOptions"
                            :filtered-activity-rows="filteredActivityRows"
                            :resolve-activity-tone="resolveActivityTone"
                            @update:activity-search="activitySearch = $event"
                            @update:activity-module-filter="activityModuleFilter = $event"
                        />

                        <SettingsPage
                            v-else-if="activeModuleId === 'settings'"
                            :settings-tab="settingsTab"
                            :settings-tabs="settingsTabs"
                            @update:settings-tab="settingsTab = $event"
                        />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
