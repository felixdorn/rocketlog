<script setup>
import { ref } from 'vue'
import { usePage } from '@inertiajs/inertia-vue3'
import AppLayout from './AppLayout.vue'
import JetButton from '@/Jetstream/Button.vue'
import Index from '@/Components/Index.vue'

const showingWelcome = ref(! usePage().props.value.user.preferences?.dismissed_welcome)

const dismissWelcome = () => {
  showingWelcome.value = false

  axios.patch(
    route('user-preferences.update'),
    { dismissed_welcome: true },
    { preserveScroll: true }
  )
}
</script>

<template>
  <AppLayout>
    <div class="md:py-12 flex-1 flex flex-col">
      <div class="flex-1 max-w-7xl w-full mx-auto md:px-6 flex flex-col md:flex-row-reverse">
        <aside class="hidden md:block md:ml-12 md:pt-12 lg:w-80">
          <Index />
        </aside>

        <div class="flex-1 p-4 sm:p-6 md:p-12 bg-white dark:bg-gray-800 sm:rounded-lg md:shadow-xl">
          <slot />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
