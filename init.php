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

if (!class_exists('mobapp_rate_state')) {

define('WC_MOBAPP_SHIPPING_BASE_PATH', dirname(__FILE__));
define('WC_MOBAPP_SHIPPING_ID', 'mobapp_rate_state');
define('WC_MOBAPP_SHIPPING_SECTION', 'config-mobapp-shipping');
define('WC_MOBAPP_SHIPPING_TITLE',  __( 'MobApp Urbano'));

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
		register_deactivation_hook( __FILE__, [$this,'mobapp_rate_state_deactivate']);

		/* Configuraciones Mobapp Urbano */
		if (!class_exists('Config_MobApp_Shipping')) {
			add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), [$this,'add_setting_link_plugin_func']);
			require_once WC_MOBAPP_SHIPPING_BASE_PATH . '/inc/config-mobapp-shipping.php';
			add_action( 'plugins_loaded', [ 'Config_MobApp_Shipping', 'init' ]);
		}
		
		/* Metodo Mobapp Urbano */
		require_once WC_MOBAPP_SHIPPING_BASE_PATH . '/inc/metodo-mobapp-shipping.php';

		add_action( 'admin_init', [$this,'init_mobapp_shipping_func']);

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

	public function mobapp_rate_state_deactivate(){
		if (class_exists('Config_MobApp_Shipping')) {
			$config_mobapp = new Config_MobApp_Shipping();

			$settingOptions = $config_mobapp->get_ids_config_mobapp_shipping_settings();
			if(is_array($settingOptions) && count($settingOptions) > 0){
				foreach ($settingOptions as $settingName ) {
					delete_option( $settingName );
				}
			}

			flush_rewrite_rules();
		}
	}

	public function init_mobapp_shipping_func(){
		if ( class_exists( 'Config_MobApp_Shipping' ) ) {
			function get_ids_config_mobapp_shipping(){
				$config_mobapp = new Config_MobApp_Shipping();
				$fields = $config_mobapp->config_mobapp_shipping_settings_func([],WC_MOBAPP_SHIPPING_SECTION);
				$ids_fields = array_column($fields,'id');
				return $ids_fields;
			}
			function get_config_mobapp_shipping(){
				$output = [];
				$config_mobapp = get_ids_config_mobapp_shipping();
				if(is_array($config_mobapp) && count($config_mobapp) > 0){
					foreach($config_mobapp as $name){
						$value = WC_Admin_Settings::get_option($name);
						if($value !== ''){
						$output[$name] = WC_Admin_Settings::get_option($name);
						}
					}
				}
				return $output;
			}
			function activated_config_mobapp_shipping(){
				$enabled = isset(get_config_mobapp_shipping()['mobapp_shipping_enable']) ? esc_attr(get_config_mobapp_shipping()['mobapp_shipping_enable']) : 'no';
				return $enabled !== 'yes' ? false : true;
			}
		}		
	}

}

$GLOBALS[WC_MOBAPP_SHIPPING_ID] = mobapp_rate_state::get_instance();

}