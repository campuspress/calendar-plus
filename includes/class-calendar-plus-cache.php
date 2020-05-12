<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Calendar_Plus_Cache
 *
 * Cache helpers
 */
class Calendar_Plus_Cache {

	/**
	 * Get prefix for use with wp_cache_set. Allows all cache in a group to be invalidated at once.
	 *
	 * @since 2.0
	 *
	 * @param  string $group
	 *
	 * @return string
	 */
	public static function get_cache_group_prefix( $group ) {
		// Get cache key - uses cache key wc_orders_cache_prefix to invalidate when needed
		$prefix = wp_cache_get( 'calendarp_' . $group . '_cache_prefix', $group );

		if ( false === $prefix ) {
			$prefix = 1;
			wp_cache_set( 'calendarp_' . $group . '_cache_prefix', $prefix, $group );
		}

		return 'calendarp_cache_' . $prefix . '_';
	}

	/**
	 * Saves the data to the cache.
	 *
	 * Differs from wp_cache_add() and wp_cache_replace() in that it will always write data.
	 *
	 * @since 2.0
	 *
	 * @param int|string $key    The cache key to use for retrieval later.
	 * @param mixed      $data   The contents to store in the cache.
	 * @param string     $group  Optional. Where to group the cache contents. Enables the same key
	 *                           to be used across groups. Default empty.
	 * @param int        $expire Optional. When to expire the cache contents, in seconds.
	 *                           Default 0 (no expiration).
	 *
	 * @return bool False on failure, true on success
	 */
	public static function set_cache( $key, $data, $group = '', $expire = 0 ) {
		if ( $group ) {
			$key = self::get_cache_group_prefix( $group ) . $key;
		} else {
			$key = 'calendarp_cache_' . $key;
		}

		return wp_cache_set( $key, $data, $group, $expire );
	}

	/**
	 * Retrieves the cache contents from the cache by key and group.
	 *
	 * @since 2.0
	 *
	 * @param int|string  $key    The key under which the cache contents are stored.
	 * @param string      $group  Optional. Where the cache contents are grouped. Default empty.
	 * @param bool        $force  Optional. Whether to force an update of the local cache from the persistent
	 *                            cache. Default false.
	 * @param bool        $found  Optional. Whether the key was found in the cache. Disambiguates a return of false,
	 *                            a storable value. Passed by reference. Default null.
	 * @return bool|mixed False on failure to retrieve contents or the cache
	 *                    contents on success
	 */
	public static function get_cache( $key, $group = '', $force = false, &$found = null ) {
		if ( $group ) {
			$key = self::get_cache_group_prefix( $group ) . $key;
		} else {
			$key = 'calendarp_cache_' . $key;
		}

		return wp_cache_get( $key, $group, $force, $found );
	}


	/**
	 * Removes the cache contents matching key and group.
	 *
	 * @since 2.0
	 *
	 * @param int|string $key   What the contents in the cache are called.
	 * @param string     $group Optional. Where the cache contents are grouped. Default empty.
	 *
	 * @return bool True on successful removal, false on failure.
	 */
	public static function delete_cache( $key, $group = '' ) {
		if ( $group ) {
			$key = self::get_cache_group_prefix( $group ) . $key;
		} else {
			$key = 'calendarp_cache_' . $key;
		}

		return wp_cache_delete( $key, $group );
	}

	/**
	 * Increment group cache prefix (invalidates cache).
	 *
	 * @since 2.0
	 *
	 * @param  string $group
	 */
	public static function delete_cache_group( $group ) {
		wp_cache_incr( 'calendarp_' . $group . '_cache_prefix', 1, $group );
	}

}
