<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  submitRoute: {
    type: String,
    default: '',
  },
  backRoute: {
    type: String,
    default: '',
  },
  isSuperAdminContext: {
    type: Boolean,
    default: false,
  },
})

const form = useForm({
  name: '',
  slug: '',
  location: '',
  county: '',
  type: '',
  status: 'active',
  note: '',
  code: '',
  logo_path: '',
  hero_image_path: '',
  principal_name: '',
  principal_email: '',
  principal_password: '',
  principal_password_confirmation: '',
})

const submit = () => {
  const target = props.submitRoute || route('superadmin.schools.store')
  form.post(target)
}
</script>

<template>
  <Head title="Register School" />

  <div class="min-h-screen bg-slate-950 text-slate-100">
    <main class="mx-auto w-full max-w-4xl px-6 py-12">
      <header>
        <p class="text-xs uppercase tracking-[0.28em] text-emerald-300">Super Admin Onboarding</p>
        <h1 class="mt-3 text-3xl font-semibold sm:text-4xl">Register A School</h1>
        <p class="mt-3 max-w-2xl text-sm text-slate-300">
          Create a school profile and assign the principal as the first school administrator.
        </p>
      </header>

      <form class="mt-10 space-y-10" @submit.prevent="submit">
        <section class="space-y-4">
          <h2 class="text-lg font-semibold">School Details</h2>

          <div class="grid gap-4 sm:grid-cols-2">
            <label class="block">
              <span class="mb-1 block text-sm text-slate-300">School Name</span>
              <input v-model="form.name" type="text" class="w-full border border-white/20 bg-slate-900 px-3 py-2" />
              <p v-if="form.errors.name" class="mt-1 text-xs text-rose-300">{{ form.errors.name }}</p>
            </label>

            <label class="block">
              <span class="mb-1 block text-sm text-slate-300">Slug</span>
              <input v-model="form.slug" type="text" class="w-full border border-white/20 bg-slate-900 px-3 py-2" placeholder="vigurungani-senior-school" />
              <p v-if="form.errors.slug" class="mt-1 text-xs text-rose-300">{{ form.errors.slug }}</p>
            </label>

            <label class="block">
              <span class="mb-1 block text-sm text-slate-300">Location</span>
              <input v-model="form.location" type="text" class="w-full border border-white/20 bg-slate-900 px-3 py-2" />
            </label>

            <label class="block">
              <span class="mb-1 block text-sm text-slate-300">County</span>
              <input v-model="form.county" type="text" class="w-full border border-white/20 bg-slate-900 px-3 py-2" />
            </label>

            <label class="block">
              <span class="mb-1 block text-sm text-slate-300">School Type</span>
              <select v-model="form.type" class="w-full border border-white/20 bg-slate-900 px-3 py-2">
                <option value="">Select type</option>
                <option value="day">Day</option>
                <option value="boarding">Boarding</option>
                <option value="mixed">Mixed</option>
              </select>
            </label>

            <label class="block">
              <span class="mb-1 block text-sm text-slate-300">Status</span>
              <select v-model="form.status" class="w-full border border-white/20 bg-slate-900 px-3 py-2">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="pending">Pending</option>
              </select>
            </label>

            <label class="block">
              <span class="mb-1 block text-sm text-slate-300">School Code</span>
              <input v-model="form.code" type="text" class="w-full border border-white/20 bg-slate-900 px-3 py-2" />
            </label>

            <label class="block">
              <span class="mb-1 block text-sm text-slate-300">Logo Path</span>
              <input v-model="form.logo_path" type="text" class="w-full border border-white/20 bg-slate-900 px-3 py-2" placeholder="/storage/schools/logo.png" />
            </label>

            <label class="block sm:col-span-2">
              <span class="mb-1 block text-sm text-slate-300">Hero Image Path</span>
              <input v-model="form.hero_image_path" type="text" class="w-full border border-white/20 bg-slate-900 px-3 py-2" placeholder="/storage/schools/hero.jpg" />
            </label>

            <label class="block sm:col-span-2">
              <span class="mb-1 block text-sm text-slate-300">School Note / Motto</span>
              <textarea v-model="form.note" rows="3" class="w-full border border-white/20 bg-slate-900 px-3 py-2" />
            </label>
          </div>
        </section>

        <section class="space-y-4">
          <h2 class="text-lg font-semibold">Principal Account</h2>

          <div class="grid gap-4 sm:grid-cols-2">
            <label class="block">
              <span class="mb-1 block text-sm text-slate-300">Principal Name</span>
              <input v-model="form.principal_name" type="text" class="w-full border border-white/20 bg-slate-900 px-3 py-2" />
              <p v-if="form.errors.principal_name" class="mt-1 text-xs text-rose-300">{{ form.errors.principal_name }}</p>
            </label>

            <label class="block">
              <span class="mb-1 block text-sm text-slate-300">Principal Email</span>
              <input v-model="form.principal_email" type="email" class="w-full border border-white/20 bg-slate-900 px-3 py-2" />
              <p v-if="form.errors.principal_email" class="mt-1 text-xs text-rose-300">{{ form.errors.principal_email }}</p>
            </label>

            <label class="block">
              <span class="mb-1 block text-sm text-slate-300">Principal Password</span>
              <input v-model="form.principal_password" type="password" class="w-full border border-white/20 bg-slate-900 px-3 py-2" />
              <p v-if="form.errors.principal_password" class="mt-1 text-xs text-rose-300">{{ form.errors.principal_password }}</p>
            </label>

            <label class="block">
              <span class="mb-1 block text-sm text-slate-300">Confirm Password</span>
              <input v-model="form.principal_password_confirmation" type="password" class="w-full border border-white/20 bg-slate-900 px-3 py-2" />
            </label>
          </div>
        </section>

        <div class="flex flex-wrap items-center gap-3 pt-2">
          <button
            type="submit"
            class="border border-emerald-300 bg-emerald-300 px-6 py-3 text-sm font-semibold text-slate-950 hover:bg-emerald-200 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="form.processing"
          >
            {{ form.processing ? 'Submitting...' : 'Create School And Principal' }}
          </button>

          <Link
            :href="backRoute || route('superadmin.dashboard')"
            class="border border-white/25 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10"
          >
            Back
          </Link>
        </div>
      </form>
    </main>
  </div>
</template>