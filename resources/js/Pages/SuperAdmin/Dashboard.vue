<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'

const props = defineProps({
  schools: {
    type: [Array, Object],
    default: () => [],
  },
  schoolPrincipals: {
    type: [Array, Object],
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({
      schools_total: 0,
      active_schools: 0,
      pending_schools: 0,
      schools_without_principal: 0,
      outstanding_billing: 0,
    }),
  },
  latestInvoices: {
    type: [Array, Object],
    default: () => [],
  },
})

const page = usePage()
const flash = computed(() => page.props?.flash ?? {})

const normalizeRows = (input) => {
  if (Array.isArray(input)) return input
  if (!input || typeof input !== 'object') return []
  if (Array.isArray(input.data)) return input.data
  return []
}

const schoolsRows = computed(() => normalizeRows(props.schools))
const principalRows = computed(() => normalizeRows(props.schoolPrincipals))
const invoiceRows = computed(() => normalizeRows(props.latestInvoices))

const formatKES = (value) => {
  const amount = Number(value ?? 0)
  return new Intl.NumberFormat('en-KE', {
    style: 'currency',
    currency: 'KES',
    maximumFractionDigits: 2,
  }).format(Number.isFinite(amount) ? amount : 0)
}

const stageBadgeClass = (stage) => {
  const s = String(stage ?? '').toLowerCase()
  if (s === 'full') return 'border-emerald-300/40 text-emerald-200 bg-emerald-400/10'
  if (s === 'half_only') return 'border-cyan-300/40 text-cyan-200 bg-cyan-400/10'
  if (s === 'quarter_only') return 'border-amber-300/40 text-amber-200 bg-amber-400/10'
  return 'border-rose-300/40 text-rose-200 bg-rose-400/10'
}

const schoolEditorId = ref(null)
const principalEditorId = ref(null)

const createSchoolForm = useForm({
  name: '',
  slug: '',
  county: '',
  email: '',
  phone: '',
  website: '',
  status: 'active',
  log_path: '',
})

const createPrincipalForm = useForm({
  school_id: '',
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const editSchoolForm = useForm({
  name: '',
  slug: '',
  county: '',
  email: '',
  phone: '',
  website: '',
  status: 'active',
  log_path: '',
})

const editPrincipalForm = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const openSchoolEditor = (school) => {
  schoolEditorId.value = school.id
  editSchoolForm.name = school.name ?? ''
  editSchoolForm.slug = school.slug ?? ''
  editSchoolForm.county = school.county ?? ''
  editSchoolForm.email = school.email ?? ''
  editSchoolForm.phone = school.phone ?? ''
  editSchoolForm.website = school.website ?? ''
  editSchoolForm.status = school.status ?? 'active'
  editSchoolForm.log_path = school.log_path ?? ''
}

const closeSchoolEditor = () => {
  schoolEditorId.value = null
  editSchoolForm.clearErrors()
}

const saveSchoolEdit = (schoolId) => {
  editSchoolForm.put(route('superadmin.schools.update', schoolId), {
    preserveScroll: true,
    onSuccess: () => closeSchoolEditor(),
  })
}

const openPrincipalEditor = (principal) => {
  principalEditorId.value = principal.id
  editPrincipalForm.name = principal.name ?? ''
  editPrincipalForm.email = principal.email ?? ''
  editPrincipalForm.password = ''
  editPrincipalForm.password_confirmation = ''
}

const closePrincipalEditor = () => {
  principalEditorId.value = null
  editPrincipalForm.clearErrors()
}

const savePrincipalEdit = (principalId) => {
  editPrincipalForm.put(route('superadmin.principals.update', principalId), {
    preserveScroll: true,
    onSuccess: () => closePrincipalEditor(),
  })
}

const submitSchool = () => {
  createSchoolForm.post(route('superadmin.schools.store'), {
    preserveScroll: true,
    onSuccess: () => createSchoolForm.reset(),
  })
}

const submitPrincipal = () => {
  createPrincipalForm.post(route('superadmin.schools.principal.store', createPrincipalForm.school_id), {
    preserveScroll: true,
    onSuccess: () => createPrincipalForm.reset(),
  })
}
</script>

<template>
  <Head title="Super Admin Dashboard" />

  <div class="min-h-screen bg-slate-950 text-slate-100">
    <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <section class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-white">Super Admin Dashboard</h1>
          <p class="mt-1 text-sm text-slate-300">Manage schools, principal accounts, and school-level billing access control.</p>
        </div>

        <div class="flex flex-wrap gap-2">
          <Link
            :href="route('superadmin.schools.register')"
            class="rounded-md border border-white/20 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-white/5"
          >
            Register School
          </Link>
          <Link
            :href="route('superadmin.billing.index')"
            class="rounded-md bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400"
          >
            Billing
          </Link>
        </div>
      </section>

      <section v-if="flash.success || flash.error" class="mb-6 space-y-2">
        <div v-if="flash.success" class="rounded-lg border border-emerald-400/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
          {{ flash.success }}
        </div>
        <div v-if="flash.error" class="rounded-lg border border-rose-400/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
          {{ flash.error }}
        </div>
      </section>

      <section class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
          <p class="text-xs uppercase tracking-wider text-slate-400">Schools</p>
          <p class="mt-2 text-xl font-bold text-white">{{ stats.schools_total }}</p>
        </article>
        <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
          <p class="text-xs uppercase tracking-wider text-slate-400">Active</p>
          <p class="mt-2 text-xl font-bold text-emerald-300">{{ stats.active_schools }}</p>
        </article>
        <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
          <p class="text-xs uppercase tracking-wider text-slate-400">Pending</p>
          <p class="mt-2 text-xl font-bold text-amber-300">{{ stats.pending_schools }}</p>
        </article>
        <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
          <p class="text-xs uppercase tracking-wider text-slate-400">No Principal</p>
          <p class="mt-2 text-xl font-bold text-rose-300">{{ stats.schools_without_principal }}</p>
        </article>
        <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
          <p class="text-xs uppercase tracking-wider text-slate-400">Outstanding Billing</p>
          <p class="mt-2 text-xl font-bold text-cyan-300">{{ formatKES(stats.outstanding_billing) }}</p>
        </article>
      </section>

      <section class="mb-8 grid gap-6 lg:grid-cols-2">
        <article class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
          <h2 class="text-lg font-semibold text-white">Create School</h2>
          <form class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="submitSchool">
            <input v-model="createSchoolForm.name" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm" placeholder="School Name" />
            <input v-model="createSchoolForm.slug" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm" placeholder="Slug" />
            <input v-model="createSchoolForm.county" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm" placeholder="County" />
            <input v-model="createSchoolForm.email" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm" placeholder="Email" />
            <input v-model="createSchoolForm.phone" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm" placeholder="Phone" />
            <input v-model="createSchoolForm.website" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm" placeholder="Website" />
            <select v-model="createSchoolForm.status" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm">
              <option value="active">active</option>
              <option value="pending">pending</option>
              <option value="inactive">inactive</option>
            </select>
            <input v-model="createSchoolForm.log_path" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm" placeholder="Logo path (optional)" />
            <button type="submit" class="md:col-span-2 w-fit rounded-md bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400">
              Save School
            </button>
          </form>
        </article>

        <article class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
          <h2 class="text-lg font-semibold text-white">Create Principal Account</h2>
          <form class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="submitPrincipal">
            <select v-model="createPrincipalForm.school_id" class="md:col-span-2 rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm">
              <option value="">Select school...</option>
              <option v-for="s in schoolsRows" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
            <input v-model="createPrincipalForm.name" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm" placeholder="Principal Name" />
            <input v-model="createPrincipalForm.email" type="email" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm" placeholder="Email" />
            <input v-model="createPrincipalForm.password" type="password" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm" placeholder="Password" />
            <input v-model="createPrincipalForm.password_confirmation" type="password" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm" placeholder="Confirm Password" />
            <button type="submit" class="md:col-span-2 w-fit rounded-md bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400">
              Save Principal
            </button>
          </form>
        </article>
      </section>

      <section class="mb-8 rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h2 class="mb-4 text-lg font-semibold text-white">Schools</h2>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-white/10 text-sm">
            <thead class="bg-slate-950/50 text-left text-slate-300">
              <tr>
                <th class="px-3 py-2">Name</th>
                <th class="px-3 py-2">County</th>
                <th class="px-3 py-2">Status</th>
                <th class="px-3 py-2">Billing %</th>
                <th class="px-3 py-2">Billing Stage</th>
                <th class="px-3 py-2">Override</th>
                <th class="px-3 py-2">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <template v-for="school in schoolsRows" :key="school.id">
                <tr>
                  <td class="px-3 py-2 text-white">{{ school.name }}</td>
                  <td class="px-3 py-2 text-slate-300">{{ school.county || '—' }}</td>
                  <td class="px-3 py-2 text-slate-300">{{ school.status || '—' }}</td>
                  <td class="px-3 py-2 text-cyan-300">{{ school.billing_paid_percent ?? 0 }}%</td>
                  <td class="px-3 py-2">
                    <span class="rounded border px-2 py-1 text-xs font-medium" :class="stageBadgeClass(school.billing_access_stage)">
                      {{ school.billing_access_stage || 'locked' }}
                    </span>
                  </td>
                  <td class="px-3 py-2 text-slate-300">{{ school.billing_access_override ? 'Yes' : 'No' }}</td>
                  <td class="px-3 py-2">
                    <div class="flex flex-wrap gap-2">
                      <button type="button" class="rounded border border-white/20 px-2 py-1 text-xs text-slate-200 hover:bg-white/5" @click="openSchoolEditor(school)">
                        Edit School
                      </button>
                      <Link :href="route('superadmin.schools.billing-access.edit', school.id)" class="rounded border border-cyan-300/40 px-2 py-1 text-xs text-cyan-200 hover:bg-cyan-400/10">
                        Manage Billing Access
                      </Link>
                    </div>
                  </td>
                </tr>

                <tr v-if="schoolEditorId === school.id" class="bg-white/[0.03]">
                  <td colspan="7" class="px-3 py-3">
                    <form class="grid gap-2 md:grid-cols-4" @submit.prevent="saveSchoolEdit(school.id)">
                      <input v-model="editSchoolForm.name" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-xs" placeholder="School Name" />
                      <input v-model="editSchoolForm.slug" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-xs" placeholder="Slug" />
                      <input v-model="editSchoolForm.county" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-xs" placeholder="County" />
                      <input v-model="editSchoolForm.email" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-xs" placeholder="Email" />
                      <input v-model="editSchoolForm.phone" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-xs" placeholder="Phone" />
                      <input v-model="editSchoolForm.website" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-xs" placeholder="Website" />
                      <select v-model="editSchoolForm.status" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-xs">
                        <option value="active">active</option>
                        <option value="pending">pending</option>
                        <option value="inactive">inactive</option>
                      </select>
                      <input v-model="editSchoolForm.log_path" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-xs" placeholder="Logo path" />
                      <div class="md:col-span-4 flex gap-2">
                        <button type="submit" class="rounded-md bg-cyan-500 px-3 py-2 text-xs font-semibold text-slate-950 hover:bg-cyan-400">Save</button>
                        <button type="button" class="rounded-md border border-white/20 px-3 py-2 text-xs hover:bg-white/5" @click="closeSchoolEditor">Cancel</button>
                      </div>
                    </form>
                  </td>
                </tr>
              </template>

              <tr v-if="!schoolsRows.length">
                <td colspan="7" class="px-3 py-6 text-center text-slate-400">No schools found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="mb-8 rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h2 class="mb-4 text-lg font-semibold text-white">Principal Accounts</h2>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-white/10 text-sm">
            <thead class="bg-slate-950/50 text-left text-slate-300">
              <tr>
                <th class="px-3 py-2">Name</th>
                <th class="px-3 py-2">Email</th>
                <th class="px-3 py-2">School</th>
                <th class="px-3 py-2">Role</th>
                <th class="px-3 py-2">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <template v-for="principal in principalRows" :key="principal.id">
                <tr>
                  <td class="px-3 py-2 text-white">{{ principal.name }}</td>
                  <td class="px-3 py-2 text-slate-300">{{ principal.email }}</td>
                  <td class="px-3 py-2 text-slate-300">{{ principal.school_name || principal.school?.name || '—' }}</td>
                  <td class="px-3 py-2 text-slate-300">{{ principal.role || 'principal' }}</td>
                  <td class="px-3 py-2">
                    <button type="button" class="rounded border border-white/20 px-2 py-1 text-xs hover:bg-white/5" @click="openPrincipalEditor(principal)">
                      Edit Principal
                    </button>
                  </td>
                </tr>

                <tr v-if="principalEditorId === principal.id" class="bg-white/[0.03]">
                  <td colspan="5" class="px-3 py-3">
                    <form class="grid gap-2 md:grid-cols-4" @submit.prevent="savePrincipalEdit(principal.id)">
                      <input v-model="editPrincipalForm.name" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-xs" placeholder="Name" />
                      <input v-model="editPrincipalForm.email" type="email" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-xs" placeholder="Email" />
                      <input v-model="editPrincipalForm.password" type="password" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-xs" placeholder="Password (optional)" />
                      <input v-model="editPrincipalForm.password_confirmation" type="password" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-xs" placeholder="Confirm password" />

                      <div class="md:col-span-4 flex gap-2">
                        <button type="submit" class="rounded-md bg-cyan-500 px-3 py-2 text-xs font-semibold text-slate-950 hover:bg-cyan-400">Save</button>
                        <button type="button" class="rounded-md border border-white/20 px-3 py-2 text-xs hover:bg-white/5" @click="closePrincipalEditor">Cancel</button>
                      </div>
                    </form>
                  </td>
                </tr>
              </template>

              <tr v-if="!principalRows.length">
                <td colspan="5" class="px-3 py-6 text-center text-slate-400">No principal accounts found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-white">Latest Invoices</h2>
          <Link :href="route('superadmin.billing.index')" class="text-sm text-cyan-300 hover:text-cyan-200">View all</Link>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-white/10 text-sm">
            <thead class="bg-slate-950/50 text-left text-slate-300">
              <tr>
                <th class="px-3 py-2">School</th>
                <th class="px-3 py-2">Reference</th>
                <th class="px-3 py-2">Amount</th>
                <th class="px-3 py-2">Status</th>
                <th class="px-3 py-2">Issued</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-for="invoice in invoiceRows" :key="invoice.id">
                <td class="px-3 py-2 text-white">{{ invoice.school_name || invoice.school?.name || '—' }}</td>
                <td class="px-3 py-2 text-slate-300">{{ invoice.reference_no || invoice.invoice_no || '—' }}</td>
                <td class="px-3 py-2 text-cyan-300">{{ formatKES(invoice.amount ?? invoice.total_amount ?? 0) }}</td>
                <td class="px-3 py-2 text-slate-300">{{ invoice.status || '—' }}</td>
                <td class="px-3 py-2 text-slate-300">{{ invoice.created_at || '—' }}</td>
              </tr>
              <tr v-if="!invoiceRows.length">
                <td colspan="5" class="px-3 py-6 text-center text-slate-400">No invoices available.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</template>