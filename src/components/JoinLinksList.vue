<template>
  <div class="space-y-4">
    <!-- Empty State -->
    <div v-if="!joinLinks.length" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No join links found</h3>
      <p class="mt-1 text-sm text-gray-500">Create your first join link to get started.</p>
    </div>

    <!-- Join Links List -->
    <div v-else class="space-y-4">
      <div
        v-for="link in joinLinks"
        :key="link.id"
        class="card hover:shadow-md transition-shadow duration-200"
      >
        <div class="card-body">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center space-x-3 mb-3">
                <h4 class="text-lg font-medium text-gray-900">
                  {{ link.title || 'Join Link' }}
                </h4>
                <span :class="getLinkStatusClass(link.status)" class="badge">
                  {{ getLinkStatusText(link.status) }}
                </span>
                <span v-if="link.type" class="badge badge-secondary">
                  {{ link.type }}
                </span>
              </div>

              <div class="space-y-2 text-sm text-gray-600">
                <div class="flex items-center">
                  <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd" />
                  </svg>
                  <code class="bg-gray-100 px-2 py-1 rounded text-xs font-mono">
                    {{ link.url }}
                  </code>
                </div>

                <div v-if="link.meeting_title" class="flex items-center">
                  <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                  </svg>
                  {{ link.meeting_title }}
                </div>

                <div v-if="link.meeting_date" class="flex items-center">
                  <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                  </svg>
                  {{ formatDateTime(link.meeting_date) }}
                </div>

                <div v-if="link.created_at" class="flex items-center">
                  <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                  </svg>
                  Created {{ formatDateTime(link.created_at) }}
                </div>

                <div v-if="link.expires_at" class="flex items-center">
                  <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                  </svg>
                  Expires {{ formatDateTime(link.expires_at) }}
                </div>

                <div v-if="link.usage_count !== undefined" class="flex items-center">
                  <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Used {{ link.usage_count }} time{{ link.usage_count !== 1 ? 's' : '' }}
                  <span v-if="link.max_uses"> (max {{ link.max_uses }})</span>
                </div>
              </div>

              <div v-if="link.description" class="mt-3 text-sm text-gray-600">
                <p class="italic">{{ link.description }}</p>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col space-y-2 ml-4">
              <button
                @click="$emit('copy-link', link.url)"
                class="btn-primary text-xs"
                :disabled="link.status === 'expired' || link.status === 'disabled'"
              >
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" />
                  <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z" />
                </svg>
                Copy
              </button>

              <button
                @click="$emit('send-link', link.id)"
                class="btn-secondary text-xs"
                :disabled="link.status === 'expired' || link.status === 'disabled'"
              >
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                  <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                </svg>
                Send
              </button>

              <button
                @click="$emit('test-host-link', link)"
                class="btn-success text-xs"
                :disabled="link.status === 'expired' || link.status === 'disabled'"
                title="Test host meeting link (opens in new tab)"
              >
                Test Host
              </button>

              <button
                v-if="link.status === 'active'"
                @click="$emit('disable-link', link.id)"
                class="btn-warning text-xs"
              >
                Disable
              </button>

              <button
                v-else-if="link.status === 'disabled'"
                @click="$emit('enable-link', link.id)"
                class="btn-success text-xs"
              >
                Enable
              </button>

              <button
                @click="$emit('delete-link', link.id)"
                class="btn-danger text-xs"
              >
                Delete
              </button>
            </div>
          </div>

          <!-- Usage Statistics -->
          <div v-if="link.usage_stats && link.usage_stats.length" class="mt-4 pt-4 border-t border-gray-200">
            <h5 class="text-sm font-medium text-gray-900 mb-2">Recent Usage</h5>
            <div class="space-y-1">
              <div
                v-for="usage in link.usage_stats.slice(0, 3)"
                :key="usage.id"
                class="flex justify-between text-xs text-gray-600"
              >
                <span>{{ usage.attendee_name || usage.attendee_email }}</span>
                <span>{{ formatDateTime(usage.used_at) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { formatDateTime } from '../utils/helpers.js'

export default {
  name: 'JoinLinksList',
  props: {
    joinLinks: {
      type: Array,
      default: () => []
    }
  },
  emits: [
    'copy-link',
    'send-link',
    'disable-link',
    'enable-link',
    'delete-link',
    'test-host-link'
  ],
  setup() {
    const getLinkStatusClass = (status) => {
      const statusClasses = {
        active: 'badge-success',
        disabled: 'badge-warning',
        expired: 'badge-danger',
        pending: 'badge-secondary'
      }
      return statusClasses[status] || 'badge-secondary'
    }

    const getLinkStatusText = (status) => {
      const statusTexts = {
        active: 'Active',
        disabled: 'Disabled',
        expired: 'Expired',
        pending: 'Pending'
      }
      return statusTexts[status] || status
    }

    const testHostLink = (link) => {
      if (link.join_url) {
        window.open(link.join_url, '_blank', 'noopener,noreferrer')
      }
    }

    return {
      formatDateTime,
      getLinkStatusClass,
      getLinkStatusText,
      testHostLink
    }
  }
}
</script>