/**
 * Internationalization utility for Vue components
 *
 * Reads translations from the PHP-provided data object
 * (window.hbcAttendeeData.translations or window.hbcHostData.translations).
 * When the site locale is set to bn_BD, PHP loads the .mo file and passes
 * Bangla strings; otherwise the English originals come through.
 *
 * @package HydraBookingCustomization
 */

/**
 * Get the translations object provided by PHP via wp_json_encode.
 *
 * @returns {Object} Key-value map of translation strings.
 */
function getServerTranslations() {
  if (window.hbcAttendeeData && window.hbcAttendeeData.translations) {
    return window.hbcAttendeeData.translations
  }
  if (window.hbcHostData && window.hbcHostData.translations) {
    return window.hbcHostData.translations
  }
  return {}
}

// Hardcoded Bangla fallback for keys not provided by PHP.
const bnFallback = {
  'loading': 'লোড হচ্ছে...',
  'logout': 'লগআউট',
  'refresh': 'রিফ্রেশ',
  'edit_profile': 'প্রোফাইল সম্পাদনা',
  'profile_settings': 'প্রোফাইল সেটিংস',
  'save': 'সংরক্ষণ',
  'cancel': 'বাতিল',
  'close': 'বন্ধ',
  'confirm': 'নিশ্চিত',
  'delete': 'মুছুন',
  'view_details': 'বিস্তারিত দেখুন',
  'not_set': 'সেট করা হয়নি',
  'edit': 'সম্পাদনা',
  'view': 'দেখুন',
  'error': 'ত্রুটি',
  'success': 'সফল',
  'attendee_dashboard': 'অংশগ্রহণকারী ড্যাশবোর্ড',
  'manage_bookings_profile': 'আপনার বুকিং এবং প্রোফাইল পরিচালনা করুন',
  'total_bookings': 'মোট বুকিং',
  'upcoming': 'আসন্ন',
  'completed': 'সম্পন্ন',
  'cancelled': 'বাতিল',
  'my_bookings': 'আমার বুকিং',
  'profile': 'প্রোফাইল',
  'no_bookings': 'কোন বুকিং নেই',
  'no_bookings_message': 'আপনার এখনও কোন বুকিং নেই।',
  'date_time': 'তারিখ ও সময়',
  'duration': 'সময়কাল',
  'start_meeting': 'মিটিং শুরু করুন',
  'scheduled': 'নির্ধারিত',
  'available_in': 'উপলব্ধ',
  'join_meeting': 'মিটিংয়ে যোগ দিন',
  'meeting': 'মিটিং',
  'min': 'মিনিট',
  'host_dashboard': 'হোস্ট ড্যাশবোর্ড',
  'manage_meetings_bookings': 'আপনার মিটিং, বুকিং এবং জয়েন লিংক পরিচালনা করুন',
  'todays_meetings': 'আজকের মিটিং',
  'active_links': 'সক্রিয় লিংক',
  'bookings': 'বুকিং',
  'join_links': 'জয়েন লিংক',
  'join_links_management': 'জয়েন লিংক ব্যবস্থাপনা',
  'generate_new_link': 'নতুন লিংক তৈরি করুন',
  'meeting_history': 'মিটিং ইতিহাস',
  'no_bookings_found': 'কোন বুকিং পাওয়া যায়নি',
  'no_bookings_criteria': 'বর্তমান মানদণ্ডের সাথে কোন বুকিং মেলে না।',
  'booked': 'বুক করা হয়েছে',
  'booking_reference': 'বুকিং রেফারেন্স',
  'mark_complete': 'সম্পন্ন চিহ্নিত করুন',
  'confirm_booking': 'বুকিং নিশ্চিত করুন',
  'cancel_booking': 'বুকিং বাতিল করুন',
  'generate_link': 'লিংক তৈরি করুন',
  'send_link': 'লিংক পাঠান',
  'first_name': 'প্রথম নাম',
  'last_name': 'শেষ নাম',
  'email': 'ইমেইল',
  'phone': 'ফোন',
  'bio': 'জীবনী',
  'pending': 'অপেক্ষমাণ',
  'confirmed': 'নিশ্চিত',
  'canceled': 'বাতিল',
  'meeting_opened': 'মিটিং নতুন ট্যাবে খোলা হয়েছে',
  'meeting_not_available': 'মিটিং লিংক উপলব্ধ নেই। অনুগ্রহ করে সাপোর্টের সাথে যোগাযোগ করুন।',
  'meeting_failed': 'মিটিং লিংক পেতে ব্যর্থ। অনুগ্রহ করে আবার চেষ্টা করুন।',
  'logout_confirm': 'আপনি কি নিশ্চিত যে আপনি লগআউট করতে চান?',
  'logout_failed': 'লগআউট ব্যর্থ। অনুগ্রহ করে আবার চেষ্টা করুন।',
  'profile_updated': 'প্রোফাইল সফলভাবে আপডেট হয়েছে',
  'error_loading_data': 'ড্যাশবোর্ড ডেটা লোড করতে ব্যর্থ',
  'error_loading_stats': 'পরিসংখ্যান লোড করতে ব্যর্থ',
  'error_updating_profile': 'প্রোফাইল আপডেট করতে ব্যর্থ',
  'error_loading_bookings': 'বুকিং লোড করতে ব্যর্থ',
  'booking_updated': 'বুকিং স্ট্যাটাস সফলভাবে আপডেট হয়েছে',
  'link_copied': 'লিংক ক্লিপবোর্ডে কপি হয়েছে',
  'email_sent': 'ইমেইল সফলভাবে পাঠানো হয়েছে',
  'join_link_generated': 'জয়েন লিংক সফলভাবে তৈরি হয়েছে',
  'booking_cancelled': 'বুকিং সফলভাবে বাতিল হয়েছে',
  'reschedule': 'পুনঃসময়সূচী',
  'today': 'আজ',
  'all_history': 'সমস্ত ইতিহাস',
  'attendee': 'অংশগ্রহণকারী',
  'host_label': 'হোস্ট:',
  'notes': 'নোট:',
  'internal_note': 'অভ্যন্তরীণ নোট:',
  'attendee_comment': 'অংশগ্রহণকারীর মন্তব্য:',
  'timezone': 'টাইমজোন',
  'save_changes': 'পরিবর্তন সংরক্ষণ করুন',
  'change_password': 'পাসওয়ার্ড পরিবর্তন করুন',
  'current_password': 'বর্তমান পাসওয়ার্ড',
  'new_password': 'নতুন পাসওয়ার্ড',
  'confirm_new_password': 'নতুন পাসওয়ার্ড নিশ্চিত করুন',
}

/**
 * Get a translated string by key.
 *
 * Priority: PHP-provided translations > Bangla fallback > key itself.
 *
 * @param {string} key   Translation key.
 * @param {string} [fallback] Optional fallback text.
 * @returns {string}
 */
export function __(key, fallback = null) {
  const server = getServerTranslations()

  // 1. PHP-provided translation (already in the correct locale).
  if (server[key]) {
    return server[key]
  }

  // 2. Detect if the locale is Bangla and use the hardcoded fallback.
  const locale = (window.hbcAttendeeData && window.hbcAttendeeData.locale) ||
    (window.hbcHostData && window.hbcHostData.locale) || ''
  if (locale.startsWith('bn') && bnFallback[key]) {
    return bnFallback[key]
  }

  // 3. Explicit fallback or just the key.
  return fallback || key
}

/**
 * Simple sprintf replacement for translated strings.
 *
 * @param {string} key     Translation key.
 * @param {...any} args    Replacement arguments.
 * @returns {string}
 */
export function sprintf(key, ...args) {
  let text = __(key)
  return text.replace(/%[sd]/g, () => {
    const arg = args.shift()
    return arg !== undefined ? String(arg) : ''
  })
}

/**
 * Plural-aware translation.
 *
 * @param {string} singular  Singular key.
 * @param {string} plural    Plural key.
 * @param {number} count     Count.
 * @returns {string}
 */
export function _n(singular, plural, count) {
  return count === 1 ? __(singular) : __(plural)
}

// Re-export fallback map for reference.
export { bnFallback as translations }
