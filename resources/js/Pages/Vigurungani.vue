<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import SchoolOverview from '@/Components/SchoolOverview.vue'

const props = defineProps({
  school: { type: Object, default: null },
  metrics: { type: Object, default: () => ({}) },
})

const page = usePage()
const authUser = computed(() => page.props?.auth?.user ?? null)
const isLoggedIn = computed(() => !!authUser.value)

const defaultLogo = '/images/logo.png'

const school = computed(() => {
  const src = props.school ?? {}
  return {
    id: src.id ?? null,
    name: src.name || 'School Portal',
    slug: src.slug || null,
    location: src.location || 'Location not provided',
    county: src.county || null,
    type: src.type || null,
    status: src.status || null,
    motto: src.motto || src.note || 'A disciplined, data-driven school community focused on growth and accountability.',
    logo_url: src.logo_url || src.logo_path || defaultLogo,
  }
})

const userRole = computed(() => String(authUser.value?.role || '').toLowerCase().trim())
const isSuperAdmin = computed(() => userRole.value === 'super_admin')

const schoolRoles = [
  'school_admin',
  'principal',
  'deputy_principal',
  'dean',
  'hod',
  'school_examiner',
  'class_teacher',
  'secretary',
  'accountant',
  'store_keeper',
]

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
  <Head :title="school.name" />

  <div class="relative min-h-screen overflow-hidden bg-slate-950 text-slate-100">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_25%,rgba(16,185,129,0.16),transparent_38%),radial-gradient(circle_at_80%_20%,rgba(59,130,246,0.16),transparent_42%),linear-gradient(180deg,#020617_0%,#030a1f_45%,#020617_100%)]" />
    <div class="absolute inset-0 bg-linear-to-b from-slate-950/70 via-slate-950/35 to-slate-950/85" />

    <main class="relative z-10 mx-auto w-full max-w-5xl px-6 py-16">
      <section class="text-center" aria-labelledby="school-page-title">
        <img
          :src="school.logo_url"
          :alt="`${school.name} logo`"
          class="mx-auto h-24 w-24 object-contain sm:h-28 sm:w-28"
        />

        <p class="mt-6 text-xs uppercase tracking-[0.28em] text-emerald-300">School Portal</p>
        <h1 id="school-page-title" class="mt-3 text-3xl font-semibold leading-tight sm:text-5xl">
          {{ school.name }}
        </h1>
        <p class="mx-auto mt-4 max-w-3xl text-base text-slate-300 sm:text-lg">
          {{ school.motto }}
        </p>
        <p class="mx-auto mt-2 max-w-3xl text-sm text-slate-400">
          {{ school.location }}
          <span v-if="school.county">, {{ school.county }}</span>
        </p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
          <Link
            v-if="isSuperAdmin"
            :href="safeRoute('superadmin.schools.register', {}, '/super-admin/schools/register')"
            class="border border-emerald-300 bg-emerald-300 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-emerald-200"
          >
            Register New School
          </Link>

          <Link
            v-if="isLoggedIn && !isSuperAdmin"
            :href="safeRoute('dashboard', {}, '/dashboard')"
            class="border border-white/25 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10"
          >
            Open My School Dashboard
          </Link>

          <Link
            v-if="isSuperAdmin"
            :href="safeRoute('superadmin.dashboard', {}, '/super-admin/dashboard')"
            class="border border-white/25 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10"
          >
            Open Super Admin Dashboard
          </Link>

          <Link
            v-if="!isLoggedIn"
            :href="safeRoute('login', { school: school.slug }, '/login')"
            class="border border-white/25 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10"
          >
            Sign In To This School
          </Link>
        </div>
      </section>

      <SchoolOverview
        :school="school"
        :auth-user="authUser"
        :metrics="metrics"
      />

      <section class="mt-10 border-t border-white/10 pt-8">
        <h2 class="text-2xl font-semibold">About This System</h2>
        <p class="mt-3 text-slate-300">
          This school portal is part of a multi-school system that supports structured workflows for academics,
          operations, and administration. Each school has its own secured context while still operating within
          a shared platform standard.
        </p>
      </section>

      <section class="mt-10 border-t border-white/10 pt-8">
        <h2 class="text-2xl font-semibold">Key Benefits</h2>
        <div class="mt-4 grid gap-3 text-sm text-slate-300 sm:grid-cols-2">
          <p>Role based access with clear accountability</p>
          <p>School scoped data for privacy and correctness</p>
          <p>Integrated modules for daily operations</p>
          <p>Improved communication with parents and staff</p>
          <p>Faster reporting and decision making</p>
          <p>Scalable onboarding for additional schools</p>
        </div>
      </section>

      <section class="mt-10 border-t border-white/10 pt-8">
        <h2 class="text-2xl font-semibold">School Roles In This Portal</h2>
        <p class="mt-3 text-slate-300">
          Access inside this school is controlled by assigned roles:
        </p>
        <p class="mt-3 text-sm text-slate-200">
          {{ schoolRoles.join(', ') }}
        </p>
      </section>
    </main>
  </div>
</template>