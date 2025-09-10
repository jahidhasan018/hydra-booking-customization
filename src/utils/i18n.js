/**
 * Internationalization utility for Vue components
 * Provides translation functions that integrate with WordPress i18n
 */

// প্লাগইনের জন্য অনুবাদ স্ট্রিং
const translations = {
  // ড্যাশবোর্ড সাধারণ
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
  'submit': 'জমা দিন',
  'search': 'অনুসন্ধান',
  'filter': 'ফিল্টার',
  'view': 'দেখুন',
  'add': 'যোগ করুন',
  'update': 'আপডেট',
  'remove': 'সরান',
  'select': 'নির্বাচন করুন',
  'all': 'সব',
  'none': 'কোনটি নয়',
  'yes': 'হ্যাঁ',
  'no': 'না',
  'ok': 'ঠিক আছে',
  'error': 'ত্রুটি',
  'success': 'সফল',
  'warning': 'সতর্কতা',
  'info': 'তথ্য',
  'notice': 'বিজ্ঞপ্তি',
  'message': 'বার্তা',
  'title': 'শিরোনাম',
  'description': 'বিবরণ',
  'name': 'নাম',
  'address': 'ঠিকানা',
  'date': 'তারিখ',
  'time': 'সময়',
  'status': 'অবস্থা',
  'active': 'সক্রিয়',
  'inactive': 'নিষ্ক্রিয়',
  'approved': 'অনুমোদিত',
  'rejected': 'প্রত্যাখ্যাত',
  'past': 'অতীত',
  'tomorrow': 'আগামীকাল',
  'yesterday': 'গতকাল',
  'week': 'সপ্তাহ',
  'month': 'মাস',
  'year': 'বছর',
  'hour': 'ঘন্টা',
  'minute': 'মিনিট',
  'second': 'সেকেন্ড',
  'max': 'সর্বোচ্চ',
  'total': 'মোট',
  'count': 'সংখ্যা',
  'number': 'নম্বর',
  'id': 'আইডি',
  'user': 'ব্যবহারকারী',
  'admin': 'প্রশাসক',
  'guest': 'অতিথি',
  'appointment': 'অ্যাপয়েন্টমেন্ট',
  'schedule': 'সময়সূচী',
  'calendar': 'ক্যালেন্ডার',
  'event': 'ইভেন্ট',
  'session': 'সেশন',
  'room': 'রুম',
  'location': 'অবস্থান',
  'settings': 'সেটিংস',
  'preferences': 'পছন্দসমূহ',
  'account': 'অ্যাকাউন্ট',
  'home': 'হোম',
  'back': 'পিছনে',
  'next': 'পরবর্তী',
  'previous': 'পূর্ববর্তী',
  'first': 'প্রথম',
  'last': 'শেষ',
  'page': 'পৃষ্ঠা',
  'of': 'এর',
  'to': 'থেকে',
  'from': 'হতে',
  'by': 'দ্বারা',
  'for': 'জন্য',
  'with': 'সাথে',
  'without': 'ছাড়া',
  'and': 'এবং',
  'or': 'অথবা',
  'not': 'না',
  'is': 'হয়',
  'are': 'আছে',
  'was': 'ছিল',
  'were': 'ছিল',
  'will': 'হবে',
  'would': 'হতো',
  'should': 'উচিত',
  'could': 'পারতো',
  'may': 'হতে পারে',
  'might': 'হতে পারে',
  'must': 'অবশ্যই',
  'can': 'পারে',
  'cannot': 'পারে না',
  'do': 'করুন',
  'does': 'করে',
  'did': 'করেছে',
  'done': 'সম্পন্ন',
  'doing': 'করছে',
  'go': 'যান',
  'come': 'আসুন',
  'get': 'পান',
  'set': 'সেট করুন',
  'put': 'রাখুন',
  'take': 'নিন',
  'give': 'দিন',
  'make': 'তৈরি করুন',
  'create': 'তৈরি করুন',
  'build': 'নির্মাণ করুন',
  'show': 'দেখান',
  'hide': 'লুকান',
  'open': 'খুলুন',
  'start': 'শুরু করুন',
  'stop': 'থামুন',
  'end': 'শেষ',
  'finish': 'শেষ করুন',
  'complete': 'সম্পূর্ণ করুন',
  'continue': 'চালিয়ে যান',
  'pause': 'বিরতি',
  'resume': 'পুনরায় শুরু',
  'restart': 'পুনরায় শুরু করুন',
  'reset': 'রিসেট করুন',
  'clear': 'পরিষ্কার করুন',
  'empty': 'খালি',
  'full': 'পূর্ণ',
  'available': 'উপলব্ধ',
  'unavailable': 'অনুপলব্ধ',
  'busy': 'ব্যস্ত',
  'free': 'মুক্ত',
  'online': 'অনলাইন',
  'offline': 'অফলাইন',
  'connected': 'সংযুক্ত',
  'disconnected': 'সংযোগ বিচ্ছিন্ন',
  'loaded': 'লোড হয়েছে',
  'saving': 'সংরক্ষণ করা হচ্ছে...',
  'saved': 'সংরক্ষিত',
  'sending': 'পাঠানো হচ্ছে...',
  'sent': 'পাঠানো হয়েছে',
  'receiving': 'গ্রহণ করা হচ্ছে...',
  'received': 'গ্রহণ করা হয়েছে',
  'processing': 'প্রক্রিয়াকরণ...',
  'processed': 'প্রক্রিয়াকৃত',
  'uploading': 'আপলোড করা হচ্ছে...',
  'uploaded': 'আপলোড হয়েছে',
  'downloading': 'ডাউনলোড করা হচ্ছে...',
  'downloaded': 'ডাউনলোড হয়েছে',
  'installing': 'ইনস্টল করা হচ্ছে...',
  'installed': 'ইনস্টল হয়েছে',
  'updating': 'আপডেট করা হচ্ছে...',
  'updated': 'আপডেট হয়েছে',
  'deleting': 'মুছে ফেলা হচ্ছে...',
  'deleted': 'মুছে ফেলা হয়েছে',
  'connecting': 'সংযোগ করা হচ্ছে...',
  'syncing': 'সিঙ্ক করা হচ্ছে...',
  'synced': 'সিঙ্ক হয়েছে',
  'validating': 'যাচাই করা হচ্ছে...',
  'validated': 'যাচাই করা হয়েছে',
  'checking': 'পরীক্ষা করা হচ্ছে...',
  'checked': 'পরীক্ষা করা হয়েছে',
  'verifying': 'যাচাই করা হচ্ছে...',
  'verified': 'যাচাই করা হয়েছে',
  'confirming': 'নিশ্চিত করা হচ্ছে...',
  'confirmed': 'নিশ্চিত করা হয়েছে',
  'rejecting': 'প্রত্যাখ্যান করা হচ্ছে...',
  'rejected': 'প্রত্যাখ্যান করা হয়েছে',
  'approving': 'অনুমোদন করা হচ্ছে...',
  'approved': 'অনুমোদন করা হয়েছে',
  'cancelling': 'বাতিল করা হচ্ছে...',
  'scheduling': 'সময়সূচী করা হচ্ছে...',
  'rescheduling': 'পুনঃসময়সূচী করা হচ্ছে...',
  'rescheduled': 'পুনঃসময়সূচী করা হয়েছে',
  'booking_confirmed': 'বুকিং নিশ্চিত করা হয়েছে',
  'booking_cancelled': 'বুকিং বাতিল করা হয়েছে',
  'booking_rescheduled': 'বুকিং পুনঃসময়সূচী করা হয়েছে',
  'meeting_started': 'মিটিং শুরু হয়েছে',
  'meeting_ended': 'মিটিং শেষ হয়েছে',
  'meeting_joined': 'মিটিংয়ে যোগ দিয়েছেন',
  'meeting_left': 'মিটিং ছেড়েছেন',
  'upcoming_meetings': 'আসন্ন মিটিং',
  'completed_meetings': 'সম্পন্ন মিটিং',
  'join_meeting': 'মিটিংয়ে যোগ দিন',
  'meeting_link': 'মিটিং লিঙ্ক',
  'copy_link': 'লিঙ্ক কপি করুন',
  
  // অংশগ্রহণকারী ড্যাশবোর্ড
  'attendee_dashboard': 'ড্যাশবোর্ড',
  'manage_bookings_profile': 'আপনার বুকিং এবং প্রোফাইল পরিচালনা করুন',
  'total_bookings': 'মোট বুকিং',
  'upcoming': 'আসন্ন',
  'upcoming_filter': 'আসন্ন',
  'completed': 'সম্পন্ন',
  'completed_filter': 'সম্পন্ন',
  'cancelled': 'বাতিল',
  'cancelled_filter': 'বাতিল',
  'my_bookings': 'আমার বুকিং',
  'my_bookings_tab': 'আমার বুকিং',
  'profile': 'প্রোফাইল',
  'profile_tab': 'প্রোফাইল',
  'no_bookings': 'কোন বুকিং নেই',
  'no_bookings_message': 'আপনার এখনও কোন বুকিং নেই।',
  'date_time': 'তারিখ ও সময়',
  'duration': 'সময়কাল',
  'start_meeting': 'মিটিং শুরু করুন',
  'scheduled': 'নির্ধারিত',
  'available_in': 'উপলব্ধ',
  'meeting_opened': 'মিটিং নতুন ট্যাবে খোলা হয়েছে',
  'meeting_not_available': 'মিটিং লিংক উপলব্ধ নেই। অনুগ্রহ করে সাপোর্টের সাথে যোগাযোগ করুন।',
  'meeting_failed': 'মিটিং লিংক পেতে ব্যর্থ। অনুগ্রহ করে আবার চেষ্টা করুন।',
  'test_mode_message': 'টেস্ট মোড: প্রোডাকশনে মিটিং লিংক এখানে উপলব্ধ হবে।',
  'logout_confirm': 'আপনি কি নিশ্চিত যে আপনি লগআউট করতে চান?',
  'logout_failed': 'লগআউট ব্যর্থ। অনুগ্রহ করে আবার চেষ্টা করুন।',
  'profile_updated': 'প্রোফাইল সফলভাবে আপডেট হয়েছে',
  
  // হোস্ট ড্যাশবোর্ড
  'host_dashboard': 'ড্যাশবোর্ড',
  'manage_meetings_bookings': 'আপনার মিটিং, বুকিং এবং জয়েন লিংক পরিচালনা করুন',
  'todays_meetings': 'আজকের মিটিং',
  'active_links': 'সক্রিয় লিংক',
  'bookings': 'বুকিং',
  'join_links': 'জয়েন লিংক',
  'meeting_history': 'মিটিং ইতিহাস',
  'join_links_management': 'জয়েন লিংক ব্যবস্থাপনা',
  'generate_new_link': 'নতুন লিংক তৈরি করুন',
  'today': 'আজ',
  'all_history': 'সমস্ত ইতিহাস',
  
  // বুকিং তালিকা
  'no_bookings_found': 'কোন বুকিং পাওয়া যায়নি',
  'no_bookings_criteria': 'বর্তমান মানদণ্ডের সাথে কোন বুকিং মেলে না।',
  'meeting': 'মিটিং',
  'min': 'মিনিট',
  'join_link_available': 'জয়েন লিংক উপলব্ধ',
  'host': 'হোস্ট',
  'booked': 'বুক করা হয়েছে',
  'booking_reference': 'বুকিং রেফারেন্স',
  'notes': 'নোট',
  'internal_note': 'অভ্যন্তরীণ নোট',
  'attendee_comment': 'অংশগ্রহণকারীর মন্তব্য',
  'mark_complete': 'সম্পন্ন চিহ্নিত করুন',
  
  // ফর্ম ক্ষেত্র
  'first_name': 'প্রথম নাম',
  'last_name': 'শেষ নাম',
  'email': 'ইমেইল',
  'email_address': 'ইমেইল ঠিকানা',
  'phone': 'ফোন',
  'phone_number': 'ফোন নম্বর',
  'timezone': 'টাইমজোন',
  'bio': 'জীবনী',
  'tell_about_yourself': 'নিজের সম্পর্কে বলুন...',
  'booking_details': 'বুকিং বিস্তারিত',
  'meeting_details': 'মিটিং বিস্তারিত',
  'meeting_title': 'মিটিং শিরোনাম',
  'booking_type': 'বুকিং ধরন',
  'attendee_details': 'অংশগ্রহণকারীর বিস্তারিত',
  'attendee_id': 'অংশগ্রহণকারী আইডি',
  'booking_id': 'বুকিং আইডি',
  'created_at': 'তৈরি হয়েছে',
  'comment': 'মন্তব্য',
  'booked_at': 'বুক করা হয়েছে',
  'activity_details': 'কার্যকলাপের বিস্তারিত',
  'generate_join_link': 'যোগদান লিঙ্ক তৈরি করুন',
  'select_meeting': 'মিটিং নির্বাচন করুন',
  'choose_meeting': 'একটি মিটিং বেছে নিন',
  'link_type': 'লিঙ্কের ধরন',
  'one_time_link': 'একবার ব্যবহারের লিঙ্ক',
  'can_only_used_once': 'শুধুমাত্র একবার ব্যবহার করা যাবে',
  'reusable_link': 'পুনর্ব্যবহারযোগ্য লিঙ্ক',
  'can_used_multiple_times': 'একাধিকবার ব্যবহার করা যাবে',
  'limited_use_link': 'সীমিত ব্যবহারের লিঙ্ক',
  'can_used_specific_times': 'নির্দিষ্ট সংখ্যকবার ব্যবহার করা যাবে',
  'maximum_uses': 'সর্বোচ্চ ব্যবহার',
  'minutes': 'মিনিট',
  'additional_information': 'অতিরিক্ত তথ্য',
  
  // স্ট্যাটাস টেক্সট
  'pending': 'অপেক্ষমাণ',
  'confirmed': 'নিশ্চিত',
  'completed_status': 'সম্পন্ন',
  'cancelled_status': 'বাতিল',
  'canceled_status': 'বাতিল',
  
  // পেমেন্ট স্ট্যাটাস
  'paid': 'পরিশোধিত',
  'unpaid': 'অপরিশোধিত',
  'refunded': 'ফেরত দেওয়া হয়েছে',
  'pending_payment': 'পেমেন্ট অপেক্ষমাণ',
  
  // ত্রুটি বার্তা
  'error_loading_data': 'ড্যাশবোর্ড ডেটা লোড করতে ব্যর্থ',
  'error_loading_stats': 'পরিসংখ্যান লোড করতে ব্যর্থ',
  'error_updating_profile': 'প্রোফাইল আপডেট করতে ব্যর্থ',
  'error_loading_bookings': 'বুকিং লোড করতে ব্যর্থ',
  'error_updating_booking': 'বুকিং স্ট্যাটাস আপডেট করতে ব্যর্থ',
  
  // সফলতার বার্তা
  'booking_updated': 'বুকিং স্ট্যাটাস সফলভাবে আপডেট হয়েছে',
  'link_copied': 'লিংক ক্লিপবোর্ডে কপি হয়েছে',
  'email_sent': 'ইমেইল সফলভাবে পাঠানো হয়েছে',
  
  // প্রোফাইল মোডাল
  'change_password': 'পাসওয়ার্ড পরিবর্তন করুন',
  'leave_blank_password': 'বর্তমান পাসওয়ার্ড রাখতে খালি রাখুন',
  'current_password': 'বর্তমান পাসওয়ার্ড',
  'new_password': 'নতুন পাসওয়ার্ড',
  'confirm_new_password': 'নতুন পাসওয়ার্ড নিশ্চিত করুন',
  'save_changes': 'পরিবর্তন সংরক্ষণ করুন',
  
  // ফিল্টার নাম
  'today_filter': 'আজ',
  'all_history_filter': 'সমস্ত ইতিহাস',
  
  // ভ্যালিডেশন ত্রুটি
  'first_name_required': 'প্রথম নাম আবশ্যক',
  'last_name_required': 'শেষ নাম আবশ্যক',
  'email_required': 'ইমেইল আবশ্যক',
  'valid_email_required': 'অনুগ্রহ করে একটি বৈধ ইমেইল ঠিকানা লিখুন',
  'valid_phone_required': 'অনুগ্রহ করে একটি বৈধ ফোন নম্বর লিখুন',
  'current_password_required': 'পাসওয়ার্ড পরিবর্তন করতে বর্তমান পাসওয়ার্ড আবশ্যক',
  'new_password_required': 'নতুন পাসওয়ার্ড আবশ্যক',
  'password_min_length': 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে',
  'passwords_not_match': 'পাসওয়ার্ড মিলছে না',
  'fix_errors_below': 'অনুগ্রহ করে নিচের ত্রুটিগুলি সংশোধন করুন',
  'failed_save_profile': 'প্রোফাইল সংরক্ষণ করতে ব্যর্থ',
  
  // অন্যান্য বার্তা
  'booking_status_updated': 'বুকিং {status} সফলভাবে হয়েছে',
  'join_link_sent': 'জয়েন লিংক অংশগ্রহণকারীদের পাঠানো হয়েছে',
  'join_link_copied': 'জয়েন লিংক ক্লিপবোর্ডে কপি হয়েছে',
  'failed_copy_link': 'লিংক কপি করতে ব্যর্থ',
  'join_link_generated': 'জয়েন লিংক সফলভাবে তৈরি হয়েছে',
  'failed_load_dashboard': 'ড্যাশবোর্ড ডেটা লোড করতে ব্যর্থ',
  'failed_load_statistics': 'পরিসংখ্যান লোড করতে ব্যর্থ',
  'booking_details_error': 'বুকিং বিস্তারিত আনতে ত্রুটি: ',
  'logout_confirmation': 'আপনি কি নিশ্চিত যে আপনি লগআউট করতে চান?',
  'logout_failed_retry': 'লগআউট ব্যর্থ। অনুগ্রহ করে আবার চেষ্টা করুন।'
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

