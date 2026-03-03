<template>
  <div v-if="shouldShowTimer" class="countdown-timer" :class="{
    'urgent': isUrgent,
    'warning': isWarning,
    'normal': isNormal
  }">
    <div class="countdown-display">
      <div v-if="timeRemaining.days > 0" class="time-unit">
        <span class="time-value">{{ timeRemaining.days }}</span>
        <span class="time-label">{{ timeRemaining.days === 1 ? 'day' : 'days' }}</span>
      </div>
      <div v-if="timeRemaining.hours > 0 || timeRemaining.days > 0" class="time-unit">
        <span class="time-value">{{ String(timeRemaining.hours).padStart(2, '0') }}</span>
        <span class="time-label">{{ timeRemaining.hours === 1 ? 'hr' : 'hrs' }}</span>
      </div>
      <div class="time-unit">
        <span class="time-value">{{ String(timeRemaining.minutes).padStart(2, '0') }}</span>
        <span class="time-label">{{ timeRemaining.minutes === 1 ? 'min' : 'mins' }}</span>
      </div>
      <div class="time-unit">
        <span class="time-value">{{ String(timeRemaining.seconds).padStart(2, '0') }}</span>
        <span class="time-label">{{ timeRemaining.seconds === 1 ? 'sec' : 'secs' }}</span>
      </div>
    </div>
    <div class="countdown-label">
      <span v-if="isExpired" class="expired-text">Meeting Started</span>
      <span v-else-if="isUrgent" class="urgent-text">Starting Soon!</span>
      <span v-else-if="isWarning" class="warning-text">Starting in</span>
      <span v-else class="normal-text">Time until meeting</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
  meetingDate: {
    type: String,
    required: true
  },
  startTime: {
    type: String,
    required: true
  },
  autoReset: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['expired', 'urgent', 'warning'])

const timeRemaining = ref({
  days: 0,
  hours: 0,
  minutes: 0,
  seconds: 0
})

const isExpired = ref(false)
const intervalId = ref(null)

// Computed properties for visual states
const isUrgent = computed(() => {
  if (isExpired.value) return false
  const totalMinutes = timeRemaining.value.days * 24 * 60 + 
                      timeRemaining.value.hours * 60 + 
                      timeRemaining.value.minutes
  return totalMinutes <= 15 // Less than 15 minutes
})

const isWarning = computed(() => {
  if (isExpired.value || isUrgent.value) return false
  const totalMinutes = timeRemaining.value.days * 24 * 60 + 
                      timeRemaining.value.hours * 60 + 
                      timeRemaining.value.minutes
  return totalMinutes <= 60 // Less than 1 hour
})

const isNormal = computed(() => {
  return !isExpired.value && !isUrgent.value && !isWarning.value
})

const shouldShowTimer = computed(() => {
  if (timeRemaining.value.days === 0 && timeRemaining.value.hours === 0 && timeRemaining.value.minutes === 0 && timeRemaining.value.seconds === 0) return false
  
  const now = new Date()
  const meetingDateTime = new Date(`${props.meetingDate} ${props.startTime}`)
  
  // Check if meeting is today
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const meetingDate = new Date(meetingDateTime)
  meetingDate.setHours(0, 0, 0, 0)
  
  const isToday = today.getTime() === meetingDate.getTime()
  
  // Check if within 12 hours
  const hoursUntilMeeting = (meetingDateTime - now) / (1000 * 60 * 60)
  const isWithin12Hours = hoursUntilMeeting <= 12
  
  return isToday && isWithin12Hours && !isExpired.value
})

const calculateTimeRemaining = () => {
  try {
    const now = new Date()
    const meetingDateTime = new Date(`${props.meetingDate} ${props.startTime}`)
    
    if (isNaN(meetingDateTime.getTime())) {
      console.error('Invalid meeting date/time:', props.meetingDate, props.startTime)
      return
    }
    
    const timeDiff = meetingDateTime.getTime() - now.getTime()
    
    if (timeDiff <= 0) {
      // Meeting has started or passed
      timeRemaining.value = { days: 0, hours: 0, minutes: 0, seconds: 0 }
      isExpired.value = true
      emit('expired')
      return
    }
    
    isExpired.value = false
    
    const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24))
    const hours = Math.floor((timeDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
    const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60))
    const seconds = Math.floor((timeDiff % (1000 * 60)) / 1000)
    
    timeRemaining.value = { days, hours, minutes, seconds }
    
    // Emit events for different states
    if (isUrgent.value) {
      emit('urgent', timeRemaining.value)
    } else if (isWarning.value) {
      emit('warning', timeRemaining.value)
    }
  } catch (error) {
    console.error('Error calculating time remaining:', error)
  }
}

const startTimer = () => {
  calculateTimeRemaining()
  intervalId.value = setInterval(calculateTimeRemaining, 1000)
}

const stopTimer = () => {
  if (intervalId.value) {
    clearInterval(intervalId.value)
    intervalId.value = null
  }
}

// Watch for prop changes to reset timer
watch([() => props.meetingDate, () => props.startTime], () => {
  if (props.autoReset) {
    stopTimer()
    startTimer()
  }
}, { immediate: false })

onMounted(() => {
  startTimer()
})

onUnmounted(() => {
  stopTimer()
})

// Expose methods for manual control
defineExpose({
  startTimer,
  stopTimer,
  calculateTimeRemaining,
  shouldShowTimer
})
</script>

<style scoped>
.countdown-timer {
  @apply bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4 mb-4 transition-all duration-300;
}

.countdown-timer.urgent {
  @apply bg-gradient-to-r from-red-50 to-pink-50 border-red-300 shadow-lg;
  animation: pulse-urgent 2s infinite;
}

.countdown-timer.warning {
  @apply bg-gradient-to-r from-yellow-50 to-orange-50 border-yellow-300;
}

.countdown-timer.normal {
  @apply bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200;
}

.countdown-display {
  @apply flex justify-center items-center space-x-4 mb-2;
}

.time-unit {
  @apply flex flex-col items-center min-w-0;
}

.time-value {
  @apply text-2xl font-bold text-gray-800;
}

.countdown-timer.urgent .time-value {
  @apply text-red-600;
}

.countdown-timer.warning .time-value {
  @apply text-yellow-600;
}

.countdown-timer.normal .time-value {
  @apply text-blue-600;
}

.time-label {
  @apply text-xs font-medium text-gray-500 uppercase tracking-wide;
}

.countdown-label {
  @apply text-center;
}

.expired-text {
  @apply text-sm font-semibold text-red-600;
}

.urgent-text {
  @apply text-sm font-semibold text-red-600 animate-pulse;
}

.warning-text {
  @apply text-sm font-medium text-yellow-600;
}

.normal-text {
  @apply text-sm font-medium text-blue-600;
}

@keyframes pulse-urgent {
  0%, 100% {
    @apply shadow-lg;
  }
  50% {
    @apply shadow-xl;
    transform: scale(1.02);
  }
}

/* Responsive design */
@media (max-width: 640px) {
  .countdown-display {
    @apply space-x-2;
  }
  
  .time-value {
    @apply text-xl;
  }
  
  .time-label {
    @apply text-xs;
  }
}

@media (max-width: 480px) {
  .countdown-display {
    @apply space-x-1;
  }
  
  .time-value {
    @apply text-lg;
  }
  
  .time-unit {
    @apply min-w-0;
  }
}
</style>