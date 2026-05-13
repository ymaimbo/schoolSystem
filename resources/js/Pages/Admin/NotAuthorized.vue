<script setup>
import { Head, Link } from '@inertiajs/vue3'
</script>

<template>
  <Head title="Not Authorized" />
  <div class="flex min-h-screen items-center justify-center bg-slate-100 px-6">
    <div class="max-w-lg border bg-white p-8 text-center">
      <h1 class="text-2xl font-bold text-slate-900">Access Denied</h1>
      <p class="mt-3 text-slate-600">Your account role does not have permission to access this section.</p>
      <Link :href="route('admin.dashboard')" class="mt-6 inline-block bg-slate-900 px-4 py-2 font-semibold text-white">
        Back to Dashboard
      </Link>
    </div>
  </div>
</template>