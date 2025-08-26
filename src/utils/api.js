import axios from 'axios'

// Get WordPress data from localized variables
const getWpData = () => {
  return window.hbcAttendeeData || window.hbcHostData || {}
}

// Create axios instance with WordPress REST API configuration
const api = axios.create({
  baseURL: getWpData().restUrl,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
  },
})

// Request interceptor to add nonce
api.interceptors.request.use((config) => {
  const wpData = getWpData()

  if (wpData.restNonce) {
    config.headers['X-WP-Nonce'] = wpData.restNonce
  }

  return config
})

// Response interceptor for error handling
api.interceptors.response.use(
  (response) => {
    return response.data
  },
  (error) => {
    console.error('API Error:', error.response?.data?.message || error.message)
    throw error
  }
)

// API methods for attendee dashboard
export const attendeeAPI = {
  getBookings: (type = 'all') => {
    // Use WordPress AJAX endpoint for attendee bookings
    const wpData = getWpData()
    const formData = new FormData()
    formData.append('action', 'hbc_get_attendee_bookings')
    formData.append('type', type)
    formData.append('nonce', wpData.nonce)
    
    return fetch(wpData.ajaxUrl, {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        return { status: true, bookings: data.data || [] }
      } else {
        throw new Error(data.data || 'Failed to fetch bookings')
      }
    })
    .catch(error => {
      console.error('Bookings API Error:', error)
      return { status: false, bookings: [], message: 'Failed to fetch bookings' }
    })
  },
  
  getProfile: () => {
    // Use current user data from WordPress
    const wpData = getWpData()
    return Promise.resolve({
      id: wpData.currentUser?.ID || 0,
      name: wpData.currentUser?.display_name || '',
      email: wpData.currentUser?.user_email || '',
      first_name: wpData.currentUser?.first_name || '',
      last_name: wpData.currentUser?.last_name || '',
      role: wpData.currentUser?.roles?.[0] || 'subscriber'
    })
  },
  
  updateProfile: (data) => {
    // Use WordPress AJAX endpoint for attendee profile update
    const wpData = getWpData()
    const formData = new FormData()
    formData.append('action', 'hbc_update_profile')
    formData.append('hbc_profile_nonce', wpData.profileNonce || wpData.nonce)
    
    // Add profile data to form
    Object.keys(data).forEach(key => {
      formData.append(key, data[key])
    })
    
    return fetch(wpData.ajaxUrl, {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        return data.data
      } else {
        throw new Error(data.data || 'Failed to update profile')
      }
    })
  },
  
  changePassword: (data) => 
    api.post('fd-dashboard/change-password', data),
  
  cancelBooking: (bookingId) => 
    api.post('booking/change-booking-status', { booking_id: bookingId, status: 'cancelled' }),
  
  rescheduleBooking: (bookingId, newDate, newTime) => 
    api.post(`booking/rebooking`, { booking_id: bookingId, new_date: newDate, new_time: newTime }),
  
  getStats: () => {
    // Use WordPress AJAX endpoint for attendee stats
    const wpData = getWpData()
    const formData = new FormData()
    formData.append('action', 'hbc_get_attendee_stats')
    formData.append('nonce', wpData.nonce)
    
    return fetch(wpData.ajaxUrl, {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        return data.data
      } else {
        throw new Error(data.data || 'Failed to fetch stats')
      }
    })
    .catch(error => {
      console.error('Stats API Error:', error)
      return { total_bookings: 0, upcoming_bookings: 0, completed_bookings: 0, cancelled_bookings: 0 }
    })
  },

  getMeetingLink: (bookingId) => {
    return api.get(`jitsi/meeting-link/${bookingId}`).then(response => {
      console.log('Raw API response for attendeeAPI:', response)
      // The response structure should contain the meeting data directly
      if (response && response.status !== undefined) {
        return response
      }
      return response.data || { status: false, meeting_url: null }
    }).catch((error) => {
      console.error('AttendeeAPI getMeetingLink error:', error)
      return { status: false, meeting_url: null }
    })
  },
}

// API methods for host dashboard
export const hostAPI = {
  getBookings: (period = 'today') => {
    // Map period to filter_type that backend expects
    let filter_type = period
    if (period === 'today') {
      filter_type = 'upcoming' // Today's meetings are upcoming meetings for current date
    }
    
    return api.post('booking/lists', { filter_data: { filter_type } }).then(response => {
      // Handle the response structure from core plugin
      if (!response.status || !response.bookings) {
        return { status: false, bookings: [], message: 'Failed to fetch bookings' }
      }
      
      // Flatten the nested booking structure
      const flatBookings = []
      response.bookings.forEach(dateGroup => {
        if (dateGroup.bookings && Array.isArray(dateGroup.bookings)) {
          flatBookings.push(...dateGroup.bookings)
        }
      })
      
      // For 'today' filter, further filter to only show today's bookings
      if (period === 'today') {
        const today = new Date().toISOString().split('T')[0]
        return { status: true, bookings: flatBookings.filter(booking => booking.meeting_dates === today) }
      }
      
      return { status: true, bookings: flatBookings }
    })
  },
  
  getProfile: () => {
    const wpData = getWpData()
    const formData = new FormData()
    formData.append('action', 'hbc_get_host_profile')
    formData.append('nonce', wpData.nonce)
    
    return axios.post(wpData.ajaxUrl, formData, {
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      }
    }).then(response => {
      if (response.data && response.data.success) {
        return response.data.data
      }
      throw new Error(response.data?.data || 'Failed to load profile')
    })
  },
  
  updateProfile: (data) => {
    const wpData = getWpData()
    const formData = new FormData()
    formData.append('action', 'hbc_update_host_profile')
    formData.append('hbc_host_profile_nonce', wpData.profileNonce)
    
    // Add profile data to form
    Object.keys(data).forEach(key => {
      formData.append(key, data[key])
    })
    
    return axios.post(wpData.ajaxUrl, formData, {
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      }
    }).then(response => {
      if (response.data && response.data.success) {
        return response.data.data
      }
      throw new Error(response.data?.data || 'Failed to update profile')
    })
  },
  
  updateBookingStatus: (bookingId, status) => 
     api.post('booking/change-booking-status', { booking_id: bookingId, status }),
  
  getBookingDetails: (bookingId) => {
    const wpData = getWpData()
    const formData = new FormData()
    formData.append('action', 'hbc_get_booking_details')
    formData.append('booking_id', bookingId)
    formData.append('nonce', wpData.nonce)
    
    return axios.post(wpData.ajaxUrl, formData, {
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      }
    })
  },
  
  generateJoinLink: (meetingId, linkType, customUrl = '') => 
    api.post('meeting/generate-join-link', { 
      meeting_id: meetingId, 
      link_type: linkType, 
      custom_url: customUrl 
    }),
  
  sendJoinLink: (linkId) => 
    api.post('meeting/send-join-link', { link_id: linkId }),
  
  getJoinLinks: () => {
    // Get meetings data from core plugin
    return api.get('meetings/lists').then(response => {
      if (!response.status || !response.meetings) {
        return []
      }
      
      // Transform meetings data to join links format
      return response.meetings.map(meeting => ({
        meeting_id: meeting.id,
        meeting_title: meeting.title,
        join_url: meeting.join_url || '',
        meeting_type: meeting.meeting_type || 'standard',
        duration: meeting.duration || 30,
        status: meeting.status || 'active'
      }))
    }).catch(() => [])
  },
  
  getStats: () => {
    // Calculate stats from bookings data
    return hostAPI.getBookings('all').then(response => {
      if (!response.status || !response.bookings) {
        return { total_bookings: 0, upcoming_meetings: 0, completed_meetings: 0, total_revenue: 0, cancelled_bookings: 0 }
      }
      
      const bookings = response.bookings
      const currentDate = new Date().toISOString().split('T')[0]
      
      const stats = {
        total_bookings: bookings.length,
        upcoming_meetings: bookings.filter(b => b.meeting_dates >= currentDate && b.status !== 'cancelled' && b.status !== 'completed').length,
        completed_meetings: bookings.filter(b => b.status === 'completed').length,
        cancelled_bookings: bookings.filter(b => b.status === 'cancelled').length,
        total_revenue: bookings.reduce((sum, b) => sum + (parseFloat(b.meeting_price) || 0), 0)
      }
      
      return stats
    })
  },

  getMeetingLink: (bookingId) => {
    return api.get(`jitsi/meeting-link/${bookingId}`).then(response => {
      console.log('Raw API response for hostAPI:', response)
      // The response structure should contain the meeting data directly
      if (response && response.status !== undefined) {
        return response
      }
      return response.data || { status: false, meeting_url: null }
    }).catch((error) => {
      console.error('HostAPI getMeetingLink error:', error)
      return { status: false, meeting_url: null }
    })
  },
}

export default api