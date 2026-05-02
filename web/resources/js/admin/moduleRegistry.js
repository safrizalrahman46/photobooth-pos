export const buildAdminModuleRegistry = (ctx) => ({
    dashboard: {
        onEnter: () => {
            if (!ctx.reportFilters.value.from || !ctx.reportFilters.value.to) {
                ctx.setDefaultReportRange();
            }

            ctx.fetchReportSummary();
        },
    },
    reports: {
        onEnter: () => {
            if (!ctx.reportFilters.value.from || !ctx.reportFilters.value.to) {
                ctx.setDefaultReportRange();
            }

            ctx.fetchReportSummary();
        },
    },
    bookings: {
        onEnter: () => {
            ctx.fetchRows(Number(ctx.pagination.value.current_page || 1), { silent: false });
            ctx.startBookingsAutoRefresh();
        },
        onLeave: () => {
            ctx.stopBookingsAutoRefresh();
        },
    },
    packages: {
        onEnter: () => {
            if (!ctx.packages.value.length && !ctx.packageLoading.value) {
                ctx.fetchPackagesData();
            }
        },
    },
    'add-ons': {
        onEnter: () => {
            if (!ctx.addOnLoading.value) {
                ctx.fetchAddOnsData();
            }
        },
    },
    stock: {
        onEnter: () => {
            if (!ctx.stockLoading.value) {
                ctx.fetchStockData();
            }
        },
    },
    designs: {
        onEnter: () => {
            if (!ctx.designs.value.length && !ctx.designLoading.value) {
                ctx.fetchDesignsData();
            }
        },
    },
    users: {
        onEnter: () => {
            if (!ctx.users.value.length && !ctx.userLoading.value) {
                ctx.fetchUsersData();
            }
        },
    },
    settings: {
        onEnter: () => {
            if (!ctx.settingsLoading.value) {
                ctx.fetchSettingsData();
            }
        },
    },
    branches: {
        onEnter: () => {
            if (!ctx.branchRows.value.length && !ctx.branchLoading.value) {
                ctx.fetchBranchesData();
            }
        },
    },
    'time-slots': {
        onEnter: () => {
            if (!ctx.timeSlotRows.value.length && !ctx.timeSlotLoading.value) {
                ctx.fetchTimeSlotsData();
            }
        },
    },
    'blackout-dates': {
        onEnter: () => {
            if (!ctx.blackoutDateRows.value.length && !ctx.blackoutDateLoading.value) {
                ctx.fetchBlackoutDatesData();
            }
        },
    },
    payments: {
        onEnter: () => {
            if (!ctx.paymentRows.value.length && !ctx.paymentLoading.value) {
                ctx.fetchPaymentsData();
            }
        },
    },
    'printer-settings': {
        onEnter: () => {
            if (!ctx.printerSettingRows.value.length && !ctx.printerSettingLoading.value) {
                ctx.fetchPrinterSettingsData();
            }
        },
    },
    'app-settings': {
        onEnter: () => {
            if (!ctx.appSettingsLoading.value) {
                ctx.fetchAppSettingsData();
            }
        },
    },
    queue: {
        onEnter: () => {
            ctx.fetchQueueData();
            ctx.startQueueAutoRefresh();
        },
        onLeave: () => {
            ctx.stopQueueAutoRefresh();
        },
    },
});
