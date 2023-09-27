<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'woocommerce_shipping_methods', function($methods){

    if(function_exists('activated_config_mobapp_shipping')){
        if(activated_config_mobapp_shipping() !== true){return $methods;}
    }

    $methods[WC_MOBAPP_SHIPPING_ID] = 'WC_Request_MobApp_Shipping_Quote_Method_State';
	return $methods;
});

add_action( 'woocommerce_shipping_init', function(){

    if(function_exists('activated_config_mobapp_shipping')){
        if(activated_config_mobapp_shipping() !== true){return false;}
    }
    if ( ! class_exists( 'WC_Request_MobApp_Shipping_Quote_Method_State' ) ) {
        class WC_Request_MobApp_Shipping_Quote_Method_State extends WC_Shipping_Method {

            public function __construct($instance_id = 0) {
				$this->id                 = WC_MOBAPP_SHIPPING_ID;
                $this->instance_id = absint( $instance_id );
				$this->method_title       = WC_MOBAPP_SHIPPING_TITLE;
				$this->method_description = __( 'Obtener Tarifas-precios desde API MobApp Urbano para Argentina por Provincias, Este metodo funciona en el calculo de envió cuando el usuario indica la región o estado o provincia solo en Argentina.');
				$this->supports           = array(
                    'shipping-zones',
                    'instance-settings',
                    'instance-settings-modal',
				);
				$this->enabled            = "yes"; 
				$this->title              = WC_MOBAPP_SHIPPING_TITLE; 
				$this->init();
			}

            function init() {
                $this->init_form_fields();
                $this->init_settings();
                $this->enabled = $this->get_option( 'enabled', 'default');
                $this->title   = $this->get_option( 'title', 'default' );
                $this->info    = $this->get_option( 'info','default' );
                add_action('woocommerce_update_options_shipping_' . $this->id,[$this, 'process_admin_options']);
                add_filter('woocommerce_generate_custom_html',[$this,'woocommerce_generate_custom_html_func'],10,3);
            }

            public function get_fuentes_mobapp_rate_state(){
                $config = new Config_MobApp_Shipping();
                $return = $config->array_mobapp_shipping_sources();
                return $return;
            }

            public function init_form_fields() {

                $url_config = add_query_arg( array(
                    'page' => 'wc-settings',
                    'tab' => 'shipping',
                    'section' => WC_MOBAPP_SHIPPING_SECTION
                ), admin_url( 'admin.php' ) );

                $get_fuentes_mobapp_rate_state = $this->get_fuentes_mobapp_rate_state();
                $fuentes = array_column($get_fuentes_mobapp_rate_state,'name');

                $this->init_settings();
                $this->instance_form_fields = array(
                    'title' => array(
                        'type'          => 'text',
                        'title'         => __('Titulo', ''),
                        'desc_tip' => true,
                        'description'   => __( 'Este metodo funciona en el calculo de envió cuando el usuario indica la región o estado o provincia. ', '' ),
                        'default'       => __( 'Tarifa por provincia de Argentina', '' ),
                    ),
                    'limit_by_zone_locations' => array(
                        'type'          => 'checkbox',
                        'title'         => __('Limitar por Región(es) de la zona', ''),
                        'desc_tip' => true,
                        'description'   => __( 'Activar si se desea que el metodo solo sea disponible para la(s) región(es) indicada(s). ', '' ),
                        'default'       => 'no',
                    ),	
                    'msg_peso_exc' => array(
                        'type'          => 'textarea',
                        'title'         => __('Mensaje Kg excedente', ''),
                        'desc_tip' => true,
                        'description'   => __( 'Si el peso es excedente mostrar este mensaje.', '' ),
                        'default'       => 'El costo para su envió debe ser cotizado.',
                    ),
                    'fuentes'    => array(
                        'title'             => __( 'Fuentes', '' ),
                        'type'              => 'multiselect',
                        'class'			=> 'chosen_select wc-enhanced-select',
                        'default'           => 0,
                        'options'           => $fuentes,
                        'custom_attributes' => array(
                            'data-placeholder' => __( 'Seleccionar fuente(s)', '' ),
                        ),
                        'description'   => sprintf('<a href="%s">Agregar o editar</a> fuentes en las configuraciones de %s.',$url_config, WC_MOBAPP_SHIPPING_TITLE),
                    ), 
                    'html' => array(
                        'type'          => 'custom',
                        'title'         => '',
                        'desc_tip' => true,
                        'description'   => '',
                        'default'       => '',
                    ),           
                );
                $this->form_fields = array(
                    'enabled' => array(
                        'title'       => __( 'Activar', '' ),
                        'type'        => 'checkbox',
                        'description' => __( '', '' ),
                        'default'     => 'yes'
                    ),
                    'title' => array(
                        'title'       => __( 'Titulo', '' ),
                        'type'        => 'text',
                        'description' => __( '', '' ),
                        'default'     => __( 'Tarifa por provincia de Argentina', '' )
                    ),
                );
                
            }
            
            public function get_instance_form_fields() {
                return parent::get_instance_form_fields();
            }

            public function fuentes_por_zonas($code_country_state=''){
                if (class_exists('mobapp_rate_state')) {
	
                    $output = [];
                    $x= new Config_MobApp_Shipping();
                    $y = new mobapp_rate_state();
                    
                    $metodos = is_array($y->get_data_metodos()) ? $y->get_data_metodos() : [];
                    $fuentes = $x->array_mobapp_shipping_sources();
                    
                    $output = array_column($metodos,WC_MOBAPP_SHIPPING_ID);
                    $data = array_column($output,'data');
                    
                    
                    if(is_array($metodos) && count($metodos) > 0){
                        foreach($metodos as $d1 => $d2){
                            $mobapp_rate_state = isset($d2[WC_MOBAPP_SHIPPING_ID]) ? $d2[WC_MOBAPP_SHIPPING_ID] : '';
                            if($mobapp_rate_state !== ''){
                                $datos[$d1] = $mobapp_rate_state;
                                foreach($mobapp_rate_state as $d2 => $d3){
                                    
                                    $datos[$d1][$d2] = $d3;
                                    if($d2 == 'data'){
                                        foreach($d3 as $d4 => $d5){
                                            
                                            $datos[$d1][$d2][$d4] = $d5;
                                            foreach($d5 as $d6 => $d7){
                                                
                                                $datos[$d1][$d2][$d4][$d6] = $d7;
                                                if($d6 == 'fuentes'){
                                                    foreach($d7 as $d8 => $d9){
                                                        
                                                        $datos[$d1][$d2][$d4][$d6][$d8] = isset($fuentes[$d9]) ? $fuentes[$d9] : [];
                                                        
                                                    }
                                                }
                                            
                                            }
                                            
                                        }
                                    }
                                    
                                }
                            }
                        }
                    }
                
                    return $datos;
                    
                }	
            }

            public function get_peso_total($package = array()){
                /* Peso (kg): total de productos en carrito */
                $items = is_array($package['contents']) ? $package['contents'] : [];
                $total_weight = 0;
                if(count($items) > 0){
                    foreach ($items as $item_id => $item ) {
                        $product = isset($item['data']) ? $item['data'] : '';
                        $weight = $product->get_weight();
                        $total_weight += $weight;
                    }
                }
                return $total_weight;
            }

			public function calculate_shipping( $package = array() ) {

                $country = isset($package["destination"]["country"]) ? $package["destination"]["country"] : '';
                $state = isset($package["destination"]["state"]) ? $package["destination"]["state"] : '';

                $code_country_state = sprintf('%s:%s',$country,$state);

                $pesos_total = $this->get_peso_total($package);

                $shipping_zone = WC_Shipping_Zones::get_zone_matching_package( $package );

                $fuentes_por_zonas = $this->fuentes_por_zonas($code_country_state);

                $debug = json_encode($pesos_total,JSON_PRETTY_PRINT);

				$rate = array(
					'label' => $this->title .'--'. $debug,
					'cost' => '10.99',
					'calc_tax' => 'per_item'
				);
				$this->add_rate( $rate );
			}

            public function woocommerce_generate_custom_html_func(){
                ob_start();
                ?>
                <script type="text/javascript">
                document.addEventListener("DOMContentLoaded", function (event) {
                    (function($){
                        $(function($, undefined){
            

                        });
                    })(jQuery);
                });
                </script>        
                <?php
                $output = ob_get_contents();
                ob_end_clean();
                return $output;
            }

        }
    }
});