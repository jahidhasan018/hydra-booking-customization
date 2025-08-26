/**
 * Centralized constants for Hydra Booking Customization
 * 
 * This file provides a centralized location for managing constants
 * across the Vue.js components, particularly for test mode management.
 */

/**
 * Test mode status constants
 */
export const TEST_MODE_STATUS = {
  ACTIVE: 'active',
  OFF: 'off'
}

/**
 * Get the current test mode status from the global window object
 * This value is set by the PHP backend through wp_localize_script
 * 
 * @returns {string} 'active' or 'off'
 */
export const getTestModeStatus = () => {
  // Check if test mode data is available from the backend
  const testModeData = window.hbcTestMode || window.hbcAttendeeData?.testMode || window.hbcHostData?.testMode
  
  if (testModeData !== undefined) {
    return testModeData ? TEST_MODE_STATUS.ACTIVE : TEST_MODE_STATUS.OFF
  }
  
  // Fallback: check individual booking objects (legacy support)
  return TEST_MODE_STATUS.OFF
}

/**
 * Check if test mode is currently active
 * 
 * @returns {boolean} true if test mode is active, false otherwise
 */
export const isTestModeActive = () => {
  return getTestModeStatus() === TEST_MODE_STATUS.ACTIVE
}

/**
 * Check if test mode is active for a specific booking
 * This function provides backward compatibility while encouraging
 * the use of the centralized constant approach
 * 
 * @param {Object} booking - The booking object
 * @returns {boolean} true if test mode is active for this booking
 */
export const isTestModeActiveForBooking = (booking) => {
  // First check the centralized test mode status
  if (isTestModeActive()) {
    return true
  }
  
  // Fallback to booking-specific testing_mode property for backward compatibility
  return booking && booking.testing_mode === true
}