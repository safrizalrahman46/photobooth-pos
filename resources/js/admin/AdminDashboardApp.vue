<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
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
import AddOnsPage from './pages/AddOnsPage.vue';
import AppSettingsPage from './pages/AppSettingsPage.vue';
import BlackoutDatesPage from './pages/BlackoutDatesPage.vue';
import BranchesPage from './pages/BranchesPage.vue';
import DesignsPage from './pages/DesignsPage.vue';
import PackagesPage from './pages/PackagesPage.vue';
import PaymentsPage from './pages/PaymentsPage.vue';
import PrinterSettingsPage from './pages/PrinterSettingsPage.vue';
import QueuePage from './pages/QueuePage.vue';
import ReportsPage from './pages/ReportsPage.vue';
import SettingsPage from './pages/SettingsPage.vue';
import TimeSlotsPage from './pages/TimeSlotsPage.vue';
import TransactionsPage from './pages/TransactionsPage.vue';
import UsersPage from './pages/UsersPage.vue';
import { useAppSettingsModule } from './composables/useAppSettingsModule';
import { useBlackoutDatesModule } from './composables/useBlackoutDatesModule';
import { useBranchesModule } from './composables/useBranchesModule';
import { usePaymentsModule } from './composables/usePaymentsModule';
import { usePrinterSettingsModule } from './composables/usePrinterSettingsModule';
import { useTimeSlotsModule } from './composables/useTimeSlotsModule';

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
    queueBookingOptions: {
        type: Array,
        default: () => [],
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
    pendingBookingsCount: {
        type: Number,
        default: 0,
    },
    dataUrl: {
        type: String,
        default: '',
    },
    reportUrl: {
        type: String,
        default: '',
    },
    packagesDataUrl: {
        type: String,
        default: '',
    },
    packageStoreUrl: {
        type: String,
        default: '',
    },
    packageBaseUrl: {
        type: String,
        default: '/admin/packages',
    },
    addOnsDataUrl: {
        type: String,
        default: '',
    },
    addOnStoreUrl: {
        type: String,
        default: '',
    },
    addOnBaseUrl: {
        type: String,
        default: '/admin/add-ons',
    },
    designsDataUrl: {
        type: String,
        default: '',
    },
    designStoreUrl: {
        type: String,
        default: '',
    },
    designBaseUrl: {
        type: String,
        default: '/admin/designs',
    },
    usersDataUrl: {
        type: String,
        default: '',
    },
    userStoreUrl: {
        type: String,
        default: '',
    },
    queueDataUrl: {
        type: String,
        default: '',
    },
    queueCallNextUrl: {
        type: String,
        default: '',
    },
    queueCheckInUrl: {
        type: String,
        default: '',
    },
    queueWalkInUrl: {
        type: String,
        default: '',
    },
    queueBaseUrl: {
        type: String,
        default: '/admin/queue',
    },
    bookingStoreUrl: {
        type: String,
        default: '',
    },
    bookingBaseUrl: {
        type: String,
        default: '/admin/bookings',
    },
    bookingAvailabilityUrl: {
        type: String,
        default: '/booking/availability',
    },
    initialSettings: {
        type: Object,
        default: () => ({
            default_branch_id: null,
            branches: [],
        }),
    },
    defaultBranchId: {
        type: [Number, String, null],
        default: null,
    },
    settingsDataUrl: {
        type: String,
        default: '',
    },
    settingsDefaultBranchUrl: {
        type: String,
        default: '',
    },
    settingsBranchStoreUrl: {
        type: String,
        default: '',
    },
    settingsBranchBaseUrl: {
        type: String,
        default: '/admin/settings/branches',
    },
    branchesDataUrl: {
        type: String,
        default: '',
    },
    branchStoreUrl: {
        type: String,
        default: '',
    },
    branchBaseUrl: {
        type: String,
        default: '/admin/branches',
    },
    timeSlotsDataUrl: {
        type: String,
        default: '',
    },
    timeSlotStoreUrl: {
        type: String,
        default: '',
    },
    timeSlotBaseUrl: {
        type: String,
        default: '/admin/time-slots',
    },
    timeSlotGenerateUrl: {
        type: String,
        default: '',
    },
    timeSlotBulkBookableUrl: {
        type: String,
        default: '',
    },
    blackoutDatesDataUrl: {
        type: String,
        default: '',
    },
    blackoutDateStoreUrl: {
        type: String,
        default: '',
    },
    blackoutDateBaseUrl: {
        type: String,
        default: '/admin/blackout-dates',
    },
    paymentsDataUrl: {
        type: String,
        default: '',
    },
    paymentsStoreUrlBase: {
        type: String,
        default: '/admin/payments',
    },
    printerSettingsDataUrl: {
        type: String,
        default: '',
    },
    printerSettingStoreUrl: {
        type: String,
        default: '',
    },
    printerSettingBaseUrl: {
        type: String,
        default: '/admin/printer-settings',
    },
    appSettingsDataUrl: {
        type: String,
        default: '',
    },
    appSettingBaseUrl: {
        type: String,
        default: '/admin/app-settings',
    },
    initialPackages: {
        type: Array,
        default: () => [],
    },
    initialAddOns: {
        type: Array,
        default: () => [],
    },
    initialDesigns: {
        type: Array,
        default: () => [],
    },
    initialUsers: {
        type: Array,
        default: () => [],
    },
    initialBranches: {
        type: Array,
        default: () => [],
    },
    initialTimeSlots: {
        type: Array,
        default: () => [],
    },
    initialBlackoutDates: {
        type: Array,
        default: () => [],
    },
    initialPayments: {
        type: Array,
        default: () => [],
    },
    initialPaymentTransactionOptions: {
        type: Array,
        default: () => [],
    },
    initialPrinterSettings: {
        type: Array,
        default: () => [],
    },
    initialAppSettingsGroups: {
        type: Object,
        default: () => ({
            general: {},
            booking: {},
            payment: {},
            ui: {},
        }),
    },
    initialUserRoles: {
        type: Array,
        default: () => [],
    },
    initialBookingOptions: {
        type: Object,
        default: () => ({
            branches: [],
            packages: [],
            designs: [],
            payment_methods: [],
            add_ons: [],
        }),
    },
    brand: {
        type: Object,
        default: () => ({}),
    },
    currentUser: {
        type: Object,
        default: () => ({}),
    },
    uiConfig: {
        type: Object,
        default: () => ({}),
    },
    panelUrl: {
        type: String,
        default: '/admin',
    },
    logoutUrl: {
        type: String,
        default: '/admin/logout',
    },
});

const search = ref('');
const filterStatus = ref('all');
const bookingSortBy = ref('date_time');
const bookingSortDir = ref('desc');
const activeRevenuePeriod = ref('7d');
const loading = ref(false);
const mobileOpen = ref(false);
const sidebarCollapsed = ref(false);
const showTopSearch = ref(false);
const topSearchValue = ref('');
const routePath = ref(typeof window !== 'undefined' ? String(window.location.pathname || '/admin') : '/admin');

const rows = ref(Array.isArray(props.initialRows) ? props.initialRows : []);
const pagination = ref({
    current_page: Number(props.initialPagination?.current_page || 1),
    per_page: Number(props.initialPagination?.per_page || 15),
    total: Number(props.initialPagination?.total || rows.value.length || 0),
    last_page: Number(props.initialPagination?.last_page || 1),
});
const pendingBookingsCount = ref(Math.max(0, Number(props.pendingBookingsCount || 0)));

let debounceTimer = null;
let reportDebounceTimer = null;
let activeRequestController = null;
let queueRefreshInterval = null;

const filterTabs = computed(() => {
    const source = Array.isArray(props.uiConfig?.booking_filter_tabs)
        ? props.uiConfig.booking_filter_tabs
        : [];

    return source
        .map((item) => ({
            key: String(item?.key || '').trim(),
            label: String(item?.label || '').trim(),
        }))
        .filter((item) => item.key !== '' && item.label !== '');
});

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
    skipped: { bg: '#FFF7ED', color: '#EA580C' },
    cancelled: { bg: '#FEF2F2', color: '#DC2626' },
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

const formatRelativeDate = (value) => {
    if (!value) {
        return 'just now';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return 'just now';
    }

    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const dayMs = 24 * 60 * 60 * 1000;

    if (diffMs < 60 * 60 * 1000) {
        return 'today';
    }

    if (diffMs < dayMs) {
        return 'today';
    }

    const days = Math.floor(diffMs / dayMs);

    return days <= 1 ? '1 day ago' : `${days} days ago`;
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

const sidebarBrandName = computed(() => {
    const value = String(props.brand?.name || '').trim();

    return value || 'Dashboard';
});

const sidebarDashboardLabel = computed(() => {
    const value = String(props.brand?.dashboard_label || '').trim();

    return value || 'Dashboard';
});

const sidebarCurrentUser = computed(() => {
    const name = String(props.currentUser?.name || '').trim();
    const roleLabel = String(props.currentUser?.role_label || '').trim();
    const role = String(props.currentUser?.role || '').trim();

    return {
        name: name || 'User',
        initials: initialsFromName(name || 'User'),
        roleLabel: roleLabel || (role ? role.charAt(0).toUpperCase() + role.slice(1) : 'User'),
    };
});

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

const panelBookingsUrl = computed(() => `${panelBaseUrl.value}/bookings`);
const panelTransactionsUrl = computed(() => `${panelBaseUrl.value}/transactions`);

const navIconMap = {
    dashboard: LayoutDashboard,
    package: Package,
    palette: Palette,
    users: Users,
    calendar: Calendar,
    list: ListOrdered,
    receipt: Receipt,
    chart: BarChart3,
    activity: Activity,
    settings: Settings,
};

const resolveNavIcon = (iconKey) => {
    const key = String(iconKey || '').trim().toLowerCase();

    return navIconMap[key] || LayoutDashboard;
};

const resolveNavHref = (href) => {
    const text = String(href || '').trim();

    if (!text) {
        return panelBaseUrl.value;
    }

    if (text.startsWith('/') || text.startsWith('http://') || text.startsWith('https://')) {
        return text;
    }

    return `${panelBaseUrl.value}/${text.replace(/^\/+/, '')}`;
};

const navGroups = computed(() => {
    const source = Array.isArray(props.uiConfig?.nav_groups) ? props.uiConfig.nav_groups : [];

    return source
        .map((item) => ({
            key: String(item?.key || '').trim(),
            label: String(item?.label || '').trim(),
        }))
        .filter((item) => item.key !== '' && item.label !== '');
});

const queueStats = computed(() => {
    const stats = queueLiveState.value?.stats || {};

    return {
        in_queue: Number(stats.in_queue || 0),
        in_session: Number(stats.in_session || 0),
        waiting: Number(stats.waiting || 0),
        completed_today: Number(stats.completed_today || 0),
    };
});

const pendingBookingBlinkDuration = computed(() => {
    const pending = Math.max(0, Number(pendingBookingsCount.value || 0));

    if (pending <= 0) {
        return null;
    }

    const seconds = Math.max(0.45, 1.9 - (Math.min(pending, 20) * 0.07));

    return `${seconds.toFixed(2)}s`;
});

const navBadgeMap = computed(() => {
    const pending = Math.max(0, Number(pendingBookingsCount.value || 0));

    return {
        bookings: pending,
        queue: pending,
    };
});

const navBadgeFor = (itemId) => {
    const value = Number(navBadgeMap.value[itemId] || 0);

    if (!value) {
        return null;
    }

    return value > 99 ? '99+' : String(value);
};

const navItems = computed(() => {
    const source = Array.isArray(props.uiConfig?.nav_items) ? props.uiConfig.nav_items : [];
    const validGroupSet = new Set(navGroups.value.map((group) => group.key));
    const fallbackGroup = navGroups.value[0]?.key || 'overview';

    return source
        .map((item, index) => {
            const id = String(item?.id || '').trim();
            const label = String(item?.label || '').trim();
            const rawGroup = String(item?.group || '').trim();
            const group = validGroupSet.has(rawGroup) ? rawGroup : fallbackGroup;

            if (id === '' || label === '') {
                return null;
            }

            return {
                id,
                label,
                icon: resolveNavIcon(item?.icon),
                href: resolveNavHref(item?.href),
                group,
                badge: navBadgeFor(id),
                blink: id === 'bookings' && Number(navBadgeMap.value.bookings || 0) > 0,
                blink_duration: id === 'bookings' ? pendingBookingBlinkDuration.value : null,
                sort_order: Number(item?.sort_order || index),
            };
        })
        .filter(Boolean)
        .sort((a, b) => Number(a.sort_order || 0) - Number(b.sort_order || 0));
});

const currentPath = computed(() => String(routePath.value || '/admin'));

const normalizedCurrentPath = computed(() => {
    const path = String(currentPath.value || '/admin');

    if (path === '/') {
        return '/';
    }

    return path.endsWith('/') ? path.slice(0, -1) : path;
});

const activeModuleId = computed(() => {
    const path = normalizedCurrentPath.value;

    const fallback = navItems.value.find((item) => item.id === 'dashboard')?.id
        || navItems.value[0]?.id
        || 'dashboard';

    const candidates = navItems.value
        .map((item) => ({
            id: item.id,
            path: toPathname(item.href, ''),
        }))
        .filter((item) => item.path !== '')
        .sort((a, b) => b.path.length - a.path.length);

    const matched = candidates.find((item) => path === item.path || path.startsWith(`${item.path}/`));

    return matched?.id || fallback;
});

const syncRoutePathFromWindow = () => {
    if (typeof window === 'undefined') {
        routePath.value = '/admin';
        return;
    }

    routePath.value = String(window.location.pathname || '/admin');
};

const navigateFromSidebar = (href) => {
    const targetPath = toPathname(href, panelBaseUrl.value);

    if (targetPath === normalizedCurrentPath.value) {
        mobileOpen.value = false;
        return;
    }

    if (typeof window !== 'undefined') {
        const url = new URL(String(href || targetPath), window.location.origin);
        window.history.pushState({}, '', `${url.pathname}${url.search}${url.hash}`);
    }

    routePath.value = targetPath;
    mobileOpen.value = false;
};

const topbarMetaById = computed(() => {
    const source = props.uiConfig?.topbar_meta;

    if (!source || typeof source !== 'object') {
        return {};
    }

    const rows = {};

    Object.entries(source).forEach(([id, value]) => {
        const key = String(id || '').trim();
        const title = String(value?.title || '').trim();
        const subtitle = String(value?.subtitle || '').trim();

        if (key === '') {
            return;
        }

        rows[key] = { title, subtitle };
    });

    return rows;
});

const topbarTitle = computed(() => {
    const id = String(activeModuleId.value || 'dashboard');
    const title = String(topbarMetaById.value[id]?.title || '').trim();
    const navLabel = String(navItems.value.find((item) => item.id === id)?.label || '').trim();

    return title || navLabel || 'Dashboard';
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

const queueLiveState = ref(props.queueLive && typeof props.queueLive === 'object' ? props.queueLive : {});

const resolveNextQueueStatus = (status) => {
    const current = String(status || '').toLowerCase();

    if (current === 'waiting') {
        return 'called';
    }

    if (current === 'called') {
        return 'checked_in';
    }

    if (current === 'checked_in') {
        return 'in_session';
    }

    if (current === 'in_session') {
        return 'finished';
    }

    return null;
};

const resolvePreviousQueueStatus = (status) => {
    const current = String(status || '').toLowerCase();

    if (current === 'called') {
        return 'waiting';
    }

    if (current === 'checked_in') {
        return 'called';
    }

    if (current === 'in_session') {
        return 'checked_in';
    }

    return null;
};

const currentQueue = computed(() => {
    const value = queueLiveState.value?.current;

    if (!value || typeof value !== 'object') {
        return null;
    }

    return {
        ticket_id: value.ticket_id ? Number(value.ticket_id) : null,
        booking_id: value.booking_id ? Number(value.booking_id) : null,
        branch_id: value.branch_id ? Number(value.branch_id) : null,
        queue_date: String(value.queue_date || ''),
        source_type: String(value.source_type || ''),
        queue_code: String(value.queue_code || '-'),
        queue_number: Number(value.queue_number || 0),
        customer_name: String(value.customer_name || '-'),
        package_name: String(value.package_name || '-'),
        status: String(value.status || 'waiting'),
        status_label: String(value.status_label || value.status || 'Waiting'),
        progress_percentage: Number(value.progress_percentage || 0),
        remaining_seconds: Number(value.remaining_seconds || 0),
        session_duration_seconds: Number(value.session_duration_seconds || 0),
        can_complete: Boolean(value.can_complete),
        can_skip: Boolean(value.can_skip),
    };
});

const waitingQueue = computed(() => {
    const list = Array.isArray(queueLiveState.value?.waiting) ? queueLiveState.value.waiting : [];

    return list.slice(0, 5).map((item, index) => ({
        ticket_id: item.ticket_id ? Number(item.ticket_id) : null,
        booking_id: item.booking_id ? Number(item.booking_id) : null,
        branch_id: item.branch_id ? Number(item.branch_id) : null,
        queue_date: String(item.queue_date || ''),
        source_type: String(item.source_type || ''),
        queue_code: String(item.queue_code || '-'),
        queue_number: Number(item.queue_number || index + 1),
        customer_name: String(item.customer_name || '-'),
        package_name: String(item.package_name || '-'),
        status: String(item.status || 'waiting'),
        status_label: String(item.status_label || item.status || 'Waiting'),
        next_status: String(item.next_status || resolveNextQueueStatus(item.status) || ''),
        previous_status: String(item.previous_status || resolvePreviousQueueStatus(item.status) || ''),
        can_cancel: item.can_cancel !== false,
        added_at: String(item.added_at || '-'),
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
        record_id: Number(row.record_id || 0),
        id: String(row.id || '-'),
        booking_code: String(row.booking_code || row.id || '-'),
        branch_id: row.branch_id ? Number(row.branch_id) : null,
        branch_name: String(row.branch_name || '-'),
        package_id: row.package_id ? Number(row.package_id) : null,
        design_catalog_id: row.design_catalog_id ? Number(row.design_catalog_id) : null,
        name: String(row.name || '-'),
        customer_phone: String(row.customer_phone || ''),
        customer_email: String(row.customer_email || ''),
        pkg: String(row.pkg || '-'),
        design_name: String(row.design_name || '-'),
        date: String(row.date || '-'),
        time: String(row.time || '-'),
        booking_date_iso: String(row.booking_date_iso || ''),
        start_time: String(row.start_time || ''),
        status: String(row.status || 'pending'),
        status_raw: String(row.status_raw || 'pending'),
        payment: String(row.payment || '-'),
        payment_status: String(row.payment_status || 'unpaid'),
        amount: Number(row.amount || 0),
        amount_text: String(row.amount_text || formatRupiah(row.amount || 0)),
        total_amount: Number(row.total_amount || row.amount || 0),
        paid_amount: Number(row.paid_amount || 0),
        remaining_amount: Number(row.remaining_amount || 0),
        notes: String(row.notes || ''),
        payment_reference: String(row.payment_reference || ''),
        transfer_proof_url: String(row.transfer_proof_url || ''),
        transfer_proof_file_name: String(row.transfer_proof_file_name || ''),
        transfer_proof_uploaded_at: String(row.transfer_proof_uploaded_at || ''),
        transfer_proof_uploaded_at_text: String(row.transfer_proof_uploaded_at_text || ''),
        transaction_id: row.transaction_id ? Number(row.transaction_id) : null,
        can_confirm_booking: Boolean(row.can_confirm_booking),
        can_confirm_payment: Boolean(row.can_confirm_payment),
        add_ons: Array.isArray(row.add_ons)
            ? row.add_ons.map((item) => ({
                add_on_id: item.add_on_id ? Number(item.add_on_id) : null,
                label: String(item.label || '-'),
                qty: Number(item.qty || 0),
                line_total: Number(item.line_total || 0),
            }))
            : [],
        add_ons_count: Number(row.add_ons_count || 0),
        add_ons_total: Number(row.add_ons_total || 0),
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
    return (packages.value || []).map((item) => {
        const revenue = Number(item.this_month_revenue || 0);
        const addOns = Array.isArray(item.add_ons)
            ? item.add_ons.map((addOn) => ({
                id: Number(addOn?.id || 0),
                code: String(addOn?.code || ''),
                name: String(addOn?.name || ''),
                description: String(addOn?.description || ''),
                price: Number(addOn?.price || 0),
                max_qty: Math.max(1, Number(addOn?.max_qty || 1)),
                is_active: Boolean(addOn?.is_active),
                sort_order: Number(addOn?.sort_order || 0),
            }))
            : [];

        return {
            id: Number(item.id || 0),
            branch_id: item.branch_id ? Number(item.branch_id) : null,
            code: String(item.code || ''),
            name: String(item.name || '-'),
            description: String(item.description || ''),
            duration_minutes: Number(item.duration_minutes || 0),
            base_price: Number(item.base_price || 0),
            is_active: Boolean(item.is_active),
            sort_order: Number(item.sort_order || 0),
            bookings: Number(item.this_month_bookings || 0),
            total_bookings: Number(item.total_bookings || 0),
            pending: Number(item.pending_bookings || 0),
            completed: Number(item.completed_bookings || 0),
            add_ons_count: Number(item.add_ons_count || addOns.length || 0),
            add_ons: addOns,
            revenue,
            revenueText: formatRupiah(revenue),
        };
    });
});

const packageOptions = computed(() => {
    return packageCards.value.map((item) => ({
        id: Number(item.id || 0),
        name: String(item.name || '-'),
    }));
});

const designCards = computed(() => {
    return (designs.value || []).map((item, index) => ({
        id: Number(item.id || 0),
        package_id: item.package_id ? Number(item.package_id) : null,
        package_name: String(item.package_name || '-'),
        code: String(item.code || ''),
        name: String(item.name || '-'),
        theme: String(item.theme || ''),
        preview_url: String(item.preview_url || ''),
        is_active: Boolean(item.is_active),
        sort_order: Number(item.sort_order || 0),
        bookings: Number(item.this_month_bookings || item.total_bookings || 0),
        total_bookings: Number(item.total_bookings || 0),
        status: Boolean(item.is_active) ? 'active' : 'inactive',
        updated: formatRelativeDate(item.updated_at),
        tone: defaultCardPalette[index % defaultCardPalette.length],
    }));
});

const userRows = computed(() => {
    return (users.value || []).map((item) => ({
        id: Number(item.id || 0),
        name: String(item.name || '-'),
        email: String(item.email || ''),
        phone: String(item.phone || ''),
        role: String(item.role || 'Staff'),
        role_key: String(item.role_key || '').toLowerCase(),
        status: String(item.status || 'inactive'),
        is_active: Boolean(item.is_active),
        source: String(item.source || 'database'),
    }));
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

const settingsTabs = computed(() => {
    const source = Array.isArray(props.uiConfig?.settings_tabs)
        ? props.uiConfig.settings_tabs
        : [];

    return source
        .map((item) => ({
            id: String(item?.id || '').trim(),
            label: String(item?.label || '').trim(),
        }))
        .filter((item) => item.id !== '' && item.label !== '');
});
const settingsTab = ref('');

watch(
    settingsTabs,
    (tabs) => {
        const rows = Array.isArray(tabs) ? tabs : [];

        if (rows.some((tab) => tab.id === settingsTab.value)) {
            return;
        }

        settingsTab.value = rows[0]?.id || 'branch';
    },
    { immediate: true },
);

const normalizeSettings = (source) => {
    const settingsSource = source && typeof source === 'object' ? source : {};
    const branchesSource = Array.isArray(settingsSource.branches) ? settingsSource.branches : [];

    const branches = branchesSource
        .map((branch) => ({
            id: Number(branch?.id || 0),
            code: String(branch?.code || ''),
            name: String(branch?.name || '-'),
            timezone: String(branch?.timezone || 'Asia/Jakarta'),
            phone: String(branch?.phone || ''),
            address: String(branch?.address || ''),
        }))
        .filter((branch) => branch.id > 0);

    const defaultBranch = Number(settingsSource.default_branch_id || 0);

    return {
        default_branch_id: defaultBranch > 0 ? defaultBranch : null,
        branches,
    };
};

const reportFilters = ref({
    from: '',
    to: '',
    package_id: '',
    cashier_id: '',
});
const reportLoading = ref(false);
const reportError = ref('');
const reportData = ref(null);
const packages = ref(Array.isArray(props.initialPackages) ? props.initialPackages : []);
const addOns = ref(Array.isArray(props.initialAddOns) ? props.initialAddOns : []);
const designs = ref(Array.isArray(props.initialDesigns) ? props.initialDesigns : []);
const users = ref(Array.isArray(props.initialUsers) ? props.initialUsers : []);
const userRoles = ref(Array.isArray(props.initialUserRoles) ? props.initialUserRoles : []);
const bookingOptions = ref(props.initialBookingOptions && typeof props.initialBookingOptions === 'object'
    ? props.initialBookingOptions
    : {
        branches: [],
        packages: [],
        designs: [],
        payment_methods: [],
    });
const settings = ref(normalizeSettings(props.initialSettings));
const settingsLoading = ref(false);
const settingsSaving = ref(false);
const settingsError = ref('');
const settingsSuccess = ref('');
const packageLoading = ref(false);
const packageSaving = ref(false);
const packageError = ref('');
const deletingPackageId = ref(null);
const addOnLoading = ref(false);
const addOnSaving = ref(false);
const addOnError = ref('');
const deletingAddOnId = ref(null);
const designLoading = ref(false);
const designSaving = ref(false);
const designError = ref('');
const deletingDesignId = ref(null);
const userLoading = ref(false);
const userSaving = ref(false);
const userError = ref('');
const bookingSaving = ref(false);
const bookingError = ref('');
const deletingBookingId = ref(null);
const processingBookingId = ref(null);
const queueLoading = ref(false);
const queueActionLoading = ref(false);
const queueError = ref('');
const queueProcessingTicketId = ref(null);
const queueBookingOptions = ref(Array.isArray(props.queueBookingOptions) ? props.queueBookingOptions : []);

const queueBranchOptions = computed(() => {
    const branches = bookingOptions.value?.branches;

    return Array.isArray(branches) ? branches : [];
});

const settingsBranchOptions = computed(() => {
    if (settings.value.branches.length) {
        return settings.value.branches;
    }

    return queueBranchOptions.value
        .map((branch) => ({
            id: Number(branch?.id || 0),
            name: String(branch?.name || '-'),
        }))
        .filter((branch) => branch.id > 0);
});

const resolvedDefaultBranchId = computed(() => {
    const fromSettings = Number(settings.value.default_branch_id || 0);

    if (fromSettings > 0) {
        return fromSettings;
    }

    const fromProps = Number(props.defaultBranchId || 0);

    if (fromProps > 0) {
        return fromProps;
    }

    const fromOptions = Number(settingsBranchOptions.value[0]?.id || 0);

    return fromOptions > 0 ? fromOptions : null;
});

const defaultQueueBranchId = computed(() => {
    const fromCurrent = currentQueue.value?.branch_id;
    const fromWaiting = waitingQueue.value[0]?.branch_id;
    const fromOptions = queueBranchOptions.value[0]?.id;

    return Number(fromCurrent || fromWaiting || fromOptions || 0) || null;
});

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
    const addOnSummary = reportData.value?.add_on_summary || {};

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
        {
            label: 'Available Add-ons',
            value: String(Number(addOnSummary.available_count || 0)),
            helper: `${Number(addOnSummary.global_count || 0)} global • ${Number(addOnSummary.package_specific_count || 0)} package-specific`,
            tone: { accent: '#0284C7', light: '#F0F9FF', border: '#BAE6FD' },
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

const reportAddOnRows = computed(() => {
    return Array.isArray(reportData.value?.add_on_performance) ? reportData.value.add_on_performance : [];
});

const reportStatusRows = computed(() => {
    const rowsValue = reportData.value?.booking_summary?.statuses;

    return Array.isArray(rowsValue) ? rowsValue : [];
});

const resolveBookingStatus = (status) => {
    return bookingStatusMap[status] || {
        label: status || 'Unknown',
        bg: '#F8FAFC',
        color: '#64748B',
    };
};

const getCsrfToken = () => {
    if (typeof document === 'undefined') {
        return '';
    }

    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
};

const parseRequestError = async (response) => {
    try {
        const json = await response.json();
        const firstValidationMessage = Object.values(json?.errors || {})?.[0]?.[0];

        return String(firstValidationMessage || json?.message || `HTTP ${response.status}`);
    } catch {
        return `HTTP ${response.status}`;
    }
};

const submitLogout = () => {
    if (typeof document === 'undefined' || !props.logoutUrl) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = props.logoutUrl;

    const tokenInput = document.createElement('input');
    tokenInput.type = 'hidden';
    tokenInput.name = '_token';
    tokenInput.value = getCsrfToken();
    form.appendChild(tokenInput);

    document.body.appendChild(form);
    form.submit();
};

const applyPackagesPayload = (payload) => {
    const nextPackages = payload?.data?.packages;

    if (Array.isArray(nextPackages)) {
        packages.value = nextPackages;
    }
};

const applyAddOnsPayload = (payload) => {
    const nextAddOns = payload?.data?.add_ons;

    if (Array.isArray(nextAddOns)) {
        addOns.value = nextAddOns;
    }
};

const applyDesignsPayload = (payload) => {
    const nextDesigns = payload?.data?.designs;

    if (Array.isArray(nextDesigns)) {
        designs.value = nextDesigns;
    }
};

const applyUsersPayload = (payload) => {
    const nextUsers = payload?.data?.users;
    const nextRoles = payload?.data?.roles;

    if (Array.isArray(nextUsers)) {
        users.value = nextUsers;
    }

    if (Array.isArray(nextRoles)) {
        userRoles.value = nextRoles;
    }
};

const applyQueuePayload = (payload) => {
    const nextQueueLive = payload?.data?.queue_live;
    const nextQueueBookingOptions = payload?.data?.queue_booking_options;

    if (nextQueueLive && typeof nextQueueLive === 'object') {
        queueLiveState.value = nextQueueLive;
    }

    if (Array.isArray(nextQueueBookingOptions)) {
        queueBookingOptions.value = nextQueueBookingOptions;
    }
};

const applySettingsPayload = (payload) => {
    const nextSettings = payload?.data?.settings;

    if (!nextSettings || typeof nextSettings !== 'object') {
        return;
    }

    settings.value = normalizeSettings(nextSettings);

    bookingOptions.value = {
        ...bookingOptions.value,
        branches: settings.value.branches.map((branch) => ({
            id: Number(branch.id || 0),
            name: String(branch.name || '-'),
        })),
    };
};

const fetchSettingsData = async ({ silent = false } = {}) => {
    if (!props.settingsDataUrl) {
        return;
    }

    if (!silent) {
        settingsLoading.value = true;
    }

    settingsError.value = '';

    try {
        const response = await fetch(props.settingsDataUrl, {
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
        applySettingsPayload(payload);
    } catch (error) {
        if (!silent) {
            settingsError.value = error instanceof Error ? error.message : 'Failed to load settings.';
        }
    } finally {
        if (!silent) {
            settingsLoading.value = false;
        }
    }
};

const saveDefaultBranch = async (branchId) => {
    const id = Number(branchId || 0);

    if (!props.settingsDefaultBranchUrl || id <= 0) {
        settingsError.value = 'Please select a valid default branch.';
        return;
    }

    settingsSaving.value = true;
    settingsError.value = '';
    settingsSuccess.value = '';

    try {
        const response = await fetch(props.settingsDefaultBranchUrl, {
            method: 'PUT',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({ branch_id: id }),
        });

        if (!response.ok) {
            throw new Error(await parseRequestError(response));
        }

        const payload = await response.json();
        applySettingsPayload(payload);
        settingsSuccess.value = 'Default branch updated.';
    } catch (error) {
        settingsError.value = error instanceof Error ? error.message : 'Failed to save settings.';
        throw error;
    } finally {
        settingsSaving.value = false;
    }
};

const createBranchSetting = async (payload) => {
    if (!props.settingsBranchStoreUrl) {
        return;
    }

    settingsSaving.value = true;
    settingsError.value = '';
    settingsSuccess.value = '';

    try {
        const response = await fetch(props.settingsBranchStoreUrl, {
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
        applySettingsPayload(result);
        settingsSuccess.value = 'Branch created.';
    } catch (error) {
        settingsError.value = error instanceof Error ? error.message : 'Failed to create branch.';
        throw error;
    } finally {
        settingsSaving.value = false;
    }
};

const updateBranchSetting = async ({ id, payload }) => {
    const branchId = Number(id || 0);

    if (!branchId || !props.settingsBranchBaseUrl) {
        return;
    }

    settingsSaving.value = true;
    settingsError.value = '';
    settingsSuccess.value = '';

    try {
        const response = await fetch(`${props.settingsBranchBaseUrl}/${branchId}`, {
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
        applySettingsPayload(result);
        settingsSuccess.value = 'Branch updated.';
    } catch (error) {
        settingsError.value = error instanceof Error ? error.message : 'Failed to update branch.';
        throw error;
    } finally {
        settingsSaving.value = false;
    }
};

const removeBranchSetting = async (id) => {
    const branchId = Number(id || 0);

    if (!branchId || !props.settingsBranchBaseUrl) {
        return;
    }

    settingsSaving.value = true;
    settingsError.value = '';
    settingsSuccess.value = '';

    try {
        const response = await fetch(`${props.settingsBranchBaseUrl}/${branchId}`, {
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
        applySettingsPayload(result);
        settingsSuccess.value = 'Branch removed from available list.';
    } catch (error) {
        settingsError.value = error instanceof Error ? error.message : 'Failed to remove branch.';
        throw error;
    } finally {
        settingsSaving.value = false;
    }
};

const fetchQueueData = async ({ silent = false } = {}) => {
    if (!props.queueDataUrl) {
        return;
    }

    if (!silent) {
        queueLoading.value = true;
    }

    queueError.value = '';

    try {
        const response = await fetch(props.queueDataUrl, {
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
        applyQueuePayload(payload);
    } catch (error) {
        if (!silent) {
            queueError.value = error instanceof Error ? error.message : 'Failed to load queue data.';
        }
    } finally {
        if (!silent) {
            queueLoading.value = false;
        }
    }
};

const startQueueAutoRefresh = () => {
    if (queueRefreshInterval) {
        return;
    }

    queueRefreshInterval = setInterval(() => {
        if (activeModuleId.value !== 'queue') {
            return;
        }

        fetchQueueData({ silent: true });
    }, 15000);
};

const stopQueueAutoRefresh = () => {
    if (!queueRefreshInterval) {
        return;
    }

    clearInterval(queueRefreshInterval);
    queueRefreshInterval = null;
};

const fetchPackagesData = async () => {
    if (!props.packagesDataUrl) {
        return;
    }

    packageLoading.value = true;
    packageError.value = '';

    try {
        const response = await fetch(props.packagesDataUrl, {
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
        applyPackagesPayload(payload);
    } catch (error) {
        packageError.value = error instanceof Error ? error.message : 'Failed to load packages.';
    } finally {
        packageLoading.value = false;
    }
};

const createPackage = async (formPayload) => {
    if (!props.packageStoreUrl) {
        return;
    }

    packageSaving.value = true;
    packageError.value = '';

    try {
        const response = await fetch(props.packageStoreUrl, {
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
        applyPackagesPayload(payload);
    } catch (error) {
        packageError.value = error instanceof Error ? error.message : 'Failed to create package.';
        throw error;
    } finally {
        packageSaving.value = false;
    }
};

const updatePackage = async ({ id, payload }) => {
    if (!id || !props.packageBaseUrl) {
        return;
    }

    packageSaving.value = true;
    packageError.value = '';

    try {
        const response = await fetch(`${props.packageBaseUrl}/${id}`, {
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
        applyPackagesPayload(result);
    } catch (error) {
        packageError.value = error instanceof Error ? error.message : 'Failed to update package.';
        throw error;
    } finally {
        packageSaving.value = false;
    }
};

const deletePackage = async (id) => {
    if (!id || !props.packageBaseUrl) {
        return;
    }

    deletingPackageId.value = Number(id);
    packageError.value = '';

    try {
        const response = await fetch(`${props.packageBaseUrl}/${id}`, {
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

        const payload = await response.json();
        applyPackagesPayload(payload);
    } catch (error) {
        packageError.value = error instanceof Error ? error.message : 'Failed to delete package.';
        throw error;
    } finally {
        deletingPackageId.value = null;
    }
};

const fetchAddOnsData = async () => {
    if (!props.addOnsDataUrl) {
        return;
    }

    addOnLoading.value = true;
    addOnError.value = '';

    try {
        const response = await fetch(props.addOnsDataUrl, {
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
        applyAddOnsPayload(payload);
    } catch (error) {
        addOnError.value = error instanceof Error ? error.message : 'Failed to load add-ons.';
    } finally {
        addOnLoading.value = false;
    }
};

const createAddOn = async (formPayload) => {
    if (!props.addOnStoreUrl) {
        return;
    }

    addOnSaving.value = true;
    addOnError.value = '';

    try {
        const response = await fetch(props.addOnStoreUrl, {
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
        applyAddOnsPayload(payload);
    } catch (error) {
        addOnError.value = error instanceof Error ? error.message : 'Failed to create add-on.';
        throw error;
    } finally {
        addOnSaving.value = false;
    }
};

const updateAddOn = async ({ id, payload }) => {
    if (!id || !props.addOnBaseUrl) {
        return;
    }

    addOnSaving.value = true;
    addOnError.value = '';

    try {
        const response = await fetch(`${props.addOnBaseUrl}/${id}`, {
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
        applyAddOnsPayload(result);
    } catch (error) {
        addOnError.value = error instanceof Error ? error.message : 'Failed to update add-on.';
        throw error;
    } finally {
        addOnSaving.value = false;
    }
};

const deleteAddOn = async (id) => {
    if (!id || !props.addOnBaseUrl) {
        return;
    }

    deletingAddOnId.value = Number(id);
    addOnError.value = '';

    try {
        const response = await fetch(`${props.addOnBaseUrl}/${id}`, {
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

        const payload = await response.json();
        applyAddOnsPayload(payload);
    } catch (error) {
        addOnError.value = error instanceof Error ? error.message : 'Failed to delete add-on.';
        throw error;
    } finally {
        deletingAddOnId.value = null;
    }
};

const addOnRows = computed(() => {
    return (addOns.value || []).map((item) => ({
        id: Number(item.id || 0),
        package_id: item.package_id ? Number(item.package_id) : null,
        package_name: String(item.package_name || 'Global'),
        code: String(item.code || ''),
        name: String(item.name || ''),
        description: String(item.description || ''),
        price: Number(item.price || 0),
        price_text: String(item.price_text || formatRupiah(Number(item.price || 0))),
        max_qty: Math.max(1, Number(item.max_qty || 1)),
        is_physical: Boolean(item.is_physical),
        type_label: String(item.type_label || (item.is_physical ? 'Physical' : 'Non-physical')),
        is_active: Boolean(item.is_active),
        sort_order: Number(item.sort_order || 0),
        updated_at: item.updated_at || null,
    }));
});

const fetchDesignsData = async () => {
    if (!props.designsDataUrl) {
        return;
    }

    designLoading.value = true;
    designError.value = '';

    try {
        const response = await fetch(props.designsDataUrl, {
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
        applyDesignsPayload(payload);
    } catch (error) {
        designError.value = error instanceof Error ? error.message : 'Failed to load designs.';
    } finally {
        designLoading.value = false;
    }
};

const createDesign = async (formPayload) => {
    if (!props.designStoreUrl) {
        return;
    }

    designSaving.value = true;
    designError.value = '';

    try {
        const response = await fetch(props.designStoreUrl, {
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
        applyDesignsPayload(payload);
    } catch (error) {
        designError.value = error instanceof Error ? error.message : 'Failed to create design.';
        throw error;
    } finally {
        designSaving.value = false;
    }
};

const updateDesign = async ({ id, payload }) => {
    if (!id || !props.designBaseUrl) {
        return;
    }

    designSaving.value = true;
    designError.value = '';

    try {
        const response = await fetch(`${props.designBaseUrl}/${id}`, {
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
        applyDesignsPayload(result);
    } catch (error) {
        designError.value = error instanceof Error ? error.message : 'Failed to update design.';
        throw error;
    } finally {
        designSaving.value = false;
    }
};

const deleteDesign = async (id) => {
    if (!id || !props.designBaseUrl) {
        return;
    }

    deletingDesignId.value = Number(id);
    designError.value = '';

    try {
        const response = await fetch(`${props.designBaseUrl}/${id}`, {
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

        const payload = await response.json();
        applyDesignsPayload(payload);
    } catch (error) {
        designError.value = error instanceof Error ? error.message : 'Failed to delete design.';
        throw error;
    } finally {
        deletingDesignId.value = null;
    }
};

const fetchUsersData = async () => {
    if (!props.usersDataUrl) {
        return;
    }

    userLoading.value = true;
    userError.value = '';

    try {
        const response = await fetch(props.usersDataUrl, {
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
        applyUsersPayload(payload);
    } catch (error) {
        userError.value = error instanceof Error ? error.message : 'Failed to load users.';
    } finally {
        userLoading.value = false;
    }
};

const createUser = async (formPayload) => {
    if (!props.userStoreUrl) {
        return;
    }

    userSaving.value = true;
    userError.value = '';

    try {
        const response = await fetch(props.userStoreUrl, {
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
        applyUsersPayload(payload);
    } catch (error) {
        userError.value = error instanceof Error ? error.message : 'Failed to create user.';
        throw error;
    } finally {
        userSaving.value = false;
    }
};

const {
    branchRows,
    branchLoading,
    branchSaving,
    branchError,
    deletingBranchId,
    fetchBranchesData,
    createBranch,
    updateBranch,
    deleteBranch,
} = useBranchesModule({
    props,
    bookingOptions,
    parseRequestError,
    getCsrfToken,
});

const {
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
} = useTimeSlotsModule({
    props,
    parseRequestError,
    getCsrfToken,
});

const {
    blackoutDateRows,
    blackoutDateLoading,
    blackoutDateSaving,
    blackoutDateError,
    deletingBlackoutDateId,
    fetchBlackoutDatesData,
    createBlackoutDate,
    updateBlackoutDate,
    deleteBlackoutDate,
} = useBlackoutDatesModule({
    props,
    parseRequestError,
    getCsrfToken,
});

const {
    paymentRows,
    paymentTransactionRows,
    paymentLoading,
    paymentSaving,
    paymentError,
    fetchPaymentsData,
    createPayment,
} = usePaymentsModule({
    props,
    parseRequestError,
    getCsrfToken,
    formatRupiah,
});

const {
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
} = usePrinterSettingsModule({
    props,
    parseRequestError,
    getCsrfToken,
});

const {
    appSettingsGroups,
    appSettingsLoading,
    appSettingsSaving,
    appSettingsError,
    appSettingsSuccess,
    fetchAppSettingsData,
    updateAppSetting,
} = useAppSettingsModule({
    props,
    parseRequestError,
    getCsrfToken,
});

const branchOptionsForManagement = computed(() => {
    const options = branchRows.value
        .filter((row) => row.id > 0)
        .map((row) => ({
            id: row.id,
            name: row.name,
        }));

    if (options.length) {
        return options;
    }

    return settingsBranchOptions.value.map((branch) => ({
        id: Number(branch.id || 0),
        name: String(branch.name || '-'),
    })).filter((branch) => branch.id > 0);
});

const refreshBookings = async (page = Number(pagination.value.current_page || 1)) => {
    await fetchRows(page);
};

const createBooking = async (formPayload) => {
    if (!props.bookingStoreUrl) {
        return;
    }

    bookingSaving.value = true;
    bookingError.value = '';

    try {
        const response = await fetch(props.bookingStoreUrl, {
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

        await refreshBookings(1);
    } catch (error) {
        bookingError.value = error instanceof Error ? error.message : 'Failed to create booking.';
        throw error;
    } finally {
        bookingSaving.value = false;
    }
};

const updateBooking = async ({ id, payload }) => {
    if (!id || !props.bookingBaseUrl) {
        return;
    }

    bookingSaving.value = true;
    bookingError.value = '';

    try {
        const response = await fetch(`${props.bookingBaseUrl}/${id}`, {
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

        await refreshBookings(Number(pagination.value.current_page || 1));
    } catch (error) {
        bookingError.value = error instanceof Error ? error.message : 'Failed to update booking.';
        throw error;
    } finally {
        bookingSaving.value = false;
    }
};

const deleteBooking = async (id) => {
    if (!id || !props.bookingBaseUrl) {
        return;
    }

    deletingBookingId.value = Number(id);
    bookingError.value = '';

    try {
        const response = await fetch(`${props.bookingBaseUrl}/${id}`, {
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

        await refreshBookings(Number(pagination.value.current_page || 1));
    } catch (error) {
        bookingError.value = error instanceof Error ? error.message : 'Failed to delete booking.';
        throw error;
    } finally {
        deletingBookingId.value = null;
    }
};

const confirmBooking = async ({ id, reason = '' }) => {
    if (!id || !props.bookingBaseUrl) {
        return;
    }

    processingBookingId.value = Number(id);
    bookingError.value = '';

    try {
        const response = await fetch(`${props.bookingBaseUrl}/${id}/confirm`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({ reason }),
        });

        if (!response.ok) {
            throw new Error(await parseRequestError(response));
        }

        await refreshBookings(Number(pagination.value.current_page || 1));
        await fetchQueueData({ silent: true });
    } catch (error) {
        bookingError.value = error instanceof Error ? error.message : 'Failed to confirm booking.';
        throw error;
    } finally {
        processingBookingId.value = null;
    }
};

const confirmBookingPayment = async ({ id, payload }) => {
    if (!id || !props.bookingBaseUrl) {
        return;
    }

    processingBookingId.value = Number(id);
    bookingError.value = '';

    try {
        const response = await fetch(`${props.bookingBaseUrl}/${id}/confirm-payment`, {
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

        await refreshBookings(Number(pagination.value.current_page || 1));
    } catch (error) {
        bookingError.value = error instanceof Error ? error.message : 'Failed to confirm payment.';
        throw error;
    } finally {
        processingBookingId.value = null;
    }
};

const callNextQueue = async ({ branch_id, queue_date } = {}) => {
    const branchId = Number(branch_id || defaultQueueBranchId.value || 0);

    if (!branchId || !props.queueCallNextUrl) {
        return;
    }

    queueActionLoading.value = true;
    queueError.value = '';
    queueProcessingTicketId.value = null;

    try {
        const response = await fetch(props.queueCallNextUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                branch_id: branchId,
                queue_date: queue_date || new Date().toISOString().slice(0, 10),
            }),
        });

        if (!response.ok) {
            throw new Error(await parseRequestError(response));
        }

        const payload = await response.json();
        applyQueuePayload(payload);
    } catch (error) {
        queueError.value = error instanceof Error ? error.message : 'Failed to call next queue.';
        throw error;
    } finally {
        queueActionLoading.value = false;
        queueProcessingTicketId.value = null;
    }
};

const transitionQueueTicket = async ({ ticketId, status }) => {
    const id = Number(ticketId || 0);
    const nextStatus = String(status || '').trim();

    if (!id || !nextStatus || !props.queueBaseUrl) {
        return;
    }

    queueActionLoading.value = true;
    queueError.value = '';
    queueProcessingTicketId.value = id;

    try {
        const response = await fetch(`${props.queueBaseUrl}/${id}/status`, {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({ status: nextStatus }),
        });

        if (!response.ok) {
            throw new Error(await parseRequestError(response));
        }

        const payload = await response.json();
        applyQueuePayload(payload);
    } catch (error) {
        queueError.value = error instanceof Error ? error.message : 'Failed to update queue status.';
        throw error;
    } finally {
        queueActionLoading.value = false;
        queueProcessingTicketId.value = null;
    }
};

const addQueueBooking = async (formPayload) => {
    const bookingId = Number(formPayload?.booking_id || 0);

    if (!props.queueCheckInUrl || !bookingId) {
        return;
    }

    queueActionLoading.value = true;
    queueError.value = '';
    queueProcessingTicketId.value = null;

    try {
        const response = await fetch(props.queueCheckInUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({ booking_id: bookingId }),
        });

        if (!response.ok) {
            throw new Error(await parseRequestError(response));
        }

        const payload = await response.json();
        applyQueuePayload(payload);
    } catch (error) {
        queueError.value = error instanceof Error ? error.message : 'Failed to add booking into queue.';
        throw error;
    } finally {
        queueActionLoading.value = false;
        queueProcessingTicketId.value = null;
    }
};

const addQueueWalkIn = async (formPayload) => {
    if (!props.queueWalkInUrl) {
        return;
    }

    queueActionLoading.value = true;
    queueError.value = '';
    queueProcessingTicketId.value = null;

    try {
        const response = await fetch(props.queueWalkInUrl, {
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
        applyQueuePayload(payload);
    } catch (error) {
        queueError.value = error instanceof Error ? error.message : 'Failed to add queue ticket.';
        throw error;
    } finally {
        queueActionLoading.value = false;
        queueProcessingTicketId.value = null;
    }
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
        params.set('sort_by', String(bookingSortBy.value || 'date_time'));
        params.set('sort_dir', String(bookingSortDir.value || 'desc'));

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
        pendingBookingsCount.value = Math.max(0, Number(data.pending_bookings_count || 0));
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

const setBookingSort = (sortBy) => {
    const key = String(sortBy || '').trim();
    const allowedSorts = ['booking_code', 'customer', 'package', 'date_time', 'amount', 'payment', 'status'];

    if (!allowedSorts.includes(key)) {
        return;
    }

    if (bookingSortBy.value === key) {
        bookingSortDir.value = bookingSortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        bookingSortBy.value = key;
        bookingSortDir.value = key === 'date_time' ? 'desc' : 'asc';
    }

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

watch(activeModuleId, (nextValue) => {
    if (nextValue !== 'reports') {
        return;
    }

    if (!reportFilters.value.from || !reportFilters.value.to) {
        setDefaultReportRange();
    }

    fetchReportSummary();
}, { immediate: true });

watch(activeModuleId, (nextValue) => {
    if (nextValue !== 'packages' && nextValue !== 'designs' && nextValue !== 'add-ons') {
        return;
    }

    if (!packages.value.length && !packageLoading.value) {
        fetchPackagesData();
    }
}, { immediate: true });

watch(activeModuleId, (nextValue) => {
    if (nextValue !== 'add-ons') {
        return;
    }

    if (!addOnLoading.value) {
        fetchAddOnsData();
    }
}, { immediate: true });

watch(activeModuleId, (nextValue) => {
    if (nextValue !== 'designs') {
        return;
    }

    if (!designs.value.length && !designLoading.value) {
        fetchDesignsData();
    }
}, { immediate: true });

watch(activeModuleId, (nextValue) => {
    if (nextValue !== 'users') {
        return;
    }

    if (!users.value.length && !userLoading.value) {
        fetchUsersData();
    }
}, { immediate: true });

watch(activeModuleId, (nextValue) => {
    if (nextValue !== 'settings') {
        return;
    }

    if (!settingsLoading.value) {
        fetchSettingsData();
    }
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

watch(activeModuleId, (nextValue) => {
    if (nextValue !== 'branches') {
        return;
    }

    if (!branchRows.value.length && !branchLoading.value) {
        fetchBranchesData();
    }
}, { immediate: true });

watch(activeModuleId, (nextValue) => {
    if (nextValue !== 'time-slots') {
        return;
    }

    if (!timeSlotRows.value.length && !timeSlotLoading.value) {
        fetchTimeSlotsData();
    }
}, { immediate: true });

watch(activeModuleId, (nextValue) => {
    if (nextValue !== 'blackout-dates') {
        return;
    }

    if (!blackoutDateRows.value.length && !blackoutDateLoading.value) {
        fetchBlackoutDatesData();
    }
}, { immediate: true });

watch(activeModuleId, (nextValue) => {
    if (nextValue !== 'payments') {
        return;
    }

    if (!paymentRows.value.length && !paymentLoading.value) {
        fetchPaymentsData();
    }
}, { immediate: true });

watch(activeModuleId, (nextValue) => {
    if (nextValue !== 'printer-settings') {
        return;
    }

    if (!printerSettingRows.value.length && !printerSettingLoading.value) {
        fetchPrinterSettingsData();
    }
}, { immediate: true });

watch(activeModuleId, (nextValue) => {
    if (nextValue !== 'app-settings') {
        return;
    }

    if (!appSettingsLoading.value) {
        fetchAppSettingsData();
    }
}, { immediate: true });

watch(activeModuleId, (nextValue) => {
    if (nextValue !== 'queue') {
        stopQueueAutoRefresh();
        return;
    }

    fetchQueueData();
    startQueueAutoRefresh();
}, { immediate: true });

onMounted(() => {
    syncRoutePathFromWindow();

    if (typeof window !== 'undefined') {
        window.addEventListener('popstate', syncRoutePathFromWindow);
    }
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

    if (typeof window !== 'undefined') {
        window.removeEventListener('popstate', syncRoutePathFromWindow);
    }

    stopQueueAutoRefresh();
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
                :brand-name="sidebarBrandName"
                :dashboard-label="sidebarDashboardLabel"
                :current-user="sidebarCurrentUser"
                @toggle-mobile="mobileOpen = !mobileOpen"
                @toggle-collapse="sidebarCollapsed = !sidebarCollapsed"
                @navigate="navigateFromSidebar"
                @logout="submitLogout"
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
                            :branch-options="branchOptionsForManagement"
                            :panel-base-url="panelBaseUrl"
                            :format-rupiah="formatRupiah"
                            :loading="packageLoading"
                            :saving="packageSaving"
                            :deleting-package-id="deletingPackageId"
                            :error-message="packageError"
                            @refresh-packages="fetchPackagesData"
                            @create-package="createPackage"
                            @update-package="updatePackage"
                            @delete-package="deletePackage"
                        />

                        <AddOnsPage
                            v-else-if="activeModuleId === 'add-ons'"
                            :add-on-rows="addOnRows"
                            :package-options="packageOptions"
                            :format-rupiah="formatRupiah"
                            :loading="addOnLoading"
                            :saving="addOnSaving"
                            :deleting-add-on-id="deletingAddOnId"
                            :error-message="addOnError"
                            @refresh-add-ons="fetchAddOnsData"
                            @create-add-on="createAddOn"
                            @update-add-on="updateAddOn"
                            @delete-add-on="deleteAddOn"
                        />

                        <DesignsPage
                            v-else-if="activeModuleId === 'designs'"
                            :design-cards="designCards"
                            :panel-base-url="panelBaseUrl"
                            :package-options="packageOptions"
                            :loading="designLoading"
                            :saving="designSaving"
                            :deleting-design-id="deletingDesignId"
                            :error-message="designError"
                            @refresh-designs="fetchDesignsData"
                            @create-design="createDesign"
                            @update-design="updateDesign"
                            @delete-design="deleteDesign"
                        />

                        <BranchesPage
                            v-else-if="activeModuleId === 'branches'"
                            :branch-rows="branchRows"
                            :loading="branchLoading"
                            :saving="branchSaving"
                            :deleting-branch-id="deletingBranchId"
                            :error-message="branchError"
                            @refresh-branches="fetchBranchesData"
                            @create-branch="createBranch"
                            @update-branch="updateBranch"
                            @delete-branch="deleteBranch"
                        />

                        <TimeSlotsPage
                            v-else-if="activeModuleId === 'time-slots'"
                            :time-slot-rows="timeSlotRows"
                            :branch-options="branchOptionsForManagement"
                            :loading="timeSlotLoading"
                            :saving="timeSlotSaving"
                            :deleting-time-slot-id="deletingTimeSlotId"
                            :error-message="timeSlotError"
                            @refresh-time-slots="fetchTimeSlotsData"
                            @create-time-slot="createTimeSlot"
                            @update-time-slot="updateTimeSlot"
                            @delete-time-slot="deleteTimeSlot"
                            @generate-time-slots="generateTimeSlots"
                            @bulk-bookable="bulkBookableTimeSlots"
                        />

                        <BlackoutDatesPage
                            v-else-if="activeModuleId === 'blackout-dates'"
                            :blackout-date-rows="blackoutDateRows"
                            :branch-options="branchOptionsForManagement"
                            :loading="blackoutDateLoading"
                            :saving="blackoutDateSaving"
                            :deleting-blackout-date-id="deletingBlackoutDateId"
                            :error-message="blackoutDateError"
                            @refresh-blackout-dates="fetchBlackoutDatesData"
                            @create-blackout-date="createBlackoutDate"
                            @update-blackout-date="updateBlackoutDate"
                            @delete-blackout-date="deleteBlackoutDate"
                        />

                        <UsersPage
                            v-else-if="activeModuleId === 'users'"
                            :user-rows="userRows"
                            :initials-from-name="initialsFromName"
                            :panel-base-url="panelBaseUrl"
                            :role-options="userRoles"
                            :loading="userLoading"
                            :saving="userSaving"
                            :error-message="userError"
                            @refresh-users="fetchUsersData"
                            @create-user="createUser"
                        />

                        <BookingsPage
                            v-else-if="activeModuleId === 'bookings'"
                            :search="search"
                            :filter-status="filterStatus"
                            :filter-tabs="filterTabs"
                            :sort-by="bookingSortBy"
                            :sort-dir="bookingSortDir"
                            :panel-bookings-url="panelBookingsUrl"
                            :normalized-rows="normalizedRows"
                            :loading="loading"
                            :saving="bookingSaving"
                            :booking-error="bookingError"
                            :booking-options="bookingOptions"
                            :default-branch-id="resolvedDefaultBranchId"
                            :availability-url="bookingAvailabilityUrl"
                            :deleting-booking-id="deletingBookingId"
                            :processing-booking-id="processingBookingId"
                            :booking-result-caption="bookingResultCaption"
                            :can-go-prev="canGoPrev"
                            :can-go-next="canGoNext"
                            :pagination="pagination"
                            :resolve-booking-status="resolveBookingStatus"
                            @update:search="search = $event"
                            @set-filter-status="setFilterStatus"
                            @set-sort="setBookingSort"
                            @refresh-bookings="refreshBookings()"
                            @create-booking="createBooking"
                            @update-booking="updateBooking"
                            @delete-booking="deleteBooking"
                            @confirm-booking="confirmBooking"
                            @confirm-booking-payment="confirmBookingPayment"
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
                            :queue-loading="queueLoading"
                            :queue-action-loading="queueActionLoading"
                            :queue-processing-ticket-id="queueProcessingTicketId"
                            :queue-error="queueError"
                            :branch-options="queueBranchOptions"
                            :booking-options="queueBookingOptions"
                            :default-branch-id="defaultQueueBranchId"
                            @refresh-queue="fetchQueueData()"
                            @call-next="callNextQueue"
                            @transition-ticket="transitionQueueTicket"
                            @add-booking="addQueueBooking"
                            @add-walk-in="addQueueWalkIn"
                        />

                        <TransactionsPage
                            v-else-if="activeModuleId === 'transactions'"
                            :panel-transactions-url="panelTransactionsUrl"
                            :normalized-recent-transactions="normalizedRecentTransactions"
                            :transaction-today-total="transactionTodayTotal"
                            :resolve-method-style="resolveMethodStyle"
                            :resolve-transaction-status="resolveTransactionStatus"
                        />

                        <PaymentsPage
                            v-else-if="activeModuleId === 'payments'"
                            :payment-rows="paymentRows"
                            :transaction-options="paymentTransactionRows"
                            :loading="paymentLoading"
                            :saving="paymentSaving"
                            :error-message="paymentError"
                            @refresh-payments="fetchPaymentsData"
                            @create-payment="createPayment"
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
                            :report-add-on-rows="reportAddOnRows"
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

                        <PrinterSettingsPage
                            v-else-if="activeModuleId === 'printer-settings'"
                            :printer-setting-rows="printerSettingRows"
                            :branch-options="branchOptionsForManagement"
                            :loading="printerSettingLoading"
                            :saving="printerSettingSaving"
                            :deleting-printer-setting-id="deletingPrinterSettingId"
                            :error-message="printerSettingError"
                            @refresh-printer-settings="fetchPrinterSettingsData"
                            @create-printer-setting="createPrinterSetting"
                            @update-printer-setting="updatePrinterSetting"
                            @delete-printer-setting="deletePrinterSetting"
                            @set-default-printer-setting="setDefaultPrinterSetting"
                        />

                        <AppSettingsPage
                            v-else-if="activeModuleId === 'app-settings'"
                            :groups="appSettingsGroups"
                            :loading="appSettingsLoading"
                            :saving="appSettingsSaving"
                            :error-message="appSettingsError"
                            :success-message="appSettingsSuccess"
                            @refresh-app-settings="fetchAppSettingsData"
                            @update-app-setting="updateAppSetting"
                        />

                        <SettingsPage
                            v-else-if="activeModuleId === 'settings'"
                            :settings-tab="settingsTab"
                            :settings-tabs="settingsTabs"
                            :settings="settings"
                            :branch-options="settingsBranchOptions"
                            :default-branch-id="resolvedDefaultBranchId"
                            :loading="settingsLoading"
                            :saving="settingsSaving"
                            :error-message="settingsError"
                            :success-message="settingsSuccess"
                            @update:settings-tab="settingsTab = $event"
                            @refresh-settings="fetchSettingsData()"
                            @save-default-branch="saveDefaultBranch"
                            @create-branch="createBranchSetting"
                            @update-branch="updateBranchSetting"
                            @remove-branch="removeBranchSetting"
                        />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
