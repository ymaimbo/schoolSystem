<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed, onMounted, onUnmounted, ref } from 'vue'

const props = defineProps({
  school: { type: Object, default: null },
  programs: { type: Array, default: () => [] },
  leadershipTeam: { type: Array, default: () => [] },
})

const page = usePage()
const authUser = computed(() => page.props?.auth?.user ?? null)
const userRole = computed(() => String(authUser.value?.role ?? '').trim().toLowerCase())
const logoutForm = useForm({})

const doLogout = () => {
  logoutForm.post('/logout')
}

const fallbackSchool = {
  name: 'Vigurungani Senior School',
  location: 'Kinango Constituency, Kwale County, Kenya',
  county: 'Kwale County',
  type: 'Public Mixed Boarding School',
  status: 'Listed in the official register of secondary schools in Kwale County.',
  note: 'Focused on academic excellence, leadership, and practical skill development.',
  code: '2109104',
  logo_url: '/images/vigurungani-logo.png',
  hero_image_url: '/images/kinango-campus.jpg',
}

const fallbackPrograms = [
  {
    id: 1,
    title: 'Sports Excellence',
    summary: 'Structured coaching and inter-school fixtures for basketball, football, and volleyball.',
    details: 'The sports program builds discipline, teamwork, and confidence through consistent training.',
    image_url: '/images/school-sports.jpg',
  },
  {
    id: 2,
    title: 'Science and Technology Program',
    summary: 'Hands-on learning in science, innovation, and practical technology skills.',
    details: 'Inquiry-based laboratory learning and technology projects.',
    image_url: '/images/kinango-campus.jpg',
  },
  {
    id: 3,
    title: 'Agriculture Program',
    summary: 'Applied agriculture learning with a functional chicken pen house.',
    details: 'Learners engage in poultry management and agribusiness basics as practical training.',
    image_url: '/images/chicken-pen-program.jpg',
  },
]

const fallbackLeadership = [
  {
    id: 1,
    name: 'Abass Ulaya',
    role: 'Principal',
    department: 'School Administration',
    bio: 'Provides strategic leadership and oversees academic and co-curricular excellence across the school.',
    image_url: '/images/leadership/principal-placeholder.jpg',
  },
  {
    id: 2,
    name: 'Deputy Principal Team',
    role: 'Deputy Principal',
    department: 'Academics and Student Affairs',
    bio: 'Coordinates academic programs, examinations, and student welfare operations.',
    image_url: '/images/leadership/deputy-placeholder.jpg',
  },
  {
    id: 3,
    name: 'Heads of Department',
    role: 'Departmental Leadership',
    department: 'Sciences, Humanities, Languages, Sports, Agriculture',
    bio: 'Lead curriculum planning, mentoring, and instructional quality in each subject area.',
    image_url: '/images/leadership/hod-placeholder.jpg',
  },
]

const modules = [
  {
    title: 'Students Management',
    text: 'Admission records, class placement, parent details, and quick updates from admin dashboards.',
  },
  {
    title: 'Finance and Bursary',
    text: 'Income and expense tracking, category filters, and financial balance monitoring.',
  },
  {
    title: 'Exams Management',
    text: 'Exam setup, 8-4-4 and CBC result entry, and performance oversight.',
  },
  {
    title: 'Store Management',
    text: 'Inventory records, stock movement, and operational supply monitoring.',
  },
]

const school = computed(() => {
  const src = props.school ?? {}
  return {
    ...fallbackSchool,
    ...src,
    logo_url: src.logo_url || src.logo_path || fallbackSchool.logo_url,
    hero_image_url: src.hero_image_url || src.hero_image_path || fallbackSchool.hero_image_url,
  }
})

const programs = computed(() => {
  if (Array.isArray(props.programs) && props.programs.length) return props.programs
  return fallbackPrograms
})

const leadership = computed(() => {
  const list = Array.isArray(props.leadershipTeam) && props.leadershipTeam.length ? props.leadershipTeam : fallbackLeadership
  return list.map((member) => ({
    ...member,
    image_url: member.image_url || '/images/leadership/placeholder.jpg',
  }))
})

const hasRoleAccess = (allowedRoles) => {
  if (!authUser.value) return false
  return allowedRoles.includes(userRole.value)
}

const dashboardLinks = computed(() => {
  const links = [
    {
      key: 'dashboard',
      label: 'Dashboard',
      href: route('dashboard'),
      roles: ['principal', 'deputy_principal', 'hod', 'accountant', 'store_keeper', 'secretary'],
    },
    {
      key: 'students',
      label: 'Students',
      href: route('admin.students.index'),
      roles: ['principal', 'deputy_principal', 'accountant', 'secretary'],
    },
    {
      key: 'programs',
      label: 'Programs',
      href: route('admin.programs.index'),
      roles: ['principal', 'deputy_principal', 'hod'],
    },
    {
      key: 'exams',
      label: 'Exams',
      href: route('admin.exams.index'),
      roles: ['principal', 'deputy_principal', 'hod'],
    },
    {
      key: 'finance',
      label: 'Finance',
      href: route('admin.finance.index'),
      roles: ['principal', 'accountant'],
    },
    {
      key: 'store',
      label: 'Store',
      href: route('admin.store.index'),
      roles: ['principal', 'accountant', 'store_keeper'],
    },
  ]

  if (!authUser.value) return []
  return links.filter((item) => hasRoleAccess(item.roles))
})

const schoolProfileRows = computed(() => [
  { label: 'Location', value: school.value.location },
  { label: 'County', value: school.value.county },
  { label: 'School Type', value: school.value.type },
  { label: 'School Code', value: school.value.code },
])

const revealElements = ref([])
let observer = null

onMounted(() => {
  revealElements.value = Array.from(document.querySelectorAll('[data-reveal]'))
  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) entry.target.classList.add('is-visible')
      })
    },
    { threshold: 0.2 },
  )

  revealElements.value.forEach((el) => observer.observe(el))
})

onUnmounted(() => {
  if (observer) observer.disconnect()
})
</script>

<template>
  <Head :title="school.name" />

  <div class="min-h-screen bg-slate-950 text-slate-100">
    <header class="fixed inset-x-0 top-0 z-40 border-b border-white/10 bg-slate-950/90 backdrop-blur">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
        <div class="flex items-center gap-3">
          <img :src="school.logo_url" alt="School logo" class="h-10 w-10 object-contain" />
          <div>
            <p class="text-xs uppercase tracking-[0.2em] text-emerald-300">Vigurungani</p>
            <p class="text-sm font-semibold">{{ school.name }}</p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <template v-if="authUser">
            <span class="text-xs text-slate-300">Hi, {{ authUser.name }}</span>
            <Link
              v-for="link in dashboardLinks"
              :key="link.key"
              :href="link.href"
              class="border-b border-white/30 pb-1 text-xs font-medium hover:text-white/70"
            >
              {{ link.label }}
            </Link>

            <button
              type="button"
              class="rounded-md border border-white/20 px-3 py-2 text-xs font-medium hover:bg-white/10"
              @click="doLogout"
            >
              {{ logoutForm.processing ? 'Logging out...' : 'Logout' }}
            </button>
          </template>

          <template v-else>
            <Link :href="route('login')" class="border-b border-white/30 pb-1 text-sm font-medium hover:text-white/70">Login</Link>
          </template>
        </div>
      </div>
    </header>

    <main>
      <section class="relative min-h-screen overflow-hidden pt-24">
        <img :src="school.hero_image_url" alt="Vigurungani campus" class="absolute inset-0 h-full w-full object-cover" />
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/70 to-slate-950/40" />

        <div class="relative mx-auto flex min-h-screen max-w-7xl items-end px-6 pb-16">
          <div class="max-w-3xl" data-reveal>
            <p class="text-sm uppercase tracking-[0.3em] text-emerald-300">Vigurungani Senior School</p>
            <h1 class="mt-3 text-4xl font-semibold leading-tight sm:text-6xl">
              Practical education for disciplined, capable futures.
            </h1>
            <p class="mt-4 max-w-2xl text-lg text-slate-200">{{ school.status }}</p>
            <p class="mt-2 max-w-2xl text-slate-300">{{ school.note }}</p>

            <div class="mt-8 flex flex-wrap gap-3">
              <a href="#programs" class="bg-emerald-400 px-6 py-3 text-sm font-semibold text-slate-950 hover:bg-emerald-300">
                Explore Programs
              </a>
              <a href="#leadership" class="border border-white/30 px-6 py-3 text-sm font-semibold hover:bg-white/10">
                Meet Leadership
              </a>
            </div>
          </div>
        </div>
      </section>

      <section class="mx-auto max-w-7xl px-6 py-20">
        <div class="mb-10" data-reveal>
          <p class="text-xs uppercase tracking-[0.25em] text-emerald-300">School Profile</p>
          <h2 class="mt-3 text-3xl font-semibold">One institution, one direction</h2>
          <p class="mt-3 max-w-2xl text-slate-300">
            Key details used for registration, discipline, accountability, and school wide growth.
          </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div v-for="row in schoolProfileRows" :key="row.label" class="border border-white/10 p-4" data-reveal>
            <p class="text-xs uppercase tracking-wider text-slate-400">{{ row.label }}</p>
            <p class="mt-2 text-base font-medium text-white">{{ row.value }}</p>
          </div>
        </div>
      </section>

      <section id="tools" class="bg-slate-900/60 py-20">
        <div class="mx-auto max-w-7xl px-6">
          <div class="mb-10" data-reveal>
            <p class="text-xs uppercase tracking-[0.25em] text-emerald-300">School System Modules</p>
            <h2 class="mt-3 text-3xl font-semibold">Operations built for scale</h2>
            <p class="mt-3 max-w-2xl text-slate-300">
              Core modules keep records, academics, finance, examinations, and supply flow organized.
            </p>
          </div>

          <div class="grid gap-8 md:grid-cols-2">
            <div v-for="module in modules" :key="module.title" data-reveal>
              <h3 class="text-xl font-semibold">{{ module.title }}</h3>
              <p class="mt-2 text-slate-300">{{ module.text }}</p>
            </div>
          </div>
        </div>
      </section>

      <section id="programs" class="mx-auto max-w-7xl px-6 py-20">
        <div class="mb-10" data-reveal>
          <p class="text-xs uppercase tracking-[0.25em] text-emerald-300">Programs</p>
          <h2 class="mt-3 text-3xl font-semibold">Learner pathways with practical outcomes</h2>
          <p class="mt-3 max-w-2xl text-slate-300">
            Academic, sports, science, and agriculture programs for complete learner development.
          </p>
        </div>

        <div class="grid gap-10 md:grid-cols-3">
          <article v-for="program in programs" :key="program.id" class="program-item" data-reveal>
            <img :src="program.image_url" :alt="program.title" class="h-64 w-full object-cover" />
            <h3 class="mt-4 text-xl font-semibold">{{ program.title }}</h3>
            <p class="mt-2 text-slate-300">{{ program.summary }}</p>
            <p class="mt-3 text-slate-200">{{ program.details }}</p>
          </article>
        </div>
      </section>

      <section id="leadership" class="bg-slate-900/60 py-20">
        <div class="mx-auto max-w-7xl px-6">
          <div class="mb-10" data-reveal>
            <p class="text-xs uppercase tracking-[0.25em] text-emerald-300">Leadership</p>
            <h2 class="mt-3 text-3xl font-semibold">Experienced school leadership</h2>
            <p class="mt-3 max-w-2xl text-slate-300">Guiding learners through strong values and quality instruction.</p>
          </div>

          <div class="grid gap-10 md:grid-cols-3">
            <article v-for="member in leadership" :key="member.id" class="member-item" data-reveal>
              <img :src="member.image_url" :alt="member.name" class="h-72 w-full object-cover" />
              <p class="mt-4 text-xs uppercase tracking-wider text-emerald-300">{{ member.role }}</p>
              <h3 class="mt-1 text-xl font-semibold">{{ member.name }}</h3>
              <p class="mt-2 text-sm text-slate-300">{{ member.department }}</p>
              <p class="mt-3 text-slate-200">{{ member.bio }}</p>
            </article>
          </div>
        </div>
      </section>
    </main>

    <footer class="border-t border-white/10 py-8">
      <div class="mx-auto max-w-7xl px-6 text-sm text-slate-400 md:flex md:items-center md:justify-between">
        <p>{{ school.name }}</p>
        <p class="mt-2 md:mt-0">{{ school.location }}</p>
      </div>
    </footer>
  </div>
</template>

<style scoped>
[data-reveal] {
  opacity: 0;
  transform: translateY(18px);
  transition: opacity 0.6s ease, transform 0.6s ease;
}

[data-reveal].is-visible {
  opacity: 1;
  transform: translateY(0);
}
</style>