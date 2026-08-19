<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()

const nav = computed(() => [
  { label: 'Dashboard', href: route('admin.dashboard') },

  { label: 'Students', href: route('admin.students.index') },
  { label: 'Student Finance', href: route('admin.student-finance.index') },

  { label: 'Finance', href: route('admin.finance.index') },
  { label: 'Fee Structures', href: route('admin.finance.fee-structures.index') },

  { label: 'Store', href: route('admin.store.index') },
  { label: 'Timetable', href: route('admin.timetable.index') },
  { label: 'Parents', href: route('admin.parents.index') },
  { label: 'Sports', href: route('admin.sports.index') },
  { label: 'Discipline', href: route('admin.discipline.index') },
  { label: 'Communications', href: route('admin.communications.index') },
  { label: 'Staff', href: route('admin.staff.index') },
])

const isActive = (href) => page.url === new URL(href, window.location.origin).pathname
</script>

<template>
  <div class="min-h-screen bg-slate-100">
    <div class="mx-auto flex max-w-[1600px]">
      <aside class="hidden w-64 shrink-0 border-r border-slate-200 bg-white p-4 lg:block">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Admin Menu</h2>
        <nav class="space-y-1">
          <Link
            v-for="item in nav"
            :key="item.label"
            :href="item.href"
            class="block rounded px-3 py-2 text-sm"
            :class="isActive(item.href) ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100'"
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