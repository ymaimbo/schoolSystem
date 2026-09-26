<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  school: {
    type: Object,
    required: true,
  },
  authUser: {
    type: Object,
    default: null,
  },
  // Optional KPI payload from backend. Falls back to demo-safe defaults.
  metrics: {
    type: Object,
    default: () => ({}),
  },
})

const role = computed(() => String(props.authUser?.role || '').toLowerCase().trim())

const roleLabel = computed(() => {
  if (!role.value) return 'Guest'
  return role.value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
})

const kpis = computed(() => {
  const m = props.metrics || {}
  return [
    {
      key: 'students',
      label: 'Students',
      value: Number(m.students_count ?? 0).toLocaleString(),
      hint: 'Active learners',
    },
    {
      key: 'staff',
      label: 'Staff',
      value: Number(m.staff_count ?? 0).toLocaleString(),
      hint: 'Teaching + non-teaching',
    },
    {
      key: 'attendance',
      label: 'Attendance Today',
      value: `${Number(m.attendance_rate_today ?? 0)}%`,
      hint: 'Present rate',
    },
    {
      key: 'fees',
      label: 'Fee Collection',
      value: `${Number(m.fee_collection_rate ?? 0)}%`,
      hint: 'Term target achieved',
    },
  ]
})

const quickActions = computed(() => {
  // Super Admin actions
  if (role.value === 'super_admin') {
    return [
      { label: 'Open Super Admin Dashboard', href: safeRoute('superadmin.dashboard', {}, '/super-admin/dashboard') },
      { label: 'Register New School', href: safeRoute('superadmin.schools.register', {}, '/super-admin/schools/register') },
      { label: 'View School Profile', href: safeRoute('schools.show', props.school.slug, `/schools/${props.school.slug || ''}`) },
    ]
  }

  // Principal actions
  if (role.value === 'principal') {
    return [
      { label: 'Open School Dashboard', href: safeRoute('dashboard', {}, '/dashboard') },
      { label: 'Assign Staff Roles', href: safeRoute('admin.staff.index', {}, '/admin/staff') },
      { label: 'Manage Student Finance', href: safeRoute('admin.student-finance.index', {}, '/admin/student-finance') },
      { label: 'View Exams', href: safeRoute('admin.exams.index', {}, '/admin/exams') },
    ]
  }

  // Accountant actions
  if (role.value === 'accountant') {
    return [
      { label: 'Open School Dashboard', href: safeRoute('dashboard', {}, '/dashboard') },
      { label: 'Post Fee Transactions', href: safeRoute('admin.finance.index', {}, '/admin/finance') },
      { label: 'Issue Receipts', href: safeRoute('admin.student-finance.index', {}, '/admin/student-finance') },
    ]
  }

  // Deputy / Dean / HOD
  if (['deputy_principal', 'dean', 'hod'].includes(role.value)) {
    return [
      { label: 'Open School Dashboard', href: safeRoute('dashboard', {}, '/dashboard') },
      { label: 'Manage Exams', href: safeRoute('admin.exams.index', {}, '/admin/exams') },
      { label: 'Manage Timetable', href: safeRoute('admin.timetable.index', {}, '/admin/timetable') },
      { label: 'Discipline Cases', href: safeRoute('admin.discipline.index', {}, '/admin/discipline') },
    ]
  }

  // Teacher / Examiner / Secretary / Others
  if (role.value) {
    return [
      { label: 'Open School Dashboard', href: safeRoute('dashboard', {}, '/dashboard') },
      { label: 'View Students', href: safeRoute('admin.students.index', {}, '/admin/students') },
      { label: 'Communications', href: safeRoute('admin.communications.index', {}, '/admin/communications') },
    ]
  }

  // Guest actions
  return [
    { label: 'Sign In To This School', href: safeRoute('login', { school: props.school.slug }, '/login') },
    { label: 'Explore School Profile', href: safeRoute('schools.show', props.school.slug, `/schools/${props.school.slug || ''}`) },
  ]
})

const termHealth = computed(() => {
  const m = props.metrics || {}
  return [
    {
      label: 'Fee Target',
      value: `${Number(m.fee_collection_rate ?? 0)}%`,
      status: Number(m.fee_collection_rate ?? 0) >= 80 ? 'On Track' : 'Needs Attention',
    },
    {
      label: 'Attendance',
      value: `${Number(m.attendance_rate_today ?? 0)}%`,
      status: Number(m.attendance_rate_today ?? 0) >= 85 ? 'Stable' : 'Low',
    },
    {
      label: 'Exam Readiness',
      value: `${Number(m.exam_readiness_rate ?? 0)}%`,
      status: Number(m.exam_readiness_rate ?? 0) >= 75 ? 'On Track' : 'Needs Attention',
    },
  ]
})

const safeRoute = (name, params = {}, fallback = '#') => {
  try {
    if (typeof route === 'function') return route(name, params)
  } catch (error) {
    return fallback
  }
  return fallback
}
</script>

<template>
  <section class="mt-12 border-t border-white/10 pt-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h2 class="text-2xl font-semibold">School Overview</h2>
        <p class="mt-1 text-sm text-slate-300">
          {{ school.name }} workspace summary
        </p>
      </div>
      <p class="text-xs uppercase tracking-[0.22em] text-emerald-300">
        Logged in as: {{ roleLabel }}
      </p>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
      <div
        v-for="kpi in kpis"
        :key="kpi.key"
        class="border border-white/10 bg-white/[0.02] px-4 py-4"
      >
        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">{{ kpi.label }}</p>
        <p class="mt-2 text-2xl font-semibold">{{ kpi.value }}</p>
        <p class="mt-1 text-xs text-slate-400">{{ kpi.hint }}</p>
      </div>
    </div>
  </section>

  <section class="mt-10 border-t border-white/10 pt-8">
    <h3 class="text-xl font-semibold">Quick Actions</h3>
    <p class="mt-2 text-sm text-slate-300">
      Actions shown below are tailored to your role.
    </p>

    <div class="mt-4 flex flex-wrap gap-3">
      <Link
        v-for="action in quickActions"
        :key="action.label"
        :href="action.href"
        class="border border-white/20 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10"
      >
        {{ action.label }}
      </Link>
    </div>
  </section>

  <section class="mt-10 border-t border-white/10 pt-8">
    <h3 class="text-xl font-semibold">Term Health</h3>
    <p class="mt-2 text-sm text-slate-300">
      Operational snapshot for leadership review.
    </p>

    <div class="mt-4 grid gap-3 sm:grid-cols-3">
      <div
        v-for="item in termHealth"
        :key="item.label"
        class="border border-white/10 px-4 py-4"
      >
        <p class="text-sm text-slate-300">{{ item.label }}</p>
        <p class="mt-2 text-2xl font-semibold">{{ item.value }}</p>
        <p class="mt-1 text-xs text-slate-400">{{ item.status }}</p>
      </div>
    </div>
  </section>
</template>