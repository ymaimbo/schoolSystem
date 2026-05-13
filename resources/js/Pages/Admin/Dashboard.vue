<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  role: { type: String, default: '' },
  cards: { type: Array, default: () => [] },
})

const normalizeRole = (value) => String(value || '').trim().toLowerCase().replace(/[\s-]+/g, '_')
const role = computed(() => normalizeRole(props.role))

const canSee = (roles) => roles.map(normalizeRole).includes(role.value)
const canPrint = computed(() => canSee(['principal', 'accountant', 'hod']))

const moduleLinks = computed(() =>
  [
    { key: 'students', label: 'Students', href: route('admin.students.index'), roles: ['principal', 'deputy_principal', 'accountant', 'secretary'] },
    { key: 'programs', label: 'Programs', href: route('admin.programs.index'), roles: ['principal', 'deputy_principal', 'hod'] },
    { key: 'exams', label: 'Exams', href: route('admin.exams.index'), roles: ['principal', 'deputy_principal', 'hod'] },
    { key: 'finance', label: 'Finance', href: route('admin.finance.index'), roles: ['principal', 'accountant'] },
    { key: 'store', label: 'Store', href: route('admin.store.index'), roles: ['principal', 'accountant', 'store_keeper'] },
  ].filter((item) => canSee(item.roles))
)

const printDashboard = () => {
  window.print()
}
</script>

<template>
  <Head title="Admin Dashboard" />
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-start justify-between gap-4 print:block">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
          <p class="text-sm text-slate-600">Role: {{ role }}</p>
        </div>

        <button
          v-if="canPrint"
          type="button"
          class="no-print border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50"
          @click="printDashboard"
        >
          Print Dashboard
        </button>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="card in cards"
          :key="card.title"
          class="border border-slate-200 bg-white p-5"
        >
          <p class="text-xs uppercase tracking-wide text-slate-500">{{ card.title }}</p>
          <p class="mt-2 text-3xl font-semibold text-slate-900">{{ card.value }}</p>
          <p class="mt-2 text-sm text-slate-500">{{ card.hint }}</p>
        </div>
      </div>

      <div class="border border-slate-200 bg-white p-5">
        <h2 class="text-lg font-semibold text-slate-900">Quick Access</h2>
        <p class="mt-1 text-sm text-slate-600">
          Open authorized modules below. Create, update, and delete actions are inside each module page.
        </p>

        <div class="mt-4 flex flex-wrap gap-3 no-print">
          <Link
            v-for="item in moduleLinks"
            :key="item.key"
            :href="item.href"
            class="border border-slate-300 px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50"
          >
            Open {{ item.label }}
          </Link>
        </div>

        <div v-if="!moduleLinks.length" class="mt-4 text-sm text-slate-500 no-print">
          No modules are assigned to this role yet.
        </div>

        <div class="mt-4 hidden print:block">
          <ul class="list-disc pl-5 text-sm text-slate-700">
            <li v-for="item in moduleLinks" :key="`print-${item.key}`">
              {{ item.label }}
            </li>
          </ul>
        </div>
      </div>

      <p class="hidden text-xs text-slate-500 print:block">
        Printed by role: {{ role }} | Generated at: {{ new Date().toLocaleString() }}
      </p>
    </div>
  </AdminLayout>
</template>

<style scoped>
@media print {
  :deep(body) {
    background: #fff !important;
  }

  .no-print {
    display: none !important;
  }

  .print\:block {
    display: block !important;
  }

  .print\:hidden {
    display: none !important;
  }
}
</style>