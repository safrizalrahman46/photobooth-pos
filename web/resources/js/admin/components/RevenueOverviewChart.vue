<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    labels: {
        type: Array,
        default: () => [],
    },
    revenueData: {
        type: Array,
        default: () => [],
    },
    bookingData: {
        type: Array,
        default: () => [],
    },
    height: {
        type: Number,
        default: 280,
    },
    emptyLabel: {
        type: String,
        default: 'Belum ada data revenue untuk ditampilkan.',
    },
});

const canvasRef = ref(null);
const chartBusy = ref(false);
let chartInstance = null;
let ChartModule = null;

const hasData = computed(() => {
    return props.revenueData.some((value) => Number(value || 0) > 0)
        || props.bookingData.some((value) => Number(value || 0) > 0);
});

const formatRupiah = (amount) => {
    return `Rp ${Number(amount || 0).toLocaleString('id-ID')}`;
};

const destroyChart = () => {
    if (!chartInstance) {
        return;
    }

    chartInstance.destroy();
    chartInstance = null;
};

const ensureChartModule = async () => {
    if (ChartModule) {
        return ChartModule;
    }

    const module = await import('chart.js/auto');
    ChartModule = module.default || module;

    return ChartModule;
};

const renderChart = async () => {
    destroyChart();

    if (!canvasRef.value || !props.labels.length || !hasData.value) {
        chartBusy.value = false;
        return;
    }

    chartBusy.value = true;

    const Chart = await ensureChartModule();

    if (!canvasRef.value) {
        chartBusy.value = false;
        return;
    }

    chartInstance = new Chart(canvasRef.value, {
        data: {
            labels: props.labels,
            datasets: [
                {
                    type: 'bar',
                    label: 'Revenue',
                    data: props.revenueData.map((value) => Number(value || 0)),
                    backgroundColor: 'rgba(37, 99, 235, 0.18)',
                    borderColor: '#2563EB',
                    borderWidth: 1,
                    borderRadius: 10,
                    yAxisID: 'yRevenue',
                },
                {
                    type: 'line',
                    label: 'Bookings',
                    data: props.bookingData.map((value) => Number(value || 0)),
                    borderColor: '#059669',
                    backgroundColor: 'rgba(5, 150, 105, 0.18)',
                    pointBackgroundColor: '#059669',
                    pointBorderColor: '#FFFFFF',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 5,
                    tension: 0.35,
                    fill: false,
                    yAxisID: 'yBookings',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 8,
                        color: '#334155',
                    },
                },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            if (context.dataset.yAxisID === 'yBookings') {
                                return `${context.dataset.label}: ${context.parsed.y} booking`;
                            }

                            return `${context.dataset.label}: ${formatRupiah(context.parsed.y)}`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: '#64748B',
                    },
                },
                yRevenue: {
                    position: 'left',
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(148, 163, 184, 0.18)',
                    },
                    ticks: {
                        color: '#64748B',
                        callback: (value) => formatRupiah(value),
                    },
                },
                yBookings: {
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        color: '#64748B',
                        precision: 0,
                    },
                },
            },
        },
    });

    chartBusy.value = false;
};

watch(
    () => [props.labels, props.revenueData, props.bookingData],
    () => {
        renderChart();
    },
    { deep: true },
);

onMounted(() => {
    renderChart();
});

onBeforeUnmount(() => {
    destroyChart();
});
</script>

<template>
    <div>
        <div v-if="!labels.length || !hasData" class="flex h-[220px] items-center justify-center rounded-2xl border border-dashed border-[#DBEAFE] bg-[#F8FAFC] px-4 text-center text-sm text-[#94A3B8]">
            {{ emptyLabel }}
        </div>
        <div v-else class="relative" :style="{ height: `${height}px` }">
            <div v-if="chartBusy" class="absolute inset-0 z-10 flex items-center justify-center rounded-2xl bg-white/75 text-sm text-[#64748B]">
                Memuat grafik...
            </div>
            <canvas ref="canvasRef"></canvas>
        </div>
    </div>
</template>
