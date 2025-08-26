# Hydra Booking Customization Plugin

A WordPress plugin that extends the Hydra Booking system with Jitsi Meet integration for automatic meeting link generation and attendee dashboard enhancements.

## Features

### Jitsi Meet Integration
- **Automatic Meeting Link Generation**: Creates unique Jitsi meeting links when bookings are confirmed
- **Attendee Dashboard Integration**: Adds meeting links directly to the attendee dashboard
- **Real-time Meeting Status**: Shows meeting status (scheduled, active, ended) with visual indicators
- **Multiple Join Options**: 
  - Direct link in new tab/window
  - Embedded meeting interface
  - Fallback for popup blockers

### Smart Meeting Management
- **Time-based Availability**: Meeting links are only active during the scheduled time
- **Unique Room Names**: Each booking gets a unique, secure meeting room
- **Status Tracking**: Automatic status updates based on meeting time
- **Responsive Design**: Works seamlessly on desktop and mobile devices

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure the Jitsi Meet plugin is installed and configured
4. Ensure the Hydra Booking plugin is installed and active

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Hydra Booking Plugin
- Webinar and Video Conference with Jitsi Meet Plugin

## Usage

### Automatic Integration
Once activated, the plugin automatically:
1. Hooks into the Hydra Booking confirmation process
2. Generates unique Jitsi meeting links for each booking
3. Stores meeting data in the database
4. Displays meeting links in the attendee dashboard

### Attendee Dashboard
Attendees will see:
- **Before Meeting**: "Join Meeting" button (disabled until meeting time)
- **During Meeting**: Active "Join Meeting" button with visual indicators
- **After Meeting**: "Meeting Ended" status

### Meeting Link Format
Meeting links follow the pattern:
```
https://meet.jit.si/HydraBooking-{booking_id}-{timestamp}
```

## Hooks and Filters

### Actions
- `hydra_booking/after_booking_confirmed` - Triggers meeting link creation
- `hbc_jitsi_meeting_created` - Fired when a new meeting link is created
- `hbc_jitsi_meeting_joined` - Fired when someone joins a meeting

### Filters
- `hbc_jitsi_meeting_url` - Modify the generated meeting URL
- `hbc_jitsi_room_name` - Customize the room name format
- `hbc_jitsi_meeting_options` - Modify Jitsi meeting configuration

## Database Schema

### hbc_jitsi_meetings Table
- `id` - Primary key
- `booking_id` - Reference to Hydra Booking
- `meeting_url` - Generated Jitsi meeting URL
- `room_name` - Unique room identifier
- `status` - Meeting status (scheduled, active, ended)
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

## Customization

### Styling
The plugin includes comprehensive CSS for:
- Meeting buttons with hover effects
- Status indicators with animations
- Responsive modal dialogs
- Accessibility features

### JavaScript API
```javascript
// Join a meeting programmatically
HBCJitsi.joinMeeting(bookingId, options);

// Check meeting status
HBCJitsi.getMeetingStatus(bookingId);

// Open embedded meeting
HBCJitsi.openEmbeddedMeeting(meetingUrl);
```

## Security Features

- **Nonce Verification**: All AJAX requests are protected
- **Capability Checks**: Proper permission validation
- **Sanitized Input**: All user input is sanitized
- **Unique Room Names**: Prevents unauthorized access

## Troubleshooting

### Common Issues

1. **Meeting links not appearing**
   - Ensure Jitsi Meet plugin is active
   - Check database table creation
   - Verify hook integration

2. **Popup blocked messages**
   - The plugin provides fallback options
   - Users can enable popups or use embedded mode

3. **Styling issues**
   - Check CSS file loading
   - Verify theme compatibility
   - Clear cache if using caching plugins

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Changelog

### Version 1.0.0
- Initial release
- Jitsi Meet integration
- Attendee dashboard enhancements
- Automatic meeting link generation
- Responsive design implementation

## Support

For support and feature requests, please contact the development team or create an issue in the project repository.

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed for integration with:
- [Hydra Booking](https://wordpress.org/plugins/hydra-booking/)
- [Webinar and Video Conference with Jitsi Meet](https://wordpress.org/plugins/webinar-and-video-conference-with-jitsi-meet/)

## Features

### Auto Registration
- Automatically creates WordPress user accounts for attendees when they book meetings
- Generates secure passwords and sends welcome emails with login credentials
- Assigns attendees to a custom `hbc_attendee` user role
- Links bookings to user accounts for better tracking

### Attendee Dashboard
- Dedicated dashboard for attendees to manage their bookings
- View upcoming and past bookings with detailed information
- Cancel bookings (with configurable time limits)
- Reschedule bookings (with configurable time limits)
- Update profile information and change passwords
- Responsive design that works on all devices

### Host Dashboard
- Comprehensive dashboard for meeting hosts to manage their bookings
- **Today's Meetings**: View and manage meetings scheduled for today
- **Upcoming Bookings**: Overview of all future meetings
- **Join Links Management**: Generate, copy, and send Jitsi meeting links
- **Profile Management**: Update host information and bio
- **Meeting History**: Review past meetings and statistics
- **Real-time Statistics**: Dashboard showing meeting counts and attendee numbers
- **Interactive Features**: 
  - Generate join links for individual or all meetings
  - Copy links to clipboard with one click
  - Send meeting reminders to attendees
  - View detailed booking information in modal dialogs
  - Update booking statuses
- **Responsive Design**: Optimized for desktop and mobile devices

### Admin Settings
- Comprehensive settings page to configure all features
- Enable/disable auto registration
- Configure cancellation and rescheduling time limits
- Select dashboard page and customize welcome emails
- View plugin statistics and attendee counts

## Installation

### Method 1: Manual Installation
1. Download or clone this plugin to your `/wp-content/plugins/` directory
2. Ensure the Hydra Booking plugin is installed and activated
3. Activate the "Hydra Booking Customization" plugin through the 'Plugins' menu in WordPress
4. The plugin will automatically:
   - Create the `hbc_attendee` user role
   - Create an "Attendee Dashboard" page with the shortcode `[hbc_attendee_dashboard]`
   - Set default configuration options

### Method 2: Using Installation Script
1. After activating the plugin, visit: `your-site.com/wp-admin/?hbc_install=1`
2. This will run the installation script and display the setup progress

### Post-Installation Setup
1. Go to **Settings > HB Customization** to configure the plugin
2. Adjust auto-registration settings, cancellation/rescheduling rules
3. Test the attendee dashboard by visiting the created page
4. Make a test booking to verify auto-registration works

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Hydra Booking plugin (must be installed and activated)

## Usage

### Setting Up the Attendee Dashboard

1. Go to Settings > HB Customization in your WordPress admin
2. Select or create a page for the attendee dashboard
3. Add the `[hbc_attendee_dashboard]` shortcode to that page
4. Configure other settings as needed

### Auto Registration

Auto registration is enabled by default. When attendees book meetings:

1. The plugin checks if a user with that email already exists
2. If not, it creates a new user account with the `hbc_attendee` role
3. A welcome email is sent with login credentials
4. The booking is linked to the user account

### Attendee Dashboard Features

Attendees can access their dashboard to:

- **View Bookings**: See all upcoming and past bookings with details
- **Cancel Bookings**: Cancel upcoming bookings (respecting time limits)
- **Reschedule Bookings**: Request to reschedule meetings (respecting time limits)
- **Manage Profile**: Update personal information and change passwords

### Host Dashboard Features

The Host Dashboard provides comprehensive meeting management capabilities:

#### Setting Up the Host Dashboard
1. Create a new page in WordPress (e.g., "Host Dashboard")
2. Add the `[hbc_host_dashboard]` shortcode to the page content
3. Assign the page to users with host privileges
4. Configure host dashboard settings in Settings > HB Customization

#### Dashboard Sections

**Today's Meetings Tab:**
- View all meetings scheduled for the current day
- See meeting status (confirmed, pending, cancelled)
- Quick access to join links and attendee information
- Generate missing join links for meetings
- Send meeting reminders to attendees

**Upcoming Bookings Tab:**
- Overview of all future meetings
- Filter by date range or status
- Bulk actions for multiple meetings
- Meeting preparation tools

**Join Links Management Tab:**
- Generate Jitsi meeting links for confirmed bookings
- Copy individual or all links to clipboard
- Send join links directly to attendees
- Manage host and attendee access levels
- View link expiration and security settings

**Profile Tab:**
- Update host information (name, email, bio)
- Change password and security settings
- Configure meeting preferences
- Set availability and notification preferences

**Meeting History Tab:**
- Review completed meetings
- View attendee feedback and ratings
- Export meeting reports
- Track meeting statistics and trends

#### Interactive Features

**Meeting Management:**
- Generate join links with one click
- Copy links to clipboard for easy sharing
- Send automated reminders to attendees
- View detailed booking information in modal dialogs
- Update booking statuses (confirm, cancel, reschedule)

**Dashboard Statistics:**
- Real-time meeting counts for today, week, and month
- Total attendee numbers across all meetings
- Meeting completion rates and trends
- Quick overview of upcoming schedule

**Responsive Design:**
- Optimized for desktop, tablet, and mobile devices
- Touch-friendly interface for mobile users
- Accessible design following WCAG guidelines

## Hooks and Filters

The plugin provides several hooks for customization:

### Actions
- `hbc_booking_cancelled` - Fired when a booking is cancelled
- `hbc_attendee_registered` - Fired when a new attendee is auto-registered
- `hbc_profile_updated` - Fired when attendee updates their profile

### Filters
- `hbc_welcome_email_subject` - Customize welcome email subject
- `hbc_welcome_email_message` - Customize welcome email content
- `hbc_dashboard_tabs` - Add custom tabs to the dashboard
- `hbc_booking_actions` - Customize available booking actions

## Customization

### Adding Custom Dashboard Tabs

```php
add_filter('hbc_dashboard_tabs', function($tabs) {
    $tabs['custom'] = __('Custom Tab', 'your-textdomain');
    return $tabs;
});

add_action('hbc_dashboard_tab_custom', function($user) {
    echo '<div>Your custom content here</div>';
});
```

### Customizing Welcome Email

```php
add_filter('hbc_welcome_email_subject', function($subject, $user) {
    return sprintf(__('Welcome to %s!', 'your-textdomain'), get_bloginfo('name'));
}, 10, 2);

add_filter('hbc_welcome_email_message', function($message, $user, $password) {
    // Return your custom message
    return $custom_message;
}, 10, 3);
```

## File Structure

```
hydra-booking-customization/
├── src/
│   ├── Core/
│   │   └── Plugin.php
│   ├── Features/
│   │   ├── AutoRegistration.php
│   │   └── AttendeeDashboard.php
│   └── Admin/
│       └── Settings.php
├── assets/
│   ├── css/
│   │   ├── attendee-dashboard.css
│   │   └── admin.css
│   └── js/
│       ├── attendee-dashboard.js
│       └── admin.js
├── composer.json
├── hydra-booking-customization.php
└── README.md
```

## Troubleshooting

### Common Issues

**Auto-registration not working:**
- Ensure the Hydra Booking plugin is active and up-to-date
- Check that auto-registration is enabled in Settings > HB Customization
- Verify that the `hbc_attendee` role exists (Users > All Users > Role filter)

**Dashboard not displaying:**
- Ensure the attendee dashboard page exists and contains the `[hbc_attendee_dashboard]` shortcode
- Check that the user is logged in and has the `hbc_attendee` role
- Clear any caching plugins

**Booking cancellation/rescheduling not working:**
- Verify the time limits are set correctly in plugin settings
- Check that the booking is within the allowed time frame
- Ensure the booking status allows modifications

**Email notifications not sending:**
- Check WordPress email configuration
- Verify SMTP settings if using an SMTP plugin
- Check spam folders

### Debug Mode
To enable debug mode, add this to your `wp-config.php`:
```php
define( 'HBC_DEBUG', true );
```

This will log additional information to help troubleshoot issues.

## Support

For support and feature requests, please contact the plugin developer or create an issue in the project repository.

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 1.0.0
- Initial release
- Auto registration feature
- Attendee dashboard
- Admin settings page
- Responsive design

## Features

### Auto Registration
- Automatically creates WordPress user accounts for attendees when they book meetings
- Generates secure passwords and sends welcome emails with login credentials
- Assigns attendees to a custom `hbc_attendee` user role
- Links bookings to user accounts for better tracking

### Attendee Dashboard
- Dedicated dashboard for attendees to manage their bookings
- View upcoming and past bookings with detailed information
- Cancel bookings (with configurable time limits)
- Reschedule bookings (with configurable time limits)
- Update profile information and change passwords
- Responsive design that works on all devices

### Admin Settings
- Comprehensive settings page to configure all features
- Enable/disable auto registration
- Configure cancellation and rescheduling time limits
- Select dashboard page and customize welcome emails
- View plugin statistics and attendee counts

## Installation

### Method 1: Manual Installation
1. Download or clone this plugin to your `/wp-content/plugins/` directory
2. Ensure the Hydra Booking plugin is installed and activated
3. Activate the "Hydra Booking Customization" plugin through the 'Plugins' menu in WordPress
4. The plugin will automatically:
   - Create the `hbc_attendee` user role
   - Create an "Attendee Dashboard" page with the shortcode `[hbc_attendee_dashboard]`
   - Set default configuration options

### Method 2: Using Installation Script
1. After activating the plugin, visit: `your-site.com/wp-admin/?hbc_install=1`
2. This will run the installation script and display the setup progress

### Post-Installation Setup
1. Go to **Settings > HB Customization** to configure the plugin
2. Adjust auto-registration settings, cancellation/rescheduling rules
3. Test the attendee dashboard by visiting the created page
4. Make a test booking to verify auto-registration works

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Hydra Booking plugin (must be installed and activated)

## Usage

### Setting Up the Attendee Dashboard

1. Go to Settings > HB Customization in your WordPress admin
2. Select or create a page for the attendee dashboard
3. Add the `[hbc_attendee_dashboard]` shortcode to that page
4. Configure other settings as needed

### Auto Registration

Auto registration is enabled by default. When attendees book meetings:

1. The plugin checks if a user with that email already exists
2. If not, it creates a new user account with the `hbc_attendee` role
3. A welcome email is sent with login credentials
4. The booking is linked to the user account

### Attendee Dashboard Features

Attendees can access their dashboard to:

- **View Bookings**: See all upcoming and past bookings with details
- **Cancel Bookings**: Cancel upcoming bookings (respecting time limits)
- **Reschedule Bookings**: Request to reschedule meetings (respecting time limits)
- **Manage Profile**: Update personal information and change passwords

## Hooks and Filters

The plugin provides several hooks for customization:

### Actions
- `hbc_booking_cancelled` - Fired when a booking is cancelled
- `hbc_attendee_registered` - Fired when a new attendee is auto-registered
- `hbc_profile_updated` - Fired when attendee updates their profile

### Filters
- `hbc_welcome_email_subject` - Customize welcome email subject
- `hbc_welcome_email_message` - Customize welcome email content
- `hbc_dashboard_tabs` - Add custom tabs to the dashboard
- `hbc_booking_actions` - Customize available booking actions

## Customization

### Adding Custom Dashboard Tabs

```php
add_filter('hbc_dashboard_tabs', function($tabs) {
    $tabs['custom'] = __('Custom Tab', 'your-textdomain');
    return $tabs;
});

add_action('hbc_dashboard_tab_custom', function($user) {
    echo '<div>Your custom content here</div>';
});
```

### Customizing Welcome Email

```php
add_filter('hbc_welcome_email_subject', function($subject, $user) {
    return sprintf(__('Welcome to %s!', 'your-textdomain'), get_bloginfo('name'));
}, 10, 2);

add_filter('hbc_welcome_email_message', function($message, $user, $password) {
    // Return your custom message
    return $custom_message;
}, 10, 3);
```

## File Structure

```
hydra-booking-customization/
├── src/
│   ├── Core/
│   │   └── Plugin.php
│   ├── Features/
│   │   ├── AutoRegistration.php
│   │   └── AttendeeDashboard.php
│   └── Admin/
│       └── Settings.php
├── assets/
│   ├── css/
│   │   ├── attendee-dashboard.css
│   │   └── admin.css
│   └── js/
│       ├── attendee-dashboard.js
│       └── admin.js
├── composer.json
├── hydra-booking-customization.php
└── README.md
```

## Troubleshooting

### Common Issues

**Auto-registration not working:**
- Ensure the Hydra Booking plugin is active and up-to-date
- Check that auto-registration is enabled in Settings > HB Customization
- Verify that the `hbc_attendee` role exists (Users > All Users > Role filter)

**Dashboard not displaying:**
- Ensure the attendee dashboard page exists and contains the `[hbc_attendee_dashboard]` shortcode
- Check that the user is logged in and has the `hbc_attendee` role
- Clear any caching plugins

**Booking cancellation/rescheduling not working:**
- Verify the time limits are set correctly in plugin settings
- Check that the booking is within the allowed time frame
- Ensure the booking status allows modifications

**Email notifications not sending:**
- Check WordPress email configuration
- Verify SMTP settings if using an SMTP plugin
- Check spam folders

### Debug Mode
To enable debug mode, add this to your `wp-config.php`:
```php
define( 'HBC_DEBUG', true );
```

This will log additional information to help troubleshoot issues.

## Support

For support and feature requests, please contact the plugin developer or create an issue in the project repository.

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 1.0.0
- Initial release
- Auto registration feature
- Attendee dashboard
- Admin settings page
- Responsive design