<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'secretary',
  department: '',
})

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <GuestLayout>
    <Head title="Register" />

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <InputLabel for="name" value="Name" />
        <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus autocomplete="name" />
        <InputError class="mt-2" :message="form.errors.name" />
      </div>

      <div>
        <InputLabel for="email" value="Email" />
        <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required autocomplete="username" />
        <InputError class="mt-2" :message="form.errors.email" />
      </div>

      <div>
        <InputLabel for="role" value="Role" />
        <select id="role" v-model="form.role" class="mt-1 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
          <option value="principal">Principal</option>
          <option value="deputy_principal">Deputy Principal</option>
          <option value="dean">Dean</option>
          <option value="hod">HOD</option>
          <option value="school_examiner">School Examiner</option>
          <option value="class_teacher">Class Teacher</option>
          <option value="secretary">Secretary</option>
          <option value="accountant">Accountant</option>
          <option value="store_keeper">Store Keeper</option>
        </select>
        <InputError class="mt-2" :message="form.errors.role" />
      </div>

      <div>
        <InputLabel for="department" value="Department (Optional)" />
        <TextInput id="department" type="text" class="mt-1 block w-full" v-model="form.department" autocomplete="organization-title" />
        <InputError class="mt-2" :message="form.errors.department" />
      </div>

      <div>
        <InputLabel for="password" value="Password" />
        <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required autocomplete="new-password" />
        <InputError class="mt-2" :message="form.errors.password" />
      </div>

      <div>
        <InputLabel for="password_confirmation" value="Confirm Password" />
        <TextInput id="password_confirmation" type="password" class="mt-1 block w-full" v-model="form.password_confirmation" required autocomplete="new-password" />
        <InputError class="mt-2" :message="form.errors.password_confirmation" />
      </div>

      <div class="flex items-center justify-end gap-4">
        <Link :href="route('login')" class="text-sm text-slate-600 underline hover:text-slate-900">Already registered?</Link>
        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">Register</PrimaryButton>
      </div>
    </form>
  </GuestLayout>
</template>