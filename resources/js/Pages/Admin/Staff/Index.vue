<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  records: {
    type: Array,
    default: () => [],
  },
  assignableRoles: {
    type: Array,
    default: () => [],
  },
})

const editingId = ref(null)

const form = useForm({
  full_name: '',
  staff_type: '',
  role_category: '',
  department: '',
  phone: '',
  email: '',
  employment_status: '',
  is_on_duty: false,
  duty_date: '',
  notes: '',
  assign_login: false,
  login_email: '',
  login_role: '',
  login_password: '',
  login_password_confirmation: '',
  revoke_login: false,
})

const resetForm = () => {
  editingId.value = null
  form.reset()
  form.clearErrors()
}

const editRecord = (record) => {
  editingId.value = record.id
  form.full_name = record.full_name || ''
  form.staff_type = record.staff_type || ''
  form.role_category = record.role_category || ''
  form.department = record.department || ''
  form.phone = record.phone || ''
  form.email = record.email || ''
  form.employment_status = record.employment_status || ''
  form.is_on_duty = !!record.is_on_duty
  form.duty_date = record.duty_date || ''
  form.notes = record.notes || ''
  form.assign_login = !!record.has_login
  form.login_email = record.login_email || ''
  form.login_role = record.login_role || ''
  form.login_password = ''
  form.login_password_confirmation = ''
  form.revoke_login = false
  form.clearErrors()
}

const submit = () => {
  if (editingId.value) {
    form.put(route('admin.staff.update', editingId.value), {
      onSuccess: () => resetForm(),
    })
    return
  }

  form.post(route('admin.staff.store'), {
    onSuccess: () => resetForm(),
  })
}

const destroyRecord = (id) => {
  if (!confirm('Delete this staff record?')) return
  form.delete(route('admin.staff.destroy', id))
}
</script>

<template>
  <Head title="Staff Directory" />

  <div class="min-h-screen bg-slate-950 px-6 py-10 text-slate-100">
    <div class="mx-auto w-full max-w-6xl">
      <h1 class="text-2xl font-semibold">Staff Directory And Role Access</h1>
      <p class="mt-2 text-sm text-slate-300">
        Principal can create staff profiles and assign login credentials with specific roles.
      </p>

      <form class="mt-8 grid gap-4 border border-white/10 p-5" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
          <input v-model="form.full_name" type="text" placeholder="Full Name" class="border border-white/20 bg-slate-900 px-3 py-2" />
          <input v-model="form.staff_type" type="text" placeholder="Staff Type" class="border border-white/20 bg-slate-900 px-3 py-2" />
          <input v-model="form.department" type="text" placeholder="Department" class="border border-white/20 bg-slate-900 px-3 py-2" />
          <input v-model="form.phone" type="text" placeholder="Phone" class="border border-white/20 bg-slate-900 px-3 py-2" />
          <input v-model="form.email" type="email" placeholder="Contact Email (optional)" class="border border-white/20 bg-slate-900 px-3 py-2" />
          <input v-model="form.employment_status" type="text" placeholder="Employment Status" class="border border-white/20 bg-slate-900 px-3 py-2" />
          <input v-model="form.duty_date" type="date" class="border border-white/20 bg-slate-900 px-3 py-2" />
          <label class="flex items-center gap-2 border border-white/20 bg-slate-900 px-3 py-2">
            <input v-model="form.is_on_duty" type="checkbox" />
            <span>Is On Duty</span>
          </label>
        </div>

        <textarea v-model="form.notes" rows="3" placeholder="Notes" class="border border-white/20 bg-slate-900 px-3 py-2" />

        <div class="border-t border-white/10 pt-4">
          <label class="flex items-center gap-2">
            <input v-model="form.assign_login" type="checkbox" />
            <span>Assign login access for this staff member</span>
          </label>

          <div v-if="form.assign_login" class="mt-3 grid gap-4 sm:grid-cols-2">
            <input v-model="form.login_email" type="email" placeholder="Login Email" class="border border-white/20 bg-slate-900 px-3 py-2" />
            <select v-model="form.login_role" class="border border-white/20 bg-slate-900 px-3 py-2">
              <option value="">Select role</option>
              <option v-for="role in assignableRoles" :key="role" :value="role">
                {{ role }}
              </option>
            </select>
            <input v-model="form.login_password" type="password" placeholder="Login Password" class="border border-white/20 bg-slate-900 px-3 py-2" />
            <input v-model="form.login_password_confirmation" type="password" placeholder="Confirm Password" class="border border-white/20 bg-slate-900 px-3 py-2" />
          </div>

          <label v-if="editingId" class="mt-4 flex items-center gap-2">
            <input v-model="form.revoke_login" type="checkbox" />
            <span>Revoke login access for this staff member</span>
          </label>
        </div>

        <div class="flex gap-3">
          <button type="submit" class="border border-emerald-300 bg-emerald-300 px-5 py-2 text-slate-950">
            {{ editingId ? 'Update Staff' : 'Create Staff' }}
          </button>
          <button type="button" class="border border-white/20 px-5 py-2" @click="resetForm">
            Reset
          </button>
        </div>

        <div v-if="Object.keys(form.errors).length" class="text-sm text-rose-300">
          <p v-for="(message, key) in form.errors" :key="key">{{ message }}</p>
        </div>
      </form>

      <div class="mt-8 overflow-x-auto border border-white/10">
        <table class="min-w-full text-left text-sm">
          <thead class="border-b border-white/10">
            <tr>
              <th class="px-3 py-2">Name</th>
              <th class="px-3 py-2">Department</th>
              <th class="px-3 py-2">Role</th>
              <th class="px-3 py-2">Login</th>
              <th class="px-3 py-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="record in records" :key="record.id" class="border-b border-white/5">
              <td class="px-3 py-2">{{ record.full_name }}</td>
              <td class="px-3 py-2">{{ record.department || '-' }}</td>
              <td class="px-3 py-2">{{ record.login_role || record.role_category || '-' }}</td>
              <td class="px-3 py-2">
                <span v-if="record.has_login">{{ record.login_email }}</span>
                <span v-else>No login</span>
              </td>
              <td class="px-3 py-2">
                <div class="flex gap-2">
                  <button class="border border-white/20 px-2 py-1" @click="editRecord(record)">Edit</button>
                  <button class="border border-rose-400/60 px-2 py-1 text-rose-300" @click="destroyRecord(record.id)">Delete</button>
                </div>
              </td>
            </tr>
            <tr v-if="!records.length">
              <td colspan="5" class="px-3 py-6 text-center text-slate-400">No staff records yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>