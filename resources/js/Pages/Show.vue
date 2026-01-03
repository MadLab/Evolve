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
              </div>
              <Link :href="`/evolve/${experiment.id}`" class="inline-block mt-1 text-sm text-emerald-600 hover:underline">View Experiment →</Link>
            </div>
          </div>
          <div>
            <div class="-mt-px flex divide-x divide-gray-200">
              <div class="flex w-0 flex-1">
                <span class="relative -mr-px inline-flex w-0 flex-1 items-center justify-center gap-x-3 rounded-bl-lg border border-transparent py-4 text-sm font-semibold text-gray-900">
                  {{ formatNumber(experiment.total_views) }} <span class="text-xs text-gray-600">Views</span>
                </span>
              </div>
              <div class="-ml-px flex w-0 flex-1">
                <span class="relative inline-flex w-0 flex-1 items-center justify-center gap-x-3 rounded-br-lg border border-transparent py-4 text-sm font-semibold text-gray-900">
                  {{ experiment.variant_logs.length }} <span class="text-xs text-gray-600">Variants</span>
                </span>
              </div>
            </div>
          </div>
        </li>
        <p v-if="!activeExperiments.length">'No Active Experiments'</p>
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
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Variants</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                      <span class="sr-only">Edit</span>
                    </th>
                  </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                  <tr v-for="experiment in inactiveExperiments" :key="experiment.id">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">{{ experiment.name }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ experiment.variant_logs.length }}</td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                      <a :href="route('evolve.experiments.show', experiment)" class="text-emerald-600 hover:underline">View Experiment →</a>
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

const props = defineProps({
  activeExperiments: Array,
  inactiveExperiments: Array,
});

function formatNumber(number) {
  return new Intl.NumberFormat().format(number);
}

// Assume Laravel's route helper is available globally via Ziggy
</script>