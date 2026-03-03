<?php
/**
 * Cache Manager
 *
 * Centralized caching utility using WordPress transients with per-entity keys
 * and event-driven invalidation.
 *
 * @package HydraBookingCustomization\Core
 * @since   1.1.0
 */

namespace HydraBookingCustomization\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache Manager Class
 *
 * Provides a simple API for caching expensive database queries with automatic
 * TTL expiration and manual invalidation support.
 *
 * @since 1.1.0
 */
class CacheManager {

	/**
	 * Cache key prefix for all plugin transients.
	 *
	 * @var string
	 */
	const PREFIX = 'hbc_cache_';

	/**
	 * Default TTL values in seconds.
	 *
	 * @var array
	 */
	const TTLS = array(
		'stats'    => 300,  // 5 minutes.
		'bookings' => 120,  // 2 minutes.
		'meeting'  => 600,  // 10 minutes.
		'links'    => 300,  // 5 minutes.
		'profile'  => 600,  // 10 minutes.
		'config'   => 3600, // 1 hour.
	);

	/**
	 * Get a cached value.
	 *
	 * @since 1.1.0
	 * @param string $key Cache key (without prefix).
	 * @return mixed|false Cached data or false if not found.
	 */
	public static function get( $key ) {
		return get_transient( self::PREFIX . $key );
	}

	/**
	 * Set a cached value.
	 *
	 * @since 1.1.0
	 * @param string $key        Cache key (without prefix).
	 * @param mixed  $data       Data to cache.
	 * @param int    $ttl        Time-to-live in seconds. Use CacheManager::TTLS constants.
	 * @return bool True if set successfully.
	 */
	public static function set( $key, $data, $ttl = 300 ) {
		return set_transient( self::PREFIX . $key, $data, $ttl );
	}

	/**
	 * Delete a cached value.
	 *
	 * @since 1.1.0
	 * @param string $key Cache key (without prefix).
	 * @return bool True if deleted successfully.
	 */
	public static function delete( $key ) {
		return delete_transient( self::PREFIX . $key );
	}

	/**
	 * Get cached data or compute and cache it.
	 *
	 * This is the primary method for using the cache. If the cache key exists,
	 * the cached data is returned. Otherwise, the callback is executed, the result
	 * is cached, and then returned.
	 *
	 * Usage:
	 *   $stats = CacheManager::remember( "host_stats_{$host_id}", CacheManager::TTLS['stats'], function() use ( $host_id ) {
	 *       return $this->compute_host_stats( $host_id );
	 *   });
	 *
	 * @since 1.1.0
	 * @param string   $key      Cache key (without prefix).
	 * @param int      $ttl      Time-to-live in seconds.
	 * @param callable $callback Function that computes the data on cache miss.
	 * @return mixed The cached or freshly computed data.
	 */
	public static function remember( $key, $ttl, $callback ) {
		$data = self::get( $key );

		if ( false !== $data ) {
			return $data;
		}

		$data = call_user_func( $callback );

		if ( null !== $data && false !== $data ) {
			self::set( $key, $data, $ttl );
		}

		return $data;
	}

	/**
	 * Invalidate all caches for a specific host.
	 *
	 * Call this when a host's bookings, stats, or profile change.
	 *
	 * @since 1.1.0
	 * @param int $host_id Host ID.
	 */
	public static function invalidate_host( $host_id ) {
		$host_id = absint( $host_id );

		self::delete( 'host_stats_' . $host_id );
		self::delete( 'host_data_' . $host_id );
		self::delete( 'join_links_' . $host_id );

		// Clear all booking type variants.
		foreach ( array( 'all', 'today', 'upcoming', 'past' ) as $type ) {
			self::delete( 'host_bookings_' . $host_id . '_' . $type );
		}
	}

	/**
	 * Invalidate all caches for a specific attendee/user.
	 *
	 * Call this when an attendee's bookings, stats, or profile change.
	 *
	 * @since 1.1.0
	 * @param int $user_id User ID.
	 */
	public static function invalidate_attendee( $user_id ) {
		$user_id = absint( $user_id );

		self::delete( 'attendee_stats_' . $user_id );

		// Clear all booking type variants.
		foreach ( array( 'all', 'upcoming', 'past' ) as $type ) {
			self::delete( 'attendee_bookings_' . $user_id . '_' . $type );
		}
	}

	/**
	 * Invalidate meeting-related caches for a specific booking.
	 *
	 * Call this when a meeting link is created or meeting data changes.
	 *
	 * @since 1.1.0
	 * @param int $booking_id Booking ID.
	 */
	public static function invalidate_meeting( $booking_id ) {
		self::delete( 'meeting_' . absint( $booking_id ) );
	}

	/**
	 * Invalidate all caches related to a booking event.
	 *
	 * Convenience method for booking create/cancel/reschedule events that
	 * affect both host and attendee caches.
	 *
	 * @since 1.1.0
	 * @param int $host_id    Host ID.
	 * @param int $user_id    Attendee user ID.
	 * @param int $booking_id Booking ID.
	 */
	public static function invalidate_booking_event( $host_id, $user_id, $booking_id ) {
		self::invalidate_host( $host_id );
		self::invalidate_attendee( $user_id );
		self::invalidate_meeting( $booking_id );
	}

	/**
	 * Flush all plugin caches.
	 *
	 * Used during plugin activation/deactivation to ensure a clean state.
	 *
	 * @since 1.1.0
	 */
	public static function flush_all() {
		global $wpdb;

		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			 WHERE option_name LIKE '_transient_" . self::PREFIX . "%'
			    OR option_name LIKE '_transient_timeout_" . self::PREFIX . "%'"
		);
	}
}
