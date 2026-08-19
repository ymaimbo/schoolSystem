<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
  transactions: { type: Object, default: () => ({ data: [] }) },
  vouchers: { type: Object, default: () => ({ data: [] }) },
  summary: { type: Object, default: () => ({ income: 0, expense: 0, balance: 0 }) },
  searchVoucher: { type: String, default: '' },
  paymentMethods: { type: Array, default: () => [] },
  voucherMethods: { type: Array, default: () => [] },
})

const txForm = useForm({
  entry_date: new Date().toISOString().slice(0, 10),
  type: 'income',
  category: '',
  description: '',
  amount: '',
  payment_method: 'bank',
  reference_no: '',
})

const voucherForm = useForm({
  supplier_name: '',
  supplier_id: '',
  purpose: '',
  amount: '',
  payment_method: 'bank',
  paid_at: new Date().toISOString().slice(0, 10),
  notes: '',
})

const voucherSearchForm = useForm({
  search_voucher: props.searchVoucher || '',
})

const transactionSearch = ref('')

const transactionRows = computed(() => {
  const rows = Array.isArray(props.transactions?.data) ? props.transactions.data : []
  const q = transactionSearch.value.trim().toLowerCase()

  if (!q) return rows

  return rows.filter((tx) => {
    const referenceNo = String(tx?.reference_no || '').toLowerCase()
    const category = String(tx?.category || '').toLowerCase()
    const description = String(tx?.description || '').toLowerCase()
    const type = String(tx?.type || '').toLowerCase()

    return (
      referenceNo.includes(q)
      || category.includes(q)
      || description.includes(q)
      || type.includes(q)
    )
  })
})

const saveTransaction = () => {
  txForm.post(route('admin.finance.store'), {
    preserveScroll: true,
    onSuccess: () => txForm.reset('category', 'description', 'amount', 'reference_no'),
  })
}

const saveVoucher = () => {
  voucherForm.post(route('admin.finance.voucher.store'), {
    preserveScroll: true,
    onSuccess: () => voucherForm.reset('supplier_name', 'supplier_id', 'purpose', 'amount', 'notes'),
  })
}

const applyVoucherSearch = () => {
  router.get(
    route('admin.finance.index'),
    { search_voucher: voucherSearchForm.search_voucher },
    { preserveState: true, replace: true, preserveScroll: true },
  )
}

const clearVoucherSearch = () => {
  voucherSearchForm.search_voucher = ''
  applyVoucherSearch()
}

const clearTransactionSearch = () => {
  transactionSearch.value = ''
}

const deleteVoucher = (id) => {
  if (!confirm('Delete this voucher?')) return
  useForm({}).delete(route('admin.finance.voucher.destroy', id), { preserveScroll: true })
}

const deleteTransaction = (id) => {
  if (!confirm('Delete this transaction?')) return
  useForm({}).delete(route('admin.finance.destroy', id), { preserveScroll: true })
}
</script>

<template>
  <Head title="Finance" />

  <AdminLayout>
    <div class="space-y-8">
      <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded border border-slate-200 bg-white p-4">
          <p class="text-xs uppercase tracking-wide text-slate-500">Finance Income</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">
            {{ Number(summary.income).toLocaleString() }}
          </p>
        </div>
        <div class="rounded border border-slate-200 bg-white p-4">
          <p class="text-xs uppercase tracking-wide text-slate-500">Finance Expense</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">
            {{ Number(summary.expense).toLocaleString() }}
          </p>
        </div>
        <div class="rounded border border-slate-200 bg-white p-4">
          <p class="text-xs uppercase tracking-wide text-slate-500">Finance Balance</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">
            {{ Number(summary.balance).toLocaleString() }}
          </p>
        </div>
      </section>

      <section class="grid gap-6 lg:grid-cols-2">
        <form class="space-y-3 rounded border border-slate-200 bg-white p-5" @submit.prevent="saveTransaction">
          <h2 class="text-lg font-semibold">Add Finance Transaction</h2>

          <input
            v-model="txForm.entry_date"
            type="date"
            class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          />

          <select
            v-model="txForm.type"
            class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          >
            <option value="income">income</option>
            <option value="expense">expense</option>
          </select>

          <input
            v-model="txForm.category"
            type="text"
            placeholder="Category"
            class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          />

          <input
            v-model="txForm.description"
            type="text"
            placeholder="Description"
            class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          />

          <input
            v-model="txForm.amount"
            type="number"
            step="0.01"
            min="0.01"
            placeholder="Amount"
            class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          />

          <select
            v-model="txForm.payment_method"
            class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          >
            <option v-for="method in paymentMethods" :key="method" :value="method">
              {{ method }}
            </option>
          </select>

          <input
            v-model="txForm.reference_no"
            type="text"
            placeholder="Reference No / Receipt No (optional)"
            class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          />

          <button
            class="rounded border border-slate-900 bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800"
          >
            Save Transaction
          </button>
        </form>

        <form class="space-y-3 rounded border border-slate-200 bg-white p-5" @submit.prevent="saveVoucher">
          <h2 class="text-lg font-semibold">Create Payment Voucher (Finance)</h2>
          <p class="text-xs text-slate-500">
            Voucher number is generated automatically and Principal is auto-approver.
          </p>

          <input
            v-model="voucherForm.supplier_name"
            type="text"
            placeholder="Supplier / Payee Name"
            class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          />

          <input
            v-model="voucherForm.supplier_id"
            type="text"
            placeholder="Supplier / Payee ID (optional)"
            class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          />

          <input
            v-model="voucherForm.purpose"
            type="text"
            placeholder="Purpose"
            class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          />

          <input
            v-model="voucherForm.amount"
            type="number"
            step="0.01"
            min="0.01"
            placeholder="Amount"
            class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          />

          <select
            v-model="voucherForm.payment_method"
            class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          >
            <option v-for="method in voucherMethods" :key="method" :value="method">
              {{ method }}
            </option>
          </select>

          <input
            v-model="voucherForm.paid_at"
            type="date"
            class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          />

          <textarea
            v-model="voucherForm.notes"
            rows="3"
            placeholder="Notes"
            class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          />

          <button
            class="rounded border border-slate-900 bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800"
          >
            Create Voucher
          </button>
        </form>
      </section>

      <section class="rounded border border-slate-200 bg-white p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h2 class="text-lg font-semibold">Payment Vouchers</h2>
            <p class="text-xs text-slate-500">Search by voucher number and print official voucher format.</p>
          </div>

          <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
            <input
              v-model="voucherSearchForm.search_voucher"
              type="text"
              placeholder="Search voucher number"
              class="w-full rounded border border-slate-300 px-3 py-2 text-sm sm:w-72"
              @keyup.enter="applyVoucherSearch"
            />
            <button
              type="button"
              class="rounded border border-slate-900 bg-slate-900 px-4 py-2 text-sm text-white"
              @click="applyVoucherSearch"
            >
              Search
            </button>
            <button
              type="button"
              class="rounded border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700"
              @click="clearVoucherSearch"
            >
              Clear
            </button>
          </div>
        </div>

        <div class="mt-4 overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-700">
              <tr>
                <th class="px-4 py-3">Voucher #</th>
                <th class="px-4 py-3">Payee</th>
                <th class="px-4 py-3">Purpose</th>
                <th class="px-4 py-3">Amount</th>
                <th class="px-4 py-3">Method</th>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="voucher in vouchers.data" :key="voucher.id" class="border-t border-slate-100">
                <td class="px-4 py-3 font-medium">{{ voucher.voucher_no }}</td>
                <td class="px-4 py-3">{{ voucher.supplier_name }}</td>
                <td class="px-4 py-3">{{ voucher.purpose }}</td>
                <td class="px-4 py-3">{{ Number(voucher.amount).toLocaleString() }}</td>
                <td class="px-4 py-3">{{ voucher.payment_method }}</td>
                <td class="px-4 py-3">{{ voucher.paid_at }}</td>
                <td class="px-4 py-3">
                  <div class="flex gap-3">
                    <Link
                      :href="route('admin.finance.voucher.print', voucher.id)"
                      target="_blank"
                      class="text-sky-700 hover:underline"
                    >
                      Print
                    </Link>
                    <button
                      type="button"
                      class="text-rose-600 hover:underline"
                      @click="deleteVoucher(voucher.id)"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!vouchers.data?.length">
                <td colspan="7" class="px-4 py-6 text-center text-slate-500">No vouchers found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="rounded border border-slate-200 bg-white p-5">
        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h2 class="text-lg font-semibold">Recent Finance Transactions</h2>
            <p class="text-xs text-slate-500">Search by receipt/reference number, category, or description.</p>
          </div>

          <div class="flex gap-2">
            <input
              v-model="transactionSearch"
              type="text"
              placeholder="Search receipt/reference no"
              class="w-full rounded border border-slate-300 px-3 py-2 text-sm sm:w-80"
            />
            <button
              type="button"
              class="rounded border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700"
              @click="clearTransactionSearch"
            >
              Clear
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-700">
              <tr>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Type</th>
                <th class="px-4 py-3">Category</th>
                <th class="px-4 py-3">Description</th>
                <th class="px-4 py-3">Amount</th>
                <th class="px-4 py-3">Method</th>
                <th class="px-4 py-3">Ref / Receipt</th>
                <th class="px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="tx in transactionRows" :key="tx.id" class="border-t border-slate-100">
                <td class="px-4 py-3">{{ tx.entry_date }}</td>
                <td class="px-4 py-3 capitalize">{{ tx.type }}</td>
                <td class="px-4 py-3">{{ tx.category }}</td>
                <td class="px-4 py-3">{{ tx.description }}</td>
                <td class="px-4 py-3">{{ Number(tx.amount).toLocaleString() }}</td>
                <td class="px-4 py-3">{{ tx.payment_method }}</td>
                <td class="px-4 py-3">{{ tx.reference_no || '-' }}</td>
                <td class="px-4 py-3">
                  <button
                    type="button"
                    class="text-rose-600 hover:underline"
                    @click="deleteTransaction(tx.id)"
                  >
                    Delete
                  </button>
                </td>
              </tr>
              <tr v-if="!transactionRows.length">
                <td colspan="8" class="px-4 py-6 text-center text-slate-500">
                  No transactions found for this search.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </AdminLayout>
</template>