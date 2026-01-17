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
      <h2 class="text-3xl font-bold text-emerald-900">Active Experiments</h2>
      <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-5">
        <li v-for="experiment in activeExperiments" :key="experiment.id" class="col-span-1 divide-y divide-gray-200 rounded-lg bg-white shadow">
          <div class="flex w-full items-center justify-between space-x-6 p-6">
            <div class="flex-1 truncate">
              <div class="flex items-center space-x-3">
                <h3 class="truncate text-xl font-medium text-gray-900">{{ experiment.name }}</h3>
                <span class="inline-flex shrink-0 items-center rounded-full bg-green-50 px-1.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                  Active
                </span>
              </div>
              <p v-if="experiment.started_at" class="mt-1 text-xs text-gray-500">
                Started {{ formatDate(experiment.started_at) }}
              </p>
              <Link :href="`/evolve/${experiment.id}`" class="inline-block mt-2 text-sm text-emerald-600 hover:underline">View Experiment →</Link>
            </div>
          </div>
          <div>
            <div class="-mt-px flex divide-x divide-gray-200">
              <div class="flex w-0 flex-1 flex-col items-center justify-center py-3">
                <span class="text-sm font-semibold text-gray-900">{{ formatNumber(experiment.total_views) }}</span>
                <span class="text-xs text-gray-500">Total Views</span>
              </div>
              <div class="flex w-0 flex-1 flex-col items-center justify-center py-3">
                <span class="text-sm font-semibold text-gray-900">{{ formatNumber(getTotalBotViews(experiment)) }}</span>
                <span class="text-xs text-gray-500">Bot Views</span>
              </div>
              <div class="flex w-0 flex-1 flex-col items-center justify-center py-3">
                <span class="text-sm font-semibold text-gray-900">{{ experiment.variant_logs.length }}</span>
                <span class="text-xs text-gray-500">Variants</span>
              </div>
            </div>
          </div>
          <div v-if="getBestConversionRate(experiment)" class="px-4 py-3 bg-gray-50 rounded-b-lg">
            <div class="text-xs text-gray-500">Best Conversion Rate</div>
            <div class="text-sm font-medium text-emerald-700">{{ getBestConversionRate(experiment) }}</div>
          </div>
        </li>
        <li v-if="!activeExperiments.length" class="col-span-full text-gray-500 italic">No Active Experiments</li>
      </ul>

      <div v-if="inactiveExperiments.length">
        <h2 class="text-2xl font-bold text-emerald-900 mt-10">Paused Experiments</h2>
        <div class="px-4 sm:px-6 lg:px-8">
          <div class="mt-8 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8 bg-white shadow rounded-lg">
              <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <table class="min-w-full divide-y divide-gray-300">
                  <thead>
                  <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Name</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Duration</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Total Views</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Variants</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                      <span class="sr-only">Edit</span>
                    </th>
                  </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                  <tr v-for="experiment in inactiveExperiments" :key="experiment.id">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                      {{ experiment.name }}
                      <span v-if="experiment.is_still_in_use" class="ml-2 inline-flex items-center rounded-full bg-amber-50 px-1.5 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">
                        Still in use
                      </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      <span v-if="experiment.started_at && experiment.stopped_at">
                        {{ formatDate(experiment.started_at) }} - {{ formatDate(experiment.stopped_at) }}
                      </span>
                      <span v-else class="text-gray-400">—</span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ formatNumber(experiment.total_views) }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ experiment.variant_logs.length }}</td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0 space-x-3">
                      <Link :href="`/evolve/${experiment.id}`" class="text-emerald-600 hover:text-emerald-900">View</Link>
                      <button
                          @click="confirmDelete(experiment)"
                          class="text-red-600 hover:text-red-900"
                      >
                        Delete
                      </button>
                    </td>
                  </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { defineProps } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  activeExperiments: Array,
  inactiveExperiments: Array,
});

function confirmDelete(experiment) {
  let message = `Are you sure you want to delete "${experiment.name}"?`;

  if (experiment.is_still_in_use) {
    message = `Warning: "${experiment.name}" is still being accessed by your site code (within the last 24 hours).\n\nThis means the <x-evolve> component is still in your templates. Deleting this experiment may cause issues.\n\nAre you sure you want to delete it?`;
  }

  if (confirm(message)) {
    const deleteForm = useForm({ action: 'delete' });
    deleteForm.post(`/evolve/${experiment.id}`, {
      preserveScroll: true,
    });
  }
}

function formatNumber(number) {
  return new Intl.NumberFormat().format(number);
}

function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function getTotalBotViews(experiment) {
  return experiment.variant_logs.reduce((total, variant) => {
    return total + (variant.view?.bot_views || 0);
  }, 0);
}

function getBestConversionRate(experiment) {
  const conversionNames = experiment.conversion_names || [];
  if (conversionNames.length === 0) return null;

  let bestRate = 0;
  let bestConversion = null;

  for (const name of conversionNames) {
    for (const variant of experiment.variant_logs) {
      if (variant.view && variant.view.conversions && variant.view.views > 0) {
        const rate = (variant.view.conversions[name] || 0) / variant.view.views * 100;
        if (rate > bestRate) {
          bestRate = rate;
          bestConversion = name;
        }
      }
    }
  }

  if (bestConversion) {
    return `${bestRate.toFixed(2)}% (${bestConversion})`;
  }
  return null;
}
</script>