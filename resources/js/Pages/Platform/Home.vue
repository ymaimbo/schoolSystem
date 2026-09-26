<script setup>
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
  schools: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({ schools: 0, modules: 0, roles: 0 }),
  },
})

const advantages = [
  {
    title: 'All departments in one place',
    description:
      'Academics, finance, discipline, store, communication, and staff operations work in one connected platform.',
  },
  {
    title: 'Strong school-level data isolation',
    description:
      'Each school works in its own secure workspace with role-based access and controlled permissions.',
  },
  {
    title: 'Built for accountability',
    description:
      'Standardized workflows and clear records reduce manual errors and improve transparency for leadership.',
  },
  {
    title: 'Fast onboarding for teams',
    description:
      'Principals can create staff records, assign roles, and activate users without complex setup.',
  },
]

const modules = [
  'Student Information Management',
  'Exams & Result Processing',
  'Timetable & Class Operations',
  'Student Finance & Receipts',
  'Fee Structures & Vote Heads',
  'Store & Inventory Management',
  'Staff Directory & Login Roles',
  'Parent Communication',
  'Discipline & Sports Departments',
  'School Billing & Invoices',
]
</script>

<template>
  <Head title="School Operations Platform" />

  <div class="min-h-screen bg-slate-950 text-slate-100">
    <header class="border-b border-white/10 bg-slate-950/90 backdrop-blur">
      <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-4">
        <div>
          <p class="text-xs uppercase tracking-[0.25em] text-emerald-300">School Platform</p>
          <p class="text-sm font-semibold">Multi-School Management System</p>
        </div>

        <div class="flex items-center gap-3">
          <Link
            :href="route('login')"
            class="rounded-md border border-white/20 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10"
          >
            Sign In
          </Link>
        </div>
      </div>
    </header>

    <main class="mx-auto w-full max-w-7xl px-6 py-10 md:py-14">
      <section class="grid gap-8 rounded-2xl border border-white/10 bg-slate-900/70 p-8 md:grid-cols-2 md:p-10">
        <div>
          <p class="text-xs uppercase tracking-[0.28em] text-emerald-300">Simple. Secure. Scalable.</p>
          <h1 class="mt-3 text-3xl font-bold leading-tight text-white md:text-5xl">
            Run school operations from one intelligent dashboard.
          </h1>
          <p class="mt-4 max-w-xl text-base text-slate-300 md:text-lg">
            Replace disconnected spreadsheets and paperwork with one platform for academics, finance, communication,
            discipline, staffing, and reporting.
          </p>

          <div class="mt-7 flex flex-wrap gap-3">
            <Link
              :href="route('login')"
              class="rounded-md bg-emerald-500 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-emerald-400"
            >
              Login / Sign Up
            </Link>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 md:grid-cols-1 lg:grid-cols-3">
          <article class="rounded-xl border border-white/10 bg-slate-950/60 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Schools</p>
            <p class="mt-2 text-2xl font-bold text-emerald-300">{{ stats.schools }}</p>
          </article>
          <article class="rounded-xl border border-white/10 bg-slate-950/60 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Modules</p>
            <p class="mt-2 text-2xl font-bold text-cyan-300">{{ stats.modules }}</p>
          </article>
          <article class="rounded-xl border border-white/10 bg-slate-950/60 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Roles</p>
            <p class="mt-2 text-2xl font-bold text-indigo-300">{{ stats.roles }}</p>
          </article>
        </div>
      </section>

      <section class="mt-12">
        <h2 class="text-2xl font-bold text-white md:text-3xl">Why this system gives your school an advantage</h2>
        <p class="mt-2 max-w-3xl text-slate-300">
          Designed for African school operations with practical workflows, clear permissions, and reliable records.
        </p>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
          <article
            v-for="item in advantages"
            :key="item.title"
            class="rounded-xl border border-white/10 bg-slate-900/60 p-5"
          >
            <h3 class="text-lg font-semibold text-white">{{ item.title }}</h3>
            <p class="mt-2 text-sm text-slate-300">{{ item.description }}</p>
          </article>
        </div>
      </section>

      <section class="mt-12">
        <h2 class="text-2xl font-bold text-white md:text-3xl">Core modules</h2>
        <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="module in modules"
            :key="module"
            class="rounded-lg border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-slate-200"
          >
            {{ module }}
          </div>
        </div>
      </section>

      <section class="mt-12 rounded-2xl border border-emerald-300/25 bg-emerald-500/10 p-8 text-center">
        <h2 class="text-2xl font-bold text-white md:text-3xl">Ready to modernize your school operations?</h2>
        <p class="mt-2 text-slate-200">Start with secure access, structured workflows, and role-based control.</p>
        <div class="mt-6">
          <Link
            :href="route('login')"
            class="inline-flex rounded-md bg-emerald-400 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300"
          >
            Get Started Now
          </Link>
        </div>
      </section>
    </main>
  </div>
</template>