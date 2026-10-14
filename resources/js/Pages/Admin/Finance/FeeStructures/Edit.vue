<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  structure: { type: Object, required: true },
  voteHeads: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  feeProfiles: { type: Array, default: () => [] },
})

const page = usePage()
const flash = computed(() => page.props?.flash ?? {})

const endpoints = {
  index: '/admin/finance/fee-structures',
  update: (id) => `/admin/finance/fee-structures/${id}`,
}

const form = useForm({
  name: props.structure.name || '',
  year: props.structure.year || new Date().getFullYear(),
  fee_profile_id:
    props.structure.fee_profile_id ??
    props.structure.feeProfile?.id ??
    '',
  category: props.structure.category || (props.categories[0] ?? 'day_scholar'),
  class_level: props.structure.class_level || '',
  notes: props.structure.notes || '',
  lines: (props.structure.lines || []).map((l, i) => ({
    vote_head_id: l.vote_head_id,
    govt_capitation_amount: Number(l.govt_capitation_amount || 0),
    parent_total_amount: Number(l.parent_total_amount || 0),
    term1_amount: Number(l.term1_amount || 0),
    term2_amount: Number(l.term2_amount || 0),
    term3_amount: Number(l.term3_amount || 0),
    total_amount: Number(l.total_amount || 0),
    sort_order: Number(l.sort_order ?? i + 1),
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
    total_amount: 0,
    sort_order: form.lines.length + 1,
  })
}

const removeLine = (idx) => {
  form.lines.splice(idx, 1)
  form.lines = form.lines.map((line, i) => ({ ...line, sort_order: i + 1 }))
}

const lineTermTotal = (line) =>
  Number(line.term1_amount || 0) + Number(line.term2_amount || 0) + Number(line.term3_amount || 0)

const syncLineTotal = (line) => {
  line.total_amount = Number(line.parent_total_amount || lineTermTotal(line) || 0)
}

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

const hasDuplicateVoteHeads = computed(() => {
  const ids = form.lines
    .map((l) => (l.vote_head_id ? Number(l.vote_head_id) : null))
    .filter((id) => id !== null)
  return new Set(ids).size !== ids.length
})

const validateForm = () => {
  form.clearErrors()

  if (!form.fee_profile_id) {
    form.setError('fee_profile_id', 'Fee profile is required.')
    return false
  }

  if (!form.lines.length) {
    form.setError('lines', 'At least one votehead line is required.')
    return false
  }

  if (hasDuplicateVoteHeads.value) {
    form.setError('lines', 'Duplicate voteheads are not allowed.')
    return false
  }

  const missingVoteHead = form.lines.some((l) => !l.vote_head_id)
  if (missingVoteHead) {
    form.setError('lines', 'Each line must have a votehead.')
    return false
  }

  return true
}

const submit = () => {
  if (!validateForm()) return

  const normalizedLines = [...form.lines]
    .map((line, idx) => ({
      ...line,
      sort_order: Number(line.sort_order || idx + 1),
    }))
    .sort((a, b) => Number(a.sort_order) - Number(b.sort_order))
    .map((line, idx) => ({
      vote_head_id: Number(line.vote_head_id),
      govt_capitation_amount: Number(line.govt_capitation_amount || 0),
      parent_total_amount: Number(line.parent_total_amount || 0),
      term1_amount: Number(line.term1_amount || 0),
      term2_amount: Number(line.term2_amount || 0),
      term3_amount: Number(line.term3_amount || 0),
      total_amount: Number(line.parent_total_amount || lineTermTotal(line) || 0),
      sort_order: idx + 1,
    }))

  form
    .transform((data) => ({
      ...data,
      year: Number(data.year),
      fee_profile_id: Number(data.fee_profile_id),
      lines: normalizedLines,
    }))
    .put(endpoints.update(props.structure.id), {
      preserveScroll: true,
    })
}

const fmt = (v) =>
  Number(v || 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
</script>

<template>
  <Head :title="`Edit Fee Structure - ${structure.name}`" />

  <AdminLayout>
    <main class="min-h-screen bg-slate-950 text-slate-100">
      <div class="mx-auto w-full max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
        <section class="rounded-2xl border border-white/10 bg-slate-900/70 p-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold tracking-tight text-white">Edit Fee Structure</h1>
              <p class="mt-1 text-sm text-slate-300">
                Update fee profile mapping and votehead lines for this structure.
              </p>
            </div>
            <Link
              :href="endpoints.index"
              class="rounded-md border border-white/20 px-3 py-2 text-xs font-medium text-slate-200 hover:bg-white/5"
            >
              Back
            </Link>
          </div>
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

        <section class="rounded-2xl border border-white/10 bg-slate-900/70 p-6 space-y-5">
          <div class="grid gap-3 md:grid-cols-2">
            <input
              v-model="form.name"
              type="text"
              placeholder="Structure name"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            />

            <input
              v-model.number="form.year"
              type="number"
              min="2020"
              max="2100"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            />

            <select
              v-model="form.fee_profile_id"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            >
              <option value="">Select fee profile...</option>
              <option v-for="fp in feeProfiles" :key="fp.id" :value="fp.id">
                {{ fp.code ? `${fp.code} - ` : '' }}{{ fp.name }}
              </option>
            </select>

            <select
              v-model="form.category"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            >
              <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
            </select>

            <input
              v-model="form.class_level"
              type="text"
              placeholder="Class level (optional)"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400 md:col-span-2"
            />
          </div>

          <p v-if="form.errors.fee_profile_id" class="text-xs text-rose-300">{{ form.errors.fee_profile_id }}</p>
          <p v-if="form.errors.lines" class="text-xs text-rose-300">{{ form.errors.lines }}</p>

          <textarea
            v-model="form.notes"
            rows="2"
            placeholder="Notes (optional)"
            class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
          />

          <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-100">Structure Lines</h3>
            <button
              type="button"
              class="rounded-md border border-white/20 px-3 py-1.5 text-xs text-slate-200 hover:bg-white/5"
              @click="addLine"
            >
              + Add Line
            </button>
          </div>

          <div class="overflow-x-auto rounded-xl border border-white/10">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-950/60 text-left text-slate-300">
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

              <tbody class="divide-y divide-white/10">
                <tr v-for="(line, idx) in form.lines" :key="idx">
                  <td class="px-3 py-2">
                    <select
                      v-model="line.vote_head_id"
                      class="w-44 rounded border border-white/15 bg-slate-950 px-2 py-1 text-sm text-slate-100"
                    >
                      <option value="">Select</option>
                      <option v-for="vh in voteHeads" :key="vh.id" :value="vh.id">
                        {{ vh.code }} - {{ vh.name }}
                      </option>
                    </select>
                  </td>

                  <td class="px-3 py-2">
                    <input
                      v-model.number="line.govt_capitation_amount"
                      type="number"
                      min="0"
                      step="0.01"
                      class="w-28 rounded border border-white/15 bg-slate-950 px-2 py-1 text-sm text-slate-100"
                    />
                  </td>
                  <td class="px-3 py-2">
                    <input
                      v-model.number="line.parent_total_amount"
                      type="number"
                      min="0"
                      step="0.01"
                      class="w-28 rounded border border-white/15 bg-slate-950 px-2 py-1 text-sm text-slate-100"
                      @input="syncLineTotal(line)"
                    />
                  </td>
                  <td class="px-3 py-2">
                    <input
                      v-model.number="line.term1_amount"
                      type="number"
                      min="0"
                      step="0.01"
                      class="w-24 rounded border border-white/15 bg-slate-950 px-2 py-1 text-sm text-slate-100"
                      @input="syncLineTotal(line)"
                    />
                  </td>
                  <td class="px-3 py-2">
                    <input
                      v-model.number="line.term2_amount"
                      type="number"
                      min="0"
                      step="0.01"
                      class="w-24 rounded border border-white/15 bg-slate-950 px-2 py-1 text-sm text-slate-100"
                      @input="syncLineTotal(line)"
                    />
                  </td>
                  <td class="px-3 py-2">
                    <input
                      v-model.number="line.term3_amount"
                      type="number"
                      min="0"
                      step="0.01"
                      class="w-24 rounded border border-white/15 bg-slate-950 px-2 py-1 text-sm text-slate-100"
                      @input="syncLineTotal(line)"
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

                <tr v-if="!form.lines.length">
                  <td colspan="8" class="px-3 py-5 text-center text-slate-400">No lines added.</td>
                </tr>
              </tbody>

              <tfoot v-if="form.lines.length" class="bg-slate-950/40 font-semibold text-slate-200">
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

          <button
            type="button"
            :disabled="form.processing"
            class="rounded-md bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-emerald-400 disabled:opacity-50"
            @click="submit"
          >
            {{ form.processing ? 'Updating...' : 'Update Structure' }}
          </button>
        </section>
      </div>
    </main>
  </AdminLayout>
</template>