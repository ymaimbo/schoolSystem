<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  role: {
    type: String,
    default: 'class_teacher',
  },
  classAssignments: {
    type: Array,
    default: () => [],
  },
  message: {
    type: String,
    default: '',
  },
  incidents: {
    type: Array,
    default: () => [],
  },
})

const roleLabel = computed(() => String(props.role || '').replaceAll('_', ' '))
const incidents = computed(() => props.incidents ?? [])
</script>

<template>
  <Head title="Class Room • Discipline" />

  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between gap-4 print:block">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">Class Discipline Desk</h1>
          <p class="text-sm capitalize text-slate-600">Role: {{ roleLabel }}</p>
        </div>

        <div class="no-print flex flex-wrap gap-2">
          <Link
            :href="route('admin.class-room.index')"
            class="border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50"
          >
            Back to Exams
          </Link>
          <button
            type="button"
            class="border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50"
            @click="window.print()"
          >
            Print Page
          </button>
        </div>
      </div>

      <div class="border border-slate-200 bg-white p-4">
        <h2 class="text-base font-semibold text-slate-900">Assigned Class Scope</h2>

        <div v-if="classAssignments.length" class="mt-3 flex flex-wrap gap-2">
          <span
            v-for="(item, idx) in classAssignments"
            :key="`assign-${idx}`"
            class="border border-slate-200 bg-slate-50 px-2 py-1 text-xs text-slate-700"
          >
            {{ item.class_level }}<span v-if="item.stream"> - {{ item.stream }}</span>
          </span>
        </div>

        <p v-else class="mt-3 text-sm text-amber-700">
          No active class assignments found.
        </p>

        <p v-if="message" class="mt-3 text-sm text-slate-600">{{ message }}</p>
      </div>

      <div class="border border-slate-200 bg-white p-4">
        <div class="flex items-center justify-between">
          <h2 class="text-base font-semibold text-slate-900">Discipline Incidents</h2>
          <span class="text-xs text-slate-500">{{ incidents.length }} record(s)</span>
        </div>

        <div class="mt-3 overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left">
              <tr>
                <th class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500">Date</th>
                <th class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500">Student</th>
                <th class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500">Class</th>
                <th class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500">Category</th>
                <th class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500">Action Taken</th>
                <th class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, idx) in incidents" :key="`inc-${idx}`" class="border-t border-slate-100">
                <td class="px-3 py-2">{{ item.date || '-' }}</td>
                <td class="px-3 py-2">{{ item.student_name || '-' }}</td>
                <td class="px-3 py-2">{{ item.class_level || '-' }}<span v-if="item.stream"> {{ item.stream }}</span></td>
                <td class="px-3 py-2">{{ item.category || '-' }}</td>
                <td class="px-3 py-2">{{ item.action_taken || '-' }}</td>
                <td class="px-3 py-2">{{ item.status || '-' }}</td>
              </tr>

              <tr v-if="!incidents.length">
                <td colspan="6" class="px-3 py-8 text-center text-slate-500">
                  No discipline records yet. Integrate your discipline controller payload to populate this list.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
}
</style>