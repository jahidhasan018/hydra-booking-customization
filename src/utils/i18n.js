/**
 * Internationalization utility for Vue components
 * Provides translation functions that integrate with WordPress i18n
 */

// Translation strings for the plugin
const translations = {
  // Dashboard common
  'loading': 'Loading...',
  'logout': 'Logout',
  'refresh': 'Refresh',
  'edit_profile': 'Edit Profile',
  'profile_settings': 'Profile Settings',
  'save': 'Save',
  'cancel': 'Cancel',
  'close': 'Close',
  'confirm': 'Confirm',
  'delete': 'Delete',
  'view_details': 'View Details',
  'not_set': 'Not set',
  
  // Attendee Dashboard
  'attendee_dashboard': 'Attendee Dashboard',
  'manage_bookings_profile': 'Manage your bookings and profile',
  'total_bookings': 'Total Bookings',
  'upcoming': 'Upcoming',
  'completed': 'Completed',
  'cancelled': 'Cancelled',
  'my_bookings': 'My Bookings',
  'profile': 'Profile',
  'no_bookings': 'No bookings',
  'no_bookings_message': "You don't have any bookings yet.",
  'date_time': 'Date & Time',
  'duration': 'Duration',
  'start_meeting': 'Start Meeting',
  'scheduled': 'Scheduled',
  'available_in': 'Available in',
  'meeting_opened': 'Meeting opened in new tab',
  'meeting_not_available': 'Meeting link not available. Please contact support.',
  'meeting_failed': 'Failed to get meeting link. Please try again.',
  'test_mode_message': 'Test mode: Meeting link would be available here in production.',
  'logout_confirm': 'Are you sure you want to logout?',
  'logout_failed': 'Logout failed. Please try again.',
  'profile_updated': 'Profile updated successfully',
  
  // Host Dashboard
  'host_dashboard': 'Host Dashboard',
  'manage_meetings_bookings': 'Manage your meetings, bookings, and join links',
  'todays_meetings': "Today's Meetings",
  'active_links': 'Active Links',
  'bookings': 'Bookings',
  'join_links': 'Join Links',
  'meeting_history': 'Meeting History',
  'join_links_management': 'Join Links Management',
  'generate_new_link': 'Generate New Link',
  'today': 'Today',
  'all_history': 'All History',
  
  // Bookings List
  'no_bookings_found': 'No bookings found',
  'no_bookings_criteria': 'No bookings match the current criteria.',
  'meeting': 'Meeting',
  'min': 'min',
  'join_link_available': 'Join link available',
  'host': 'Host',
  'booked': 'Booked',
  'booking_reference': 'Booking reference',
  'notes': 'Notes',
  'internal_note': 'Internal Note',
  'attendee_comment': 'Attendee Comment',
  'mark_complete': 'Mark Complete',
  
  // Form fields
  'first_name': 'First Name',
  'last_name': 'Last Name',
  'email': 'Email',
  'phone': 'Phone',
  'timezone': 'Timezone',
  'bio': 'Bio',
  
  // Status texts
  'pending': 'Pending',
  'confirmed': 'Confirmed',
  'completed_status': 'Completed',
  'cancelled_status': 'Cancelled',
  'canceled_status': 'Canceled',
  
  // Payment status
  'paid': 'Paid',
  'unpaid': 'Unpaid',
  'refunded': 'Refunded',
  'pending_payment': 'Pending Payment',
  
  // Error messages
  'error_loading_data': 'Failed to load dashboard data',
  'error_loading_stats': 'Failed to load statistics',
  'error_updating_profile': 'Failed to update profile',
  'error_loading_bookings': 'Failed to load bookings',
  'error_updating_booking': 'Failed to update booking status',
  
  // Success messages
  'booking_updated': 'Booking status updated successfully',
  'link_copied': 'Link copied to clipboard',
  'email_sent': 'Email sent successfully'
}

/**
 * Get translated string
 * @param {string} key - Translation key
 * @param {string} fallback - Fallback text if translation not found
 * @returns {string} Translated string
 */
export function __(key, fallback = null) {
  // Check if WordPress i18n is available
  if (window.wp && window.wp.i18n && window.wp.i18n.__) {
    return window.wp.i18n.__(translations[key] || fallback || key, 'hydra-booking-customization')
  }
  
  // Fallback to our translations object
  return translations[key] || fallback || key
}

/**
 * Get translated string with sprintf formatting
 * @param {string} key - Translation key
 * @param {...any} args - Arguments for sprintf
 * @returns {string} Translated and formatted string
 */
export function sprintf(key, ...args) {
  const text = __(key)
  
  // Simple sprintf implementation
  return text.replace(/%[sd]/g, (match) => {
    const arg = args.shift()
    return match === '%s' ? String(arg) : Number(arg)
  })
}

/**
 * Get plural translation
 * @param {string} singular - Singular form key
 * @param {string} plural - Plural form key
 * @param {number} count - Count to determine singular/plural
 * @returns {string} Translated string
 */
export function _n(singular, plural, count) {
  if (window.wp && window.wp.i18n && window.wp.i18n._n) {
    return window.wp.i18n._n(
      translations[singular] || singular,
      translations[plural] || plural,
      count,
      'hydra-booking-customization'
    )
  }
  
  return count === 1 ? __(singular) : __(plural)
}

/**
 * Initialize WordPress i18n for Vue components
 */
export function initI18n() {
  // Load WordPress i18n if available
  if (window.wp && window.wp.i18n) {
    const { setLocaleData } = window.wp.i18n
    
    // Set locale data if available
    if (window.hbcI18nData) {
      setLocaleData(window.hbcI18nData, 'hydra-booking-customization')
    }
  }
}

// Export translations object for reference
export { translations }