<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
  schools: { type: Array, default: () => [] },
  schoolAdmins: { type: Array, default: () => [] },
  totals: {
    type: Object,
    default: () => ({ schools: 0, school_admins: 0, schools_without_principal: 0 }),
  },
  billing: {
    type: Object,
    default: () => ({
      enabled: false,
      total_invoiced: '0.00',
      outstanding: '0.00',
      overdue_count: 0,
      due_7_days: 0,
      recent_invoices: [],
      renewal_watchlist: [],
    }),
  },
})

const page = usePage()
const flashSuccess = computed(() => page.props?.flash?.success || '')
const flashError = computed(() => page.props?.flash?.error || '')

const editingSchoolId = ref(null)
const editingPrincipalId = ref(null)
const creatingPrincipalSchoolId = ref(null)

const schoolForm = useForm({
  name: '',
  slug: '',
  location: '',
  county: '',
  type: '',
  status: '',
  note: '',
  code: '',
  logo_path: '',
  hero_image_path: '',
})

const principalForm = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const createPrincipalForm = useForm({})

const openSchoolEditor = (school) => {
  editingSchoolId.value = school.id
  schoolForm.name = school.name || ''
  schoolForm.slug = school.slug || ''
  schoolForm.location = school.location || ''
  schoolForm.county = school.county || ''
  schoolForm.type = school.type || ''
  schoolForm.status = school.status || 'active'
  schoolForm.note = school.note || ''
  schoolForm.code = school.code || ''
  schoolForm.logo_path = school.logo_path || ''
  schoolForm.hero_image_path = school.hero_image_path || ''
  schoolForm.clearErrors()
}

const openPrincipalEditor = (principal) => {
  editingPrincipalId.value = principal.id
  principalForm.name = principal.name || ''
  principalForm.email = principal.email || ''
  principalForm.password = ''
  principalForm.password_confirmation = ''
  principalForm.clearErrors()
}

const createPrincipalForSchool = (school) => {
  if (!confirm(`Create a principal account for ${school.name}?`)) return

  creatingPrincipalSchoolId.value = school.id
  createPrincipalForm.post(route('superadmin.schools.principal.store', school.id), {
    preserveScroll: true,
    onFinish: () => {
      creatingPrincipalSchoolId.value = null
    },
  })
}

const cancelSchoolEdit = () => {
  editingSchoolId.value = null
  schoolForm.reset()
  schoolForm.clearErrors()
}

const cancelPrincipalEdit = () => {
  editingPrincipalId.value = null
  principalForm.reset()
  principalForm.clearErrors()
}

const submitSchoolUpdate = () => {
  schoolForm.put(route('superadmin.schools.update', editingSchoolId.value), {
    preserveScroll: true,
    onSuccess: () => cancelSchoolEdit(),
  })
}

const submitPrincipalUpdate = () => {
  principalForm.put(route('superadmin.principals.update', editingPrincipalId.value), {
    preserveScroll: true,
    onSuccess: () => cancelPrincipalEdit(),
  })
}

const statusClass = (status) => {
  if (status === 'active') return 'border-emerald-400/40 text-emerald-300 bg-emerald-500/10'
  if (status === 'inactive') return 'border-slate-400/40 text-slate-300 bg-slate-500/10'
  if (status === 'pending') return 'border-amber-400/40 text-amber-300 bg-amber-500/10'
  return 'border-white/20 text-slate-300'
}

const invoiceStatusClass = (status) => {
  if (status === 'paid') return 'text-emerald-300'
  if (status === 'overdue') return 'text-rose-300'
  if (status === 'partial') return 'text-amber-300'
  return 'text-slate-300'
}
</script>

<template>
  <Head title="Super Admin Dashboard" />

  <div class="min-h-screen bg-slate-950 text-slate-100">
    <main class="mx-auto w-full max-w-7xl px-6 py-12">
      <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p class="text-xs uppercase tracking-[0.28em] text-emerald-300">Global Control Center</p>
          <h1 class="mt-2 text-3xl font-semibold sm:text-4xl">Super Admin Dashboard</h1>
          <p class="mt-2 max-w-3xl text-sm text-slate-300">
            Manage schools, principals, billing operations, and renewal risk from one command view.
          </p>
        </div>

        <div class="flex flex-wrap gap-2">
          <Link :href="route('superadmin.schools.register')" class="border border-emerald-300 bg-emerald-300 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-200">
            Register New School
          </Link>

          <Link v-if="billing.enabled" :href="route('superadmin.billing.index')" class="border border-white/25 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">
            Open Billing Console
          </Link>
        </div>
      </header>

      <div v-if="flashSuccess" class="mt-6 border border-emerald-400/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
        {{ flashSuccess }}
      </div>
      <div v-if="flashError" class="mt-3 border border-rose-400/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
        {{ flashError }}
      </div>

      <section class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="border border-white/10 bg-white/[0.02] px-4 py-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Schools</p>
          <p class="mt-2 text-2xl font-semibold">{{ totals.schools }}</p>
        </div>
        <div class="border border-white/10 bg-white/[0.02] px-4 py-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Principals</p>
          <p class="mt-2 text-2xl font-semibold">{{ totals.school_admins }}</p>
        </div>
        <div class="border border-white/10 bg-white/[0.02] px-4 py-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">No Principal</p>
          <p class="mt-2 text-2xl font-semibold text-amber-300">{{ totals.schools_without_principal }}</p>
        </div>
        <div class="border border-white/10 bg-white/[0.02] px-4 py-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Outstanding Billing</p>
          <p class="mt-2 text-2xl font-semibold text-rose-300">KES {{ billing.outstanding }}</p>
        </div>
      </section>

      <section class="mt-8 border border-white/10 p-5">
        <h2 class="text-lg font-semibold">Schools</h2>
        <div class="mt-4 overflow-x-auto">
          <table class="min-w-full text-left text-sm">
            <thead class="border-b border-white/10 text-slate-300">
              <tr>
                <th class="px-3 py-2 font-medium">Name</th>
                <th class="px-3 py-2 font-medium">Slug</th>
                <th class="px-3 py-2 font-medium">County</th>
                <th class="px-3 py-2 font-medium">Type</th>
                <th class="px-3 py-2 font-medium">Status</th>
                <th class="px-3 py-2 font-medium">Principal</th>
                <th class="px-3 py-2 font-medium">Actions</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="school in schools" :key="school.id">
                <tr class="border-b border-white/5">
                  <td class="px-3 py-2">{{ school.name }}</td>
                  <td class="px-3 py-2 text-slate-300">{{ school.slug }}</td>
                  <td class="px-3 py-2 text-slate-300">{{ school.county || '-' }}</td>
                  <td class="px-3 py-2 text-slate-300">{{ school.type || '-' }}</td>
                  <td class="px-3 py-2">
                    <span class="inline-flex items-center border px-2 py-1 text-xs" :class="statusClass(school.status)">
                      {{ school.status || '-' }}
                    </span>
                  </td>
                  <td class="px-3 py-2">
                    <span v-if="school.has_principal" class="text-emerald-300">{{ school.principal_name }}</span>
                    <span v-else class="text-amber-300">Not assigned</span>
                  </td>
                  <td class="px-3 py-2">
                    <div class="flex flex-wrap gap-2">
                      <Link :href="route('schools.show', school.slug)" class="border border-white/20 px-2 py-1 hover:bg-white/10">
                        View Page
                      </Link>

                      <button class="border border-white/20 px-2 py-1 hover:bg-white/10" @click="openSchoolEditor(school)">
                        Edit
                      </button>

                      <button
                        v-if="!school.has_principal"
                        class="border border-amber-300/60 px-2 py-1 text-amber-200 hover:bg-amber-500/10"
                        @click="createPrincipalForSchool(school)"
                        :disabled="creatingPrincipalSchoolId === school.id"
                      >
                        {{ creatingPrincipalSchoolId === school.id ? 'Creating...' : 'Create Principal' }}
                      </button>
                    </div>
                  </td>
                </tr>

                <tr v-if="editingSchoolId === school.id" class="border-b border-white/10 bg-white/[0.03]">
                  <td colspan="7" class="px-3 py-4">
                    <form class="grid gap-3 sm:grid-cols-2" @submit.prevent="submitSchoolUpdate">
                      <input v-model="schoolForm.name" class="border border-white/20 bg-slate-900 px-3 py-2" placeholder="School Name" />
                      <input v-model="schoolForm.slug" class="border border-white/20 bg-slate-900 px-3 py-2" placeholder="Slug" />
                      <input v-model="schoolForm.location" class="border border-white/20 bg-slate-900 px-3 py-2" placeholder="Location" />
                      <input v-model="schoolForm.county" class="border border-white/20 bg-slate-900 px-3 py-2" placeholder="County" />
                      <input v-model="schoolForm.type" class="border border-white/20 bg-slate-900 px-3 py-2" placeholder="Type: day|boarding|mixed" />
                      <input v-model="schoolForm.status" class="border border-white/20 bg-slate-900 px-3 py-2" placeholder="Status: active|inactive|pending" />
                      <input v-model="schoolForm.code" class="border border-white/20 bg-slate-900 px-3 py-2" placeholder="Code" />
                      <input v-model="schoolForm.logo_path" class="border border-white/20 bg-slate-900 px-3 py-2" placeholder="Logo Path" />
                      <input v-model="schoolForm.hero_image_path" class="border border-white/20 bg-slate-900 px-3 py-2 sm:col-span-2" placeholder="Hero Image Path" />
                      <textarea v-model="schoolForm.note" rows="2" class="border border-white/20 bg-slate-900 px-3 py-2 sm:col-span-2" placeholder="Note / Motto" />

                      <div class="sm:col-span-2 flex gap-2">
                        <button type="submit" class="border border-emerald-300 bg-emerald-300 px-4 py-2 text-slate-950">Save School</button>
                        <button type="button" class="border border-white/20 px-4 py-2" @click="cancelSchoolEdit">Cancel</button>
                      </div>

                      <p v-for="(message, key) in schoolForm.errors" :key="key" class="sm:col-span-2 text-sm text-rose-300">
                        {{ message }}
                      </p>
                    </form>
                  </td>
                </tr>
              </template>

              <tr v-if="!schools.length">
                <td colspan="7" class="px-3 py-6 text-center text-slate-400">No schools available.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="mt-8 border border-white/10 p-5">
        <h2 class="text-lg font-semibold">Principal Accounts</h2>
        <div class="mt-4 overflow-x-auto">
          <table class="min-w-full text-left text-sm">
            <thead class="border-b border-white/10 text-slate-300">
              <tr>
                <th class="px-3 py-2 font-medium">Name</th>
                <th class="px-3 py-2 font-medium">Email</th>
                <th class="px-3 py-2 font-medium">School</th>
                <th class="px-3 py-2 font-medium">Created</th>
                <th class="px-3 py-2 font-medium">Actions</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="principal in schoolAdmins" :key="principal.id">
                <tr class="border-b border-white/5">
                  <td class="px-3 py-2">{{ principal.name }}</td>
                  <td class="px-3 py-2 text-slate-300">{{ principal.email }}</td>
                  <td class="px-3 py-2 text-slate-300">{{ principal.school?.name || '-' }}</td>
                  <td class="px-3 py-2 text-slate-400">{{ principal.created_at }}</td>
                  <td class="px-3 py-2">
                    <button class="border border-white/20 px-2 py-1 hover:bg-white/10" @click="openPrincipalEditor(principal)">
                      Edit
                    </button>
                  </td>
                </tr>

                <tr v-if="editingPrincipalId === principal.id" class="border-b border-white/10 bg-white/[0.03]">
                  <td colspan="5" class="px-3 py-4">
                    <form class="grid gap-3 sm:grid-cols-2" @submit.prevent="submitPrincipalUpdate">
                      <input v-model="principalForm.name" class="border border-white/20 bg-slate-900 px-3 py-2" placeholder="Principal Name" />
                      <input v-model="principalForm.email" class="border border-white/20 bg-slate-900 px-3 py-2" placeholder="Principal Email" />
                      <input v-model="principalForm.password" type="password" class="border border-white/20 bg-slate-900 px-3 py-2" placeholder="New Password (optional)" />
                      <input v-model="principalForm.password_confirmation" type="password" class="border border-white/20 bg-slate-900 px-3 py-2" placeholder="Confirm Password" />

                      <div class="sm:col-span-2 flex gap-2">
                        <button type="submit" class="border border-emerald-300 bg-emerald-300 px-4 py-2 text-slate-950">Save Principal</button>
                        <button type="button" class="border border-white/20 px-4 py-2" @click="cancelPrincipalEdit">Cancel</button>
                      </div>

                      <p v-for="(message, key) in principalForm.errors" :key="key" class="sm:col-span-2 text-sm text-rose-300">
                        {{ message }}
                      </p>
                    </form>
                  </td>
                </tr>
              </template>

              <tr v-if="!schoolAdmins.length">
                <td colspan="5" class="px-3 py-6 text-center text-slate-400">No principal accounts found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-if="billing.enabled" class="mt-8 border border-white/10 p-5">
        <h2 class="text-lg font-semibold">Recent Invoices</h2>
        <div class="mt-4 overflow-x-auto">
          <table class="min-w-full text-left text-sm">
            <thead class="border-b border-white/10 text-slate-300">
              <tr>
                <th class="px-3 py-2 font-medium">Ref</th>
                <th class="px-3 py-2 font-medium">School</th>
                <th class="px-3 py-2 font-medium">Item</th>
                <th class="px-3 py-2 font-medium">Invoiced</th>
                <th class="px-3 py-2 font-medium">Balance</th>
                <th class="px-3 py-2 font-medium">Due</th>
                <th class="px-3 py-2 font-medium">Status</th>
                <th class="px-3 py-2 font-medium">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="inv in billing.recent_invoices" :key="inv.id" class="border-b border-white/5">
                <td class="px-3 py-2">{{ inv.reference_no }}</td>
                <td class="px-3 py-2">{{ inv.school_name || '-' }}</td>
                <td class="px-3 py-2">{{ inv.item }}</td>
                <td class="px-3 py-2">KES {{ inv.invoiced_amount }}</td>
                <td class="px-3 py-2">KES {{ inv.balance_amount }}</td>
                <td class="px-3 py-2">{{ inv.due_date || '-' }}</td>
                <td class="px-3 py-2" :class="invoiceStatusClass(inv.status)">{{ inv.status }}</td>
                <td class="px-3 py-2">
                  <Link :href="route('superadmin.billing.edit', inv.id)" class="border border-white/20 px-2 py-1 hover:bg-white/10">Open</Link>
                </td>
              </tr>
              <tr v-if="!billing.recent_invoices?.length">
                <td colspan="8" class="px-3 py-6 text-center text-slate-400">No invoices available.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</template>