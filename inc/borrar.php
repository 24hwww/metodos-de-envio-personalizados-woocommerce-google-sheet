
add_action('woocommerce_shipping_init', function(){
	if (!class_exists('WC_Request_MobApp_Shipping_Quote_Method_State')) {
	require_once WC_MOBAPP_SHIPPING_BASE_PATH . '/class/class-mobapp-shipping-method.php';
	}
});

if (!class_exists('Class_Setting_MobApp_Shipping_Method')) {
	require_once WC_MOBAPP_SHIPPING_BASE_PATH . '/inc/class-setting-mobapp-shipping-method.php';
	add_action( 'plugins_loaded', [ 'Class_Setting_MobApp_Shipping_Method', 'init' ]);
}

if (!class_exists('Config_MobApp_Shipping')) {
	require_once WC_MOBAPP_SHIPPING_BASE_PATH . '/inc/config-mobapp-shipping.php';
	add_action( 'plugins_loaded', [ 'Config_MobApp_Shipping', 'init' ]);
}

/******************/

add_filter('woocommerce_shipping_methods', 'add_request_shipping_quote');
function add_request_shipping_quote( $methods ) {
	$methods[WC_MOBAPP_SHIPPING_ID] = 'WC_Request_MobApp_Shipping_Quote_Method_State';
    return $methods;
}

	if(!function_exists('getAmount')):
	function getAmount($money){
		$cleanString = preg_replace('/([^0-9\.,])/i', '', $money);
		$onlyNumbersString = preg_replace('/([^0-9])/i', '', $money);

		$separatorsCountToBeErased = strlen($cleanString) - strlen($onlyNumbersString) - 1;

		$stringWithCommaOrDot = preg_replace('/([,\.])/', '', $cleanString, $separatorsCountToBeErased);
		$removedThousandSeparator = preg_replace('/(\.|,)(?=[0-9]{3,}$)/', '',  $stringWithCommaOrDot);

		return (float) str_replace(',', '.', $removedThousandSeparator);
	}
	endif;

	function peso_mas_cercano($arr,$var){
		usort($arr, function($a,$b) use ($var){
			return  abs($a - $var) - abs($b - $var);
		});
		return array_shift($arr);
	}

	function tarifas_por_pesos($array = array()){
		$w = [];
		if(count($array) == 0): return false; endif;
		foreach($array as $key => $api){
			if($api['id'] == $calc_provincia){
						
				$peso = $api['peso'];
				$kg_extra = $api['kg_exc'];
						
				if(strpos($peso, '<=') !== false) {
					$tpeso = str_replace('<=', "", $peso);
					$tpeso = str_replace('kg', "", $tpeso);
					$tpeso = intval($tpeso);
							
					$w[$tpeso] = !empty($api['tarifa']) ? $api['tarifa'] : 0;
							
				}else if(strpos($peso, '>') !== false){
					$tpeso = str_replace('>', "", $peso);
					$tpeso = str_replace('kg', "", $tpeso);
					$tpeso = intval($tpeso);
					if($calc_provincia > $tpeso){
						#print_r($api['tarifa']);
					}
					$w[$tpeso] = $api['tarifa'];
				}
			}
		}
		return $w;
	}

add_action('init', 'get_api_response_mobapp_func');
function get_api_response_mobapp_func() {
	if(is_admin()): return false; endif;
	/*$mobapp_shipping_enabled = get_option( 'mobapp_shipping_enabled', 'no' );
	if($mobapp_shipping_enabled !== 'yes'){return false;}
	
	$getjson = json_decode(get_option( 'mobapp_shipping_api_sources', json_encode([])),true);
	$getjson = is_array($getjson) ? $getjson : array();

	if(count($getjson) == 0): return false; endif; 
	
	$api = get_transient( 'api_mobapp_response' );
		
	if ( false === $api) {
		
	$csv = [];
	$html = [];
	$array = array();
	$tr_no = 0;
	if(count($getjson) > 0){
		$xml = new DOMDocument();
		$xml->validateOnParse = true;

		foreach($getjson as $kurl => $vurl){
		$url = isset($vurl['url']) ? esc_url($vurl['url']) : '';
		$response = wp_remote_get($url);
		if (200 !== wp_remote_retrieve_response_code($response)) {continue;}
			$xml->loadHTML( wp_remote_retrieve_body($response) );
			$xpath = new DOMXPath($xml);
			
			foreach( $xpath->evaluate('//tr') as $sel ){
				$array[$tr_no] = array();
				$td_no = 0;
				foreach( $sel->childNodes as $td ){
				   if( strtolower( $td->tagName )  == 'td' ){
						$innerHTML = '';
						foreach ($td->childNodes as $child){
							$innerHTML .= $td->ownerDocument->saveHTML($child);
						}
						if($innerHTML !== ''):
							$array[$tr_no][$td_no] = $innerHTML;
						endif;
						$td_no++;
					}
				}
				$tr_no++;
			}
		}
	}
	
	$array = array_filter($array);
	unset($array[1]);
	foreach($array as $krow => $vrow){

		$provincia = $vrow[1];
		$peso = $vrow[2];
		$precio = getAmount($vrow[3]);
		$kg_exc = isset($vrow[4]) ? $vrow[4] : null;
		$empresa = isset($vrow[5]) ? $vrow[5] : null;
		
		$split_provincia = explode('_',$provincia);
		$id_provincia = $split_provincia[0];

				$csv[] = array(
					'id' => $krow,
					'id_provincia' => $id_provincia,
					'provincia' => $provincia,
					'peso' => $peso,
					'tarifa' => $precio,
					'kg_exc' => $kg_exc,
					'empresa' => $empresa,
				);
	}
		set_transient( 'api_mobapp_response', json_encode($csv), 5*MINUTE_IN_SECONDS );
	}*/
}

function arreglo_tarifa_por_peso($array=[]){
	global $woocommerce;
	$arreglo = [];
	
	$peso_carrito = $woocommerce->cart->cart_contents_weight > 0 ? $woocommerce->cart->cart_contents_weight : 1;
	
	$pesos = array_column($array,'peso');
	$tarifas = array_column($array,'tarifa');
	
	$kgs_extras = array_column($array,'kg_exc');
	
	$peso_maximo_por_provincia = intval(preg_replace('/[^0-9]/', '',end($pesos)));
	
	foreach($pesos as $k => $peso){
		$array = preg_split('/(\d+)/', $peso, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		
		$numero_peso = isset($array[1]) ? intval($array[1]) : 0;
		$condicional = isset($array[0]) ? $array[0] : '';
		$tarifa = isset($tarifas[$k]) ? intval($tarifas[$k]) : 0;
		
		$kg_extra = isset($kgs_extras[$k]) ? intval($kgs_extras[$k]) : 0;
		
		if($condicional !== '>' && $tarifa > 0){
			$numero_peso = $numero_peso-1;
		}
		
		$arreglo[$numero_peso] = array('peso'=>$numero_peso, 'tarifa'=>$tarifa,'kg_extra'=>$kg_extra);
	}
	
	
	$encontrar_peso = intval(peso_mas_cercano(array_keys($arreglo),$peso_carrito));
	$costo = isset($arreglo[$encontrar_peso]['tarifa']) ? intval($arreglo[$encontrar_peso]['tarifa']) : 0;
	
	$kg_extra = isset($arreglo[$encontrar_peso]['kg_extra']) ? intval($arreglo[$encontrar_peso]['kg_extra']) : 0;
	
	$retorno = array('peso_aproximado'=>$encontrar_peso, 'kg_extra' => $kg_extra,'costo_encontrado'=>$costo, 'peso_max_por_provincia'=>$peso_maximo_por_provincia);
	
	return $retorno;
}

function data_settings_rate_mobapp(){
	$meta_data_method = [];
	$zones = WC_Shipping_Zones::get_zones();
	$methods = array_map(function($zone) {return $zone['shipping_methods'];}, $zones);
	if(is_array($methods) && count($methods) > 0){
		foreach($methods as $instance_id => $method) {
			$id_method = isset($method[$instance_id]) ? $method[$instance_id] : '';
			if($id_method !== ''){
				$get_id_instance = $method[$instance_id]->id;
				if($get_id_instance == WC_MOBAPP_SHIPPING_ID){
					$meta_data_method[] = $method[$instance_id]->instance_settings;
				}
			}
		}
	}
	$data = isset($meta_data_method[0]) ? $meta_data_method[0] : false;
	return $data;
}

add_filter( 'woocommerce_package_rates', 'mobapp_packages_rates_cost', 10, 2 );
function mobapp_packages_rates_cost( $rates, $package ) {
	global $woocommerce;
	
	$mobapp_shipping_enabled = get_option( 'mobapp_shipping_enabled', 'no' );
	if($mobapp_shipping_enabled !== 'yes'): return false; endif;
	
	$api = get_transient( 'api_mobapp_response' );
	
	if ( false !== $api) {

		/*******/

		$mobapp_shipping_api_sources = json_decode(get_option( 'mobapp_shipping_api_sources', json_encode(array()) ),true);
		$mobapp_shipping_api_sources = is_array($mobapp_shipping_api_sources) ? $mobapp_shipping_api_sources : array();

		/*******/
		
		$gapi = is_array(json_decode($api,true)) ? json_decode($api,true) : array();
		$provincia_usuario = isset( $state ) ? $state : WC()->customer->get_shipping_state();		
		$provincia_encontrada = array_search($provincia_usuario, array_column($gapi, 'id_provincia'));
		
		$peso_en_carrito = $woocommerce->cart->cart_contents_weight > 0 ? $woocommerce->cart->cart_contents_weight : 1;
		
		$get_tarifas_por_provincias = array_filter(array_map(function($var){
			$provincia_usuario = WC()->customer->get_shipping_state();
			if($provincia_usuario == $var['id_provincia']):
				return $var;
			endif;
		}, $gapi)); 
		
		$tarifa_x_peso = arreglo_tarifa_por_peso($get_tarifas_por_provincias);
		
		$tarifa = $tarifa_x_peso['costo_encontrado'];
		$peso_aproximado = $tarifa_x_peso['peso_aproximado'];
		$kg_extra = $tarifa_x_peso['kg_extra'];
		$peso_max_por_provincia = $tarifa_x_peso['peso_max_por_provincia'];
		
		if($peso_en_carrito > $tarifa){
			$kilos_excedente = $peso_en_carrito-$peso_aproximado;
			$calc_tarifa = $kilos_excedente * $kg_extra;
			$tarifa = $calc_tarifa + $tarifa;
		}

		#$shipping_zone = WC_Shipping_Zones::get_zone_matching_package();
		#$methods = $shipping_zone->get_shipping_methods();
		$zones = WC_Shipping_Zones::get_zones();
		$methods = array_map(function($zone) {
			return $zone['shipping_methods'];
		}, $zones);
		
		foreach($rates as $rate){
			if($rate->id == WC_MOBAPP_SHIPPING_ID):
			$option_key = 'woocommerce_' . $rate->method_id . '_' . $rate->instance_id . '_settings';
			$settings = get_option($option_key);
			
			$titulo_del_metodo = isset($settings['title']) ? $settings['title'] : $rate->label;
			$mensaje_del_metodo = !empty($settings['msg_peso_exc']) ? esc_attr($settings['msg_peso_exc']) : 'El costo para su envio debe ser cotizado.';
			
			if($peso_en_carrito >= $peso_max_por_provincia){
				$titulo_del_metodo = $mensaje_del_metodo;
			}
		
			$rate->label = $titulo_del_metodo.'---'.$rate->label;
			$rate->cost = $tarifa;
				
			endif;
		}
	
	}
				
	return $rates;
}


/*PARA HACER DEBUG AL API EN EL CARRITO.*/
/*add_action('woocommerce_cart_totals_before_shipping','debug_request_api_cart_func');
function debug_request_api_cart_func(){
	global $woocommerce;
	echo '<pre>';
	
	$api = get_transient( 'api_mobapp_response' );
	
	if ( false !== $api) {
		
	$gapi = is_array(json_decode($api,true)) ? json_decode($api,true) : array();
	$provincia_usuario = isset( $state ) ? $state : WC()->customer->get_shipping_state();		
	$provincia_encontrada = array_search($provincia_usuario, array_column($gapi, 'id_provincia'));
	
	$peso_en_carrito = $woocommerce->cart->cart_contents_weight > 0 ? $woocommerce->cart->cart_contents_weight : 1;
	
	$get_tarifas_por_provincias = array_filter(array_map(function($var){
		$provincia_usuario = WC()->customer->get_shipping_state();
		if($provincia_usuario == $var['id_provincia']):
			return $var;
		endif;
    }, $gapi)); 
	
	$tarifa_x_peso = arreglo_tarifa_por_peso($get_tarifas_por_provincias);
	
	//$tarifa = $tarifa_x_peso['costo_encontrado'];
	//$peso_aproximado = $tarifa_x_peso['peso_aproximado'];
	//$kg_extra = $tarifa_x_peso['kg_extra'];
	//$peso_max_por_provincia = $tarifa_x_peso['peso_max_por_provincia'];
	
	//if($peso_max_por_provincia >= $peso_en_carrito || $peso_aproximado >= $peso_max_por_provincia){
		
	//}
	
	//if($peso_en_carrito > $tarifa){
		//$kilos_excedente = $peso_en_carrito-$peso_aproximado;
		//$calc_tarifa = $kilos_excedente * $kg_extra;
		//$tarifa = $calc_tarifa + $tarifa;
	//}
	
	print_r($tarifa_x_peso);
	}
	echo '</pre>';
}*/


/********************* */
