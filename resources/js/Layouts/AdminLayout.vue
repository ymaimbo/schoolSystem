<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()

const normalizeRole = (value) =>
  String(value || '')
    .trim()
    .toLowerCase()
    .replace(/[\s-]+/g, '_')

const userRole = computed(() => {
  const direct = page.props?.auth?.user?.role
  const fallback = page.props?.role
  return normalizeRole(direct || fallback)
})

const hasRole = (...roles) => roles.includes(userRole.value)

const hasNamedRoute = (name) => {
  try {
    if (typeof route !== 'function') return false
    const ziggy = route()
    if (typeof ziggy?.has === 'function') return ziggy.has(name)
    route(name)
    return true
  } catch {
    return false
  }
}

const routeOrNull = (name, params = undefined) => {
  try {
    if (!hasNamedRoute(name)) return null
    return params === undefined ? route(name) : route(name, params)
  } catch {
    return null
  }
}

const examsHref = computed(() => {
  if (hasRole('class_teacher')) return routeOrNull('admin.class-room.index')
  if (hasRole('subject_teacher')) return routeOrNull('admin.subject-room.index')
  return routeOrNull('admin.exams.index')
})

const nav = computed(() => {
  const items = []

  const pushIf = (condition, label, routeName, params = undefined) => {
    if (!condition) return
    const href = routeOrNull(routeName, params)
    if (!href) return
    items.push({ label, href })
  }

  pushIf(true, 'Dashboard', 'admin.dashboard')

  pushIf(hasRole('principal', 'deputy_principal', 'dean', 'secretary', 'accountant'), 'Students', 'admin.students.index')
  pushIf(hasRole('principal', 'accountant'), 'Student Finance', 'admin.student-finance.index')

  if (hasRole('principal', 'deputy_principal', 'dean', 'hod', 'class_teacher', 'subject_teacher') && examsHref.value) {
    items.push({ label: 'Exams', href: examsHref.value })
  }

  pushIf(hasRole('principal', 'accountant'), 'Finance', 'admin.finance.index')
  pushIf(hasRole('principal', 'accountant'), 'Fee Structures', 'admin.finance.fee-structures.index')

  pushIf(hasRole('principal', 'accountant', 'store_keeper'), 'Store', 'admin.store.index')
  pushIf(hasRole('principal', 'deputy_principal', 'dean'), 'Timetable', 'admin.timetable.index')
  pushIf(hasRole('principal', 'secretary', 'dean'), 'Parents', 'admin.parents.index')
  pushIf(hasRole('principal', 'deputy_principal', 'dean'), 'Sports', 'admin.sports.index')
  pushIf(hasRole('principal', 'deputy_principal', 'dean'), 'Discipline', 'admin.discipline.index')
  pushIf(hasRole('principal', 'deputy_principal', 'secretary', 'dean'), 'Communications', 'admin.communications.index')
  pushIf(hasRole('principal', 'deputy_principal'), 'Staff', 'admin.staff.index')
  pushIf(hasRole('principal'), 'Class Teacher Assignments', 'admin.class-teacher-assignments.index')

  return items
})

const isActive = (href) => {
  try {
    return page.url === new URL(href, window.location.origin).pathname
  } catch {
    return false
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-100">
    <div class="mx-auto flex w-full max-w-[1600px]">
      <aside class="hidden w-64 shrink-0 border-r border-white/10 bg-slate-900/70 p-4 lg:block">
        <h2 class="mb-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Admin Menu</h2>

        <nav class="space-y-1">
          <Link
            v-for="item in nav"
            :key="item.label"
            :href="item.href"
            class="block rounded-md border border-transparent px-3 py-2 text-sm transition"
            :class="isActive(item.href) ? 'border-white/10 bg-slate-950 text-white' : 'text-slate-300 hover:border-white/10 hover:bg-white/5 hover:text-white'"
          >
            {{ item.label }}
          </Link>
        </nav>
      </aside>

      <main class="min-w-0 flex-1 p-4 lg:p-6">
        <slot />
      </main>
    </div>
  </div>
</template>