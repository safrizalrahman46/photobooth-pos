<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    labels: {
        type: Array,
        default: () => [],
    },
    datasets: {
        type: Array,
        default: () => [],
    },
    height: {
        type: Number,
        default: 280,
    },
    emptyLabel: {
        type: String,
        default: 'Belum ada data untuk ditampilkan.',
    },
});

const canvasRef = ref(null);
const chartBusy = ref(false);
let chartInstance = null;
let ChartModule = null;

const formatRupiah = (amount) => {
    return `Rp ${Number(amount || 0).toLocaleString('id-ID')}`;
};

const hasData = computed(() => {
    return (props.datasets || []).some((dataset) => {
        return Array.isArray(dataset?.data) && dataset.data.some((value) => Number(value || 0) > 0);
    });
});

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
        type: 'bar',
        data: {
            labels: props.labels,
            datasets: (props.datasets || []).map((dataset) => ({
                label: String(dataset?.label || '-'),
                data: Array.isArray(dataset?.data) ? dataset.data.map((value) => Number(value || 0)) : [],
                backgroundColor: String(dataset?.backgroundColor || '#2563EB'),
                borderColor: String(dataset?.borderColor || dataset?.backgroundColor || '#2563EB'),
                borderWidth: 1,
                borderRadius: 8,
                borderSkipped: false,
                stack: 'cashier-performance',
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                x: {
                    stacked: true,
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: '#64748B',
                    },
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(148, 163, 184, 0.18)',
                    },
                    ticks: {
                        color: '#64748B',
                        callback: (value) => formatRupiah(value),
                    },
                },
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
                        label: (context) => `${context.dataset.label}: ${formatRupiah(context.parsed.y)}`,
                    },
                },
            },
        },
    });

    chartBusy.value = false;
};

watch(
    () => [props.labels, props.datasets],
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
