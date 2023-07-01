<script setup>
import JournalLayout from '@/Layouts/JournalLayout.vue'
import Bullet from '@/Components/Bullet.vue'
import ContentUpdateNotification from '@/Components/ContentUpdateNotification.vue'
import NewBullet from '@/Components/NewBullet.vue'
import JetConfirmationModal from '@/Jetstream/ConfirmationModal.vue'
import JetDropdown from '@/Jetstream/Dropdown.vue'
import JetDropdownLink from '@/Jetstream/DropdownLink.vue'
import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
import JetDangerButton from '@/Jetstream/DangerButton.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import Icon from '@/Components/Icon.vue'
import {Link, useForm} from '@inertiajs/inertia-vue3'
import Draggable from 'vuedraggable'
import {Inertia} from '@inertiajs/inertia'
import {nextTick, onMounted, onUnmounted, ref, watch} from 'vue'
import dayjs from 'dayjs'

const props = defineProps(['collection'])

const bullets = ref(props.collection.bullets)
const processing = ref(false)
const name = ref(props.collection.name)
const type = ref(props.collection.type)
const hideDone = ref(props.collection.hide_done)
const drawer = ref(false)
const confirmingClearDone = ref(false)
const confirmingDeleteCollection = ref(false)
const addUserForm = useForm({
  email: '',
})
const userBeingRemoved = ref(null)
const removeUserForm = useForm()
const contentUpdateAvailable = ref(false)
const reloading = ref(false)

const reloadWhenHiddenIfContentAvailable = () => {
  const handler = (e) => {
    if (document.visibilityState === 'hidden' && contentUpdateAvailable.value) {
      reload()
    }
  }

  document.addEventListener('visibilitychange', handler)

  onUnmounted(() => document.removeEventListener('visibilitychange', handler))
}

const update = () => Inertia.patch(
  route('c.update', props.collection.hashid),
  {
    name: name.value,
    type: type.value,
    hide_done: hideDone.value,
  },
  {preserveScroll: true}
)

const storeBullet = (bullet) => new Promise((resolve, reject) => {
  Inertia.post(
    route('c.bullets.store', props.collection.hashid),
    bullet,
    {
      preserveScroll: true,

      onSuccess: () => resolve(),
      onError: () => reject(),
    }
  )
})

const updateBullet = (bullet) => Inertia.patch(
  route('c.bullets.update', [props.collection.hashid, bullet.id]),
  bullet,
  {
    preserveScroll: true,

  }
)

const migrateBulletTo = (bullet, collection) => Inertia.put(
  route('c.bullets.move', collection.hashid),
  {id: bullet.id},
  {
    preserveScroll: true,

  }
)

const migrateBulletToDailyLog = (bullet) => Inertia.put(
  route('daily-log.move'),
  {
    id: bullet.id,
    date: dayjs().startOf('day').format('YYYY-MM-DD'),
  },
  {
    preserveScroll: true,

  }
)

const deleteBullet = (bullet) => Inertia.delete(
  route('c.bullets.destroy', [props.collection.hashid, bullet.id]),
  {
    preserveScroll: true,

  }
)

const clearDone = () => {
  processing.value = true

  Inertia.delete(
    route('c.destroy-done', props.collection.hashid),
    {
      preserveScroll: true,

      onFinish: () => {
        processing.value = false
        confirmingClearDone.value = false
      }
    }
  )
}

const deleteCollection = () => {
  processing.value = true

  Inertia.delete(
    route('c.destroy', props.collection.hashid),
    {
      preserveScroll: true,

      onFinish: () => processing.value = false
    }
  )
}

const addUser = () => {
  processing.value = true

  Inertia.post(
    route('c.users.store', props.collection.hashid),
    {email: addUserEmail.value},
    {
      preserveScroll: true,

      onFinish: () => {
        processing.value = false
        addUserEmail.value = ''
      }
    }
  )
}

const confirmUserRemoval = (user) => {
  userBeingRemoved.value = user
}

const removeUser = () => removeUserForm.delete(
  route('c.users.destroy',
    [props.collection.hashid, userBeingRemoved.value]),
  {
    errorBag: 'removeUser',
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => (userBeingRemoved.value = null),
  }
)

const reload = () => {
  reloading.value = true

  Inertia.reload({
    only: ['collection'],
    onFinish: () => {
      contentUpdateAvailable.value = false
      nextTick(() => reloading.value = false)
    }
  })
}

const saveOrder = () => Inertia.put(
  route('c.order.update', props.collection.hashid),
  bullets.value.map(bullet => bullet.id),
  {
    preserveScroll: true,

  }
)

watch(name, update)
watch(type, update)
watch(hideDone, update)
watch(() => props.collection.bullets, newBullets => bullets.value = newBullets)

onMounted(() => {
  listenForUpdates()
  reloadWhenHiddenIfContentAvailable()
})
</script>

<template>
  <JournalLayout>
    <ContentUpdateNotification v-if="contentUpdateAvailable" :reloading="reloading" @reload="reload">
      Collection updated
    </ContentUpdateNotification>

    <div v-if="$page.props.collections.length === 1 && collection.bullets.length === 0"
         class="mb-10 leading-relaxed text-gray-600 dark:text-gray-300">
      <h1 class="text-xl font-semibold text-gray-700 dark:text-gray-300">
        <Icon autosize class="mr-1 text-gray-600 dark:text-gray-400" name="medium/clipboard"/>
        Collections
      </h1>
      <p class="mt-4">Collections can be used for all sorts of things, such as:</p>
      <ul class="mt-4 list-disc ml-4">
        <li>Project ideas</li>
        <li>Books to read</li>
        <li>Movies and TV shows to watch</li>
        <li>Shopping lists</li>
        <li>A menu of things you like to eat</li>
        <li>A meal plan</li>
      </ul>
      <p class="mt-4">Collections can also be shared with other users. Handy for a family shopping list!</p>
      <p class="mt-4">You can also choose between a bulleted list, or a simple checklist. Also toggle between showing or
        hiding your completed items.</p>
      <p class="mt-4">Unlike the daily log, nothing will fade away here, so try not to let things get out of hand.</p>
      <p class="mt-4">Enter your first item to get started...</p>
    </div>

    <div class="flex flex-col">
      <div :class="drawer ? '' : 'border-b border-gray-200 dark:border-gray-700'"
           class="-mt-3 flex items-center justify-between">
        <input
          v-model.lazy="name"
          class="w-full flex-1 px-0 py-3 font-bold border-none bg-transparent text-gray-700 dark:text-gray-200 focus:ring-0"
          spellcheck="false"
          type="text"
        />

        <div class="flex shrink-0 gap-2 text-gray-400">
          <button
            :class="hideDone ? 'text-gray-400 hover:text-gray-500 focus:text-gray-500' : 'text-pink-600 hover:text-pink-700 focus:text-pink-700'"
            class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 transition duration-150 ease-in-out"
            title="Show done"
            type="button"
            @click="hideDone = ! hideDone"
          >
            <Icon class="w-6 h-6 md:w-5 md:h-5" name="medium/clipboard-check"/>
          </button>

          <button
            :class="drawer === 'share' ? 'bg-gray-100 dark:bg-gray-900 text-gray-500 dark:text-gray-300' : 'text-gray-400'"
            class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 transition duration-150 ease-in-out"
            type="button"
            @click="drawer = drawer === 'share' ? '' : 'share'"
          >
            <Icon class="w-6 h-6 md:w-5 md:h-5" name="medium/share"/>
          </button>

          <JetDropdown>
            <template #trigger>
              <button
                class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 transition duration-150 ease-in-out text-gray-400"
                type="button"
              >
                <Icon class="w-6 h-6 md:w-5 md:h-5" name="medium/adjustments"/>
              </button>
            </template>
            <template #content>
              <div class="px-4 py-3 space-y-2">
                <div class="text-xs text-gray-400 dark:text-gray-300">
                  List style
                </div>
                <label class="flex items-center">
                  <input v-model="type"
                         class="h-5 w-5 border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-700"
                         name="type" type="radio"
                         value="bullet">
                  <span class="ml-2 font-semibold text-gray-700 dark:text-gray-300">Bullets</span>
                </label>
                <label class="flex items-center">
                  <input v-model="type"
                         class="h-5 w-5 border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-700"
                         name="type" type="radio"
                         value="checklist">
                  <span class="ml-2 font-semibold text-gray-700 dark:text-gray-300">Checklist</span>
                </label>
              </div>
              <div class="border-t border-gray-100 dark:border-gray-600"></div>
              <JetDropdownLink as="button" @click.native="confirmingClearDone = true">
                <svg class="-mt-px mr-1 h-4 w-4 inline-block text-gray-500" fill="none"
                     stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M9 13h6m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    stroke-linecap="round" stroke-linejoin="round"
                    stroke-width="2"/>
                </svg>
                Clear Done
              </JetDropdownLink>
              <JetDropdownLink as="button" @click.native="confirmingDeleteCollection = true">
                <svg class="-mt-px mr-1 h-4 w-4 inline-block text-gray-500" fill="none"
                     stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    stroke-linecap="round" stroke-linejoin="round"
                    stroke-width="2"/>
                </svg>
                Delete Collection
              </JetDropdownLink>
            </template>
          </JetDropdown>
        </div>
      </div>

      <div
        v-show="drawer"
        class="-mx-4 px-4 sm:-mx-6 sm:px-6 md:-mx-12 md:px-12 py-6 bg-gray-100 dark:bg-black dark:bg-opacity-20 shadow-inner"
      >
        <div v-show="drawer === 'share'" class="grid gap-6">
          <div v-if="collection.users.length > 0"
               class="p-6 space-y-6 border border-gray-300 dark:border-gray-700 rounded">
            <div v-for="user in collection.users" :key="user.id" class="flex items-center justify-between">
              <div class="flex items-center">
                <img :alt="user.name" :src="user.profile_photo_url" class="w-8 h-8 rounded-full">
                <div class="ml-4 text-gray-900 dark:text-gray-100">{{ user.name }}</div>
              </div>

              <div class="flex items-center">
                <button class="cursor-pointer ml-6 text-sm text-red-500 focus:outline-none"
                        @click="confirmUserRemoval(user)">
                  Remove
                </button>
              </div>
            </div>
          </div>

          <form @submit.prevent="addUserForm.post(route('c.users.store', collection.hashid))">
            <h3 class="text-gray-800 dark:text-gray-200">
              Add a new collaborator, allowing them to view and edit this collection.
            </h3>

            <div class="mt-2 max-w-xl text-sm text-gray-600 dark:text-gray-400">
              Please provide the email address of the person you would like to add to this collection. The email address
              must be associated with an existing account.
            </div>

            <label class="mt-4 block font-semibold text-gray-600 dark:text-gray-400" for="email">Email</label>

            <div class="mt-1 flex flex-wrap justify-end gap-4">
              <input
                id="email"
                v-model="addUserForm.email"
                class="flex-1 block border-gray-300 dark:border-gray-500 rounded bg-transparent text-gray-900 dark:text-gray-100"
                name="email"
                type="email"
              />

              <button
                :disabled="addUserForm.processing"
                class="px-4 py-2 flex items-center border border-gray-300 dark:border-gray-500 rounded font-medium text-gray-900 dark:text-gray-100"
                type="submit"
              >
                <Icon class="mr-1 text-gray-400" name="small/user-add" style="height: 1em; width: 1em;"/>
                Add
              </button>
            </div>

            <JetInputError :message="addUserForm.errors.email" class="mt-2" for="email"/>
          </form>
        </div>
      </div>
    </div>

    <Draggable
      v-model="bullets"
      handle=".drag-handle"
      item-key="id"
      @update="saveOrder"
    >
      <template #item="{ element: bullet }">
        <Bullet
          v-show="! bullet.complete || ! hideDone"
          :bullet="bullet"
          :draggable="true"
          :type="type"
          @delete="deleteBullet"
          @input="updateBullet"
          @migrateTo="migrateBulletTo"
          @migrateToDailyLog="migrateBulletToDailyLog"
        >
          <template #status>
            <Link
              v-if="bullet.date && bullet.user_id === $page.props.user.id"
              :class="[
                                bullet.complete ? 'opacity-50' : ''
                            ]"
              :href="route('daily-log.index')"
              class="inline-block md:-mt-1 -mb-1 p-2 rounded-md text-gray-400 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 transition duration-150 ease-in-out"
              title="Appears in daily log"
            >
              <Icon class="h-6 w-6 md:h-5 md:w-5" name="medium/calendar"/>
            </Link>
          </template>
        </Bullet>
      </template>
    </Draggable>

    <NewBullet @input="storeBullet"/>

    <JetConfirmationModal :show="confirmingClearDone" @close="confirmingClearDone = false">
      <template #title>
        Clear Done
      </template>
      <template #content>
        Are you sure you want to clear all complete items?
      </template>
      <template #footer>
        <JetSecondaryButton @click.native="confirmingClearDone = false">
          Nevermind
        </JetSecondaryButton>

        <JetDangerButton :class="{ 'opacity-25': processing }" :disabled="processing" class="ml-2"
                         @click.native="clearDone">
          Clear Done
        </JetDangerButton>
      </template>
    </JetConfirmationModal>

    <JetConfirmationModal :show="confirmingDeleteCollection" @close="confirmingDeleteCollection = false">
      <template #title>
        Delete Collection
      </template>
      <template #content>
        Are you sure you want to delete this collection?
      </template>
      <template #footer>
        <jet-secondary-button @click.native="confirmingDeleteCollection = false">
          Nevermind
        </jet-secondary-button>

        <jet-danger-button :class="{ 'opacity-25': processing }" :disabled="processing" class="ml-2"
                           @click.native="deleteCollection">
          Delete Collection
        </jet-danger-button>
      </template>
    </JetConfirmationModal>

    <JetConfirmationModal :show="userBeingRemoved !== null" @close="userBeingRemoved = null">
      <template #title>
        Remove User
      </template>
      <template #content>
        Are you sure you want to remove this user?
      </template>
      <template #footer>
        <JetSecondaryButton @click.native="userBeingRemoved = null">
          Nevermind
        </JetSecondaryButton>

        <JetDangerButton :class="{ 'opacity-25': removeUserForm.processing }" :disabled="removeUserForm.processing"
                         class="ml-2"
                         @click.native="removeUser">
          Remove User
        </JetDangerButton>
      </template>
    </JetConfirmationModal>
  </JournalLayout>
</template>
