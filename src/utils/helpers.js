// Date and time formatting utilities
export const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

export const formatTime = (timeString) => {
  if (!timeString) return ''
  const [hours, minutes] = timeString.split(':')
  const date = new Date()
  date.setHours(parseInt(hours), parseInt(minutes))
  return date.toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  })
}

export const formatDateTime = (dateString, timeString) => {
  if (!dateString || !timeString) return ''
  return `${formatDate(dateString)} at ${formatTime(timeString)}`
}

// Status utilities
export const getStatusClass = (status) => {
  const statusClasses = {
    pending: 'badge-warning',
    confirmed: 'badge-success',
    completed: 'badge-primary',
    cancelled: 'badge-danger',
    rescheduled: 'badge-gray'
  }
  return statusClasses[status] || 'badge-gray'
}

export const getStatusText = (status) => {
  const statusTexts = {
    pending: 'Pending',
    confirmed: 'Confirmed',
    completed: 'Completed',
    cancelled: 'Cancelled',
    rescheduled: 'Rescheduled'
  }
  return statusTexts[status] || 'Unknown'
}

// Clipboard utilities
export const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text)
    return true
  } catch (err) {
    // Fallback for older browsers
    const textArea = document.createElement('textarea')
    textArea.value = text
    document.body.appendChild(textArea)
    textArea.focus()
    textArea.select()
    try {
      document.execCommand('copy')
      document.body.removeChild(textArea)
      return true
    } catch (fallbackErr) {
      document.body.removeChild(textArea)
      return false
    }
  }
}

// Validation utilities
export const validateEmail = (email) => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

export const validatePhone = (phone) => {
  const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/
  return phoneRegex.test(phone.replace(/\s/g, ''))
}

export const validatePassword = (password) => {
  return password.length >= 8
}

// Debounce utility
export const debounce = (func, wait) => {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

// Local storage utilities
export const getFromStorage = (key, defaultValue = null) => {
  try {
    const item = localStorage.getItem(key)
    return item ? JSON.parse(item) : defaultValue
  } catch (error) {
    console.error('Error reading from localStorage:', error)
    return defaultValue
  }
}

export const setToStorage = (key, value) => {
  try {
    localStorage.setItem(key, JSON.stringify(value))
    return true
  } catch (error) {
    console.error('Error writing to localStorage:', error)
    return false
  }
}

export const removeFromStorage = (key) => {
  try {
    localStorage.removeItem(key)
    return true
  } catch (error) {
    console.error('Error removing from localStorage:', error)
    return false
  }
}

// Error handling utilities
export const handleApiError = (error) => {
  if (error.response?.data?.message) {
    return error.response.data.message
  } else if (error.message) {
    return error.message
  } else {
    return 'An unexpected error occurred. Please try again.'
  }
}

// Loading state utilities
export const createLoadingState = () => ({
  isLoading: false,
  error: null,
  data: null
})

export const setLoading = (state, loading = true) => {
  state.isLoading = loading
  if (loading) {
    state.error = null
  }
}

export const setError = (state, error) => {
  state.isLoading = false
  state.error = error
}

export const setData = (state, data) => {
  state.isLoading = false
  state.error = null
  state.data = data
}