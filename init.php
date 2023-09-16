<?php
/**
 * Plugin Name: MobApp Correo Urbano
 * Plugin URI: https://cuatroideasgroup.com/
 * Description: Obtener precios de envio con MobApp
 * Version: 3.0.0
 * Author: Cuatro Ideas Group
 * Author URI: https://cuatroideasgroup.com/
 * Text Domain: correo-mobapp-shipping-arg
 * Requires at least: 6.1
 * Requires PHP: 7.3
 *
 */

defined( 'ABSPATH' ) or die( 'Prohibido acceso directo.' );

define('WC_MOBAPP_SHIPPING_BASE_PATH', dirname(__FILE__));
define('WC_MOBAPP_SHIPPING_ID', 'mobapp_rate_state');
define('WC_MOBAPP_SHIPPING_SECTION', 'config-mobapp-shipping');

class mobapp_rate_state{
	private static $instance = null;
    public $id = null;

	public static function get_instance(){
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    function __construct() {
		
		$this->id = WC_MOBAPP_SHIPPING_ID;

		add_action( 'admin_init', [$this,'if_check_plugin_dependency_func']);

		if (!class_exists('Config_MobApp_Shipping')) {
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), [$this,'add_setting_link_plugin_func']);
		require_once WC_MOBAPP_SHIPPING_BASE_PATH . '/inc/config-mobapp-shipping.php';
		add_action( 'plugins_loaded', [ 'Config_MobApp_Shipping', 'init' ]);
		}

	}

    public function if_check_plugin_dependency_func(){
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            deactivate_plugins( plugin_basename(__FILE__) );
            add_action( 'admin_notices', function(){
                $class = 'notice notice-error';
                $message = __( 'Debe estar activado woocommerce.', 'default' );
                printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
            });
        }
    }

	public function add_setting_link_plugin_func(array $links){
		$url_config = add_query_arg( array(
			'page' => 'wc-settings',
			'tab' => 'shipping',
			'section' => WC_MOBAPP_SHIPPING_SECTION
		), admin_url( 'admin.php' ) );
		$settings_link = sprintf('<a href="%s">%s</a>', esc_url($url_config) , __('Settings', 'default'));
		
		array_unshift(
			$links,
			$settings_link
	   	);
		
		return $links;
	}

}

$GLOBALS[WC_MOBAPP_SHIPPING_ID] = mobapp_rate_state::get_instance();