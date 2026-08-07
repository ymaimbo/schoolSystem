<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  students: { type: Array, default: () => [] },
  accounts: { type: Object, default: () => ({ data: [] }) },
  recentPayments: { type: Array, default: () => [] },
  paymentMethods: { type: Array, default: () => [] },
})

const accountForm = useForm({
  student_id: '',
  total_fee_due: '',
  sponsor_org_name: '',
  sponsor_org_id: '',
  notes: '',
})

const paymentForm = useForm({
  student_id: '',
  amount: '',
  payment_method: 'cash',
  organization_name: '',
  organization_id: '',
  receipt_no: '',
  paid_at: new Date().toISOString().slice(0, 10),
  notes: '',
})

const selectedStudent = computed(() =>
  props.students.find((s) => String(s.id) === String(paymentForm.student_id || accountForm.student_id))
)

const saveAccount = () => {
  accountForm.post(route('admin.student-finance.account.upsert'), {
    preserveScroll: true,
  })
}

const savePayment = () => {
  paymentForm.post(route('admin.student-finance.payment.store'), {
    preserveScroll: true,
    onSuccess: () => paymentForm.reset('amount', 'organization_name', 'organization_id', 'receipt_no', 'notes'),
  })
}

const deletePayment = (id) => {
  if (!confirm('Delete this payment record?')) return
  useForm({}).delete(route('admin.student-finance.payment.destroy', id), { preserveScroll: true })
}
</script>

<template>
  <Head title="Student Finance" />
  <AdminLayout>
    <div class="space-y-8">
      <section class="space-y-2">
        <h1 class="text-2xl font-semibold text-slate-900">Student Finance</h1>
        <p class="text-sm text-slate-600">
          Manage fee due, payments, balance, payment method, and sponsoring organization details.
        </p>
      </section>

      <section class="grid gap-6 lg:grid-cols-2">
        <form class="space-y-4 border border-slate-200 bg-white p-5" @submit.prevent="saveAccount">
          <h2 class="text-lg font-semibold text-slate-900">Set or Update Fee Account</h2>

          <select v-model="accountForm.student_id" class="w-full border border-slate-300 px-3 py-2 text-sm">
            <option value="">Select student</option>
            <option v-for="student in students" :key="student.id" :value="student.id">
              {{ student.admission_no }} - {{ student.first_name }} {{ student.last_name }}
            </option>
          </select>

          <input
            v-model="accountForm.total_fee_due"
            type="number"
            min="0"
            step="0.01"
            placeholder="Total fee due"
            class="w-full border border-slate-300 px-3 py-2 text-sm"
          />

          <input
            v-model="accountForm.sponsor_org_name"
            type="text"
            placeholder="Sponsor organization name (optional)"
            class="w-full border border-slate-300 px-3 py-2 text-sm"
          />

          <input
            v-model="accountForm.sponsor_org_id"
            type="text"
            placeholder="Sponsor organization ID (optional)"
            class="w-full border border-slate-300 px-3 py-2 text-sm"
          />

          <textarea
            v-model="accountForm.notes"
            rows="3"
            placeholder="Notes"
            class="w-full border border-slate-300 px-3 py-2 text-sm"
          />

          <button type="submit" class="border border-slate-900 bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
            Save Fee Account
          </button>
        </form>

        <form class="space-y-4 border border-slate-200 bg-white p-5" @submit.prevent="savePayment">
          <h2 class="text-lg font-semibold text-slate-900">Record Fee Payment</h2>

          <select v-model="paymentForm.student_id" class="w-full border border-slate-300 px-3 py-2 text-sm">
            <option value="">Select student</option>
            <option v-for="student in students" :key="student.id" :value="student.id">
              {{ student.admission_no }} - {{ student.first_name }} {{ student.last_name }}
            </option>
          </select>

          <input
            v-model="paymentForm.amount"
            type="number"
            min="0.01"
            step="0.01"
            placeholder="Amount paid"
            class="w-full border border-slate-300 px-3 py-2 text-sm"
          />

          <select v-model="paymentForm.payment_method" class="w-full border border-slate-300 px-3 py-2 text-sm">
            <option v-for="method in paymentMethods" :key="method" :value="method">
              {{ method }}
            </option>
          </select>

          <input
            v-model="paymentForm.organization_name"
            type="text"
            placeholder="Organization name (if sponsor pays)"
            class="w-full border border-slate-300 px-3 py-2 text-sm"
          />

          <input
            v-model="paymentForm.organization_id"
            type="text"
            placeholder="Organization ID (optional)"
            class="w-full border border-slate-300 px-3 py-2 text-sm"
          />

          <input
            v-model="paymentForm.receipt_no"
            type="text"
            placeholder="Receipt / transaction reference"
            class="w-full border border-slate-300 px-3 py-2 text-sm"
          />

          <input
            v-model="paymentForm.paid_at"
            type="date"
            class="w-full border border-slate-300 px-3 py-2 text-sm"
          />

          <textarea
            v-model="paymentForm.notes"
            rows="2"
            placeholder="Notes"
            class="w-full border border-slate-300 px-3 py-2 text-sm"
          />

          <button type="submit" class="border border-emerald-700 bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600">
            Record Payment
          </button>

          <p v-if="selectedStudent" class="text-xs text-slate-500">
            Active student: {{ selectedStudent.admission_no }} - {{ selectedStudent.first_name }} {{ selectedStudent.last_name }}
          </p>
        </form>
      </section>

      <section class="space-y-3">
        <h2 class="text-lg font-semibold text-slate-900">Student Fee Accounts</h2>
        <div class="overflow-x-auto border border-slate-200 bg-white">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-700">
              <tr>
                <th class="px-4 py-3">Student</th>
                <th class="px-4 py-3">Fee Due</th>
                <th class="px-4 py-3">Paid</th>
                <th class="px-4 py-3">Balance</th>
                <th class="px-4 py-3">Sponsor</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="account in accounts.data" :key="account.id" class="border-t border-slate-100">
                <td class="px-4 py-3">
                  <p class="font-medium text-slate-900">
                    {{ account.student?.first_name }} {{ account.student?.last_name }}
                  </p>
                  <p class="text-xs text-slate-500">{{ account.student?.admission_no }}</p>
                </td>
                <td class="px-4 py-3">{{ Number(account.total_fee_due).toLocaleString() }}</td>
                <td class="px-4 py-3">{{ Number(account.paid_total).toLocaleString() }}</td>
                <td class="px-4 py-3 font-semibold" :class="Number(account.balance) > 0 ? 'text-rose-600' : 'text-emerald-700'">
                  {{ Number(account.balance).toLocaleString() }}
                </td>
                <td class="px-4 py-3 text-slate-700">
                  {{ account.sponsor_org_name || '-' }}
                  <span v-if="account.sponsor_org_id" class="text-xs text-slate-500">({{ account.sponsor_org_id }})</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="space-y-3">
        <h2 class="text-lg font-semibold text-slate-900">Recent Payments</h2>
        <div class="overflow-x-auto border border-slate-200 bg-white">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-700">
              <tr>
                <th class="px-4 py-3">Student</th>
                <th class="px-4 py-3">Amount</th>
                <th class="px-4 py-3">Method</th>
                <th class="px-4 py-3">Organization</th>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in recentPayments" :key="payment.id" class="border-t border-slate-100">
                <td class="px-4 py-3">
                  {{ payment.student?.admission_no }} - {{ payment.student?.first_name }} {{ payment.student?.last_name }}
                </td>
                <td class="px-4 py-3">{{ Number(payment.amount).toLocaleString() }}</td>
                <td class="px-4 py-3 uppercase">{{ payment.payment_method }}</td>
                <td class="px-4 py-3">
                  {{ payment.organization_name || '-' }}
                  <span v-if="payment.organization_id" class="text-xs text-slate-500">({{ payment.organization_id }})</span>
                </td>
                <td class="px-4 py-3">{{ payment.paid_at }}</td>
                <td class="px-4 py-3">
                  <button
                    type="button"
                    class="text-xs font-medium text-rose-600 hover:text-rose-700"
                    @click="deletePayment(payment.id)"
                  >
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </AdminLayout>
</template>