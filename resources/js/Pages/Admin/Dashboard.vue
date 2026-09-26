<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  role: { type: String, default: '' },
  cards: { type: Array, default: () => [] },
  principalClassStats: { type: Object, default: null },
})

const normalizeRole = (value) =>
  String(value || '')
    .trim()
    .toLowerCase()
    .replace(/[\s-]+/g, '_')

const role = computed(() => normalizeRole(props.role))
const canSee = (...roles) => roles.map(normalizeRole).includes(role.value)
const canPrint = computed(() => canSee(['principal', 'deputy_principal', 'dean', 'hod', 'accountant']))
const isPrincipal = computed(() => role.value === 'principal')

const moduleLinks = computed(() =>
  [
    { key: 'students', label: 'Students', href: route('admin.students.index'), roles: ['principal', 'deputy_principal', 'dean', 'accountant', 'secretary'] },
    { key: 'programs', label: 'Programs', href: route('admin.programs.index'), roles: ['principal', 'deputy_principal', 'dean', 'hod'] },
    { key: 'exams', label: 'Exams', href: route('admin.exams.index'), roles: ['principal', 'deputy_principal', 'dean', 'hod'] },
    { key: 'staff', label: 'Staff', href: route('admin.staff.index'), roles: ['principal', 'deputy_principal'] },
    { key: 'finance', label: 'Finance', href: route('admin.finance.index'), roles: ['principal', 'accountant'] },
    { key: 'student_finance', label: 'Student Finance', href: route('admin.student-finance.index'), roles: ['principal', 'accountant'] },
    { key: 'store', label: 'Store', href: route('admin.store.index'), roles: ['principal', 'accountant', 'store_keeper'] },
    { key: 'timetable', label: 'Timetable', href: route('admin.timetable.index'), roles: ['principal', 'deputy_principal', 'dean'] },
    { key: 'parents', label: 'Parents', href: route('admin.parents.index'), roles: ['principal', 'secretary', 'dean'] },
    { key: 'sports', label: 'Sports', href: route('admin.sports.index'), roles: ['principal', 'deputy_principal', 'dean', 'hod'] },
    { key: 'discipline', label: 'Discipline', href: route('admin.discipline.index'), roles: ['principal', 'deputy_principal', 'dean'] },
    { key: 'communications', label: 'Messages', href: route('admin.communications.index'), roles: ['principal', 'deputy_principal', 'secretary', 'dean'] },
    { key: 'class_teacher_assignments', label: 'Class Teacher Assignments', href: route('admin.class-teacher-assignments.index'), roles: ['principal'] },
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
          <h1 class="text-2xl font-bold tracking-tight text-white">Dashboard</h1>
          <p class="mt-1 text-sm text-slate-300">Role: {{ role }}</p>
        </div>

        <button
          v-if="canPrint"
          type="button"
          class="no-print rounded-md border border-white/20 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-white/5"
          @click="printDashboard"
        >
          Print Dashboard
        </button>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="card in cards"
          :key="card.title"
          class="rounded-xl border border-white/10 bg-slate-900/70 p-5"
        >
          <p class="text-xs uppercase tracking-wider text-slate-400">{{ card.title }}</p>
          <p class="mt-2 text-3xl font-semibold text-white">{{ card.value }}</p>
          <p class="mt-2 text-sm text-slate-300">{{ card.hint }}</p>
        </div>
      </div>

      <div
        v-if="isPrincipal && principalClassStats"
        class="rounded-xl border border-white/10 bg-slate-900/70 p-5"
      >
        <h2 class="text-lg font-semibold text-white">Class Assignment Health</h2>
        <p class="mt-1 text-sm text-slate-300">
          Monitor class-teacher assignment coverage and fix gaps quickly.
        </p>

        <div class="mt-4 grid gap-3 sm:grid-cols-3">
          <Link
            :href="route('admin.class-teacher-assignments.index', { view: 'assigned' })"
            class="rounded-lg border border-white/10 bg-slate-950/60 p-4 transition hover:bg-white/5"
          >
            <p class="text-xs uppercase tracking-wider text-slate-400">Assigned Class Teachers</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-300">
              {{ principalClassStats.assigned_class_teachers }}
            </p>
          </Link>

          <Link
            :href="route('admin.class-teacher-assignments.index', { view: 'unassigned_teachers' })"
            class="rounded-lg border border-white/10 bg-slate-950/60 p-4 transition hover:bg-white/5"
          >
            <p class="text-xs uppercase tracking-wider text-slate-400">Unassigned Class Teachers</p>
            <p class="mt-2 text-2xl font-semibold text-amber-300">
              {{ principalClassStats.unassigned_class_teachers }}
            </p>
          </Link>

          <Link
            :href="route('admin.class-teacher-assignments.index', { view: 'unassigned_classes' })"
            class="rounded-lg border border-white/10 bg-slate-950/60 p-4 transition hover:bg-white/5"
          >
            <p class="text-xs uppercase tracking-wider text-slate-400">Classes Without Assignment</p>
            <p class="mt-2 text-2xl font-semibold text-rose-300">
              {{ principalClassStats.classes_without_assignment }}
            </p>
          </Link>
        </div>

        <div class="mt-4 no-print">
          <Link
            :href="route('admin.class-teacher-assignments.index', { view: 'all' })"
            class="inline-flex rounded-md border border-cyan-300/40 px-4 py-2 text-sm font-medium text-cyan-200 hover:bg-cyan-400/10"
          >
            Manage Class Teacher Assignments
          </Link>
        </div>
      </div>

      <div class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h2 class="text-lg font-semibold text-white">Quick Access</h2>
        <p class="mt-1 text-sm text-slate-300">Open authorized modules below.</p>

        <div class="mt-4 flex flex-wrap gap-3 no-print">
          <Link
            v-for="item in moduleLinks"
            :key="item.key"
            :href="item.href"
            class="rounded-md border border-white/20 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-white/5"
          >
            Open {{ item.label }}
          </Link>
        </div>
      </div>
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
}
</style>