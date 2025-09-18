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
     * Constructor - Initialize hooks and actions
     */
    public function __construct() {
        $this->init_hooks();
        $this->init_cron_jobs();
    }

    /**
     * Initialize WordPress hooks
     * 
     * @since 1.0.0
     */
    private function init_hooks() {
        // Booking lifecycle hooks
        add_action( 'hydra_booking/after_booking_confirmed', array( $this, 'create_jitsi_meeting_link' ), 20, 1 );
        
        // Dashboard and frontend hooks
        add_filter( 'hbc_attendee_booking_data', array( $this, 'add_jitsi_link_to_booking_data' ), 10, 2 );
        add_action( 'hbc_booking_actions_before', array( $this, 'display_meeting_button' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_hbc_join_jitsi_meeting', array( $this, 'ajax_join_meeting' ) );
        
        // REST API endpoints
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        
        // Asset and routing hooks
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_jitsi_scripts' ) );
        add_action( 'init', array( $this, 'add_meeting_page_rewrite_rule' ) );
        add_action( 'template_redirect', array( $this, 'handle_meeting_page_request' ) );
        add_filter( 'query_vars', array( $this, 'add_meeting_query_vars' ) );
        
        // Meeting duration and reminder hooks
        add_action( 'hbc_meeting_reminder', array( $this, 'send_meeting_reminder' ), 10, 2 );
        add_action( 'hbc_meeting_cleanup', array( $this, 'cleanup_expired_meeting' ), 10, 1 );
        add_action( 'hbc_meeting_terminate', array( $this, 'terminate_meeting_room' ), 10, 1 );
    }

    /**
     * Create Jitsi meeting link after booking confirmation
     * 
     * @param object $booking The booking object
     */
    public function create_jitsi_meeting_link( $booking ) {
        if ( ! $this->is_jitsi_plugin_active() ) {
            error_log( 'HBC Jitsi Integration: Jitsi Meet plugin is not active' );
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
        $this->schedule_meeting_events( $booking );
        
        error_log( "HBC Jitsi Integration: Created meeting link for booking {$booking->booking_id}: {$meeting_url}" );
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
     * Get Jitsi configuration from plugin settings
     * 
     * @return array
     */
    private function get_jitsi_config() {
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
     * Get meeting data from booking meta
     * 
     * @param int $booking_id
     * @return array|null
     */
    private function get_meeting_data( $booking_id ) {
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
            
            // Check if testing mode is enabled to bypass time restrictions
			$testing_mode = ( HBC_TEST_MODE_STATUS === 'active' );
            
            // Determine if current user is the host
            $user_role = $this->get_user_role_in_meeting( $booking->booking_id, $user_id );
            
            // Generate secure meeting URL for current user
            $secure_meeting_url = $this->generate_secure_meeting_url( $booking->booking_id, $user_id, $user_role );
            
            // In testing mode, always show active meeting button
            if ( $testing_mode ) {
                $button_text = $user_role === 'host' ? __( 'Start Meeting', 'hydra-booking-customization' ) : __( 'Join Meeting', 'hydra-booking-customization' );
                echo '<a href="' . esc_url( $secure_meeting_url ) . '" class="button button-primary hbc-secure-meeting-btn hbc-btn-active" target="_blank">';
                echo '<i class="fas fa-video"></i> ' . esc_html( $button_text );
                if ( $user_role === 'host' ) {
                    echo ' <span class="host-badge">' . esc_html__( '(Host)', 'hydra-booking-customization' ) . '</span>';
                }
                echo '</a>';
                return;
            }
            
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
     * Get user role in meeting (host or attendee)
     * 
     * @param int $booking_id
     * @param int $user_id
     * @return string
     */
    private function get_user_role_in_meeting( $booking_id, $user_id ) {
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
            error_log( sprintf( 
                'Jitsi Integration: User %d successfully generated token for booking %d with role %s', 
                $user_id, 
                $booking_id, 
                $user_role 
            ) );

            wp_send_json_success( array(
                'meeting_url' => esc_url( $secure_meeting_url ),
                'room_name' => sanitize_text_field( $meeting_data['room_name'] ),
                'role' => sanitize_text_field( $user_role ),
                'booking_id' => $booking_id
            ) );
            
        } catch ( \Exception $e ) {
            // Log the error for debugging
            error_log( 'Jitsi Integration AJAX Error: ' . $e->getMessage() );
            
            wp_send_json_error( array( 
                'message' => __( 'An unexpected error occurred. Please try again later.', 'hydra-booking-customization' ),
                'code' => 'unexpected_error'
            ) );
        }
    }

    /**
     * Register REST API routes
     * 
     * @since 1.0.0
     */
    public function register_rest_routes() {
        register_rest_route( 'hydra-booking/v1', '/jitsi/meeting-link/(?P<booking_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array( $this, 'rest_get_meeting_link' ),
            'permission_callback' => array( $this, 'rest_permission_check' ),
            'args' => array(
                'booking_id' => array(
                    'required' => true,
                    'validate_callback' => function( $param, $request, $key ) {
                        return is_numeric( $param );
                    }
                ),
            ),
        ) );
    }

    /**
     * REST API permission check
     * 
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function rest_permission_check( $request ) {
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'rest_forbidden', __( 'You must be logged in to access meeting links.', 'hydra-booking-customization' ), array( 'status' => 401 ) );
        }
        
        $booking_id = (int) $request['booking_id'];
        $user_id = get_current_user_id();
        
        // Check if user has access to this booking
        if ( ! $this->user_has_booking_access( $user_id, $booking_id ) ) {
            return new WP_Error( 'rest_forbidden', __( 'You do not have permission to access this meeting.', 'hydra-booking-customization' ), array( 'status' => 403 ) );
        }
        
        return true;
    }

    /**
     * REST API endpoint to get meeting link
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function rest_get_meeting_link( $request ) {
        $booking_id = (int) $request['booking_id'];
        $user_id = get_current_user_id();
        
        try {
            // Get booking data
            $booking = $this->get_booking_data( $booking_id );
            if ( ! $booking ) {
                return new WP_Error( 'booking_not_found', __( 'Booking not found.', 'hydra-booking-customization' ), array( 'status' => 404 ) );
            }
            
            // Check booking status
            if ( $booking->status !== 'confirmed' ) {
                return new WP_Error( 'booking_not_confirmed', __( 'Meeting is only available for confirmed bookings.', 'hydra-booking-customization' ), array( 'status' => 400 ) );
            }
            
            // Get meeting data
            $meeting_data = $this->get_meeting_data( $booking_id );
            if ( ! $meeting_data ) {
                return new WP_Error( 'meeting_not_found', __( 'Meeting link not found for this booking.', 'hydra-booking-customization' ), array( 'status' => 404 ) );
            }
            
            // Check meeting timing (5 minutes before to end time)
            $current_time = current_time( 'timestamp' );
            $meeting_start = strtotime( $booking->meeting_dates . ' ' . $booking->start_time );
            $meeting_end = strtotime( $booking->meeting_dates . ' ' . $booking->end_time );
            $five_minutes_before = $meeting_start - ( 5 * 60 );
            
            // Check if testing mode is enabled to bypass time restrictions
			$testing_mode = ( HBC_TEST_MODE_STATUS === 'active' );
            
            if ( ! $testing_mode ) {
                // Only enforce time restrictions when testing mode is disabled
                if ( $current_time < $five_minutes_before ) {
                    return new WP_Error( 'meeting_not_available', __( 'Meeting will be available 5 minutes before the scheduled time.', 'hydra-booking-customization' ), array( 'status' => 400 ) );
                }
                
                if ( $current_time > $meeting_end ) {
                    return new WP_Error( 'meeting_ended', __( 'This meeting has ended.', 'hydra-booking-customization' ), array( 'status' => 400 ) );
                }
            }
            
            // Determine user role
            $user_role = $this->get_user_role_in_meeting( $booking_id, $user_id );
            
            // Generate secure meeting URL
            $secure_meeting_url = $this->generate_secure_meeting_url( $booking_id, $user_id, $user_role );
            
            if ( ! $secure_meeting_url ) {
                return new WP_Error( 'meeting_link_generation_failed', __( 'Failed to generate meeting link.', 'hydra-booking-customization' ), array( 'status' => 500 ) );
            }
            
            return rest_ensure_response( array(
                'status' => true,
                'meeting_url' => $secure_meeting_url,
                'room_name' => $meeting_data['room_name'],
                'role' => $user_role,
                'booking_id' => $booking_id,
                'meeting_start' => $meeting_start,
                'meeting_end' => $meeting_end,
                'testing_mode' => $testing_mode
            ) );
            
        } catch ( \Exception $e ) {
            error_log( 'Jitsi REST API Error: ' . $e->getMessage() );
            return new WP_Error( 'internal_error', __( 'An internal error occurred.', 'hydra-booking-customization' ), array( 'status' => 500 ) );
        }
    }

    /**
     * Check if user has access to booking
     * 
     * @param int $user_id
     * @param int $booking_id
     * @return bool
     */
    private function user_has_booking_access( $user_id, $booking_id ) {
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
            error_log( 'HBC Jitsi Integration: Jitsi Meet plugin is not active' );
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
            error_log( 'HBC Jitsi Integration: No bookings found that need meeting links' );
            return;
        }
        
        $created_count = 0;
        foreach ( $bookings as $booking ) {
            $this->create_jitsi_meeting_link( $booking );
            $created_count++;
        }
        
        error_log( "HBC Jitsi Integration: Created meeting links for {$created_count} existing bookings" );
        
        return $created_count;
    }

    /**
     * Add rewrite rule for custom meeting page
     */
    public function add_meeting_page_rewrite_rule() {
        add_rewrite_rule(
            '^meeting/([^/]+)/?$',
            'index.php?hbc_meeting_token=$matches[1]',
            'top'
        );
        
        // Set flag to flush rewrite rules on next page load
        if ( ! get_option( 'hbc_rewrite_rules_added' ) ) {
            update_option( 'hbc_flush_rewrite_rules', true );
            update_option( 'hbc_rewrite_rules_added', true );
        }
    }

    /**
     * Add query vars for meeting page
     * 
     * @param array $vars
     * @return array
     */
    public function add_meeting_query_vars( $vars ) {
        $vars[] = 'hbc_meeting_token';
        return $vars;
    }

    /**
     * Handle meeting page request
     */
    public function handle_meeting_page_request() {
        $meeting_token = get_query_var( 'hbc_meeting_token' );
        
        if ( ! empty( $meeting_token ) ) {
            $this->display_meeting_page( $meeting_token );
            exit;
        }
    }

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
     * Validate meeting token
     * 
     * Validates a meeting token for authenticity, expiration, and user permissions.
     * 
     * @param string $token The token to validate
     * @return array|\WP_Error Token data on success, WP_Error on failure
     * @since 1.0.0
     */
    private function validate_meeting_token( $token ) {
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
                error_log( 'Jitsi Integration: User agent mismatch for token validation' );
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

    /**
     * Display secure meeting page
     * 
     * Handles the main meeting page display logic with proper error handling
     * and meeting status management.
     * 
     * @param string $token The meeting token
     * @since 1.0.0
     */
    private function display_meeting_page( $token ) {
        // Validate the meeting token
        $token_data = $this->validate_meeting_token( $token );
        
        if ( is_wp_error( $token_data ) ) {
            $this->display_error_page( 
                __( 'Invalid Meeting Link', 'hydra-booking-customization' ),
                $token_data->get_error_message(),
                $token_data->get_error_code()
            );
            return;
        }
        
        $booking_id = (int) $token_data['booking_id'];
        $token_user_id = (int) $token_data['user_id'];
        $role = sanitize_text_field( $token_data['role'] );
        
        // Check if user is logged in
        $current_user_id = get_current_user_id();
        if ( ! $current_user_id ) {
            $this->display_error_page(
                __( 'Authentication Required', 'hydra-booking-customization' ),
                __( 'You must be logged in to access this meeting. Please log in and try again.', 'hydra-booking-customization' ),
                'authentication_required'
            );
            return;
        }
        
        // Verify that the logged-in user matches the token user
        if ( $current_user_id !== $token_user_id ) {
            $this->display_error_page(
                __( 'Access Denied', 'hydra-booking-customization' ),
                __( 'This meeting link is not valid for your account. Please use the correct meeting link for your account.', 'hydra-booking-customization' ),
                'user_mismatch'
            );
            return;
        }
        
        // Get booking and meeting data
        $booking = $this->get_booking_data( $booking_id );
        $meeting_data = $this->get_meeting_data( $booking_id );
        
        if ( ! $booking || ! $meeting_data ) {
            $this->display_error_page(
                __( 'Meeting Not Found', 'hydra-booking-customization' ),
                __( 'The requested meeting could not be found or has been removed.', 'hydra-booking-customization' ),
                'meeting_not_found'
            );
            return;
        }
        
        // Determine meeting status and display appropriate interface
        $meeting_status = $this->get_meeting_status( $booking );
        
        switch ( $meeting_status ) {
            case 'waiting':
                $meeting_start = strtotime( $booking->meeting_dates . ' ' . $booking->start_time );
                $this->display_waiting_page( $booking, $meeting_start );
                break;
                
            case 'ended':
                $this->display_ended_page( $booking );
                break;
                
            case 'active':
            case 'joinable':
                $this->display_meeting_interface( $booking, $meeting_data, $current_user_id, $role );
                break;
                
            default:
                $this->display_error_page(
                    __( 'Meeting Status Error', 'hydra-booking-customization' ),
                    __( 'Unable to determine meeting status. Please try again later.', 'hydra-booking-customization' ),
                    'status_error'
                );
        }
    }

    /**
     * Get meeting status based on current time and booking schedule
     * 
     * @param object $booking The booking object
     * @return string Meeting status (waiting|joinable|active|ended)
     * @since 1.0.0
     */
    private function get_meeting_status( $booking ) {
        $meeting_start = strtotime( $booking->meeting_dates . ' ' . $booking->start_time );
        $meeting_end = strtotime( $booking->meeting_dates . ' ' . $booking->end_time );
        $current_time = current_time( 'timestamp' );
        
        // Check if meeting has been manually terminated
        $meeting_data = $this->get_meeting_data( $booking->booking_id );
        if ( $meeting_data && isset( $meeting_data['terminated'] ) && $meeting_data['terminated'] ) {
            return 'ended';
        }
        
        // Check if testing mode is enabled to bypass time restrictions
		$testing_mode = ( HBC_TEST_MODE_STATUS === 'active' );
        if ( $testing_mode ) {
            // In testing mode, always return 'active' to allow immediate access
            return 'active';
        }
        
        // Allow joining 15 minutes before start time
        $join_time = $meeting_start - self::MEETING_GRACE_PERIOD;
        
        if ( $current_time < $join_time ) {
            return 'waiting';
        } elseif ( $current_time >= $join_time && $current_time < $meeting_start ) {
            return 'joinable';
        } elseif ( $current_time >= $meeting_start && $current_time <= $meeting_end ) {
            return 'active';
        } else {
            return 'ended';
        }
    }

    /**
     * Display error page for meeting access issues
     * 
     * @param string $title Error title
     * @param string $message Error message
     * @param string $code Error code
     * @since 1.0.0
     */
    private function display_error_page( $title, $message, $code = '' ) {
        // Log the error for debugging
        error_log( sprintf( 'Jitsi Integration Error [%s]: %s', $code, $message ) );
        
        ?><!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html( $title ); ?> - <?php bloginfo( 'name' ); ?></title>
            <?php wp_head(); ?>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
                .error-container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .error-title { color: #d63638; margin-bottom: 20px; font-size: 24px; }
                .error-message { color: #666; font-size: 16px; line-height: 1.5; margin-bottom: 30px; }
                .error-actions { margin-top: 30px; }
                .button { display: inline-block; padding: 12px 24px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; margin: 0 10px; }
                .button:hover { background: #005a87; color: white; text-decoration: none; }
                .error-code { font-size: 12px; color: #999; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h1 class="error-title"><?php echo esc_html( $title ); ?></h1>
                <div class="error-message">
                    <p><?php echo esc_html( $message ); ?></p>
                </div>
                <div class="error-actions">
                    <a href="<?php echo esc_url( home_url() ); ?>" class="button">
                        <?php _e( 'Go to Homepage', 'hydra-booking-customization' ); ?>
                    </a>
                    <a href="javascript:history.back()" class="button">
                        <?php _e( 'Go Back', 'hydra-booking-customization' ); ?>
                    </a>
                </div>
                <?php if ( $code ): ?>
                    <div class="error-code">
                        <?php printf( __( 'Error Code: %s', 'hydra-booking-customization' ), esc_html( $code ) ); ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html><?php
        exit;
    }

    /**
     * Get booking data
     * 
     * @param int $booking_id
     * @return object|null
     */
    private function get_booking_data( $booking_id ) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'tfhb_bookings';
        $meetings_table = $wpdb->prefix . 'tfhb_meetings';
        
        $query = $wpdb->prepare(
            "SELECT b.*, m.title as meeting_title, m.duration 
             FROM {$bookings_table} b 
             LEFT JOIN {$meetings_table} m ON b.meeting_id = m.id 
             WHERE b.id = %d",
            $booking_id
        );
        
        return $wpdb->get_row( $query );
    }

    /**
     * Display waiting page when meeting hasn't started
     * 
     * @param object $booking
     * @param int $meeting_start
     */
    private function display_waiting_page( $booking, $meeting_start ) {
        $meeting_time = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $meeting_start );
        $testing_mode = ( HBC_TEST_MODE_STATUS === 'active' );
        
        ?><!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html( $booking->meeting_title ); ?> - <?php bloginfo( 'name' ); ?></title>
            <?php wp_head(); ?>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
                .waiting-container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .meeting-title { color: #333; margin-bottom: 20px; }
                .countdown { font-size: 24px; color: #0073aa; margin: 20px 0; }
                .meeting-info { background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .testing-mode { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
            </style>
        </head>
        <body>
            <div class="waiting-container">
                <h1 class="meeting-title"><?php echo esc_html( $booking->meeting_title ); ?></h1>
                <p><?php _e( 'Your meeting will start at:', 'hydra-booking-customization' ); ?></p>
                <div class="countdown"><?php echo esc_html( $meeting_time ); ?></div>
                <div class="meeting-info <?php echo $testing_mode ? 'testing-mode' : ''; ?>">
                    <?php if ( $testing_mode ) : ?>
                        <p><?php _e( 'TESTING MODE: Meeting access restrictions have been bypassed. You can join immediately.', 'hydra-booking-customization' ); ?></p>
                    <?php else : ?>
                        <p><?php _e( 'Please keep this page open and refresh it closer to the meeting time.', 'hydra-booking-customization' ); ?></p>
                    <?php endif; ?>
                </div>
                <button onclick="location.reload()" class="button"><?php _e( 'Refresh Page', 'hydra-booking-customization' ); ?></button>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html><?php
    }

    /**
     * Display ended page when meeting has finished
     * 
     * @param object $booking
     */
    private function display_ended_page( $booking ) {
        ?><!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html( $booking->meeting_title ); ?> - <?php bloginfo( 'name' ); ?></title>
            <?php wp_head(); ?>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
                .ended-container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .meeting-title { color: #333; margin-bottom: 20px; }
                .ended-message { color: #666; font-size: 18px; }
            </style>
        </head>
        <body>
            <div class="ended-container">
                <h1 class="meeting-title"><?php echo esc_html( $booking->meeting_title ); ?></h1>
                <div class="ended-message">
                    <p><?php _e( 'This meeting has ended.', 'hydra-booking-customization' ); ?></p>
                    <p><?php _e( 'Thank you for participating!', 'hydra-booking-customization' ); ?></p>
                </div>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html><?php
    }

    /**
     * Display meeting interface with embedded Jitsi
     * 
     * @param object $booking
     * @param array $meeting_data
     * @param int $user_id
     * @param string $role
     */
    private function display_meeting_interface( $booking, $meeting_data, $user_id, $role ) {
        $user = get_user_by( 'id', $user_id );
        $display_name = $user ? $user->display_name : 'Guest';
        
        ?><!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html( $booking->meeting_title ); ?> - <?php bloginfo( 'name' ); ?></title>
            <?php wp_head(); ?>
            <style>
                body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
                .meeting-header { background: #0073aa; color: white; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; }
                .meeting-title { margin: 0; font-size: 18px; }
                .meeting-timer { font-size: 16px; font-weight: bold; margin-right: 20px; padding: 5px 10px; background: rgba(255,255,255,0.2); border-radius: 5px; display: flex; flex-direction: column; align-items: center; gap: 2px; }
                .timer-elapsed { font-family: 'Courier New', monospace; }
                .timer-duration { font-family: 'Noto Sans Bengali', Arial, sans-serif; font-size: 12px; opacity: 0.9; }
                .meeting-timer.overtime { background: #ff4444; animation: pulse 1s infinite; }
                .user-info { font-size: 14px; }
                #jitsi-container { width: 100%; height: calc(100vh - 60px); }
                .loading { text-align: center; padding: 50px; }
                @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.7; } 100% { opacity: 1; } }
                
                /* Bengali Popup Notification Styles */
                .meeting-notification-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.7);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 10000;
                    font-family: 'Noto Sans Bengali', Arial, sans-serif;
                }
                .meeting-notification {
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    text-align: center;
                    max-width: 400px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                }
                .meeting-notification h2 {
                    color: #d32f2f;
                    margin-bottom: 15px;
                    font-size: 24px;
                }
                .meeting-notification p {
                    font-size: 18px;
                    margin-bottom: 20px;
                    color: #333;
                }
                .meeting-notification button {
                    background: #0073aa;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 16px;
                }
                .meeting-notification button:hover {
                    background: #005a87;
                }
            </style>
            <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;700&display=swap" rel="stylesheet">
        </head>
        <body>
            <div class="meeting-header">
                <h1 class="meeting-title"><?php echo esc_html( $booking->meeting_title ); ?></h1>
                <div class="timer-duration">সময়কাল <?php echo intval( $booking->duration ?? 30 ); ?> min</div>
                <div style="display: flex; align-items: center;">
                    <div class="meeting-timer" id="meeting-timer">
                    <div class="timer-elapsed">00:00</div>
                </div>
                    <div class="user-info">
                        <?php printf( __( 'Welcome, %s', 'hydra-booking-customization' ), esc_html( $display_name ) ); ?>
                        <?php if ( $role === 'host' ): ?>
                            <span class="host-badge"><?php _e( '(Host)', 'hydra-booking-customization' ); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div id="jitsi-container">
                <div class="loading"><?php _e( 'Loading meeting...', 'hydra-booking-customization' ); ?></div>
            </div>
            
            <?php
            // Load appropriate Jitsi External API first
            $jitsi_config = $this->get_jitsi_config();
            if ( $jitsi_config['api_select'] === 'jaas' ) {
                echo '<script src="https://8x8.vc/external_api.js"></script>';
            } else {
                echo '<script src="https://' . esc_attr( $meeting_data['domain'] ) . '/external_api.js"></script>';
            }
            
            // Load toast notification script for vanilla JS environment
            echo '<script src="' . HBC_PLUGIN_URL . 'src/utils/toast-notifications.js"></script>';
            ?>
            
            <script>
                // Meeting timer and notification system
                const bookingId = '<?php echo esc_js( $booking->id ?? 'default' ); ?>';
                const storageKey = 'meeting_start_time_' + bookingId;
                let meetingStartTime;
                let meetingDuration = <?php echo intval( $booking->duration ?? 30 ); ?> * 60 * 1000; // Convert minutes to milliseconds
                let timerInterval;
                let notificationShown = false;
                
                // Actual scheduled meeting times from booking data
                const scheduledMeetingDate = '<?php echo esc_js( $booking->meeting_dates ?? '' ); ?>';
                const scheduledStartTime = '<?php echo esc_js( $booking->start_time ?? '' ); ?>';
                const scheduledEndTime = '<?php echo esc_js( $booking->end_time ?? '' ); ?>';
                
                // Calculate actual meeting end time from scheduled data
                let actualMeetingEndTime = null;
                if (scheduledMeetingDate && scheduledEndTime) {
                    actualMeetingEndTime = new Date(scheduledMeetingDate + ' ' + scheduledEndTime);
                } else if (scheduledMeetingDate && scheduledStartTime) {
                    // Fallback: calculate end time from start time + duration
                    const startDateTime = new Date(scheduledMeetingDate + ' ' + scheduledStartTime);
                    actualMeetingEndTime = new Date(startDateTime.getTime() + meetingDuration);
                }
                
                // Initialize or retrieve meeting start time
                function initializeMeetingTime() {
                    const storedStartTime = localStorage.getItem(storageKey);
                    if (storedStartTime) {
                        meetingStartTime = new Date(parseInt(storedStartTime));
                    } else {
                        meetingStartTime = new Date();
                        localStorage.setItem(storageKey, meetingStartTime.getTime().toString());
                    }
                }
                
                function updateTimer() {
                    const now = new Date();
                    const elapsed = now - meetingStartTime;
                    const minutes = Math.floor(elapsed / 60000);
                    const seconds = Math.floor((elapsed % 60000) / 1000);
                    
                    const timerElement = document.getElementById('meeting-timer');
                     const elapsedElement = timerElement.querySelector('.timer-elapsed');
                     const formattedTime = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                     elapsedElement.textContent = formattedTime;
                    
                    // Check if actual meeting time has expired (current time > scheduled end time)
                    let shouldShowExpiredNotification = false;
                    
                    if (actualMeetingEndTime) {
                        // Use actual scheduled end time
                        shouldShowExpiredNotification = now > actualMeetingEndTime;
                    } else {
                        // Fallback to duration-based check if scheduled times are not available
                        shouldShowExpiredNotification = elapsed >= meetingDuration;
                    }
                    
                    if (shouldShowExpiredNotification && !notificationShown) {
                        timerElement.classList.add('overtime');
                        showMeetingEndNotification();
                        notificationShown = true;
                    }
                }
                
                function showMeetingEndNotification() {
                    const overlay = document.createElement('div');
                    overlay.className = 'meeting-notification-overlay';
                    overlay.innerHTML = `
                        <div class="meeting-notification">
                            <h2>মিটিংয়ের সময় শেষ</h2>
                            <p>মিটিংয়ের সময় শেষ হয়েছে</p>
                            <button onclick="closeMeetingNotification()">ঠিক আছে</button>
                        </div>
                    `;
                    document.body.appendChild(overlay);
                    
                    // Auto-close after 10 seconds if not manually closed
                    setTimeout(() => {
                        if (document.body.contains(overlay)) {
                            overlay.remove();
                        }
                    }, 10000);
                }
                
                function closeMeetingNotification() {
                     const overlay = document.querySelector('.meeting-notification-overlay');
                     if (overlay) {
                         overlay.remove();
                     }
                 }
                 
                 // Make function globally accessible
                 window.closeMeetingNotification = closeMeetingNotification;
                
                // Start timer when page loads
                function startMeetingTimer() {
                    initializeMeetingTime();
                    timerInterval = setInterval(updateTimer, 1000);
                    updateTimer(); // Initial call
                }
                
                // Reset timer for new meeting session
                function resetMeetingTimer() {
                    localStorage.removeItem(storageKey);
                    initializeMeetingTime();
                    notificationShown = false;
                    const timerElement = document.getElementById('meeting-timer');
                    if (timerElement) {
                        timerElement.classList.remove('overtime');
                    }
                }
                
                document.addEventListener('DOMContentLoaded', function() {
                    // Start the meeting timer
                    startMeetingTimer();
                    // Browser compatibility check
                    function checkBrowserCompatibility() {
                        const userAgent = navigator.userAgent;
                        const isChrome = /Chrome/.test(userAgent) && /Google Inc/.test(navigator.vendor);
                        const isFirefox = /Firefox/.test(userAgent);
                        const isSafari = /Safari/.test(userAgent) && /Apple Computer/.test(navigator.vendor);
                        const isEdge = /Edg/.test(userAgent);
                        
                        // Check for required features
                        const hasWebRTC = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
                        const hasWebSocket = !!window.WebSocket;
                        
                        return {
                            isSupported: (isChrome || isFirefox || isSafari || isEdge) && hasWebRTC && hasWebSocket,
                            browser: isChrome ? 'Chrome' : isFirefox ? 'Firefox' : isSafari ? 'Safari' : isEdge ? 'Edge' : 'Unknown',
                            hasWebRTC: hasWebRTC,
                            hasWebSocket: hasWebSocket
                        };
                    }
                    
                    const browserInfo = checkBrowserCompatibility();
                    // Browser compatibility checked
                    
                    // Wait for Jitsi External API to load with timeout
                    let apiCheckAttempts = 0;
                    const maxAttempts = 10;
                    
                    function waitForJitsiAPI() {
                        if (typeof JitsiMeetExternalAPI !== 'undefined') {
                            initializeJitsi();
                        } else if (apiCheckAttempts < maxAttempts) {
                            apiCheckAttempts++;
                            setTimeout(waitForJitsiAPI, 500);
                        } else {
                            console.error('Jitsi Meet External API failed to load after multiple attempts');
                            if (window.ToastNotifications) {
                                window.ToastNotifications.showError('Failed to load meeting interface. Please refresh the page.');
                            }
                            showFallbackMessage();
                        }
                    }
                    
                    function showFallbackMessage() {
                        const fallbackHTML = `
                            <div class="loading" style="padding: 40px; text-align: center;">
                                <h3>Unable to load meeting interface</h3>
                                <p>Please try one of the following:</p>
                                <ul style="text-align: left; display: inline-block;">
                                    <li>Refresh this page</li>
                                    <li>Check your internet connection</li>
                                    <li>Try using a different browser (Chrome, Firefox, Safari, or Edge)</li>
                                    <li>Disable browser extensions temporarily</li>
                                </ul>
                                <p><a href="<?php echo esc_js( $meeting_data['meeting_url'] ); ?>" target="_blank" class="button">Open in new tab</a></p>
                                <button onclick="location.reload()" class="button">Refresh Page</button>
                            </div>
                        `;
                        document.querySelector('#jitsi-container').innerHTML = fallbackHTML;
                    }
                    
                    function initializeJitsi() {
                         if (!browserInfo.isSupported) {
                             console.warn('Browser may not be fully supported, but attempting to load Jitsi anyway');
                         }
                     
                         const domain = '<?php echo esc_js( $meeting_data['domain'] ); ?>';
                         const roomName = '<?php echo esc_js( $meeting_data['room_name'] ); ?>';
                         const displayName = '<?php echo esc_js( $display_name ); ?>';
                         const userRole = '<?php echo esc_js( $role ); ?>';
                    
                    const options = {
                        roomName: roomName,
                        width: '100%',
                        height: '100%',
                        parentNode: document.querySelector('#jitsi-container'),
                        userInfo: {
                            displayName: displayName
                        },
                        configOverwrite: {
                            startWithAudioMuted: true,
                            startWithVideoMuted: false,
                            enableWelcomePage: false,
                            prejoinPageEnabled: false,
                            disableDeepLinking: true,
                            enableClosePage: false,
                            // Modern browser compatibility settings
                            constraints: {
                                video: {
                                    aspectRatio: 16 / 9,
                                    height: {
                                        ideal: 720,
                                        max: 720,
                                        min: 240
                                    }
                                }
                            },
                            // Disable features that might cause compatibility issues
                            disableH264: false,
                            enableLayerSuspension: true,
                            channelLastN: -1,
                            // Browser compatibility
                            enableInsecureRoomNameWarning: false,
                            enableLobbyChat: false
                        },
                        interfaceConfigOverwrite: {
                            TOOLBAR_BUTTONS: [
                                'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen',
                                'fodeviceselection', 'hangup', 'profile', 'chat', 'recording',
                                'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand',
                                'videoquality', 'filmstrip', 'invite', 'feedback', 'stats', 'shortcuts',
                                'tileview', 'videobackgroundblur', 'download', 'help', 'mute-everyone'
                            ],
                            SETTINGS_SECTIONS: ['devices', 'language', 'moderator', 'profile', 'calendar'],
                            SHOW_JITSI_WATERMARK: false,
                            SHOW_WATERMARK_FOR_GUESTS: false,
                            SHOW_BRAND_WATERMARK: false,
                            BRAND_WATERMARK_LINK: '',
                            SHOW_POWERED_BY: false,
                            SHOW_PROMOTIONAL_CLOSE_PAGE: false,
                            SHOW_CHROME_EXTENSION_BANNER: false
                        }
                    };
                    
                    // Set moderator rights for host
                    if (userRole === 'host') {
                        options.configOverwrite.startAudioMuted = 0;
                        options.configOverwrite.startVideoMuted = 0;
                    }
                    
                    try {
                        const api = new JitsiMeetExternalAPI(domain, options);
                        
                        // Handle meeting events
                        api.addEventListener('readyToClose', function() {
                            // Stop the timer when meeting ends
                            if (timerInterval) {
                                clearInterval(timerInterval);
                            }
                            if (window.opener) {
                                window.close();
                            } else {
                                window.location.href = '<?php echo esc_url( home_url() ); ?>';
                            }
                        });
                        
                        api.addEventListener('participantLeft', function(event) {
                            // Participant left event
                            if (window.ToastNotifications) {
                                window.ToastNotifications.showInfo('A participant left the meeting');
                            }
                        });
                        
                        api.addEventListener('participantJoined', function(event) {
                            // Participant joined event
                            if (window.ToastNotifications) {
                                window.ToastNotifications.showInfo('A participant joined the meeting');
                            }
                        });
                        
                        // Handle video conference errors
                        api.addEventListener('videoConferenceLeft', function(event) {
                            // Video conference left event
                        });
                        
                        api.addEventListener('videoConferenceJoined', function(event) {
                            // Video conference joined event - synchronize timer
                            // Check if this is a fresh meeting session
                            const storedStartTime = localStorage.getItem(storageKey);
                            const now = new Date();
                            
                            // If stored time is more than 5 minutes old, consider it a new session
                            if (storedStartTime) {
                                const timeDiff = now.getTime() - parseInt(storedStartTime);
                                if (timeDiff > 5 * 60 * 1000) { // 5 minutes
                                    resetMeetingTimer();
                                }
                            }
                        });
                        
                        // Remove loading message once API is ready
                        api.addEventListener('videoConferenceJoined', function() {
                            const loadingDiv = document.querySelector('#jitsi-container .loading');
                            if (loadingDiv) {
                                loadingDiv.remove();
                            }
                            if (window.ToastNotifications) {
                                window.ToastNotifications.showSuccess('Successfully joined the meeting!');
                            }
                        });
                        
                    } catch (error) {
                             console.error('Error initializing Jitsi Meet:', error);
                             if (window.ToastNotifications) {
                                 window.ToastNotifications.showError('Unable to initialize meeting. Please check your browser compatibility and try again.');
                             }
                             document.querySelector('#jitsi-container').innerHTML = '<div class="loading">Error: Unable to initialize meeting. Please check your browser compatibility and try again.</div>';
                         }
                     }
                     
                     // Start the API loading check
                     waitForJitsiAPI();
                 });
             </script>
            
            <?php wp_footer(); ?>
        </body>
        </html><?php
    }

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
            error_log( 'Failed to generate meeting token: ' . $token->get_error_message() );
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

    /**
     * Initialize cron jobs for meeting management
     * 
     * @since 1.0.0
     */
    private function init_cron_jobs() {
        // Schedule cron jobs on plugin activation if not already scheduled
        if ( ! wp_next_scheduled( 'hbc_check_meeting_reminders' ) ) {
            wp_schedule_event( time(), 'every_minute', 'hbc_check_meeting_reminders' );
        }
        
        if ( ! wp_next_scheduled( 'hbc_cleanup_expired_meetings' ) ) {
            wp_schedule_event( time(), 'hourly', 'hbc_cleanup_expired_meetings' );
        }
        
        // Hook the cron actions
        add_action( 'hbc_check_meeting_reminders', array( $this, 'check_meeting_reminders' ) );
        add_action( 'hbc_cleanup_expired_meetings', array( $this, 'cleanup_all_expired_meetings' ) );
        
        // Add custom cron schedule for every minute
        add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) );
    }

    /**
     * Add custom cron schedules
     * 
     * @param array $schedules
     * @return array
     */
    public function add_cron_schedules( $schedules ) {
        $schedules['every_minute'] = array(
            'interval' => 60,
            'display'  => __( 'Every Minute', 'hydra-booking-customization' )
        );
        return $schedules;
    }

    /**
     * Check for meetings that need reminders
     * 
     * @since 1.0.0
     */
    public function check_meeting_reminders() {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'tfhb_bookings';
        $meta_table = $wpdb->prefix . 'tfhb_booking_meta';
        
        // Get meetings that end in 5 minutes and haven't been reminded yet
        $current_time = current_time( 'mysql' );
        $reminder_time = gmdate( 'Y-m-d H:i:s', strtotime( $current_time ) + self::REMINDER_TIME_BEFORE_END );
        
        $query = $wpdb->prepare(
            "SELECT b.id as booking_id, b.meeting_dates, b.start_time, b.end_time, b.attendee_email, b.attendee_name
             FROM {$bookings_table} b
             LEFT JOIN {$meta_table} m ON b.id = m.booking_id AND m.meta_key = 'reminder_sent'
             WHERE b.status = 'confirmed'
             AND CONCAT(b.meeting_dates, ' ', b.end_time) BETWEEN %s AND %s
             AND m.booking_id IS NULL",
            $current_time,
            $reminder_time
        );
        
        $meetings = $wpdb->get_results( $query );
        
        foreach ( $meetings as $meeting ) {
            $this->send_meeting_reminder( $meeting->booking_id, $meeting );
        }
    }

    /**
     * Send meeting reminder to participants
     * 
     * @param int $booking_id
     * @param object $booking
     * @since 1.0.0
     */
    public function send_meeting_reminder( $booking_id, $booking ) {
        global $wpdb;
        
        // Mark reminder as sent
        $meta_table = $wpdb->prefix . 'tfhb_booking_meta';
        $wpdb->replace(
            $meta_table,
            array(
                'booking_id' => $booking_id,
                'meta_key' => 'reminder_sent',
                'value' => current_time( 'mysql' ),
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' )
            ),
            array( '%d', '%s', '%s', '%s', '%s' )
        );
        
        // Send email reminder
        $subject = __( 'Meeting Ending Soon - 5 Minutes Remaining', 'hydra-booking-customization' );
        $message = sprintf(
            __( 'Hello %s,\n\nYour meeting is scheduled to end in 5 minutes.\n\nMeeting Details:\nDate: %s\nTime: %s - %s\n\nPlease wrap up your discussion.\n\nThank you!', 'hydra-booking-customization' ),
            $booking->attendee_name,
            $booking->meeting_dates,
            $booking->start_time,
            $booking->end_time
        );
        
        wp_mail( $booking->attendee_email, $subject, $message );
        
        // Log the reminder
        error_log( "HBC Jitsi Integration: Sent 5-minute reminder for booking {$booking_id}" );
    }

    /**
     * Cleanup all expired meetings
     * 
     * @since 1.0.0
     */
    public function cleanup_all_expired_meetings() {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'tfhb_bookings';
        $current_time = current_time( 'mysql' );
        
        // Get meetings that have ended
        $query = $wpdb->prepare(
            "SELECT id as booking_id FROM {$bookings_table}
             WHERE status = 'confirmed'
             AND CONCAT(meeting_dates, ' ', end_time) < %s",
            $current_time
        );
        
        $expired_meetings = $wpdb->get_results( $query );
        
        foreach ( $expired_meetings as $meeting ) {
            $this->cleanup_expired_meeting( $meeting->booking_id );
        }
    }

    /**
     * Cleanup expired meeting data
     * 
     * @param int $booking_id
     * @since 1.0.0
     */
    public function cleanup_expired_meeting( $booking_id ) {
        global $wpdb;
        
        $meta_table = $wpdb->prefix . 'tfhb_booking_meta';
        
        // Mark meeting as terminated in meeting data
        $meeting_data = $this->get_meeting_data( $booking_id );
        if ( $meeting_data ) {
            $meeting_data['terminated'] = true;
            $meeting_data['terminated_at'] = current_time( 'mysql' );
            
            $wpdb->replace(
                $meta_table,
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
        
        // Mark meeting as terminated
        $wpdb->replace(
            $meta_table,
            array(
                'booking_id' => $booking_id,
                'meta_key' => 'meeting_terminated',
                'value' => current_time( 'mysql' ),
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' )
            ),
            array( '%d', '%s', '%s', '%s', '%s' )
        );
        
        // Clean up expired tokens
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$meta_table} WHERE booking_id = %d AND meta_key LIKE 'meeting_token_%'",
            $booking_id
        ) );
        
        error_log( "HBC Jitsi Integration: Cleaned up expired meeting {$booking_id}" );
    }

    /**
     * Terminate meeting room (placeholder for future Jitsi API integration)
     * 
     * @param int $booking_id
     * @since 1.0.0
     */
    public function terminate_meeting_room( $booking_id ) {
        // This would integrate with Jitsi API to actually terminate the room
        // For now, we just log the termination
        error_log( "HBC Jitsi Integration: Meeting room terminated for booking {$booking_id}" );
        
        // Mark room as terminated
        $this->cleanup_expired_meeting( $booking_id );
    }

    /**
     * Schedule meeting events (reminders and cleanup) for a specific booking
     * 
     * @param object $booking
     * @since 1.0.0
     */
    private function schedule_meeting_events( $booking ) {
        $meeting_start = strtotime( $booking->meeting_dates . ' ' . $booking->start_time );
        $meeting_end = strtotime( $booking->meeting_dates . ' ' . $booking->end_time );
        
        // Schedule reminder 5 minutes before meeting ends
        $reminder_time = $meeting_end - self::REMINDER_TIME_BEFORE_END;
        if ( $reminder_time > time() ) {
            wp_schedule_single_event( $reminder_time, 'hbc_meeting_reminder', array( $booking->booking_id, $booking ) );
        }
        
        // Schedule cleanup right after meeting ends
        if ( $meeting_end > time() ) {
            wp_schedule_single_event( $meeting_end + 60, 'hbc_meeting_cleanup', array( $booking->booking_id ) );
        }
        
        // Schedule room termination at meeting end time
        if ( $meeting_end > time() ) {
            wp_schedule_single_event( $meeting_end, 'hbc_meeting_terminate', array( $booking->booking_id ) );
        }
        
        error_log( "HBC Jitsi Integration: Scheduled events for booking {$booking->booking_id} - reminder at " . date( 'Y-m-d H:i:s', $reminder_time ) . ", cleanup at " . date( 'Y-m-d H:i:s', $meeting_end + 60 ) );
    }
}