# Hydra Booking Customization - Usage Guide

## Quick Start

### 1. Activation
1. Ensure the **Hydra Booking** plugin is installed and activated
2. Activate the **Hydra Booking Customization** plugin
3. Visit `Settings > HB Customization` to configure options

### 2. Configuration
Navigate to **Settings > HB Customization** and configure:

**Auto Registration:**
- ✅ Enable auto-registration for new attendees
- ✅ Send welcome email with login credentials

**Attendee Dashboard:**
- Select the page that will serve as the attendee dashboard
- Default: "Attendee Dashboard" page (auto-created)

**Booking Management:**
- ✅ Allow booking cancellation (with time limit)
- ✅ Allow booking rescheduling (with time limit)
- Set time limits in hours (e.g., 24 hours before meeting)

### 3. How It Works

#### For New Attendees (Auto-Registration)
1. Someone books a meeting through your Hydra Booking form
2. If they're not a registered user, the plugin automatically:
   - Creates a WordPress user account
   - Assigns the `hbc_attendee` role
   - Sends a welcome email with login credentials
   - Links the booking to their new account

#### For Existing Attendees
1. Attendees can log in to WordPress
2. Visit the attendee dashboard page
3. View all their bookings, cancel/reschedule if allowed
4. Update their profile information

### 4. Attendee Dashboard Features

The dashboard provides attendees with:

**My Bookings Tab:**
- View all upcoming and past bookings
- See booking details (date, time, host, location)
- Cancel bookings (if within time limit)
- Reschedule bookings (if within time limit)

**Profile Tab:**
- Update name and email address
- Change password
- View account information

### 5. Admin Features

**Settings Page:**
- Configure all plugin options
- View plugin information and statistics
- See total number of attendees

**User Management:**
- View attendees in Users > All Users (filter by `hbc_attendee` role)
- Manually assign attendee role to existing users

### 6. Integration Points

The plugin integrates with Hydra Booking through:
- `hydra_booking/after_booking_confirmation` hook
- AJAX booking confirmation handlers
- Hydra Booking database tables

### 7. Customization

#### Hooks Available:
```php
// Before auto-registration
do_action( 'hbc_before_auto_registration', $attendee_email, $booking_data );

// After auto-registration
do_action( 'hbc_after_auto_registration', $user_id, $booking_data );

// Before welcome email
apply_filters( 'hbc_welcome_email_content', $message, $user_data );

// Dashboard access control
apply_filters( 'hbc_can_access_dashboard', $can_access, $user_id );
```

#### CSS Customization:
Add custom styles to your theme's CSS:
```css
/* Customize attendee dashboard */
.hbc-dashboard { /* your styles */ }
.hbc-booking-card { /* your styles */ }
.hbc-nav-tabs { /* your styles */ }
```

### 8. Testing

1. **Test Auto-Registration:**
   - Make a booking with a new email address
   - Check if user account was created
   - Verify welcome email was sent

2. **Test Dashboard:**
   - Log in as an attendee
   - Visit the dashboard page
   - Try canceling/rescheduling a booking

3. **Test Profile Updates:**
   - Update profile information
   - Change password
   - Verify changes are saved

### 9. Maintenance

- Regularly check the plugin settings
- Monitor attendee accounts for any issues
- Keep the Hydra Booking plugin updated
- Test functionality after WordPress/plugin updates

### 10. Support

If you encounter issues:
1. Check the troubleshooting section in README.md
2. Enable debug mode in wp-config.php
3. Check WordPress error logs
4. Verify Hydra Booking plugin compatibility