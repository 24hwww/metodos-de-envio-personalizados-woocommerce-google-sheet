<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Config_MobApp_Shipping{
    private static $_instance = null;
    public $wc_settings_tab = '';
	public $section_menu = '';
    public $fuentes = '';
    
    public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

    public function __construct() {

        $this->wc_settings_tab = 'shipping'; 
		$this->section_menu = WC_MOBAPP_SHIPPING_SECTION;
        $this->fuentes = 'mobapp_shipping_sources'; 
        
    }

	public static function init() {
        $instance = self::instance();

        add_filter("woocommerce_get_sections_{$instance->wc_settings_tab}", [$instance,'config_mobapp_shipping_section_func']);
        add_filter("woocommerce_get_settings_{$instance->wc_settings_tab}", [$instance,'config_mobapp_shipping_settings_func'], 10, 2 );

        add_action( 'woocommerce_admin_field_button' , [$instance,'config_mobapp_shipping_admin_field_func']);

        #add_action("woocommerce_update_options_{$instance->wc_settings_tab}",[$instance,'validate_field_option_func']);

        #add_filter( 'woocommerce_admin_settings_sanitize_option', [$instance,'woocommerce_admin_settings_sanitize_option_filter_func'], 10, 3 );

	}
	
    public function config_mobapp_shipping_section_func( $settings_tab ){
        $settings_tab[$this->section_menu] = WC_MOBAPP_SHIPPING_TITLE;
        return $settings_tab;
    }

    public function config_mobapp_shipping_settings_func($settings, $current_section){

        if($this->section_menu !== $current_section){return $settings;}
        /***********/
        $custom_settings = array();
        $custom_settings =  array(
            array(
                'name' => __( 'Configuraciones MobApp Urbano' ),
                'type' => 'title',
                'desc' => __( 'Integración con metodos de envio via API personalizada para Argentina.' ),
                'id'   => $this->section_menu
            ),
            array(
                'name' => __( 'Activate' ),
                'type' => 'checkbox',
                'desc' => __( 'Activar funcionamiento.'),
                'id'    => 'mobapp_shipping_enable'
            ),
            array(
                'name' => __( 'Fuentes (Url API CSV)' ),
                'type' => 'button',
                'desc' => __( 'Insertar fuentes, para eliminar deje los campos vacios de la fila que desea borrar.'),
                'desc_tip' => true,
                'class' => 'button-secondary',
                'id'    => $this->fuentes,
            ),
            
            array( 'type' => 'sectionend', 'id' => $this->section_menu),
        );

        return $custom_settings;
    }

    public function get_ids_config_mobapp_shipping_settings(){
        $fields = $this->config_mobapp_shipping_settings_func([],$this->section_menu);
		$ids_fields = array_column($fields,'id');
        return $ids_fields;
    }

    public function array_mobapp_shipping_sources(){
        $array = [];
        
        $fuentes = (array) WC_Admin_Settings::get_option($this->fuentes);

        if(is_array($fuentes) && count($fuentes) > 0){
            foreach($fuentes as $key => $cadena){
                if(!is_array($cadena)){ continue; }
                foreach($cadena as $i => $v){
                    #if($v !== ''){
                    $array[$i][$key] = $v;
                   # }
                }
            }
        }
        
        return $array;
    }

    public function config_mobapp_shipping_admin_field_func($value){
    $section = isset($_GET['section']) ? esc_attr($_GET['section']) : '';
    if($section !== $this->section_menu): return false; endif;

    $description = WC_Admin_Settings::get_field_description( $value );
    $values = $this->array_mobapp_shipping_sources();

    settings_errors($this->fuentes);

    echo '<pre>';
    print_r($values);
    echo '</pre>';

    ?>
    <style>
        .textarea-input{width:100% !important;}
    </style>
    <tr valign="top">
        <th scope="row" class="titledesc">
            <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
            <?php echo  $description['tooltip_html'];?>
        </th>
        
        <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">

        <table class="form-table">
			<tbody><tr valign="top">
			<td class="wc_payment_gateways_wrapper" colspan="2">
				<table class="wc_gateways widefat" cellspacing="0" aria-describedby="payment_gateways_options-description">
					<thead>
						<tr>
							<th class="sort"></th>
							<th class="name">Nombre de Metodo</th>
							<th class="source">Fuente</th>
							<th class="description">Descripción</th>
						</tr>
						</thead>
					<tbody id="display_sources" class="ui-sortable">
						<tr>

                            <?php 
                            $tr = '';
                            if(is_array($values) && count($values) > 0){

                                foreach($values as $key => $v){
                                    $tr .= '<tr>';

                                    $tr .= '<td class="sort ui-sortable-handle" width="1%">';
                                    $tr .= '<div class="wc-item-reorder-nav">';
                                    $tr .= '<button type="button" class="wc-move-up wc-move-disabled" tabindex="-1" aria-hidden="true" aria-label="">Subir</button><button type="button" class="wc-move-down" tabindex="0" aria-hidden="false" aria-label="">Mover abajo</button>';
                                    $tr .= '</div>';
                                    $tr .= '</td>';                                    

                                    foreach($v as $a => $b){ 
                                
                                        $tr .= sprintf('<td><input type="text" class="textarea-input regular-text" placeholder="" name="%s[%s][]" value="%s" /></td>', $value['id'], $a, $b);
                                    
                                    }
                                    $tr .= '</tr>';
                                } 
                            }else{

                                $tr .= '<tr>';

                                $tr .= '<td class="sort ui-sortable-handle" width="1%">';
                                $tr .= '<div class="wc-item-reorder-nav">';
                                $tr .= '<button type="button" class="wc-move-up wc-move-disabled" tabindex="-1" aria-hidden="true" aria-label="">Subir</button><button type="button" class="wc-move-down" tabindex="0" aria-hidden="false" aria-label="">Mover abajo</button>';
                                $tr .= '</div>';
                                $tr .= '</td>';    

                                $tr .= sprintf('<td><input type="text" class="textarea-input regular-text" placeholder="" name="%s[%s][]" value="%s" /></td>', $value['id'], 'name','');

                                $tr .= sprintf('<td><input type="text" class="textarea-input regular-text" placeholder="" name="%s[%s][]" value="%s" /></td>', $value['id'], 'source','');

                                $tr .= sprintf('<td><input type="text" class="textarea-input regular-text" placeholder="" name="%s[%s][]" value="%s" /></td>', $value['id'], 'desc','');

                                $tr .= '</tr>';

                            }
                            echo $tr;
                            ?>

						</tr>
					</tbody>
					<tfoot>
						<tr>
						<td colspan="4"><button type="button" class="button button-repeat-tr">+ Añadir fuente</button>
						</td>
						</tr>
					</tfoot>
					</table>
				</td>
			</tr>
			</tbody>
	    </table>

        <p class="description"><strong>Ejemplo de Fuente (CSV):</strong><br/><code>https://docs.google.com/spreadsheets/d/e/2PACX-1vTb7mxeY65ALDoMJJbERSp38L452M275qRaLkCPMIF7SNK1l6Wn5hTvCMRYiVDwvU4GlyT5lSYlgAd5/pub?gid=1490307897&amp;single=true&amp;output=csv</code></p>
            
        </td>
    </tr>

    <script type="text/javascript">
	document.addEventListener("DOMContentLoaded", function (event) {
	(function($){
	$(function($, undefined){
        
    const displaysource = $('tbody#display_sources');	    
    $(document).on('click', '.button-repeat-tr',  function(){
		const tr = displaysource.find('tr');
		const count_tr = tr.length;
		const num_tr = tr.length+1;

		var $trLast = displaysource.find("tr:last");
        var $trNew = $trLast.clone();
		$trNew.find('input,textarea').attr('name', function(idx, attrVal) {
        return attrVal;
    	}).val('');
		$trLast.after($trNew);
	});

    });
	})(jQuery);
	});
	</script>
    <?php       
    }

    public function validate_field_option_func(){

        $fuentes = isset($_POST[$this->fuentes]) ? $_POST[$this->fuentes] : [];
        $fuentes = array_filter($fuentes);

        if(is_array($fuentes) && count($fuentes) > 0){

        update_option($this->fuentes,$fuentes);

        }else{
            return false;
        }

    }
    
    public function woocommerce_admin_settings_sanitize_option_filter_func($value, $option, $raw_value){
    }
}