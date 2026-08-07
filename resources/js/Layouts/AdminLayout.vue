<script setup>
import { computed } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'

const page = usePage()

const normalizeRole = (value) => String(value || '').trim().toLowerCase().replace(/[\s-]+/g, '_')

const authUser = computed(() => page.props.auth?.user ?? null)
const pageRole = computed(() => normalizeRole(page.props.role ?? ''))
const role = computed(() => normalizeRole(authUser.value?.role ?? pageRole.value))

const logoutForm = useForm({})
const canSee = (roles) => roles.map(normalizeRole).includes(role.value)

const doLogout = () => {
  logoutForm.post('/logout')
}
</script>

<template>
  <div class="min-h-screen bg-slate-100 text-slate-900">
    <header class="border-b bg-slate-900 text-white">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
        <div>
          <p class="text-sm uppercase tracking-[0.2em] text-amber-300">Vigurungani Admin</p>
          <p class="text-xs text-slate-300">{{ authUser?.name || 'Guest' }} ({{ role || 'guest' }})</p>
        </div>

        <nav class="flex flex-wrap items-center gap-4 text-sm">
          <Link :href="route('admin.dashboard')" class="hover:text-amber-300">Dashboard</Link>

          <Link v-if="canSee(['principal','deputy_principal','dean','accountant','secretary'])" :href="route('admin.students.index')" class="hover:text-amber-300">Students</Link>
          <Link v-if="canSee(['principal','deputy_principal','dean','hod'])" :href="route('admin.programs.index')" class="hover:text-amber-300">Programs</Link>
          <Link v-if="canSee(['principal','deputy_principal','dean','hod'])" :href="route('admin.exams.index')" class="hover:text-amber-300">Exams</Link>

          <Link v-if="canSee(['principal','deputy_principal'])" :href="route('admin.staff.index')" class="hover:text-amber-300">Staff</Link>

          <Link v-if="canSee(['principal','accountant'])" :href="route('admin.finance.index')" class="hover:text-amber-300">Finance</Link>
          <Link v-if="canSee(['principal','accountant'])" :href="route('admin.student-finance.index')" class="hover:text-amber-300">Student Finance</Link>

          <Link v-if="canSee(['principal','accountant','store_keeper'])" :href="route('admin.store.index')" class="hover:text-amber-300">Store</Link>
          <Link v-if="canSee(['principal','deputy_principal','dean'])" :href="route('admin.timetable.index')" class="hover:text-amber-300">Timetable</Link>
          <Link v-if="canSee(['principal','secretary','dean'])" :href="route('admin.parents.index')" class="hover:text-amber-300">Parents</Link>
          <Link v-if="canSee(['principal','deputy_principal','dean','hod'])" :href="route('admin.sports.index')" class="hover:text-amber-300">Sports Dept</Link>
          <Link v-if="canSee(['principal','deputy_principal','dean'])" :href="route('admin.discipline.index')" class="hover:text-amber-300">Discipline</Link>
          <Link v-if="canSee(['principal','deputy_principal','secretary','dean'])" :href="route('admin.communications.index')" class="hover:text-amber-300">Messages</Link>

          <Link :href="route('home')" class="hover:text-amber-300">Website</Link>

          <button
            v-if="authUser"
            type="button"
            class="rounded border border-white/30 px-3 py-1 hover:bg-white/10"
            :disabled="logoutForm.processing"
            @click="doLogout"
          >
            {{ logoutForm.processing ? 'Logging out...' : 'Logout' }}
          </button>

          <Link v-else :href="route('login')" class="rounded border border-white/30 px-3 py-1 hover:bg-white/10">Login</Link>
        </nav>
      </div>
    </header>

    <main class="mx-auto max-w-7xl px-6 py-8">
      <slot />
    </main>
  </div>
</template>