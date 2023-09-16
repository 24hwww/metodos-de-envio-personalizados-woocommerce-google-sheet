<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Class_Setting_MobApp_Shipping_Method{
    private static $_instance = null;
	public $section_menu = '';

    public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
    public function __construct() {
		$this->section_menu = 'setting-shipping-mobapp'; 
    }
	public static function init() {
        $instance = self::instance();
		
		add_action('admin_head', [$instance,'estilos_personalizados_backend_wp_func']);
		add_action('admin_footer', [$instance,'custom_code_init_mobapp_shipping_footer_func']);
		
		#add_action( 'wp_ajax_render_tr_sources', [$instance,'render_tr_sources_func']);
		#add_action( 'wp_ajax_nopriv_render_tr_sources', [$instance,'render_tr_sources_func']);

		add_filter( 'woocommerce_get_sections_shipping', [$instance,'config_mobapp_add_section_func']);
		
		add_filter( 'woocommerce_get_settings_shipping', [$instance,'config_mobapp_add_settings_func'], 10, 2 );

		add_action( 'admin_notices', function () : void {
			$errors = get_settings_errors();
			print_r( $errors );
		} );

	}
	
	public function estilos_personalizados_backend_wp_func() {
		$section = isset($_GET['section']) ? esc_attr($_GET['section']) : '';
		if($section !== $this->section_menu): return false; endif;
		echo '<style type="text/css">#mobapp_shipping_api_sources{display:none;}.textarea-input{resize:none;width:100% !important;display:block;border-radius: 4px;border: 1px solid #8c8f94;}</style>';
	}
	
	public function custom_code_init_mobapp_shipping_footer_func(){
	ob_start();
	$my_current_screen = json_encode(get_current_screen());
	$section = isset($_GET['section']) ? esc_attr($_GET['section']) : '';
	if($section !== $this->section_menu): return false; endif;
	?>
	<script type="text/javascript">
	document.addEventListener("DOMContentLoaded", function (event) {
	(function($){
	$(function($, undefined){
		
	const displaysource = $('#display_sources');	
	const jxadmin = '<?php echo admin_url( "admin-ajax.php" ); ?>';
		
	/*$.ajax({type : "GET",url : jxadmin,
	data : {action: 'render_tr_sources', gettr: 1, display: 'get_display_sources_tr'},
				beforeSend: function() {
					displaysource.block({message: null,overlayCSS: {background: "#fff",opacity: .6}});
				},
			   error: function(response){
				   console.log(response);
			   },
			   success: function(resp) {
				   console.log(resp);
				   const tr = resp.data.tr;
				   displaysource.html(tr);
				   displaysource.unblock();
			   }
		   });*/
		
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

		/*$.ajax({type : "GET",url : jxadmin,
			   data : {
				   action: 'render_tr_sources', 
				   display: 'get_display_sources_tr',
				   gettr: count_tr+1
			   },
		beforeSend: function(){displaysource.block({message: null,overlayCSS: {background: "#fff",opacity: .6}});},
		error: function(response){console.log(response);},
		success: function(resp){
		  const tr = resp.data.tr;
		  displaysource.html(tr);
			displaysource.unblock();
	   }
	   });*/	
	});
		
	$.fn.serializeObject = function(){var o = {};var a = this.serializeArray();$.each(a, function() {if (o[this.name]) {if (!o[this.name].push) {o[this.name] = [o[this.name]];}o[this.name].push(this.value || '');}else{o[this.name] = this.value || '';}});return o;};	
		
	$(document).on('submit', 'form#mainform',  function(evt){
		evt.preventDefault();
		const form = $(this);
		const formdata = form.serializeObject();
		var returna = false;

		$.ajax({type: "POST",url: jxadmin,
		data: {action: 'render_tr_sources', formdata: formdata,},
		beforeSend: function(){displaysource.block({message: null,overlayCSS: {background: "#fff",opacity: .6}});},
		error: function(response){console.log(response);evt.target.submit();},
		success: function(resp){
			console.log(resp);
			displaysource.unblock();
			returna = true;

			evt.target.submit();
		}
		});	
		
	});
		
	});
	})(jQuery);
	});
	</script>
	<?php
	$output = ob_get_contents();
	ob_end_clean();
	echo $output;
	}
	
	public function render_tr_sources_func(){
	
	$output = [];
	$array = [];
	
	$mobapp_shipping_api_sources = json_decode(get_option( 'mobapp_shipping_api_sources', json_encode(array()) ),true);
	$mobapp_shipping_api_sources = is_array($mobapp_shipping_api_sources) ? $mobapp_shipping_api_sources : array();
	
	$num_row = isset($_GET['gettr']) ? intval($_GET['gettr']) : count($mobapp_shipping_api_sources);
	
	$formdata = isset($_POST['formdata']) ? $_POST['formdata'] : array();
	
	#$mobapp_shipping_enabled = !empty($formdata['mobapp_shipping_enabled_checkbox']) ? $formdata['mobapp_shipping_enabled_checkbox'] : 'yes';
	
	if(count($formdata) > 0){
		
		$f=0;
		foreach($formdata as $kdata => $vdata){
			if(strpos($kdata,'mobapp_shipping_api_sources') !== false){
					if(is_array($vdata) && count($vdata) > 0):
						if($vdata['url'] !== ''):
							$array[] = $vdata;
						endif;
					endif;
				$f++;
			}
		}
		
		if(count($array) > 0):
			update_option( 'mobapp_shipping_api_sources', json_encode($array));
		endif;
		
		$output['mobapp_shipping_enabled'] = $mobapp_shipping_enabled;
	}
	
	update_option( 'mobapp_shipping_enabled', 'yes');

	$tr = '';

	for($i=0;$i<=$num_row;$i++){
		$v = isset($mobapp_shipping_api_sources[$i]) ? $mobapp_shipping_api_sources[$i] : [];
	#foreach($mobapp_shipping_api_sources as $i => $v){
		$name = isset($v['name']) ? esc_html($v['name']) : '';
		$url = isset($v['url']) ? esc_url($v['url']) : '';
		$desc = isset($v['desc']) ? esc_html($v['desc']) : '';

		$tr .= '<tr>';
		$tr .= '<td class="sort ui-sortable-handle" width="1%">';
		$tr .= '<div class="wc-item-reorder-nav">';
		$tr .= '<button type="button" class="wc-move-up wc-move-disabled" tabindex="-1" aria-hidden="true" aria-label="">Subir</button><button type="button" class="wc-move-down" tabindex="0" aria-hidden="false" aria-label="">Mover abajo</button>';
		$tr .= '</div>';
		$tr .= '</td>';

		$tr .= sprintf('<td><input type="text" class="textarea-input" placeholder="Nombre" name="mobapp_shipping_api_sources[name][]" value="%s" /></td>',$i,$name);

		$tr .= sprintf('<td><input type="text" class="textarea-input" placeholder="Url API CSV" name="mobapp_shipping_api_sources[url][]" value="%s" /></td>',$i,$url);

		$tr .= sprintf('<td><textarea class="textarea-input" placeholder="Descripción" name="mobapp_shipping_api_sources[desc][]">%s</textarea></td>',$i,$desc);

		$tr .= '</tr>';

	#}

	}
		
	$output['tr'] = $tr;
	
	wp_send_json_success($output);
	}
	
	public function enabled_mobapp_shipping(){
		$mobapp_shipping_api_csv = get_option( 'mobapp_shipping_api_csv', '' );
		$mobapp_shipping_enabled = get_option( 'mobapp_shipping_enabled', 'no' );
		if($mobapp_shipping_enabled !== 'yes'){
			return false;
		}
	}
	public function config_mobapp_add_section_func( $sections ) {
	$mobapp_shipping_api_csv = get_option( 'mobapp_shipping_api_csv', '' );
	$mobapp_shipping_enabled = get_option( 'mobapp_shipping_enabled', 'no' );
	
	$status = $mobapp_shipping_enabled !== 'yes' ? 'Inactivo' : 'Activo';
	
	$sections[$this->section_menu] = __( 'Configuraciones MobApp Urbano: '.$status, 'wc' );
	return $sections;
	}
	public function config_mobapp_add_settings_func($settings, $current_section){
		if ( $current_section == $this->section_menu) {
			
			$settings_mobapp = array();

			$settings_mobapp[] = array( 'name' => __( 'Configuraciones MobApp Urbano', 'wcmobapp' ), 'type' => 'title', 'desc' => __( 'Integración con metodos de envio via API personalizada para Argentina.', 'wcmobapp' ), 'id' => 'wcmobapp' );

			$settings_mobapp[] = array(
				'name'     => __( 'Activar funcionamiento', 'wcmobapp' ),
				'id'       => 'mobapp_shipping_enabled',
				'type'     => 'checkbox',
				'css'      => 'min-width:300px;',
				'desc'     => __( 'Activar funcionamiento de el metodo de envio Urbano via Api.', 'wcmobapp' ),
			);

			$settings_mobapp[] = array(
				'name'     => __( 'Fuentes (Url API CSV)', 'wcmobapp' ),
				'id'       => 'mobapp_shipping_api_sources',
				'type'     => 'text',
				'default'  => '',
				'desc'     => $this->table_type_setting_shipping(),
			);
			
			$settings_mobapp[] = array( 'type' => 'sectionend', 'id' => 'wcmobapp' );
			return $settings_mobapp;
		
		}else{
			return $settings;
		}
	}
	
	public function table_type_setting_shipping(){
		ob_start();
			
		$mobapp_shipping_enabled = get_option( 'mobapp_shipping_enabled', 'no' );
		$getjson = json_decode(get_option( 'mobapp_shipping_api_sources', json_encode([])),true);

		$api = get_transient( 'api_mobapp_response' );
		
		$array_json_api = is_array(json_decode($api,true)) ? count(json_decode($api,true)) : 0;
			
		echo '<pre>';
		print_r( data_settings_rate_mobapp() );
		echo '</pre>';

	?>
	
	<?php if($array_json_api > 0): ?> 
	<div id="message" class="notice notice-success"><p><strong>Datos obtenidos exitosamente!.</strong></p></div>
	<?php else: ?>
	<div id="message" class="notice notice-warning"><p><strong>Datos aun no obtenidos.</strong></p></div>
	<?php endif; ?>

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
							<td class="sort ui-sortable-handle" width="1%">
							<div class="wc-item-reorder-nav">
							<button type="button" class="wc-move-up wc-move-disabled" tabindex="-1" aria-hidden="true" aria-label="">Subir</button><button type="button" class="wc-move-down" tabindex="0" aria-hidden="false" aria-label="">Mover abajo</button>
							</div>
							</td>
							<td><input type="text" class="textarea-input" placeholder="Nombre" name="mobapp_shipping_api_sources[name][]" /></td>
							<td><input type="text" class="textarea-input" placeholder="Url API CSV" name="mobapp_shipping_api_sources[url][]" /></td>
							<td><input type="text" class="textarea-input" placeholder="Descripción" name="mobapp_shipping_api_sources[desc][]" /></td>
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
	<p class="description"><strong>Ejemplo de Fuente (CSV):</strong> https://docs.google.com/spreadsheets/d/e/2PACX-1vTb7mxeY65ALDoMJJbERSp38L452M275qRaLkCPMIF7SNK1l6Wn5hTvCMRYiVDwvU4GlyT5lSYlgAd5/pub?gid=1490307897&amp;single=true&amp;output=csv</p>
	<?php
	$output = ob_get_contents();
	ob_end_clean();
	return $output;
	}

}