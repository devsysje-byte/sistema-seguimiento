<template>
  <AppShell title="Dashboard" subtitle="Here's what's happening today.">
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
      <MetricCard
        v-for="card in cards"
        :key="card.label"
        :label="card.label"
        :value="card.value"
        :change="card.change"
        :icon="card.icon"
        :tone="card.tone"
      />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
      <div class="lg:col-span-2">
        <CurrentVisitsChart />
      </div>
      <div class="lg:col-span-3">
        <WebsiteVisitsChart />
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '../components/ui/AppShell.vue';
import MetricCard from '../components/dashboard/MetricCard.vue';
import CurrentVisitsChart from '../components/dashboard/CurrentVisitsChart.vue';
import WebsiteVisitsChart from '../components/dashboard/WebsiteVisitsChart.vue';

const cards = [
  { label: 'Weekly sales', value: '714k', change: '+2.6%', icon: 'shopping-bag', tone: 'blue' },
  { label: 'New users', value: '1.35m', change: '-0.1%', icon: 'user', tone: 'violet' },
  { label: 'Purchase orders', value: '1.72m', change: '+2.8%', icon: 'bar-chart', tone: 'amber' },
  { label: 'Messages', value: '234', change: '+3.6%', icon: 'mail', tone: 'rose' },
];
</script>
