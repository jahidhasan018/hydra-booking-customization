// Dashboard configuration for different user roles

export const hostDashboardConfig = {
  title: 'Host Dashboard',
  subtitle: 'Manage your meetings, bookings, and join links',
  statsGridClass: 'grid grid-cols-1 md:grid-cols-4',
  stats: [
    {
      key: 'today_meetings',
      label: "Today's Meetings",
      icon: 'CalendarIcon',
      iconBgClass: 'bg-primary-100',
      iconClass: 'text-primary-600'
    },
    {
      key: 'upcoming_meetings',
      label: 'Upcoming',
      icon: 'ClockIcon',
      iconBgClass: 'bg-warning-100',
      iconClass: 'text-warning-600'
    },
    {
      key: 'completed_meetings',
      label: 'Completed',
      icon: 'CheckIcon',
      iconBgClass: 'bg-success-100',
      iconClass: 'text-success-600'
    },
    {
      key: 'active_join_links',
      label: 'Active Links',
      icon: 'LinkIcon',
      iconBgClass: 'bg-purple-100',
      iconClass: 'text-purple-600'
    }
  ],
  tabs: [
    { id: 'bookings', name: 'Bookings' },
    { id: 'join-links', name: 'Join Links' },
    { id: 'profile', name: 'Profile' }
  ],
  filters: [
    { id: 'upcoming', name: 'Upcoming', icon: 'calendar' },
    { id: 'today', name: 'Today', icon: 'clock' },
    { id: 'completed', name: 'Completed', icon: 'check' },
    { id: 'cancelled', name: 'Cancelled', icon: 'x' },
    { id: 'history', name: 'All History', icon: 'archive' }
  ]
}

export const attendeeDashboardConfig = {
  title: 'Attendee Dashboard',
  subtitle: 'Manage your bookings and profile',
  statsGridClass: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
  stats: [
    {
      key: 'total_bookings',
      label: 'Total Bookings',
      icon: 'CalendarIcon',
      iconBgClass: 'bg-primary-100',
      iconClass: 'text-primary-600'
    },
    {
      key: 'upcoming_bookings',
      label: 'Upcoming',
      icon: 'ClockIcon',
      iconBgClass: 'bg-warning-100',
      iconClass: 'text-warning-600'
    },
    {
      key: 'completed_bookings',
      label: 'Completed',
      icon: 'CheckIcon',
      iconBgClass: 'bg-success-100',
      iconClass: 'text-success-600'
    },
    {
      key: 'cancelled_bookings',
      label: 'Cancelled',
      icon: 'XIcon',
      iconBgClass: 'bg-red-100',
      iconClass: 'text-red-600'
    }
  ],
  tabs: [
    { id: 'bookings', name: 'My Bookings' },
    { id: 'profile', name: 'Profile' }
  ],
  filters: [
    { id: 'upcoming', name: 'Upcoming', icon: 'calendar' },
    { id: 'completed', name: 'Completed', icon: 'check' },
    { id: 'cancelled', name: 'Cancelled', icon: 'x' }
  ]
}

// Helper function to get config based on user role
export const getDashboardConfig = (userRole) => {
  switch (userRole) {
    case 'host':
      return hostDashboardConfig
    case 'attendee':
      return attendeeDashboardConfig
    default:
      return attendeeDashboardConfig // Default to attendee
  }
}

// Helper function to determine user role from WordPress data
export const getUserRole = () => {
  // Check if host data is available (indicates host role)
  if (window.hbcHostData && window.hbcHostData.userId) {
    return 'host'
  }
  // Check if attendee data is available (indicates attendee role)
  if (window.hbcAttendeeData && window.hbcAttendeeData.userId) {
    return 'attendee'
  }
  // Default to attendee if no specific data is found
  return 'attendee'
}