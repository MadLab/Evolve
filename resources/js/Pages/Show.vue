<template>
  <div>
    <!-- Navbar -->
    <header class="bg-white shadow shadow-emerald-900">
      <div class="container mx-auto px-4 py-4 flex justify-between items-center">
        <Link :href="'/evolve'" class="text-xl font-bold text-emerald-700">Evolve</Link>
        <nav>
          <ul class="flex space-x-4">
            <li><a href="/" class="text-gray-600 hover:text-green-600">Return to Site</a></li>
          </ul>
        </nav>
      </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
      <div class="bg-white shadow rounded-lg p-6">
        <div class="px-4 sm:px-6 lg:px-8">
          <!-- Header with experiment info -->
          <div class="sm:flex sm:items-center sm:justify-between">
            <div class="sm:flex-auto">
              <h1 class="text-2xl font-bold text-gray-900">{{ experiment.name }}</h1>
              <div class="mt-2 flex flex-wrap items-center gap-4 text-sm text-gray-500">
                <span :class="[
                  'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                  experiment.is_active
                    ? 'bg-green-100 text-green-800'
                    : 'bg-gray-100 text-gray-800'
                ]">
                  {{ experiment.is_active ? 'Active' : 'Paused' }}
                </span>
                <span v-if="experiment.started_at">
                  Started: {{ formatDate(experiment.started_at) }}
                </span>
                <span v-if="experiment.stopped_at">
                  Stopped: {{ formatDate(experiment.stopped_at) }}
                </span>
                <span>
                  Total Views: {{ formatNumber(experiment.total_views) }}
                </span>
                <span>
                  Bot Views: {{ formatNumber(totalBotViews) }}
                </span>
              </div>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
              <form @submit.prevent="toggleExperiment">
                <input type="hidden" name="action" :value="experiment.is_active ? 'disable' : 'enable'">
                <button
                    type="submit"
                    :class="[
                      'block rounded-md px-3 py-2 text-center text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2',
                      experiment.is_active
                        ? 'bg-amber-600 hover:bg-amber-500 focus-visible:outline-amber-600'
                        : 'bg-green-600 hover:bg-emerald-500 focus-visible:outline-emerald-600'
                    ]"
                >
                  {{ experiment.is_active ? 'Pause' : 'Start' }} Experiment
                </button>
              </form>
            </div>
          </div>

          <!-- Time Series Chart -->
          <div v-if="hasChartData" class="mt-8">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-900">Performance Over Time</h2>
              <div class="flex items-center gap-2">
                <label for="metric-select" class="text-sm text-gray-600">Metric:</label>
                <select
                    id="metric-select"
                    v-model="selectedMetric"
                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                >
                  <option value="views">Views</option>
                  <option v-for="name in conversionNames" :key="name" :value="name">
                    {{ capitalize(name) }} Rate
                  </option>
                </select>
              </div>
            </div>
            <div class="h-64 bg-gray-50 rounded-lg p-4">
              <Line :data="chartData" :options="chartOptions" />
            </div>
          </div>

          <!-- Variants Table -->
          <div class="mt-8 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
              <div class="inline-block min-w-full py-2 align-middle">
                <table class="min-w-full divide-y divide-gray-300">
                  <thead>
                  <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6 lg:pl-8">Variant</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Views</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Bot Views</th>
                    <th v-for="conversionName in conversionNames" :key="conversionName" scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      {{ capitalize(conversionName) }}
                    </th>
                  </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200 bg-white">
                  <tr v-for="variant in experiment.variant_logs" :key="variant.id">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6 lg:pl-8 overflow-scroll">
                      <textarea class="w-full h-full text-xs border-gray-200 rounded" rows="5" v-model="variant.content" disabled></textarea>
                      <form @submit.prevent="deleteVariant(variant.id)" class="mt-2">
                        <input type="hidden" name="action" value="deleteVariant">
                        <input type="hidden" name="variant_id" :value="variant.id">
                        <button type="submit" class="inline-block mt-1 text-sm text-red-600 hover:underline">Delete Variant</button>
                      </form>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ variant.view?.views || 0 }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-400">{{ variant.view?.bot_views || 0 }}</td>
                    <td v-for="conversionName in conversionNames" :key="conversionName" :class="['whitespace-nowrap px-3 py-4', maxRate(conversionName) === variant.id ? 'bg-green-50' : '']">
                      <ul class="space-y-2">
                        <li class="font-medium text-lg flex flex-col">
                          {{ variant.view?.conversions?.[conversionName] || 0 }}
                          <span class="text-xs text-gray-500">Conversions</span>
                        </li>
                        <li class="font-medium text-lg flex flex-col">
                          {{ variant.view ? conversionRate(variant.view, conversionName) : '0' }}%
                          <span class="text-xs text-gray-500">Rate</span>
                        </li>
                        <li class="text-sm flex flex-col">
                          {{ variant.view?.range?.[conversionName] || 'N/A' }}
                          <span class="text-xs text-gray-500">95% CI</span>
                        </li>
                      </ul>
                    </td>
                  </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Conversion Logs Section -->
        <div class="mt-10">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-2xl font-bold text-emerald-900">Recent Conversion Logs</h2>
            <span v-if="!conversionLoggingEnabled" class="text-sm text-amber-600 bg-amber-50 px-3 py-1 rounded-full">
              Logging disabled - set EVOLVE_LOG_CONVERSIONS=true to enable
            </span>
          </div>
          <div v-if="conversionLogs.length > 0" class="overflow-x-auto bg-white shadow rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900">Conversion</th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Variant</th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Model Type</th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Model ID</th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="log in conversionLogs" :key="log.id">
                  <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900">{{ log.conversion_name }}</td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ getVariantName(log.variant_id) }}</td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 font-mono text-xs">{{ log.loggable_type || '-' }}</td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ log.loggable_id || '-' }}</td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ log.created_at }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="text-gray-500 text-sm bg-gray-50 rounded-lg p-4">
            No conversion logs recorded yet.
          </div>
        </div>

        <!-- Deleted Variants Section -->
        <div v-if="deletedVariants.length > 0">
          <h2 class="text-2xl font-bold text-emerald-900 mt-10">Deleted Variants</h2>
          <div class="px-4 sm:px-6 lg:px-8">
            <div class="mt-8 flow-root">
              <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8 bg-white shadow rounded-lg">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                  <table class="min-w-full divide-y divide-gray-300">
                    <thead>
                    <tr>
                      <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Name</th>
                      <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                        <span class="sr-only">Edit</span>
                      </th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    <tr v-for="variant in deletedVariants" :key="variant.id">
                      <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">{{ variant.content }}</td>
                      <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                        <form @submit.prevent="restoreVariant(variant.id)" class="mt-2">
                          <input type="hidden" name="action" value="restoreVariant">
                          <input type="hidden" name="variant_id" :value="variant.id">
                          <button type="submit" class="inline-block mt-1 text-sm text-emerald-600 hover:underline">Restore</button>
                        </form>
                      </td>
                    </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { defineProps, computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
);

const props = defineProps({
  experiment: {
    type: Object,
    required: true,
  },
  deletedVariants: {
    type: Array,
    default: () => [],
  },
  dailyStats: {
    type: Object,
    default: () => ({}),
  },
  conversionLogs: {
    type: Array,
    default: () => [],
  },
  conversionLoggingEnabled: {
    type: Boolean,
    default: false,
  },
});

const selectedMetric = ref('views');

const conversionNames = computed(() => {
  return props.experiment.conversion_names || [];
});

const totalBotViews = computed(() => {
  return props.experiment.variant_logs.reduce((total, variant) => {
    return total + (variant.view?.bot_views || 0);
  }, 0);
});

const hasChartData = computed(() => {
  return Object.keys(props.dailyStats).length > 0;
});

// Generate distinct colors for each variant
const variantColors = [
  { border: 'rgb(16, 185, 129)', background: 'rgba(16, 185, 129, 0.1)' },
  { border: 'rgb(59, 130, 246)', background: 'rgba(59, 130, 246, 0.1)' },
  { border: 'rgb(245, 158, 11)', background: 'rgba(245, 158, 11, 0.1)' },
  { border: 'rgb(239, 68, 68)', background: 'rgba(239, 68, 68, 0.1)' },
  { border: 'rgb(139, 92, 246)', background: 'rgba(139, 92, 246, 0.1)' },
  { border: 'rgb(236, 72, 153)', background: 'rgba(236, 72, 153, 0.1)' },
];

const chartData = computed(() => {
  if (!hasChartData.value) {
    return { labels: [], datasets: [] };
  }

  // Get all unique dates across all variants
  const allDates = new Set();
  Object.values(props.dailyStats).forEach(stats => {
    stats.forEach(stat => allDates.add(stat.date));
  });
  const labels = Array.from(allDates).sort();

  // Create a dataset for each variant
  const datasets = props.experiment.variant_logs.map((variant, index) => {
    const variantStats = props.dailyStats[variant.id] || [];
    const statsMap = {};
    variantStats.forEach(stat => {
      statsMap[stat.date] = stat;
    });

    const data = labels.map(date => {
      const stat = statsMap[date];
      if (!stat) return 0;

      if (selectedMetric.value === 'views') {
        return stat.views;
      } else {
        // Calculate conversion rate for the selected metric
        const conversions = stat.conversions?.[selectedMetric.value] || 0;
        const views = stat.views || 1;
        return ((conversions / views) * 100).toFixed(2);
      }
    });

    const colorIndex = index % variantColors.length;
    return {
      label: `Variant ${index + 1}: ${variant.content.substring(0, 20)}${variant.content.length > 20 ? '...' : ''}`,
      data,
      borderColor: variantColors[colorIndex].border,
      backgroundColor: variantColors[colorIndex].background,
      tension: 0.3,
    };
  });

  return { labels, datasets };
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      title: {
        display: true,
        text: computed(() => selectedMetric.value === 'views' ? 'Views' : 'Conversion Rate (%)').value,
      },
    },
    x: {
      title: {
        display: true,
        text: 'Date',
      },
    },
  },
};

function capitalize(str) {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatNumber(number) {
  return new Intl.NumberFormat().format(number || 0);
}

function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

const toggleForm = useForm({
  action: props.experiment.is_active ? 'disable' : 'enable',
});

function toggleExperiment() {
  toggleForm.post(`/evolve/${props.experiment.id}`, {
    preserveState: true,
    onSuccess: () => {
      toggleForm.action = props.experiment.is_active ? 'disable' : 'enable';
    },
  });
}

function deleteVariant(variantId) {
  const deleteForm = useForm({
    action: 'deleteVariant',
    variant_id: variantId,
  });
  deleteForm.post(`/evolve/${props.experiment.id}`, {
    preserveState: true,
  });
}

function restoreVariant(variantId) {
  const restoreForm = useForm({
    action: 'restoreVariant',
    variant_id: variantId,
  });
  restoreForm.post(`/evolve/${props.experiment.id}`, {
    preserveState: true,
  });
}

function conversionRate(view, conversionName) {
  return view.conversions?.[conversionName] && view.views
      ? ((view.conversions[conversionName] / view.views) * 100).toFixed(2)
      : '0';
}

function maxRate(conversionName) {
  const maxVariant = props.experiment.variant_logs.reduce((max, variant) => {
    const rate = variant.view && variant.view.conversions?.[conversionName] && variant.view.views
        ? variant.view.conversions[conversionName] / variant.view.views
        : 0;
    return rate > (max.rate || 0) ? { id: variant.id, rate } : max;
  }, { rate: 0 });
  return maxVariant.id;
}

function getVariantName(variantId) {
  const variant = props.experiment.variant_logs.find(v => v.id === variantId);
  if (!variant) return `ID: ${variantId}`;
  const content = variant.content || '';
  return content.length > 30 ? content.substring(0, 30) + '...' : content;
}
</script>
