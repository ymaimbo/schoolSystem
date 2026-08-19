<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  structure: { type: Object, required: true },
  voteHeads: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
})

const form = useForm({
  name: props.structure.name || '',
  year: props.structure.year || new Date().getFullYear(),
  category: props.structure.category || 'day_scholar',
  class_level: props.structure.class_level || '',
  notes: props.structure.notes || '',
  lines: (props.structure.lines || []).map((l, i) => ({
    vote_head_id: l.vote_head_id,
    govt_capitation_amount: Number(l.govt_capitation_amount || 0),
    parent_total_amount: Number(l.parent_total_amount || 0),
    term1_amount: Number(l.term1_amount || 0),
    term2_amount: Number(l.term2_amount || 0),
    term3_amount: Number(l.term3_amount || 0),
    sort_order: Number(l.sort_order ?? (i + 1)),
  })),
})

const addLine = () => {
  form.lines.push({
    vote_head_id: '',
    govt_capitation_amount: 0,
    parent_total_amount: 0,
    term1_amount: 0,
    term2_amount: 0,
    term3_amount: 0,
    sort_order: form.lines.length + 1,
  })
}

const removeLine = (idx) => form.lines.splice(idx, 1)

const totals = computed(() => {
  return form.lines.reduce((acc, r) => {
    acc.govt += Number(r.govt_capitation_amount || 0)
    acc.parent += Number(r.parent_total_amount || 0)
    acc.t1 += Number(r.term1_amount || 0)
    acc.t2 += Number(r.term2_amount || 0)
    acc.t3 += Number(r.term3_amount || 0)
    return acc
  }, { govt: 0, parent: 0, t1: 0, t2: 0, t3: 0 })
})

const submit = () => {
  form.put(route('admin.finance.fee-structures.update', props.structure.id), {
    preserveScroll: true,
  })
}

const fmt = (v) => Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
</script>

<template>
  <Head :title="`Edit Fee Structure - ${structure.name}`" />
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">Edit Fee Structure</h1>
        <Link :href="route('admin.finance.fee-structures.index')" class="text-sky-700 hover:underline">
          Back
        </Link>
      </div>

      <section class="rounded border border-slate-200 bg-white p-5 space-y-4">
        <div class="grid gap-3 md:grid-cols-2">
          <input v-model="form.name" type="text" class="rounded border border-slate-300 px-3 py-2 text-sm" />
          <input v-model="form.year" type="number" min="2020" max="2100" class="rounded border border-slate-300 px-3 py-2 text-sm" />
          <select v-model="form.category" class="rounded border border-slate-300 px-3 py-2 text-sm">
            <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
          </select>
          <input v-model="form.class_level" type="text" placeholder="Class level (optional)" class="rounded border border-slate-300 px-3 py-2 text-sm" />
        </div>

        <textarea v-model="form.notes" rows="2" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" />

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
                <th class="px-3 py-2">Govt</th>
                <th class="px-3 py-2">Parent</th>
                <th class="px-3 py-2">T1</th>
                <th class="px-3 py-2">T2</th>
                <th class="px-3 py-2">T3</th>
                <th class="px-3 py-2">Order</th>
                <th class="px-3 py-2">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(line, idx) in form.lines" :key="idx" class="border-t border-slate-100">
                <td class="px-3 py-2">
                  <select v-model="line.vote_head_id" class="w-44 rounded border border-slate-300 px-2 py-1 text-sm">
                    <option value="">Select</option>
                    <option v-for="vh in voteHeads" :key="vh.id" :value="vh.id">{{ vh.code }} - {{ vh.name }}</option>
                  </select>
                </td>
                <td class="px-3 py-2"><input v-model.number="line.govt_capitation_amount" type="number" min="0" step="0.01" class="w-28 rounded border border-slate-300 px-2 py-1 text-sm" /></td>
                <td class="px-3 py-2"><input v-model.number="line.parent_total_amount" type="number" min="0" step="0.01" class="w-28 rounded border border-slate-300 px-2 py-1 text-sm" /></td>
                <td class="px-3 py-2"><input v-model.number="line.term1_amount" type="number" min="0" step="0.01" class="w-24 rounded border border-slate-300 px-2 py-1 text-sm" /></td>
                <td class="px-3 py-2"><input v-model.number="line.term2_amount" type="number" min="0" step="0.01" class="w-24 rounded border border-slate-300 px-2 py-1 text-sm" /></td>
                <td class="px-3 py-2"><input v-model.number="line.term3_amount" type="number" min="0" step="0.01" class="w-24 rounded border border-slate-300 px-2 py-1 text-sm" /></td>
                <td class="px-3 py-2"><input v-model.number="line.sort_order" type="number" min="0" class="w-20 rounded border border-slate-300 px-2 py-1 text-sm" /></td>
                <td class="px-3 py-2">
                  <button type="button" class="text-rose-600 hover:underline" @click="removeLine(idx)">Remove</button>
                </td>
              </tr>
              <tr v-if="!form.lines.length">
                <td colspan="8" class="px-3 py-5 text-center text-slate-500">No lines added.</td>
              </tr>
            </tbody>
            <tfoot v-if="form.lines.length" class="bg-slate-50 font-semibold">
              <tr>
                <td class="px-3 py-2">TOTAL</td>
                <td class="px-3 py-2">{{ fmt(totals.govt) }}</td>
                <td class="px-3 py-2">{{ fmt(totals.parent) }}</td>
                <td class="px-3 py-2">{{ fmt(totals.t1) }}</td>
                <td class="px-3 py-2">{{ fmt(totals.t2) }}</td>
                <td class="px-3 py-2">{{ fmt(totals.t3) }}</td>
                <td colspan="2"></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <button type="button" class="rounded border border-emerald-700 bg-emerald-700 px-4 py-2 text-sm text-white" @click="submit">
          Update Structure
        </button>
      </section>
    </div>
  </AdminLayout>
</template>