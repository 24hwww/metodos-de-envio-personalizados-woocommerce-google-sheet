<?php
class WC_Request_MobApp_Shipping_Quote_Method_State extends WC_Shipping_Method {
public function __construct( $instance_id = 0) {
$this->id = WC_MOBAPP_SHIPPING_ID;
$this->instance_id = absint( $instance_id );
$this->domain = 'mobapp';
$this->method_title = __( 'MobApp Urbano', $this->domain );
$this->method_description = __( 'Obtener Tarifas-precios desde API MobApp Urbano para Argentina por Provincias, Este metodo funciona en el calculo de envió cuando el usuario indica la región o estado o provincia solo en Argentina.', $this->domain );
$this->supports = array(
'shipping-zones',
'instance-settings',
'instance-settings-modal',
);
$this->init();
}
## Load the settings API
function init() {
$this->init_form_fields();
$this->init_settings();
$this->enabled = $this->get_option( 'enabled', $this->domain );
$this->title   = $this->get_option( 'title', $this->domain );
$this->info    = $this->get_option( 'info', $this->domain );
add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
}
function init_form_fields() {

	$mobapp_shipping_api_sources = json_decode(get_option( 'mobapp_shipping_api_sources', json_encode(array()) ),true);
	$mobapp_shipping_api_sources = is_array($mobapp_shipping_api_sources) ? $mobapp_shipping_api_sources : array();

	$nombre_fuentes = array_column($mobapp_shipping_api_sources, 'name');

$this->instance_form_fields = array(
	'title' => array(
		'type'          => 'text',
		'title'         => __('Titulo', $this->domain),
		'description'   => __( 'Este metodo funciona en el calculo de envió cuando el usuario indica la región o estado o provincia. ', $this->domain ),
		'default'       => __( 'Tarifa por Provincia', $this->domain ),
	),
	'limit_by_zone_locations' => array(
		'type'          => 'checkbox',
		'title'         => __('Limitar por Región(es) de la zona', $this->domain),
		'description'   => __( 'Activar si se desea que el metodo solo sea disponible para la(s) región(es) indicada(s). ', $this->domain ),
		'default'       => 'no',
	),	
	'msg_peso_exc' => array(
		'type'          => 'textarea',
		'title'         => __('Mensaje Kg excedente', $this->domain),
		'description'   => __( 'Si el peso es excedente mostrar este mensaje.', $this->domain ),
		'default'       => 'El costo para su envió debe ser cotizado.',
	),
	'fuentes'    => array(
		'title'             => __( 'Fuentes', $this->domain ),
		'type'              => 'multiselect',
		'class'             => 'chosen_select wc-enhanced-select',
		'css'               => '',
		'default'           => 0,
		'options'           => $nombre_fuentes,
		'custom_attributes' => array(
			'data-placeholder' => __( 'Seleccionar fuente(s)', $this->domain ),
		),
	),	
);
	$this->form_fields = array(
		'enabled' => array(
		'title'       => __( 'Activar', 'dc_raq' ),
		'type'        => 'checkbox',
		'description' => __( '', 'dc_raq' ),
		'default'     => 'yes'
	),
		'title' => array(
		'title'       => __( 'Titulo', 'dc_raq' ),
		'type'        => 'text',
		'description' => __( '', 'dc_raq' ),
		'default'     => __( 'Tarifa por Provincia', 'dc_raq' )
	),
	);
}
public function calculate_shipping( $packages = array() ) {
global $woocommerce;

//METODO DE ENVIO DISPONIBLE SOLO PARA ARGENTINA (ARG).

$country = isset($packages["destination"]["country"]) ? $packages["destination"]["country"] : '';
$state = isset($packages["destination"]["state"]) ? $packages["destination"]["state"] : '';

if($country !== 'AR'): return false; endif;

$shipping_zone = WC_Shipping_Zones::get_zone_matching_package( $packages );
$zone_locations = $shipping_zone->get_zone_locations();

$methods = $shipping_zone->get_shipping_methods();

$limit_by_zone_locations = [];
$fuentes = [];

if(count($methods) > 0):
foreach($methods as $instance_id => $method) {
	$limit_by_zone_locations[] = isset($method->instance_settings['limit_by_zone_locations']) ? $method->instance_settings['limit_by_zone_locations'] : 'no';

	$fuentes[] = isset($method->instance_settings['fuentes']) ? $method->instance_settings['fuentes'] : '';

}
endif;

$fuentes = count($fuentes) > 0 ? $fuentes[0] : false;

$available_mobapp = [];

if(in_array('yes',$limit_by_zone_locations)):

if($zone_locations){
	foreach($zone_locations as $kzl => $vzone){
		$code = $vzone->code;
		$code_arg_region = explode(':',$code);
		if(in_array($state,$code_arg_region)){
		$available_mobapp[] = array();
		}
	}
}

if(count($available_mobapp) == 0): return false; endif;

endif;

if($fuentes == false): return false; endif;

	$mobapp_shipping_api_sources = json_decode(get_option( 'mobapp_shipping_api_sources', json_encode([]) ),true);
	$mobapp_shipping_api_sources = is_array($mobapp_shipping_api_sources) ? $mobapp_shipping_api_sources : [];

//	
	foreach($fuentes as $index => $id_fuente){

		$nombre_fuente = isset($mobapp_shipping_api_sources[$id_fuente]['name']) ? $mobapp_shipping_api_sources[$id_fuente]['name'] : '';

		$rate = array(
			'id'       => sprintf('%d_%d',$this->id,$id_fuente),
			'label'    => sprintf('%s: %s', $nombre_fuente, $this->title),
			'cost'     => '0',
			'calc_tax' => 'per_item'
		);
		$this->add_rate( $rate );

	}

}
}