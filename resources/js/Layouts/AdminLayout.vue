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

  // ── Dashboard ──────────────────────────────────────────────────
  pushIf(true, 'Dashboard', 'admin.dashboard')

  // ── Students ───────────────────────────────────────────────────
  pushIf(
    hasRole('principal', 'deputy_principal', 'dean', 'secretary', 'accountant', 'bursar'),
    'Students',
    'admin.students.index'
  )

  // ── Student Finance (School Fees) ──────────────────────────────
  pushIf(
    hasRole('principal', 'accountant'),
    'Student Finance',
    'admin.student-finance.index'
  )

  // ── Student Pocket Money (Bursar Wallet) ───────────────────────
  pushIf(
    hasRole('principal', 'deputy_principal', 'accountant', 'bursar'),
    'Pocket Money',
    'admin.pocket-money.index'
  )

  // ── Exams ──────────────────────────────────────────────────────
  if (
    hasRole('principal', 'deputy_principal', 'dean', 'hod', 'class_teacher', 'subject_teacher') &&
    examsHref.value
  ) {
    items.push({ label: 'Exams', href: examsHref.value })
  }

  // ── Finance (Income / Expense / Vouchers) ──────────────────────
  pushIf(
    hasRole('principal', 'accountant'),
    'Finance',
    'admin.finance.index'
  )

  // ── Fee Structures ─────────────────────────────────────────────
  pushIf(
    hasRole('principal', 'accountant'),
    'Fee Structures',
    'admin.finance.fee-structures.index'
  )

  // ── Billing (Platform Invoices) ────────────────────────────────
  pushIf(
    hasRole('principal', 'accountant'),
    'Billing',
    'admin.billing.index'
  )

  // ── Store / Inventory ──────────────────────────────────────────
  pushIf(
    hasRole('principal', 'accountant', 'bursar', 'store_keeper'),
    'Store',
    'admin.store.index'
  )

  // ── Timetable ──────────────────────────────────────────────────
  pushIf(
    hasRole('principal', 'deputy_principal', 'dean'),
    'Timetable',
    'admin.timetable.index'
  )

  // ── Parents ────────────────────────────────────────────────────
  pushIf(
    hasRole('principal', 'secretary', 'dean'),
    'Parents',
    'admin.parents.index'
  )

  // ── Sports ─────────────────────────────────────────────────────
  pushIf(
    hasRole('principal', 'deputy_principal', 'dean'),
    'Sports',
    'admin.sports.index'
  )

  // ── Discipline ─────────────────────────────────────────────────
  pushIf(
    hasRole('principal', 'deputy_principal', 'dean'),
    'Discipline',
    'admin.discipline.index'
  )

  // ── Communications ─────────────────────────────────────────────
  pushIf(
    hasRole('principal', 'deputy_principal', 'secretary', 'dean'),
    'Communications',
    'admin.communications.index'
  )

  // ── Staff ──────────────────────────────────────────────────────
  pushIf(
    hasRole('principal', 'deputy_principal'),
    'Staff',
    'admin.staff.index'
  )

  // ── Class Teacher Assignments ──────────────────────────────────
  pushIf(
    hasRole('principal'),
    'Class Teacher Assignments',
    'admin.class-teacher-assignments.index'
  )

  // ── Programs ───────────────────────────────────────────────────
  pushIf(
    hasRole('principal', 'deputy_principal', 'dean'),
    'Programs',
    'admin.programs.index'
  )

  return items
})

const isActive = (href) => {
  try {
    const pathname = new URL(href, window.location.origin).pathname
    // Exact match or starts with (for sub-pages like /pocket-money/statement/1)
    return page.url === pathname || page.url.startsWith(pathname + '/')
  } catch {
    return false
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-100">
    <div class="mx-auto flex w-full max-w-[1600px]">

      <!-- Desktop sidebar -->
      <aside class="hidden w-64 shrink-0 border-r border-white/10 bg-slate-900/70 p-4 lg:block">
        <h2 class="mb-4 text-xs font-semibold uppercase tracking-wider text-slate-400">
          Admin Menu
        </h2>

        <nav class="space-y-1">
          <Link
            v-for="item in nav"
            :key="item.label"
            :href="item.href"
            class="block rounded-md border border-transparent px-3 py-2 text-sm transition"
            :class="
              isActive(item.href)
                ? 'border-white/10 bg-slate-950 text-white font-medium'
                : 'text-slate-300 hover:border-white/10 hover:bg-white/5 hover:text-white'
            "
          >
            {{ item.label }}
          </Link>
        </nav>

        <!-- Role badge at bottom -->
        <div class="mt-6 border-t border-white/10 pt-4">
          <p class="text-[10px] uppercase tracking-wider text-slate-500">Logged in as</p>
          <p class="mt-1 text-xs font-medium text-slate-300 capitalize">
            {{ userRole.replace(/_/g, ' ') }}
          </p>
        </div>
      </aside>

      <!-- Mobile nav (hamburger-triggered, simple version) -->
      <div class="lg:hidden fixed top-0 left-0 right-0 z-40 border-b border-white/10 bg-slate-900/90 backdrop-blur px-4 py-3">
        <div class="flex items-center justify-between">
          <h1 class="text-sm font-bold text-white">Admin Panel</h1>
          <details class="relative">
            <summary class="cursor-pointer rounded-md border border-white/20 px-3 py-1.5 text-xs text-slate-200 hover:bg-white/5">
              Menu
            </summary>
            <nav class="absolute right-0 top-full mt-1 w-56 rounded-lg border border-white/10 bg-slate-900 p-2 shadow-xl z-50">
              <Link
                v-for="item in nav"
                :key="'mobile-' + item.label"
                :href="item.href"
                class="block rounded-md px-3 py-2 text-sm transition"
                :class="
                  isActive(item.href)
                    ? 'bg-slate-950 text-white font-medium'
                    : 'text-slate-300 hover:bg-white/5 hover:text-white'
                "
              >
                {{ item.label }}
              </Link>
            </nav>
          </details>
        </div>
      </div>

      <!-- Main content -->
      <main class="min-w-0 flex-1 p-4 lg:p-6 mt-14 lg:mt-0">
        <slot />
      </main>
    </div>
  </div>
</template>