<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  structures: { type: [Object, Array], default: () => ({ data: [] }) },
  voteHeads: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
})

const page = usePage()
const flash = computed(() => page.props?.flash ?? {})

const endpoints = {
  store: '/admin/finance/fee-structures',
  edit: (id) => `/admin/finance/fee-structures/${id}/edit`,
  destroy: (id) => `/admin/finance/fee-structures/${id}`,
}

const structureRows = computed(() => {
  if (Array.isArray(props.structures?.data)) return props.structures.data
  if (Array.isArray(props.structures)) return props.structures
  return []
})

const voteHeadRows = computed(() => (Array.isArray(props.voteHeads) ? props.voteHeads : []))

const createDefaults = () => ({
  name: '',
  year: new Date().getFullYear(),
  category: 'day_scholar',
  class_level: '',
  notes: '',
  lines: [],
})

const createForm = useForm(createDefaults())

const lineTemplate = () => ({
  vote_head_id: '',
  govt_capitation_amount: 0,
  parent_total_amount: 0,
  term1_amount: 0,
  term2_amount: 0,
  term3_amount: 0,
  sort_order: createForm.lines.length + 1,
})

const addLine = () => createForm.lines.push(lineTemplate())

const removeLine = (idx) => {
  createForm.lines.splice(idx, 1)
  createForm.lines = createForm.lines.map((line, i) => ({
    ...line,
    sort_order: i + 1,
  }))
}

const voteHeadCode = (id) => voteHeadRows.value.find((v) => Number(v.id) === Number(id))?.code ?? ''

const fmt = (v) =>
  Number(v || 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })

const grandCreateTotals = computed(() => {
  return (createForm.lines || []).reduce(
    (acc, r) => {
      acc.govt += Number(r.govt_capitation_amount || 0)
      acc.parent += Number(r.parent_total_amount || 0)
      acc.t1 += Number(r.term1_amount || 0)
      acc.t2 += Number(r.term2_amount || 0)
      acc.t3 += Number(r.term3_amount || 0)
      return acc
    },
    { govt: 0, parent: 0, t1: 0, t2: 0, t3: 0 },
  )
})

const submitCreate = () => {
  createForm
    .transform((data) => ({
      ...data,
      year: Number(data.year),
      lines: (data.lines || []).map((line, index) => ({
        ...line,
        vote_head_id: line.vote_head_id ? Number(line.vote_head_id) : null,
        govt_capitation_amount: Number(line.govt_capitation_amount || 0),
        parent_total_amount: Number(line.parent_total_amount || 0),
        term1_amount: Number(line.term1_amount || 0),
        term2_amount: Number(line.term2_amount || 0),
        term3_amount: Number(line.term3_amount || 0),
        sort_order: Number(line.sort_order || index + 1),
      })),
    }))
    .post(endpoints.store, {
      preserveScroll: true,
      onSuccess: () => {
        createForm.reset()
        Object.assign(createForm, createDefaults())
      },
    })
}

const deleteStructure = (id) => {
  if (!confirm('Delete this fee structure?')) return
  router.delete(endpoints.destroy(id), { preserveScroll: true })
}

const structureGovtTotal = (s) =>
  Number(s.govt_total ?? s.total_govt ?? s.govt_capitation_total ?? 0)

const structureParentTotal = (s) =>
  Number(s.parent_total ?? s.total_parent ?? s.parent_amount_total ?? 0)

const structureAccountsCount = (s) =>
  Number(s.fee_accounts_count ?? s.accounts_count ?? s.students_count ?? 0)
</script>

<template>
  <Head title="Fee Structures" />

  <AdminLayout>
    <main class="min-h-screen bg-slate-950 text-slate-100">
      <div class="mx-auto w-full max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
        <section class="rounded-2xl border border-white/10 bg-slate-900/70 p-6">
          <h1 class="text-2xl font-bold tracking-tight text-white">Fee Structures</h1>
          <p class="mt-1 text-sm text-slate-300">
            Configure structure templates by vote head, capitation, and term distribution.
          </p>
        </section>

        <section v-if="flash.success || flash.error" class="space-y-2">
          <div
            v-if="flash.success"
            class="rounded-lg border border-emerald-400/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200"
          >
            {{ flash.success }}
          </div>
          <div
            v-if="flash.error"
            class="rounded-lg border border-rose-400/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200"
          >
            {{ flash.error }}
          </div>
        </section>

        <section class="rounded-2xl border border-white/10 bg-slate-900/70 p-6">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-white">Create Fee Structure</h2>
            <button
              type="button"
              class="rounded-md border border-white/20 px-3 py-1.5 text-xs text-slate-200 hover:bg-white/5"
              @click="addLine"
            >
              + Add Line
            </button>
          </div>

          <form class="space-y-5" @submit.prevent="submitCreate">
            <div class="grid gap-3 md:grid-cols-2">
              <input
                v-model="createForm.name"
                type="text"
                placeholder="Structure name (e.g. 2026 Day Scholars)"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
              />
              <input
                v-model.number="createForm.year"
                type="number"
                min="2020"
                max="2100"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
              />
              <select
                v-model="createForm.category"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
              >
                <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
              </select>
              <input
                v-model="createForm.class_level"
                type="text"
                placeholder="Class level (optional)"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
              />
            </div>

            <textarea
              v-model="createForm.notes"
              rows="2"
              placeholder="Notes (optional)"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            />

            <div class="overflow-x-auto rounded-xl border border-white/10">
              <table class="min-w-full text-sm">
                <thead class="bg-slate-950/60 text-left text-slate-300">
                  <tr>
                    <th class="px-3 py-2">Vote Head</th>
                    <th class="px-3 py-2">Govt Capitation</th>
                    <th class="px-3 py-2">Parent Total</th>
                    <th class="px-3 py-2">T1</th>
                    <th class="px-3 py-2">T2</th>
                    <th class="px-3 py-2">T3</th>
                    <th class="px-3 py-2">Order</th>
                    <th class="px-3 py-2">Action</th>
                  </tr>
                </thead>

                <tbody class="divide-y divide-white/10">
                  <tr v-for="(line, idx) in createForm.lines" :key="idx">
                    <td class="px-3 py-2">
                      <select
                        v-model="line.vote_head_id"
                        class="w-44 rounded border border-white/15 bg-slate-950 px-2 py-1 text-sm text-slate-100"
                      >
                        <option value="">Select</option>
                        <option v-for="vh in voteHeadRows" :key="vh.id" :value="vh.id">
                          {{ vh.code }} - {{ vh.name }}
                        </option>
                      </select>
                      <p class="mt-1 text-[11px] text-slate-400">{{ voteHeadCode(line.vote_head_id) }}</p>
                    </td>

                    <td class="px-3 py-2">
                      <input
                        v-model.number="line.govt_capitation_amount"
                        type="number"
                        min="0"
                        step="0.01"
                        class="w-32 rounded border border-white/15 bg-slate-950 px-2 py-1 text-sm text-slate-100"
                      />
                    </td>
                    <td class="px-3 py-2">
                      <input
                        v-model.number="line.parent_total_amount"
                        type="number"
                        min="0"
                        step="0.01"
                        class="w-32 rounded border border-white/15 bg-slate-950 px-2 py-1 text-sm text-slate-100"
                      />
                    </td>
                    <td class="px-3 py-2">
                      <input
                        v-model.number="line.term1_amount"
                        type="number"
                        min="0"
                        step="0.01"
                        class="w-24 rounded border border-white/15 bg-slate-950 px-2 py-1 text-sm text-slate-100"
                      />
                    </td>
                    <td class="px-3 py-2">
                      <input
                        v-model.number="line.term2_amount"
                        type="number"
                        min="0"
                        step="0.01"
                        class="w-24 rounded border border-white/15 bg-slate-950 px-2 py-1 text-sm text-slate-100"
                      />
                    </td>
                    <td class="px-3 py-2">
                      <input
                        v-model.number="line.term3_amount"
                        type="number"
                        min="0"
                        step="0.01"
                        class="w-24 rounded border border-white/15 bg-slate-950 px-2 py-1 text-sm text-slate-100"
                      />
                    </td>
                    <td class="px-3 py-2">
                      <input
                        v-model.number="line.sort_order"
                        type="number"
                        min="1"
                        step="1"
                        class="w-20 rounded border border-white/15 bg-slate-950 px-2 py-1 text-sm text-slate-100"
                      />
                    </td>
                    <td class="px-3 py-2">
                      <button type="button" class="text-rose-300 hover:text-rose-200" @click="removeLine(idx)">
                        Remove
                      </button>
                    </td>
                  </tr>

                  <tr v-if="!createForm.lines.length">
                    <td colspan="8" class="px-3 py-6 text-center text-slate-400">No lines added yet.</td>
                  </tr>
                </tbody>

                <tfoot v-if="createForm.lines.length" class="bg-slate-950/40 font-semibold text-slate-200">
                  <tr>
                    <td class="px-3 py-2">TOTAL</td>
                    <td class="px-3 py-2">{{ fmt(grandCreateTotals.govt) }}</td>
                    <td class="px-3 py-2">{{ fmt(grandCreateTotals.parent) }}</td>
                    <td class="px-3 py-2">{{ fmt(grandCreateTotals.t1) }}</td>
                    <td class="px-3 py-2">{{ fmt(grandCreateTotals.t2) }}</td>
                    <td class="px-3 py-2">{{ fmt(grandCreateTotals.t3) }}</td>
                    <td class="px-3 py-2" colspan="2"></td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <div class="flex items-center gap-2">
              <button
                type="submit"
                :disabled="createForm.processing"
                class="rounded-md bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-emerald-400 disabled:opacity-50"
              >
                {{ createForm.processing ? 'Saving...' : 'Save Fee Structure' }}
              </button>
            </div>
          </form>
        </section>

        <section class="rounded-2xl border border-white/10 bg-slate-900/70 p-6">
          <h2 class="mb-4 text-lg font-semibold text-white">Existing Fee Structures</h2>

          <div class="overflow-x-auto rounded-xl border border-white/10">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-950/60 text-left text-slate-300">
                <tr>
                  <th class="px-4 py-2">Year</th>
                  <th class="px-4 py-2">Name</th>
                  <th class="px-4 py-2">Category</th>
                  <th class="px-4 py-2">Class</th>
                  <th class="px-4 py-2">Govt Total</th>
                  <th class="px-4 py-2">Parent Total</th>
                  <th class="px-4 py-2">Assigned Accounts</th>
                  <th class="px-4 py-2">Actions</th>
                </tr>
              </thead>

              <tbody class="divide-y divide-white/10">
                <tr v-for="s in structureRows" :key="s.id">
                  <td class="px-4 py-2 text-slate-200">{{ s.year }}</td>
                  <td class="px-4 py-2 font-medium text-white">{{ s.name }}</td>
                  <td class="px-4 py-2 text-slate-300">{{ s.category }}</td>
                  <td class="px-4 py-2 text-slate-300">{{ s.class_level || '—' }}</td>
                  <td class="px-4 py-2 text-cyan-300">{{ fmt(structureGovtTotal(s)) }}</td>
                  <td class="px-4 py-2 text-amber-300">{{ fmt(structureParentTotal(s)) }}</td>
                  <td class="px-4 py-2 text-slate-300">{{ structureAccountsCount(s) }}</td>
                  <td class="px-4 py-2">
                    <div class="flex gap-3">
                      <Link :href="endpoints.edit(s.id)" class="text-cyan-300 hover:text-cyan-200">
                        Edit
                      </Link>
                      <button type="button" class="text-rose-300 hover:text-rose-200" @click="deleteStructure(s.id)">
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>

                <tr v-if="!structureRows.length">
                  <td colspan="8" class="px-4 py-6 text-center text-slate-400">No structures found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </main>
  </AdminLayout>
</template>