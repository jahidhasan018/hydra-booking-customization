# Hydra Booking Customization

A WordPress plugin that extends the [Hydra Booking](https://wordpress.org/plugins/hydra-booking/) system with auto-registration, role-based dashboards, and [Jitsi Meet](https://wordpress.org/plugins/webinar-and-video-conference-with-jitsi-meet/) video conferencing integration.

## Requirements

| Dependency | Minimum Version |
|---|---|
| WordPress | 5.0+ |
| PHP | 7.4+ |
| Hydra Booking plugin | Active |
| Jitsi Meet plugin | Active (for video features) |

## Features

### Auto Registration
- Creates WordPress user accounts automatically when attendees book meetings.
- Assigns attendees to a custom `hbc_attendee` role.
- Sends welcome emails with login credentials.
- Links all bookings to user accounts for tracking.

### Attendee Dashboard (`[hbc_attendee_dashboard]`)
- View upcoming and past bookings with full details.
- Cancel bookings (24-hour minimum advance notice).
- Reschedule bookings (48-hour minimum advance notice).
- Update profile information and change password.
- Responsive Vue.js interface.

### Host Dashboard (`[hbc_host_dashboard]`)
- Today's meetings, upcoming bookings, and meeting history.
- Generate, copy, and send Jitsi meeting join links.
- View detailed booking info in modal dialogs.
- Update host profile (name, email, bio, password).
- Real-time stats: today's meetings, upcoming, completed, active links.

### Jitsi Meet Integration
- Automatic meeting link generation on booking confirmation.
- Secure token-based meeting access with role permissions.
- Embedded meeting interface with responsive design.
- Meeting lifecycle management: reminders, cleanup, termination.
- 5-minute end-of-meeting reminder emails.

### Admin Settings
- Enable/disable auto-registration.
- Configure cancellation and rescheduling time limits.
- Select dashboard pages and customise welcome emails.
- View plugin statistics and attendee counts.

### Caching
- Transient-based caching for stats, bookings, and profile data.
- Automatic cache invalidation on data mutations.
- Cache flushed on plugin activation/deactivation.

## Installation

1. Upload the `hydra-booking-customization` folder to `/wp-content/plugins/`.
2. Activate the plugin via **Plugins → Activate**.
3. Ensure **Hydra Booking** and **Jitsi Meet** plugins are installed and active.
4. The plugin will automatically:
   - Create the `hbc_attendee` user role.
   - Create an "Attendee Dashboard" page with the `[hbc_attendee_dashboard]` shortcode.
   - Set default configuration options.
5. Go to **Settings → HB Customization** to configure.

## Shortcodes

| Shortcode | Description |
|---|---|
| `[hbc_attendee_dashboard]` | Renders the Vue.js attendee dashboard |
| `[hbc_host_dashboard]` | Renders the Vue.js host dashboard |
| `[hbc_login_form]` | Renders the login form for non-authenticated users |

## Hooks & Filters

### Actions
| Hook | Description |
|---|---|
| `hbc_booking_cancelled` | Fired when a booking is cancelled |
| `hbc_booking_rescheduled` | Fired when a booking is rescheduled |
| `hbc_attendee_registered` | Fired when a new attendee is auto-registered |
| `hbc_booking_actions_before` | Renders custom actions before default booking actions |
| `hbc_booking_actions_after` | Renders custom actions after default booking actions |

### Filters
| Filter | Description |
|---|---|
| `hbc_welcome_email_subject` | Customise the welcome email subject |
| `hbc_welcome_email_message` | Customise the welcome email content |
| `hbc_attendee_booking_data` | Modify booking data passed to the attendee dashboard |

## File Structure

```
hydra-booking-customization/
├── hydra-booking-customization.php   # Bootstrap & activation hooks
├── composer.json
├── package.json / vite.config.js     # Vue.js build tooling
├── src/
│   ├── Core/
│   │   ├── Plugin.php                # Core plugin class & shortcode registration
│   │   └── CacheManager.php          # Transient-based caching layer
│   ├── Features/
│   │   ├── AutoRegistration.php      # Auto-registration on booking
│   │   ├── AttendeeDashboard.php     # Attendee AJAX handlers
│   │   ├── HostDashboard.php         # Host AJAX handlers
│   │   ├── JitsiIntegration.php      # Jitsi meeting core logic
│   │   ├── MeetingLifecycle.php      # Cron, reminders, cleanup
│   │   └── MeetingRestApi.php        # REST API endpoints
│   ├── Admin/
│   │   └── Settings.php              # Admin settings page
│   ├── components/                   # Vue.js SFC components
│   ├── utils/                        # JS utility modules
│   └── style.css                     # Dashboard styles
└── dist/                             # Vite build output
```

## REST API

### `GET /wp-json/hydra-booking/v1/jitsi/meeting-link/{booking_id}`

Returns a secure meeting URL for the authenticated user.

**Authentication**: Requires logged-in user who is either the host or an attendee of the booking.

**Response**:
```json
{
  "status": true,
  "meeting_url": "https://example.com/meeting/{token}/",
  "room_name": "meeting-123-title-abc12345",
  "role": "host",
  "booking_id": 123,
  "meeting_start": 1709000000,
  "meeting_end": 1709003600
}
```

## Troubleshooting

| Issue | Solution |
|---|---|
| Meeting links not appearing | Ensure Jitsi Meet plugin is active |
| Dashboard not displaying | Verify the page contains the correct shortcode and user has the right role |
| Auto-registration not working | Check Settings → HB Customization and ensure Hydra Booking is active |
| Stale data on dashboard | Plugin uses 2–10 min cache TTLs; data refreshes automatically |

Enable WordPress debug logging for detailed diagnostics:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

## Changelog

### 1.1.0
- Extracted `MeetingLifecycle` and `MeetingRestApi` from `JitsiIntegration`.
- Added `CacheManager` with transient-based caching and automatic invalidation.
- Extracted AJAX validation helpers in both dashboard classes.
- Removed all debug/test files and `console.log` statements.
- Wrapped `error_log` calls with `WP_DEBUG` checks.
- Hardened security: re-enabled nonce verification, sanitised all inputs.

### 1.0.0
- Initial release.
- Auto-registration, attendee & host dashboards, Jitsi integration.

## License

GPL v2 or later.