<?php
/**
 * Calendar Plus: includes/class-calendar-plus-i18n.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 */
class Calendar_Plus_i18n {


	/**
	 * The domain specified for this plugin.
	 *
	 * @var string
	 */
	private $domain;

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function load_plugin_textdomain() {

		// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- CampusPress also distributes this plugin privately, so its bundled MO files still need native text-domain path registration for just-in-time loading.
		load_plugin_textdomain(
			$this->domain,
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}

	/**
	 * Set the domain equal to that of the specified domain.
	 *
	 * @param string $domain The domain that represents the locale of this plugin.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function set_domain( $domain ) {
		$this->domain = $domain;
	}
}
