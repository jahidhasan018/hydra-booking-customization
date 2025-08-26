<?php
/**
 * Admin Settings
 *
 * @package HydraBookingCustomization\Admin
 */

namespace HydraBookingCustomization\Admin;

/**
 * Admin Settings Class
 */
class Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'init_settings' ) );
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Hydra Booking Customization', 'hydra-booking-customization' ),
			__( 'HB Customization', 'hydra-booking-customization' ),
			'manage_options',
			'hbc-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Initialize settings.
	 */
	public function init_settings() {
		// Register settings.
		register_setting( 'hbc_settings', 'hbc_enable_auto_registration' );
		register_setting( 'hbc_settings', 'hbc_auto_registration_role' );
		register_setting( 'hbc_settings', 'hbc_send_welcome_email' );
		register_setting( 'hbc_settings', 'hbc_attendee_dashboard_page_id' );
		register_setting( 'hbc_settings', 'hbc_allow_booking_cancellation' );
		register_setting( 'hbc_settings', 'hbc_cancellation_hours_limit' );
		register_setting( 'hbc_settings', 'hbc_allow_booking_rescheduling' );
		register_setting( 'hbc_settings', 'hbc_rescheduling_hours_limit' );
		register_setting( 'hbc_settings', 'hbc_jitsi_testing_mode' );

		// Add settings sections.
		add_settings_section(
			'hbc_auto_registration_section',
			__( 'Auto Registration Settings', 'hydra-booking-customization' ),
			array( $this, 'render_auto_registration_section' ),
			'hbc_settings'
		);

		add_settings_section(
			'hbc_dashboard_section',
			__( 'Attendee Dashboard Settings', 'hydra-booking-customization' ),
			array( $this, 'render_dashboard_section' ),
			'hbc_settings'
		);

		add_settings_section(
			'hbc_booking_management_section',
			__( 'Booking Management Settings', 'hydra-booking-customization' ),
			array( $this, 'render_booking_management_section' ),
			'hbc_settings'
		);

		add_settings_section(
			'hbc_jitsi_section',
			__( 'Jitsi Meeting Settings', 'hydra-booking-customization' ),
			array( $this, 'render_jitsi_section' ),
			'hbc_settings'
		);

		// Add settings fields.
		$this->add_auto_registration_fields();
		$this->add_dashboard_fields();
		$this->add_booking_management_fields();
		$this->add_jitsi_fields();
	}

	/**
	 * Add auto registration fields.
	 */
	private function add_auto_registration_fields() {
		add_settings_field(
			'hbc_enable_auto_registration',
			__( 'Enable Auto Registration', 'hydra-booking-customization' ),
			array( $this, 'render_checkbox_field' ),
			'hbc_settings',
			'hbc_auto_registration_section',
			array(
				'option_name'   => 'hbc_enable_auto_registration',
				'description'   => __( 'Automatically create user accounts for attendees when they book a meeting.', 'hydra-booking-customization' ),
				'default_value' => true,
			)
		);

		add_settings_field(
			'hbc_send_welcome_email',
			__( 'Send Welcome Email', 'hydra-booking-customization' ),
			array( $this, 'render_checkbox_field' ),
			'hbc_settings',
			'hbc_auto_registration_section',
			array(
				'option_name'   => 'hbc_send_welcome_email',
				'description'   => __( 'Send welcome email with login credentials to auto-registered attendees.', 'hydra-booking-customization' ),
				'default_value' => true,
			)
		);
	}

	/**
	 * Add dashboard fields.
	 */
	private function add_dashboard_fields() {
		add_settings_field(
			'hbc_attendee_dashboard_page_id',
			__( 'Attendee Dashboard Page', 'hydra-booking-customization' ),
			array( $this, 'render_page_select_field' ),
			'hbc_settings',
			'hbc_dashboard_section',
			array(
				'option_name' => 'hbc_attendee_dashboard_page_id',
				'description' => __( 'Select the page that will serve as the attendee dashboard.', 'hydra-booking-customization' ),
			)
		);
	}

	/**
	 * Add booking management fields.
	 */
	private function add_booking_management_fields() {
		add_settings_field(
			'hbc_allow_booking_cancellation',
			__( 'Allow Booking Cancellation', 'hydra-booking-customization' ),
			array( $this, 'render_checkbox_field' ),
			'hbc_settings',
			'hbc_booking_management_section',
			array(
				'option_name'   => 'hbc_allow_booking_cancellation',
				'description'   => __( 'Allow attendees to cancel their bookings from the dashboard.', 'hydra-booking-customization' ),
				'default_value' => true,
			)
		);

		add_settings_field(
			'hbc_cancellation_hours_limit',
			__( 'Cancellation Hours Limit', 'hydra-booking-customization' ),
			array( $this, 'render_number_field' ),
			'hbc_settings',
			'hbc_booking_management_section',
			array(
				'option_name'   => 'hbc_cancellation_hours_limit',
				'description'   => __( 'Minimum hours before the meeting that cancellation is allowed.', 'hydra-booking-customization' ),
				'default_value' => 24,
				'min'           => 1,
				'max'           => 168,
			)
		);

		add_settings_field(
			'hbc_allow_booking_rescheduling',
			__( 'Allow Booking Rescheduling', 'hydra-booking-customization' ),
			array( $this, 'render_checkbox_field' ),
			'hbc_settings',
			'hbc_booking_management_section',
			array(
				'option_name'   => 'hbc_allow_booking_rescheduling',
				'description'   => __( 'Allow attendees to reschedule their bookings from the dashboard.', 'hydra-booking-customization' ),
				'default_value' => true,
			)
		);

		add_settings_field(
			'hbc_rescheduling_hours_limit',
			__( 'Rescheduling Hours Limit', 'hydra-booking-customization' ),
			array( $this, 'render_number_field' ),
			'hbc_settings',
			'hbc_booking_management_section',
			array(
				'option_name'   => 'hbc_rescheduling_hours_limit',
				'description'   => __( 'Minimum hours before the meeting that rescheduling is allowed.', 'hydra-booking-customization' ),
				'default_value' => 48,
				'min'           => 1,
				'max'           => 168,
			)
		);
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<div class="hbc-admin-header">
				<p><?php _e( 'Configure the Hydra Booking Customization plugin settings below.', 'hydra-booking-customization' ); ?></p>
			</div>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'hbc_settings' );
				do_settings_sections( 'hbc_settings' );
				submit_button();
				?>
			</form>

			<div class="hbc-admin-info">
				<h3><?php _e( 'Plugin Information', 'hydra-booking-customization' ); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row"><?php _e( 'Plugin Version', 'hydra-booking-customization' ); ?></th>
						<td><?php echo esc_html( HBC_VERSION ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Attendee Role', 'hydra-booking-customization' ); ?></th>
						<td><code>hbc_attendee</code></td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Dashboard Shortcode', 'hydra-booking-customization' ); ?></th>
						<td><code>[hbc_attendee_dashboard]</code></td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Total Attendees', 'hydra-booking-customization' ); ?></th>
						<td><?php echo esc_html( $this->get_attendee_count() ); ?></td>
					</tr>
				</table>
			</div>

			<div class="hbc-admin-tools">
				<h3><?php _e( 'Jitsi Integration Tools', 'hydra-booking-customization' ); ?></h3>
				<p><?php _e( 'Use these tools to manage Jitsi meeting links for existing bookings.', 'hydra-booking-customization' ); ?></p>
				<p>
					<a href="<?php echo esc_url( admin_url( '?hbc_create_jitsi_links=1' ) ); ?>" class="button button-secondary">
						<?php _e( 'Create Missing Jitsi Meeting Links', 'hydra-booking-customization' ); ?>
					</a>
				</p>
				<p class="description">
					<?php _e( 'This will create Jitsi meeting links for all confirmed upcoming bookings that don\'t already have them.', 'hydra-booking-customization' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render auto registration section.
	 */
	public function render_auto_registration_section() {
		echo '<p>' . __( 'Configure automatic user registration for attendees who book meetings.', 'hydra-booking-customization' ) . '</p>';
	}

	/**
	 * Render dashboard section.
	 */
	public function render_dashboard_section() {
		echo '<p>' . __( 'Configure the attendee dashboard settings.', 'hydra-booking-customization' ) . '</p>';
	}

	/**
	 * Render booking management section.
	 */
	public function render_booking_management_section() {
		echo '<p>' . __( 'Configure booking management options for attendees.', 'hydra-booking-customization' ) . '</p>';
	}

	/**
	 * Render checkbox field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_checkbox_field( $args ) {
		$option_name = $args['option_name'];
		$description = $args['description'] ?? '';
		$default_value = $args['default_value'] ?? false;
		$value = get_option( $option_name, $default_value );
		?>
		<label for="<?php echo esc_attr( $option_name ); ?>">
			<input type="checkbox" id="<?php echo esc_attr( $option_name ); ?>" name="<?php echo esc_attr( $option_name ); ?>" value="1" <?php checked( $value, true ); ?>>
			<?php echo esc_html( $description ); ?>
		</label>
		<?php
	}

	/**
	 * Render number field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_number_field( $args ) {
		$option_name = $args['option_name'];
		$description = $args['description'] ?? '';
		$default_value = $args['default_value'] ?? 0;
		$min = $args['min'] ?? 0;
		$max = $args['max'] ?? 999;
		$value = get_option( $option_name, $default_value );
		?>
		<input type="number" id="<?php echo esc_attr( $option_name ); ?>" name="<?php echo esc_attr( $option_name ); ?>" value="<?php echo esc_attr( $value ); ?>" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" class="small-text">
		<?php if ( $description ) : ?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render page select field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_page_select_field( $args ) {
		$option_name = $args['option_name'];
		$description = $args['description'] ?? '';
		$value = get_option( $option_name );
		
		$pages = get_pages();
		?>
		<select id="<?php echo esc_attr( $option_name ); ?>" name="<?php echo esc_attr( $option_name ); ?>">
			<option value=""><?php _e( 'Select a page...', 'hydra-booking-customization' ); ?></option>
			<?php foreach ( $pages as $page ) : ?>
				<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $value, $page->ID ); ?>>
					<?php echo esc_html( $page->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php if ( $description ) : ?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render Jitsi section.
	 */
	public function render_jitsi_section() {
		?>
		<p><?php _e( 'Configure Jitsi meeting integration settings.', 'hydra-booking-customization' ); ?></p>
		<?php
	}

	/**
	 * Add Jitsi fields.
	 */
	private function add_jitsi_fields() {
		add_settings_field(
			'hbc_jitsi_testing_mode',
			__( 'Enable Testing Mode', 'hydra-booking-customization' ),
			array( $this, 'render_checkbox_field' ),
			'hbc_settings',
			'hbc_jitsi_section',
			array(
				'option_name'   => 'hbc_jitsi_testing_mode',
				'description'   => __( 'Bypass time restrictions for meeting access. Allows immediate access to Start/Join Meeting buttons regardless of scheduled time. <strong>For testing purposes only.</strong>', 'hydra-booking-customization' ),
				'default_value' => false,
			)
		);
	}

	/**
	 * Get attendee count.
	 *
	 * @return int
	 */
	private function get_attendee_count() {
		$users = get_users(
			array(
				'role'   => 'hbc_attendee',
				'fields' => 'ID',
			)
		);

		return count( $users );
	}
}