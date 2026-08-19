<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  structures: { type: Object, default: () => ({ data: [] }) },
  voteHeads: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
})

const createForm = useForm({
  name: '',
  year: new Date().getFullYear(),
  category: 'day_scholar',
  class_level: '',
  notes: '',
  lines: [],
})

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

const removeLine = (idx) => createForm.lines.splice(idx, 1)

const voteHeadCode = (id) => props.voteHeads.find(v => Number(v.id) === Number(id))?.code ?? ''

const submitCreate = () => {
  createForm.post(route('admin.finance.fee-structures.store'), {
    preserveScroll: true,
    onSuccess: () => {
      createForm.reset()
      createForm.year = new Date().getFullYear()
      createForm.category = 'day_scholar'
      createForm.lines = []
    },
  })
}

const deleteStructure = (id) => {
  if (!confirm('Delete this fee structure?')) return
  useForm({}).delete(route('admin.finance.fee-structures.destroy', id), { preserveScroll: true })
}

const grandCreateTotals = computed(() => {
  const rows = createForm.lines || []
  return rows.reduce((acc, r) => {
    acc.govt += Number(r.govt_capitation_amount || 0)
    acc.parent += Number(r.parent_total_amount || 0)
    acc.t1 += Number(r.term1_amount || 0)
    acc.t2 += Number(r.term2_amount || 0)
    acc.t3 += Number(r.term3_amount || 0)
    return acc
  }, { govt: 0, parent: 0, t1: 0, t2: 0, t3: 0 })
})

const fmt = (v) => Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
</script>

<template>
  <Head title="Fee Structures" />
  <AdminLayout>
    <div class="space-y-8">
      <section class="rounded border border-slate-200 bg-white p-5">
        <h1 class="text-xl font-semibold">Fee Structures (Vote Heads)</h1>
        <p class="mt-1 text-sm text-slate-600">
          Create annual fee structures (Day Scholar / Boarder) with vote-head split, capitation, and term distribution.
        </p>
      </section>

      <section class="rounded border border-slate-200 bg-white p-5 space-y-4">
        <h2 class="text-lg font-semibold">Create Fee Structure</h2>

        <div class="grid gap-3 md:grid-cols-2">
          <input v-model="createForm.name" type="text" placeholder="Structure name (e.g. 2026 Day Scholars)" class="rounded border border-slate-300 px-3 py-2 text-sm" />
          <input v-model="createForm.year" type="number" min="2020" max="2100" class="rounded border border-slate-300 px-3 py-2 text-sm" />
          <select v-model="createForm.category" class="rounded border border-slate-300 px-3 py-2 text-sm">
            <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
          </select>
          <input v-model="createForm.class_level" type="text" placeholder="Class level (optional)" class="rounded border border-slate-300 px-3 py-2 text-sm" />
        </div>

        <textarea v-model="createForm.notes" rows="2" placeholder="Notes (optional)" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" />

        <div class="flex items-center justify-between">
          <h3 class="font-semibold">Structure Lines</h3>
          <button type="button" class="rounded border border-slate-900 bg-slate-900 px-3 py-1.5 text-sm text-white" @click="addLine">
            + Add Line
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-700">
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
            <tbody>
              <tr v-for="(line, idx) in createForm.lines" :key="idx" class="border-t border-slate-100">
                <td class="px-3 py-2">
                  <select v-model="line.vote_head_id" class="w-44 rounded border border-slate-300 px-2 py-1 text-sm">
                    <option value="">Select</option>
                    <option v-for="vh in voteHeads" :key="vh.id" :value="vh.id">{{ vh.code }} - {{ vh.name }}</option>
                  </select>
                </td>
                <td class="px-3 py-2"><input v-model.number="line.govt_capitation_amount" type="number" min="0" step="0.01" class="w-32 rounded border border-slate-300 px-2 py-1 text-sm" /></td>
                <td class="px-3 py-2"><input v-model.number="line.parent_total_amount" type="number" min="0" step="0.01" class="w-32 rounded border border-slate-300 px-2 py-1 text-sm" /></td>
                <td class="px-3 py-2"><input v-model.number="line.term1_amount" type="number" min="0" step="0.01" class="w-24 rounded border border-slate-300 px-2 py-1 text-sm" /></td>
                <td class="px-3 py-2"><input v-model.number="line.term2_amount" type="number" min="0" step="0.01" class="w-24 rounded border border-slate-300 px-2 py-1 text-sm" /></td>
                <td class="px-3 py-2"><input v-model.number="line.term3_amount" type="number" min="0" step="0.01" class="w-24 rounded border border-slate-300 px-2 py-1 text-sm" /></td>
                <td class="px-3 py-2"><input v-model.number="line.sort_order" type="number" min="0" class="w-20 rounded border border-slate-300 px-2 py-1 text-sm" /></td>
                <td class="px-3 py-2">
                  <button type="button" class="text-rose-600 hover:underline" @click="removeLine(idx)">Remove</button>
                </td>
              </tr>
              <tr v-if="!createForm.lines.length">
                <td colspan="8" class="px-3 py-5 text-center text-slate-500">No lines added yet.</td>
              </tr>
            </tbody>
            <tfoot v-if="createForm.lines.length" class="bg-slate-50 font-semibold">
              <tr>
                <td class="px-3 py-2">TOTAL</td>
                <td class="px-3 py-2">{{ fmt(grandCreateTotals.govt) }}</td>
                <td class="px-3 py-2">{{ fmt(grandCreateTotals.parent) }}</td>
                <td class="px-3 py-2">{{ fmt(grandCreateTotals.t1) }}</td>
                <td class="px-3 py-2">{{ fmt(grandCreateTotals.t2) }}</td>
                <td class="px-3 py-2">{{ fmt(grandCreateTotals.t3) }}</td>
                <td colspan="2"></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <button type="button" class="rounded border border-emerald-700 bg-emerald-700 px-4 py-2 text-sm text-white" @click="submitCreate">
          Save Fee Structure
        </button>
      </section>

      <section class="rounded border border-slate-200 bg-white p-5">
        <h2 class="text-lg font-semibold mb-3">Existing Fee Structures</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-700">
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
            <tbody>
              <tr v-for="s in structures.data" :key="s.id" class="border-t border-slate-100">
                <td class="px-4 py-2">{{ s.year }}</td>
                <td class="px-4 py-2 font-medium">{{ s.name }}</td>
                <td class="px-4 py-2">{{ s.category }}</td>
                <td class="px-4 py-2">{{ s.class_level || '-' }}</td>
                <td class="px-4 py-2">{{ fmt(s.govt_total) }}</td>
                <td class="px-4 py-2">{{ fmt(s.parent_total) }}</td>
                <td class="px-4 py-2">{{ s.fee_accounts_count }}</td>
                <td class="px-4 py-2">
                  <div class="flex gap-3">
                    <Link :href="route('admin.finance.fee-structures.edit', s.id)" class="text-sky-700 hover:underline">Edit</Link>
                    <button type="button" class="text-rose-600 hover:underline" @click="deleteStructure(s.id)">Delete</button>
                  </div>
                </td>
              </tr>
              <tr v-if="!structures.data?.length">
                <td colspan="8" class="px-4 py-6 text-center text-slate-500">No structures found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </AdminLayout>
</template>