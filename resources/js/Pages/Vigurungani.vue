<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const logoImage = '/images/logo.png'

const props = defineProps({
  school: { type: Object, default: null },
})

const page = usePage()
const authUser = computed(() => page.props?.auth?.user ?? null)

const fallbackSchool = {
  name: 'Vigurungani Senior School',
  motto: 'Practical education for disciplined, capable futures.',
  logo_url: logoImage,
  location: 'Kinango Constituency, Kwale County, Kenya',
}

const school = computed(() => {
  const src = props.school ?? {}
  return {
    ...fallbackSchool,
    ...src,
    logo_url: logoImage,
    motto: src.motto || src.note || fallbackSchool.motto,
  }
})

const memberRoles = [
  'Principal',
  'Deputy Principal',
  'School Examiner',
  'Class Teacher',
  'Secretary',
  'Accountant',
  'Store Keeper',
]

const safeRoute = (name, fallback = '#') => {
  try {
    if (typeof route === 'function') return route(name)
  } catch (_err) {
    return fallback
  }
  return fallback
}
</script>

<template>
  <Head :title="school.name" />

  <div class="relative min-h-screen overflow-hidden bg-slate-950 text-slate-100">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_25%,rgba(16,185,129,0.14),transparent_38%),radial-gradient(circle_at_80%_20%,rgba(59,130,246,0.16),transparent_42%),linear-gradient(180deg,#020617_0%,#030a1f_45%,#020617_100%)]" />
    <div class="absolute inset-0 bg-[linear-gradient(to_bottom,rgba(2,6,23,0.55),rgba(2,6,23,0.9))]" />

    <main class="relative z-10 mx-auto flex min-h-screen max-w-4xl items-center px-6 py-16">
      <section class="w-full text-center" aria-labelledby="school-landing-title">
        <img
          :src="school.logo_url || logoImage"
          :alt="`${school.name} logo`"
          class="mx-auto h-24 w-24 object-contain sm:h-28 sm:w-28"
        />

        <p class="mt-6 text-xs uppercase tracking-[0.32em] text-emerald-300">School Portal</p>
        <h1 id="school-landing-title" class="mt-3 text-3xl font-semibold leading-tight sm:text-5xl">
          {{ school.name }}
        </h1>
        <p class="mx-auto mt-4 max-w-2xl text-base text-slate-300 sm:text-lg">
          {{ school.motto }}
        </p>

        <p class="mx-auto mt-2 max-w-2xl text-sm text-slate-400">
          This platform can serve multiple schools by changing logo and school profile details.
        </p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
          <Link
            :href="safeRoute('register', '/register')"
            class="border border-emerald-300 bg-emerald-300 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-emerald-200"
          >
            Register Role Member
          </Link>
          <Link
            :href="safeRoute('login', '/login')"
            class="border border-white/30 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10"
          >
            Login Role Member
          </Link>
          <Link
            v-if="authUser"
            :href="safeRoute('dashboard', '/dashboard')"
            class="border border-white/30 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10"
          >
            Go To Dashboard
          </Link>
        </div>

        <div class="mx-auto mt-8 max-w-3xl border-t border-white/10 pt-6">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Role Members</p>
          <p class="mt-3 text-sm text-slate-300">
            {{ memberRoles.join(' | ') }}
          </p>
        </div>
      </section>
    </main>
  </div>
</template>