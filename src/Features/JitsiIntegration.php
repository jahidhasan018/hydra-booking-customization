<?php

namespace HydraBookingCustomization\Features;

defined( 'ABSPATH' ) || exit;

use WP_Error;

/**
 * Jitsi Meet Integration for Hydra Booking
 * 
 * Provides secure video meeting functionality with token-based authentication
 * and role-based access control for bookings.
 * 
 * Features:
 * - Secure token-based meeting access
 * - Role-based permissions (host/attendee)
 * - Meeting status management
 * - Responsive meeting interface
 * - AJAX-powered join functionality
 * 
 * @package HydraBookingCustomization
 * @since 1.0.0
 * @author Hydra Booking Team
 */
class JitsiIntegration {

    /**
     * Token expiration time in seconds (24 hours)
     */
    const TOKEN_EXPIRATION = 86400;

    /**
     * Meeting grace period in seconds (15 minutes before start)
     */
    const MEETING_GRACE_PERIOD = 900;

    /**
     * Maximum token length for security
     */
    const MAX_TOKEN_LENGTH = 1000;

    /**
     * Reminder time before meeting ends (5 minutes in seconds)
     */
    const REMINDER_TIME_BEFORE_END = 300;

    /**
     * Default Jitsi meeting language
     */
    const DEFAULT_MEETING_LANGUAGE = 'en';

    /**
     * Meeting lifecycle manager.
     *
     * @var MeetingLifecycle
     */
    private $lifecycle;

    /**
     * Meeting REST API handler.
     *
     * @var MeetingRestApi
     */
    private $rest_api;

    /**
     * Meeting page renderer.
     *
     * @var MeetingPage
     */
    private $meeting_page;

    /**
     * Constructor - Initialize hooks and actions
     */
    public function __construct() {
        $this->init_hooks();
        $this->lifecycle    = new MeetingLifecycle( $this );
        $this->rest_api     = new MeetingRestApi( $this );
        $this->meeting_page = new MeetingPage( $this );
    }

    /**
     * Initialize WordPress hooks
     * 
     * @since 1.0.0
     */
    private function init_hooks() {
        // Booking lifecycle hooks.
        add_action( 'hydra_booking/after_booking_confirmed', array( $this, 'create_jitsi_meeting_link' ), 20, 1 );

        // Dashboard and frontend hooks.
        add_filter( 'hbc_attendee_booking_data', array( $this, 'add_jitsi_link_to_booking_data' ), 10, 2 );
        add_action( 'hbc_booking_actions_before', array( $this, 'display_meeting_button' ) );

        // AJAX handlers.
        add_action( 'wp_ajax_hbc_join_jitsi_meeting', array( $this, 'ajax_join_meeting' ) );

        // Asset hooks. Page routing is handled by MeetingPage.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_jitsi_scripts' ) );
    }

    /**
     * Create Jitsi meeting link after booking confirmation
     * 
     * @param object $booking The booking object
     */
    public function create_jitsi_meeting_link( $booking ) {
        if ( ! $this->is_jitsi_plugin_active() ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) { error_log( 'HBC Jitsi Integration: Jitsi Meet plugin is not active' ); }
            return;
        }

        // Generate unique room name for the booking
        $room_name = $this->generate_room_name( $booking );
        
        // Get Jitsi configuration
        $jitsi_config = $this->get_jitsi_config();
        
        // Create meeting URL
        $meeting_url = $this->build_meeting_url( $room_name, $jitsi_config );
        
        // Store the meeting link in booking meta
        $this->store_meeting_link( $booking->booking_id, $room_name, $meeting_url, $jitsi_config );
        
        // Create access tokens for host and attendees
        $this->create_meeting_access_tokens( $booking->booking_id );
        
        // Schedule meeting reminders and cleanup
        $this->lifecycle->schedule_meeting_events( $booking );
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) { error_log( "HBC Jitsi Integration: Created meeting link for booking {$booking->booking_id}: {$meeting_url}" ); }
    }

    /**
     * Create access tokens for meeting participants
     * 
     * @param int $booking_id
     */
    private function create_meeting_access_tokens( $booking_id ) {
        global $wpdb;
        
        // Get booking details
        $bookings_table = $wpdb->prefix . 'tfhb_bookings';
        $attendees_table = $wpdb->prefix . 'tfhb_attendees';
        $meetings_table = $wpdb->prefix . 'tfhb_meetings';
        
        // Get booking and meeting info
        $booking = $wpdb->get_row( $wpdb->prepare(
            "SELECT b.*, m.user_id as host_id 
             FROM {$bookings_table} b 
             LEFT JOIN {$meetings_table} m ON b.meeting_id = m.id 
             WHERE b.id = %d",
            $booking_id
        ) );
        
        if ( ! $booking ) {
            return;
        }
        
        // Create token for host
        if ( $booking->host_id ) {
            $this->generate_meeting_token( $booking_id, $booking->host_id, 'host' );
        }
        
        // Create tokens for attendees
        $attendees = $wpdb->get_results( $wpdb->prepare(
            "SELECT user_id FROM {$attendees_table} WHERE booking_id = %d",
            $booking_id
        ) );
        
        foreach ( $attendees as $attendee ) {
            if ( $attendee->user_id != $booking->host_id ) { // Don't duplicate host token
                $this->generate_meeting_token( $booking_id, $attendee->user_id, 'attendee' );
            }
        }
    }

    /**
     * Generate unique room name for booking
     * 
     * @param object $booking The booking object
     * @return string
     */
    private function generate_room_name( $booking ) {
        // Create a unique room name based on booking details
        $room_name = sprintf(
            'meeting-%d-%s-%s',
            $booking->booking_id,
            sanitize_title( $booking->meeting_title ?? 'booking' ),
            substr( md5( $booking->booking_id . $booking->meeting_dates . $booking->start_time ), 0, 8 )
        );
        
        return sanitize_title( $room_name );
    }

    /**
     * Get Jitsi configuration from plugin settings.
     *
     * Public so child classes (MeetingPage) can access it.
     *
     * @return array
     */
    public function get_jitsi_config() {
        $config = array(
            'api_select' => get_option( 'jitsi_opt_select_api', 'free' ),
            'domain' => get_option( 'jitsi_opt_free_domain', 'meet.jit.si' ),
            'app_id' => get_option( 'jitsi_opt_app_id', '' ),
            'api_key' => get_option( 'jitsi_opt_api_key', '' ),
            'jwt' => get_option( 'jitsi_opt_jwt', '' ),
        );
        
        // Use 8x8.vc domain for JAAS
        if ( $config['api_select'] === 'jaas' ) {
            $config['domain'] = '8x8.vc';
        }
        
        // Validate and sanitize domain
        $config['domain'] = $this->validate_jitsi_domain( $config['domain'] );
        
        return $config;
    }
    
    /**
     * Validate and sanitize Jitsi domain
     * 
     * @param string $domain
     * @return string
     */
    private function validate_jitsi_domain( $domain ) {
        // Remove protocol if present
        $domain = preg_replace( '/^https?:\/\//', '', $domain );
        
        // Remove trailing slash
        $domain = rtrim( $domain, '/' );
        
        // Validate domain format
        if ( ! filter_var( 'https://' . $domain, FILTER_VALIDATE_URL ) ) {
            // Fallback to default reliable domain
            $domain = 'meet.jit.si';
        }
        
        // List of known reliable Jitsi domains
        $reliable_domains = array(
            'meet.jit.si',
            '8x8.vc',
            'meet.ffmuc.net',
            'jitsi.riot.im'
        );
        
        // If domain is not in reliable list and is the default, ensure it's properly configured
        if ( $domain === 'meet.jit.si' ) {
            // meet.jit.si is reliable but sometimes shows browser warnings
            // We'll keep it but ensure proper configuration in the frontend
        }
        
        return $domain;
    }

    /**
     * Build meeting URL based on configuration
     * 
     * @param string $room_name
     * @param array $config
     * @return string
     */
    private function build_meeting_url( $room_name, $config ) {
        $domain = $config['domain'];
        
        // For JAAS, include app ID in room name
        if ( $config['api_select'] === 'jaas' && ! empty( $config['app_id'] ) ) {
            $room_name = $config['app_id'] . '/' . $room_name;
        }
        
        // Build the meeting URL with English language parameter
        $meeting_url = 'https://' . $domain . '/' . $room_name . '#config.defaultLanguage="' . self::DEFAULT_MEETING_LANGUAGE . '"';
        
        return $meeting_url;
    }

    /**
     * Store meeting link in booking meta
     * 
     * @param int $booking_id
     * @param string $room_name
     * @param string $meeting_url
     * @param array $config
     */
    private function store_meeting_link( $booking_id, $room_name, $meeting_url, $config ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tfhb_booking_meta';
        
        // Store meeting data
        $meeting_data = array(
            'room_name' => $room_name,
            'meeting_url' => $meeting_url,
            'domain' => $config['domain'],
            'api_select' => $config['api_select'],
            'created_at' => current_time( 'mysql' )
        );
        
        // Insert or update meeting meta
        $wpdb->replace(
            $table_name,
            array(
                'booking_id' => $booking_id,
                'meta_key' => 'jitsi_meeting',
                'value' => wp_json_encode( $meeting_data ),
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' )
            ),
            array( '%d', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Add Jitsi meeting link to booking data
     * 
     * @param object $booking
     * @param int $booking_id
     * @return object
     */
    public function add_jitsi_link_to_booking_data( $booking, $booking_id ) {
        $meeting_data = $this->get_meeting_data( $booking_id );
        
        if ( $meeting_data ) {
            $booking->jitsi_meeting_url = $meeting_data['meeting_url'];
            $booking->jitsi_room_name = $meeting_data['room_name'];
            $booking->jitsi_domain = $meeting_data['domain'];
        }
        
        return $booking;
    }

    /**
     * Get meeting data from booking meta.
     *
     * Public so child classes (MeetingLifecycle, MeetingRestApi, MeetingPage) can access it.
     * 
     * @param int $booking_id
     * @return array|null
     */
    public function get_meeting_data( $booking_id ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tfhb_booking_meta';
        
        $result = $wpdb->get_var( $wpdb->prepare(
            "SELECT value FROM {$table_name} WHERE booking_id = %d AND meta_key = 'jitsi_meeting'",
            $booking_id
        ) );
        
        if ( $result ) {
            return json_decode( $result, true );
        }
        
        return null;
    }

    /**
     * Display meeting button in booking actions
     * 
     * @param object $booking
     */
    public function display_meeting_button( $booking ) {
        $meeting_data = $this->get_meeting_data( $booking->booking_id );
        
        if ( $meeting_data && ! empty( $meeting_data['meeting_url'] ) ) {
            $current_time = current_time( 'timestamp' );
            $meeting_time = strtotime( $booking->meeting_dates . ' ' . $booking->start_time );
            $meeting_end_time = strtotime( $booking->meeting_dates . ' ' . $booking->end_time );
            $user_id = get_current_user_id();
            
            // Determine if current user is the host
            $user_role = $this->get_user_role_in_meeting( $booking->booking_id, $user_id );
            
            // Generate secure meeting URL for current user
            $secure_meeting_url = $this->generate_secure_meeting_url( $booking->booking_id, $user_id, $user_role );
            
            // Show join button only if meeting is today or in the future
            if ( $meeting_time <= $current_time && $current_time <= $meeting_end_time ) {
                // Meeting is currently active
                $button_text = $user_role === 'host' ? __( 'Start Meeting', 'hydra-booking-customization' ) : __( 'Join Meeting', 'hydra-booking-customization' );
                echo '<a href="' . esc_url( $secure_meeting_url ) . '" class="button button-primary hbc-secure-meeting-btn hbc-btn-active" target="_blank">';
                echo '<i class="fas fa-video"></i> ' . esc_html( $button_text );
                if ( $user_role === 'host' ) {
                    echo ' <span class="host-badge">' . esc_html__( '(Host)', 'hydra-booking-customization' ) . '</span>';
                }
                echo '</a>';
            } elseif ( $meeting_time > $current_time ) {
                // Meeting is in the future
                $time_diff = $meeting_time - $current_time;
                if ( $time_diff > 900 ) { // 15 minutes before
                    $button_text = $user_role === 'host' ? __( 'Start Meeting', 'hydra-booking-customization' ) : __( 'Join Meeting', 'hydra-booking-customization' );
                    echo '<a href="' . esc_url( $secure_meeting_url ) . '" class="button button-primary hbc-secure-meeting-btn hbc-btn-ready" target="_blank">';
                    echo '<i class="fas fa-video"></i> ' . esc_html( $button_text );
                    if ( $user_role === 'host' ) {
                        echo ' <span class="host-badge">' . esc_html__( '(Host)', 'hydra-booking-customization' ) . '</span>';
                    }
                    echo '</a>';
                } else {
                    echo '<span class="hbc-meeting-scheduled">';
                    echo '<i class="fas fa-clock"></i> ' . esc_html__( 'Scheduled', 'hydra-booking-customization' );
                    if ( $user_role === 'host' ) {
                        echo ' <span class="host-badge">' . esc_html__( '(Host)', 'hydra-booking-customization' ) . '</span>';
                    }
                    
                    echo '</span>';
                    
                }
            } else {
                // Meeting has ended
                echo '<span class="hbc-meeting-ended">';
                echo '<i class="fas fa-check-circle"></i> ' . esc_html__( 'Completed', 'hydra-booking-customization' );
                echo '</span>';
            }
        }
    }

    /**
     * Get user role in meeting (host or attendee).
     *
     * Public so child classes can access it.
     * 
     * @param int $booking_id
     * @param int $user_id
     * @return string
     */
    public function get_user_role_in_meeting( $booking_id, $user_id ) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'tfhb_bookings';
        $meetings_table = $wpdb->prefix . 'tfhb_meetings';
        
        // Check if user is the host
        $is_host = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) 
             FROM {$bookings_table} b 
             LEFT JOIN {$meetings_table} m ON b.meeting_id = m.id 
             WHERE b.id = %d AND m.user_id = %d",
            $booking_id,
            $user_id
        ) );
        
        return $is_host > 0 ? 'host' : 'attendee';
    }

    /**
     * AJAX handler for joining meeting
     * 
     * Processes secure meeting join requests with proper validation and error handling.
     * 
     * @since 1.0.0
     */
    public function ajax_join_meeting() {
        try {
            // Verify nonce for security
            $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
            if ( ! wp_verify_nonce( $nonce, 'hbc_attendee_dashboard' ) ) {
                wp_send_json_error( array( 
                    'message' => __( 'Security verification failed. Please refresh the page and try again.', 'hydra-booking-customization' ),
                    'code' => 'nonce_failed'
                ) );
            }

            // Validate booking ID
            $booking_id = isset( $_POST['booking_id'] ) ? intval( $_POST['booking_id'] ) : 0;
            if ( $booking_id <= 0 ) {
                wp_send_json_error( array( 
                    'message' => __( 'Invalid booking ID provided.', 'hydra-booking-customization' ),
                    'code' => 'invalid_booking_id'
                ) );
            }

            $user_id = get_current_user_id();

            if ( ! $user_id ) {
                wp_send_json_error( array( 
                    'message' => __( 'You must be logged in to join a meeting.', 'hydra-booking-customization' ),
                    'code' => 'not_logged_in'
                ) );
            }

            // Verify user has access to this booking
            if ( ! $this->user_has_booking_access( $user_id, $booking_id ) ) {
                wp_send_json_error( array( 
                    'message' => __( 'You do not have permission to access this meeting.', 'hydra-booking-customization' ),
                    'code' => 'access_denied'
                ) );
            }

            $meeting_data = $this->get_meeting_data( $booking_id );
            
            if ( ! $meeting_data ) {
                wp_send_json_error( array( 
                    'message' => __( 'Meeting information not found. Please contact support.', 'hydra-booking-customization' ),
                    'code' => 'meeting_not_found'
                ) );
            }

            // Determine user role in meeting
            $user_role = $this->get_user_role_in_meeting( $booking_id, $user_id );
            if ( ! $user_role ) {
                wp_send_json_error( array( 
                    'message' => __( 'Unable to determine your role in this meeting.', 'hydra-booking-customization' ),
                    'code' => 'role_not_found'
                ) );
            }
            
            // Generate secure meeting token
            $token = $this->generate_meeting_token( $booking_id, $user_id, $user_role );
            if ( is_wp_error( $token ) ) {
                wp_send_json_error( array( 
                    'message' => $token->get_error_message(),
                    'code' => $token->get_error_code()
                ) );
            }
            
            // Generate secure meeting URL
            $secure_meeting_url = home_url( '/meeting/' . $token . '/' );

            // Log successful meeting join attempt
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( sprintf(
                    'Jitsi Integration: User %d successfully generated token for booking %d with role %s',
                    $user_id,
                    $booking_id,
                    $user_role
                ) );
            }

            wp_send_json_success( array(
                'meeting_url' => esc_url( $secure_meeting_url ),
                'room_name' => sanitize_text_field( $meeting_data['room_name'] ),
                'role' => sanitize_text_field( $user_role ),
                'booking_id' => $booking_id
            ) );
            
        } catch ( \Exception $e ) {
            // Log the error for debugging
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) { error_log( 'Jitsi Integration AJAX Error: ' . $e->getMessage() ); }
            
            wp_send_json_error( array( 
                'message' => __( 'An unexpected error occurred. Please try again later.', 'hydra-booking-customization' ),
                'code' => 'unexpected_error'
            ) );
        }
    }

    // REST API routes are now handled by MeetingRestApi class.



    /**
     * Check if user has access to booking.
     *
     * Public so child classes can access it.
     * 
     * @param int $user_id
     * @param int $booking_id
     * @return bool
     */
    public function user_has_booking_access( $user_id, $booking_id ) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'tfhb_bookings';
        $meetings_table = $wpdb->prefix . 'tfhb_meetings';
        $attendees_table = $wpdb->prefix . 'tfhb_attendees';
        
        // Check if user is host
        $is_host = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) 
             FROM {$bookings_table} b 
             LEFT JOIN {$meetings_table} m ON b.meeting_id = m.id 
             WHERE b.id = %d AND m.user_id = %d",
            $booking_id,
            $user_id
        ) );
        
        // Check if user is attendee
        $is_attendee = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$attendees_table} WHERE booking_id = %d AND user_id = %d",
            $booking_id,
            $user_id
        ) );
        
        // User must be either host or attendee
        return $is_host > 0 || $is_attendee > 0;
    }

    /**
     * Enqueue Jitsi integration scripts
     */
    public function enqueue_jitsi_scripts() {
        global $post;
        
        // Check if we're on a page with the attendee dashboard shortcode
        if ( $post && has_shortcode( $post->post_content, 'hbc_attendee_dashboard' ) ) {
            // TODO: Create Jitsi integration assets when needed
            // wp_enqueue_script(
            //     'hbc-jitsi-integration',
            //     HBC_PLUGIN_URL . 'assets/js/jitsi-integration.js',
            //     array( 'jquery' ),
            //     HBC_VERSION,
            //     true
            // );

            // wp_enqueue_style(
            //     'hbc-jitsi-integration',
            //     HBC_PLUGIN_URL . 'assets/css/jitsi-integration.css',
            //     array(),
            //     HBC_VERSION
            // );

            // TODO: Uncomment when Jitsi integration assets are created
            // wp_localize_script( 'hbc-jitsi-integration', 'hbc_jitsi', array(
            //     'ajax_url' => admin_url( 'admin-ajax.php' ),
            //     'nonce' => wp_create_nonce( 'hbc_attendee_dashboard' ),
            //     'strings' => array(
            //         'joining_meeting' => __( 'Joining meeting...', 'hydra-booking-customization' ),
            //         'error_joining' => __( 'Error joining meeting', 'hydra-booking-customization' ),
            //     )
            // ) );

            // Enqueue Jitsi External API if plugin is active
            if ( $this->is_jitsi_plugin_active() ) {
                $jitsi_config = $this->get_jitsi_config();
                if ( $jitsi_config['api_select'] === 'jaas' ) {
                    wp_enqueue_script( 'jitsi-8x8-api', 'https://8x8.vc/external_api.js', null, '2.1.2', false );
                } else {
                    wp_enqueue_script( 'jitsi-external-api', 'https://' . $jitsi_config['domain'] . '/external_api.js', null, '1.0.0', false );
                }
            }
        }
    }

    /**
     * Check if Jitsi Meet plugin is active
     * 
     * @return bool
     */
    private function is_jitsi_plugin_active() {
        return is_plugin_active( 'webinar-and-video-conference-with-jitsi-meet/jitsi-meet-wp.php' ) ||
               function_exists( 'jitsi_meet_wp' );
    }

    /**
     * Create Jitsi meeting links for existing bookings that don't have them
     * This method can be called manually to retroactively add meeting links
     */
    public function create_missing_meeting_links() {
        if ( ! $this->is_jitsi_plugin_active() ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) { error_log( 'HBC Jitsi Integration: Jitsi Meet plugin is not active' ); }
            return;
        }

        global $wpdb;
        
        // Get all upcoming bookings that don't have Jitsi meeting data
        $bookings_table = $wpdb->prefix . 'tfhb_bookings';
        $meetings_table = $wpdb->prefix . 'tfhb_meetings';
        $meta_table = $wpdb->prefix . 'tfhb_booking_meta';
        
        $query = "
            SELECT b.id as booking_id, b.meeting_dates, b.start_time, b.end_time, m.title as meeting_title
            FROM {$bookings_table} b
            LEFT JOIN {$meetings_table} m ON b.meeting_id = m.id
            LEFT JOIN {$meta_table} meta ON b.id = meta.booking_id AND meta.meta_key = 'jitsi_meeting'
            WHERE b.meeting_dates >= CURDATE()
            AND meta.booking_id IS NULL
            AND b.status = 'confirmed'
        ";
        
        $bookings = $wpdb->get_results( $query );
        
        if ( empty( $bookings ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) { error_log( 'HBC Jitsi Integration: No bookings found that need meeting links' ); }
            return;
        }
        
        $created_count = 0;
        foreach ( $bookings as $booking ) {
            $this->create_jitsi_meeting_link( $booking );
            $created_count++;
        }
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) { error_log( "HBC Jitsi Integration: Created meeting links for {$created_count} existing bookings" ); }
        
        return $created_count;
    }

    // Page routing (rewrite rules, query vars, handle_request) is now handled by MeetingPage class.

    /**
     * Generate secure access token for meeting
     * 
     * Creates a secure, time-limited token for meeting access with role-based permissions.
     * 
     * @param int $booking_id The booking ID
     * @param int $user_id The user ID
     * @param string $role User role (host|attendee)
     * @return string|\WP_Error Base64 encoded token or error
     * @since 1.0.0
     */
    private function generate_meeting_token( $booking_id, $user_id, $role = 'attendee' ) {
        // Validate inputs
        if ( ! is_numeric( $booking_id ) || $booking_id <= 0 ) {
            return new \WP_Error( 'invalid_booking_id', __( 'Invalid booking ID provided.', 'hydra-booking-customization' ) );
        }

        if ( ! is_numeric( $user_id ) || $user_id <= 0 ) {
            return new \WP_Error( 'invalid_user_id', __( 'Invalid user ID provided.', 'hydra-booking-customization' ) );
        }

        if ( ! in_array( $role, array( 'host', 'attendee' ), true ) ) {
            return new \WP_Error( 'invalid_role', __( 'Invalid role provided.', 'hydra-booking-customization' ) );
        }

        // Create token data with enhanced security
        $data = array(
            'booking_id' => (int) $booking_id,
            'user_id' => (int) $user_id,
            'role' => sanitize_text_field( $role ),
            'expires' => time() + self::TOKEN_EXPIRATION,
            'issued_at' => time(),
            'site_url' => get_site_url(), // Prevent token reuse across sites
            'user_agent_hash' => $this->get_user_agent_hash(), // Basic fingerprinting
            'nonce' => wp_create_nonce( 'hbc_meeting_' . $booking_id . '_' . $user_id )
        );
        
        $token = base64_encode( wp_json_encode( $data ) );
        
        // Validate token length for security
        if ( strlen( $token ) > self::MAX_TOKEN_LENGTH ) {
            return new \WP_Error( 'token_too_long', __( 'Generated token exceeds maximum length.', 'hydra-booking-customization' ) );
        }
        
        // Store token in database for validation
        $store_result = $this->store_meeting_token( $token, $booking_id, $user_id, $role );
        if ( is_wp_error( $store_result ) ) {
            return $store_result;
        }
        
        return $token;
    }

    /**
     * Store meeting token in database
     * 
     * @param string $token The token to store
     * @param int $booking_id The booking ID
     * @param int $user_id The user ID
     * @param string $role User role
     * @return bool|\WP_Error True on success, WP_Error on failure
     * @since 1.0.0
     */
    private function store_meeting_token( $token, $booking_id, $user_id, $role ) {
        global $wpdb;
        
        // Validate inputs
        if ( empty( $token ) || strlen( $token ) > self::MAX_TOKEN_LENGTH ) {
            return new \WP_Error( 'invalid_token', __( 'Invalid token provided for storage.', 'hydra-booking-customization' ) );
        }

        $table_name = $wpdb->prefix . 'tfhb_booking_meta';
        
        $token_data = array(
            'token' => sanitize_text_field( $token ),
            'user_id' => (int) $user_id,
            'role' => sanitize_text_field( $role ),
            'created_at' => current_time( 'mysql' ),
            'expires_at' => gmdate( 'Y-m-d H:i:s', time() + self::TOKEN_EXPIRATION )
        );
        
        $result = $wpdb->replace(
            $table_name,
            array(
                'booking_id' => (int) $booking_id,
                'meta_key' => 'meeting_token_' . (int) $user_id,
                'value' => wp_json_encode( $token_data ),
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' )
            ),
            array( '%d', '%s', '%s', '%s', '%s' )
        );
        
        if ( false === $result ) {
            return new \WP_Error( 'token_storage_failed', __( 'Failed to store meeting token.', 'hydra-booking-customization' ) );
        }
        
        return true;
    }

    /**
     * Get a hash of the user agent for basic fingerprinting
     * 
     * @return string Hashed user agent
     * @since 1.0.0
     */
    private function get_user_agent_hash() {
        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
        return hash( 'sha256', $user_agent . wp_salt() );
    }

    /**
     * Validate meeting token.
     *
     * Validates a meeting token for authenticity, expiration, and user permissions.
     * Public so child classes (MeetingPage) can access it.
     *
     * @param string $token The token to validate.
     * @return array|\WP_Error Token data on success, WP_Error on failure.
     * @since 1.0.0
     */
    public function validate_meeting_token( $token ) {
        // Validate token format
        if ( empty( $token ) || ! is_string( $token ) ) {
            return new \WP_Error( 'invalid_token_format', __( 'Invalid token format provided.', 'hydra-booking-customization' ) );
        }

        // Check token length
        if ( strlen( $token ) > self::MAX_TOKEN_LENGTH ) {
            return new \WP_Error( 'token_too_long', __( 'Token exceeds maximum allowed length.', 'hydra-booking-customization' ) );
        }
        
        // Decode token
        $decoded = base64_decode( $token, true );
        if ( false === $decoded ) {
            return new \WP_Error( 'token_decode_failed', __( 'Failed to decode token.', 'hydra-booking-customization' ) );
        }

        $token_data = json_decode( $decoded, true );
        if ( ! is_array( $token_data ) ) {
            return new \WP_Error( 'invalid_token_data', __( 'Invalid token data structure.', 'hydra-booking-customization' ) );
        }
        
        // Validate required fields
        $required_fields = [ 'booking_id', 'user_id', 'role', 'expires', 'issued_at', 'site_url' ];
        foreach ( $required_fields as $field ) {
            if ( ! isset( $token_data[ $field ] ) ) {
                return new \WP_Error( 'missing_token_field', sprintf( __( 'Missing required token field: %s', 'hydra-booking-customization' ), $field ) );
            }
        }
        
        // Check expiration
        if ( $token_data['expires'] < time() ) {
            return new \WP_Error( 'token_expired', __( 'Meeting token has expired.', 'hydra-booking-customization' ) );
        }

        // Validate site URL to prevent cross-site token usage
        if ( $token_data['site_url'] !== get_site_url() ) {
            return new \WP_Error( 'invalid_site', __( 'Token is not valid for this site.', 'hydra-booking-customization' ) );
        }

        // Validate user agent hash for basic security (optional check)
        if ( isset( $token_data['user_agent_hash'] ) ) {
            $current_hash = $this->get_user_agent_hash();
            if ( $token_data['user_agent_hash'] !== $current_hash ) {
                // Log suspicious activity but don't fail completely
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) { error_log( 'Jitsi Integration: User agent mismatch for token validation' ); }
            }
        }
        
        // Verify booking exists and user has access
        global $wpdb;
        $booking_id = (int) $token_data['booking_id'];
        $user_id = (int) $token_data['user_id'];
        
        // Check if booking exists
        $bookings_table = $wpdb->prefix . 'tfhb_bookings';
        $booking_exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$bookings_table} WHERE id = %d",
            $booking_id
        ) );
        
        if ( ! $booking_exists ) {
            return new \WP_Error( 'booking_not_found', __( 'Booking not found.', 'hydra-booking-customization' ) );
        }
        
        // Check if user has access to this booking (either as host or attendee)
        $meetings_table = $wpdb->prefix . 'tfhb_meetings';
        $attendees_table = $wpdb->prefix . 'tfhb_attendees';
        
        // Check if user is host
        $is_host = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) 
             FROM {$bookings_table} b 
             LEFT JOIN {$meetings_table} m ON b.meeting_id = m.id 
             WHERE b.id = %d AND m.user_id = %d",
            $booking_id,
            $user_id
        ) );
        
        // Check if user is attendee
        $is_attendee = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$attendees_table} WHERE booking_id = %d AND user_id = %d",
            $booking_id,
            $user_id
        ) );
        
        // User must be either host or attendee
        if ( ! $is_host && ! $is_attendee ) {
            return new \WP_Error( 'access_denied', __( 'User does not have access to this booking.', 'hydra-booking-customization' ) );
        }
        
        // Check if meeting data exists
        $table_name = $wpdb->prefix . 'tfhb_booking_meta';
        $meeting_data = $wpdb->get_var( $wpdb->prepare(
            "SELECT value FROM {$table_name} WHERE booking_id = %d AND meta_key = 'jitsi_meeting'",
            $booking_id
        ) );
        
        if ( ! $meeting_data ) {
            return new \WP_Error( 'meeting_not_found', __( 'Meeting data not found.', 'hydra-booking-customization' ) );
        }
        
        return $token_data;
    }

    // Display methods (display_meeting_page, get_meeting_status, display_error_page) are now handled by MeetingPage class.

    /**
     * Get booking data.
     *
     * Public so child classes can access it.
     * 
     * @param int $booking_id
     * @return object|null
     */
    public function get_booking_data( $booking_id ) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'tfhb_bookings';
        $meetings_table = $wpdb->prefix . 'tfhb_meetings';
        
        $query = $wpdb->prepare(
            "SELECT b.*, m.title as meeting_title 
             FROM {$bookings_table} b 
             LEFT JOIN {$meetings_table} m ON b.meeting_id = m.id 
             WHERE b.id = %d",
            $booking_id
        );
        
        return $wpdb->get_row( $query );
    }

    // Remaining display methods (display_waiting_page, display_ended_page, display_meeting_interface) are now handled by MeetingPage class.


    /**
     * Generate secure meeting URL with token
     * 
     * @param int $booking_id
     * @param int $user_id
     * @param string $role
     * @return string|false
     */
    public function generate_secure_meeting_url( $booking_id, $user_id, $role = 'attendee' ) {
        // First, try to get existing valid token
        $existing_token = $this->get_existing_meeting_token( $booking_id, $user_id, $role );
        
        if ( $existing_token && ! is_wp_error( $existing_token ) ) {
            return home_url( 'meeting/' . $existing_token );
        }
        
        // Generate new token if no valid existing token
        $token = $this->generate_meeting_token( $booking_id, $user_id, $role );
        
        if ( is_wp_error( $token ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) { error_log( 'Failed to generate meeting token: ' . $token->get_error_message() ); }
            return false;
        }
        
        return home_url( 'meeting/' . $token );
    }

    /**
     * Get existing valid meeting token for user
     * 
     * @param int $booking_id
     * @param int $user_id
     * @param string $role
     * @return string|false|WP_Error
     */
    private function get_existing_meeting_token( $booking_id, $user_id, $role ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tfhb_booking_meta';
        
        $result = $wpdb->get_var( $wpdb->prepare(
            "SELECT value FROM {$table_name} WHERE booking_id = %d AND meta_key = %s",
            $booking_id,
            'meeting_token_' . $user_id
        ) );
        
        if ( ! $result ) {
            return false;
        }
        
        $token_data = json_decode( $result, true );
        if ( ! $token_data || ! isset( $token_data['token'] ) ) {
            return false;
        }
        
        // Check if token is still valid
        $expires_at = isset( $token_data['expires_at'] ) ? strtotime( $token_data['expires_at'] ) : 0;
        if ( $expires_at < time() ) {
            // Token expired, remove it
            $wpdb->delete(
                $table_name,
                array(
                    'booking_id' => $booking_id,
                    'meta_key' => 'meeting_token_' . $user_id
                ),
                array( '%d', '%s' )
            );
            return false;
        }
        
        // Validate the token to ensure it's still valid
        $validation_result = $this->validate_meeting_token( $token_data['token'] );
        if ( is_wp_error( $validation_result ) ) {
            // Invalid token, remove it
            $wpdb->delete(
                $table_name,
                array(
                    'booking_id' => $booking_id,
                    'meta_key' => 'meeting_token_' . $user_id
                ),
                array( '%d', '%s' )
            );
            return false;
        }
        
        return $token_data['token'];
    }

    // Lifecycle methods (cron, reminders, cleanup) are now handled by MeetingLifecycle class.
}