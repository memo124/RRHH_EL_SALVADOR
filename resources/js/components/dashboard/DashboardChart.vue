<template>
  <div class="relative w-full" :style="{ height: `${height}px` }">
    <canvas ref="canvasRef" />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import {
  Chart,
  BarController,
  BarElement,
  LineController,
  LineElement,
  PointElement,
  DoughnutController,
  ArcElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js';

Chart.register(
  BarController,
  BarElement,
  LineController,
  LineElement,
  PointElement,
  DoughnutController,
  ArcElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
  Filler,
);

const props = defineProps({
  type: { type: String, default: 'bar' },
  labels: { type: Array, default: () => [] },
  datasets: { type: Array, default: () => [] },
  height: { type: Number, default: 280 },
  currencyY: { type: Boolean, default: false },
});

const canvasRef = ref(null);
let chartInstance = null;
let themeObserver = null;

const isDark = () => document.documentElement.classList.contains('dark');

const chartColors = () => {
  const dark = isDark();
  return {
    text: dark ? '#cbd5e1' : '#64748b',
    grid: dark ? 'rgba(148, 163, 184, 0.15)' : 'rgba(148, 163, 184, 0.35)',
  };
};

const palette = [
  'rgba(99, 102, 241, 0.85)',
  'rgba(16, 185, 129, 0.85)',
  'rgba(245, 158, 11, 0.85)',
  'rgba(236, 72, 153, 0.85)',
  'rgba(59, 130, 246, 0.85)',
  'rgba(139, 92, 246, 0.85)',
  'rgba(20, 184, 166, 0.85)',
  'rgba(249, 115, 22, 0.85)',
];

const buildConfig = () => {
  const { text, grid } = chartColors();
  const styledDatasets = props.datasets.map((ds, i) => ({
    borderWidth: props.type === 'line' ? 2.5 : 1,
    borderRadius: props.type === 'bar' ? 6 : 0,
    tension: 0.35,
    fill: props.type === 'line' ? (ds.fill ?? false) : undefined,
    backgroundColor: ds.backgroundColor ?? (props.type === 'doughnut'
      ? palette
      : palette[i % palette.length]),
    borderColor: ds.borderColor ?? palette[i % palette.length],
    ...ds,
  }));

  return {
    type: props.type,
    data: {
      labels: props.labels,
      datasets: styledDatasets,
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: props.type === 'doughnut' || props.datasets.length > 1,
          labels: { color: text, boxWidth: 12, padding: 14 },
        },
        tooltip: {
          callbacks: props.currencyY ? {
            label: (ctx) => {
              const val = ctx.parsed.y ?? ctx.parsed;
              return `${ctx.dataset.label}: $${Number(val).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
            },
          } : undefined,
        },
      },
      scales: props.type === 'doughnut' ? undefined : {
        x: {
          ticks: { color: text, maxRotation: 45, minRotation: 0, font: { size: 11 } },
          grid: { color: grid },
        },
        y: {
          beginAtZero: true,
          ticks: {
            color: text,
            font: { size: 11 },
            callback: props.currencyY
              ? (v) => '$' + Number(v).toLocaleString('en-US', { maximumFractionDigits: 0 })
              : undefined,
          },
          grid: { color: grid },
        },
      },
    },
  };
};

const renderChart = () => {
  if (!canvasRef.value) return;
  if (chartInstance) {
    chartInstance.destroy();
  }
  chartInstance = new Chart(canvasRef.value, buildConfig());
};

watch(() => [props.labels, props.datasets, props.type], renderChart, { deep: true });

onMounted(() => {
  renderChart();
  themeObserver = new MutationObserver(renderChart);
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});

onUnmounted(() => {
  themeObserver?.disconnect();
  chartInstance?.destroy();
});
</script>
