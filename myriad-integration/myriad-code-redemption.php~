<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
use \theantichris\WpPluginFramework as wpf;
global $viewData;

class myriad_code_redemption_plugin{

	public function __construct(){
		global $wpdb, $wp_query, $table_prefix;
		$this->wpdb =& $wpdb;
		$this->table_prefix = $table_prefix;
		$this->wp_query =& $wp_query;

		add_shortcode("voucher_code_redemption_form", array(&$this, "get_voucher_code_redemption_form"));

		$myView = new wpf\View(plugin_dir_path( __FILE__ )."views/admintab-voucher_codes.php");

		$pageArg = new wpf\PageArg('Myriad Voucher Code', $myView);
		$optionsPage = new wpf\OptionsPage($pageArg); 

		// Admin form
		add_action('admin_init', array(&$this, 'register_voucher_code_settings'));
	}

	public function get_voucher_code_redemption_form(){
		if(count($_POST)){
			$this->get_submit_action();
		}

		return $this->load_template_part('code_redemption');
	}

	private function get_submit_action(){
		global $woocommerce;
		$voucher_code = $_POST['voucher_code'];

		$voucher = $this->wpdb->get_row(sprintf('SELECT * FROM '.$this->table_prefix.'myriad_voucher_code WHERE BINARY code="%s"', $_POST['voucher_code']));

		if(!is_null($voucher) && (int)$voucher->order_id == 0){
			$voucher_group = $this->wpdb->get_row(sprintf('SELECT * FROM '.$this->table_prefix.'myriad_voucher WHERE id="%s"', $voucher->group_id));

			$coupon_code = $this->create_coupon_code($voucher_group->coupon, $voucher_group->product_id);

			$woocommerce->cart->empty_cart();
			$woocommerce->cart->add_to_cart($voucher_group->product_id);
			$woocommerce->cart->add_discount($coupon_code);
			wp_redirect($woocommerce->cart->get_checkout_url());
		}
	}

	public function register_voucher_code_settings(){
		global $viewData;

		add_settings_section('myriad_voucher_code_main', 'Main Settings', array(&$this, 'myriad_voucher_code_settings_render'), 'myriad_voucher_settings');

		/*
		 * If no voucher code is set we default to the user choosing one.
		 */
		if(!isset($_GET['state'])){
			$viewData['page_state'] = 'select_voucher';


			$groups = $this->wpdb->get_results("SELECT * FROM ".$this->table_prefix."myriad_voucher order by id");

			foreach ($groups as $group) 
			{
				$viewData['groups'][$group->id] = $group->group_id;
			}
		}

		/*
		 * Create a new voucher code group
		 */
		else if(isset($_GET['state']) && $_GET['state'] == "create_voucher"){
			$viewData['page_state'] = 'create_voucher';

			$orderController =  MyriadOrderController::instance();
			$paymentTypes = $orderController->getPaymentTypes(1);

			$viewData['payment_types'] = $paymentTypes;

			if(isset($_POST) && count($_POST)){
				$this->wpdb->insert($this->table_prefix . 'myriad_voucher', array('group_id' => $_POST['group_name'], 'product_id' => $_POST['product_id'], 'coupon' => $_POST['discount'], 'payment_type_id' => $_POST['payment_type']), array('%s', '%d', '%d'));
				wp_redirect('/wp-admin/options-general.php?page=myriad-voucher-code');
			}
		}

		/*
		 * Manage a voucher code group
		 */
		else if(isset($_GET['state']) && $_GET['state'] == "view_voucher"){
			$viewData['page_state'] = 'view_voucher';

			$voucher = $this->wpdb->get_row(sprintf('SELECT * FROM '.$this->table_prefix.'myriad_voucher WHERE id="%s"', $_GET['voucher_id']));

			if(is_null($voucher)){
				   $this->wp_query->set_404();
				   $this->wp_query->max_num_pages = 0;
			}

			//Get all voucher codes
			$vouchers = $this->wpdb->get_results("SELECT * FROM ".$this->table_prefix."myriad_voucher_code WHERE group_id = '".$voucher->id."' order by code");

			foreach ($vouchers as $voucher) 
			{
				$viewData['vouchers'][] = $voucher;
			}

			//Download CSV

			//Upload CSV
				//Overwrite
				//Append
				//Only add new
			if(isset($_POST) && count($_POST)){
				$csv = str_getcsv($_POST['csvinput'], "\n");
				foreach($csv as &$line) $line = str_getcsv($line, ","); //parse the items in rows 

				$mode = $_POST['action'];

				switch($mode){
					case 'append':
						foreach($csv as $line){
							$order_data = array('group_id' => $_GET['voucher_id']);
							if(isset($line[0])) $order_data['code'] = $line[0];
							if(isset($line[1])) $order_data['order_id'] = $line[1];
							$voucher_line = $this->wpdb->get_row('SELECT * FROM '.$this->table_prefix.'myriad_voucher_code WHERE code="'.$line[0].'"');

							if(is_null($voucher_line)){
								$this->wpdb->insert($this->table_prefix . 'myriad_voucher_code', $order_data);
							} else {
								// $this->wpdb->replace('wp_myriad_voucher_code', (array('id' => $voucher_line->id) + $order_data));	
							}
						}
						break;
					case 'overwrite':
						foreach($csv as $line){
							$order_data = array('group_id' => $_GET['voucher_id']);
							if(isset($line[0])) $order_data['code'] = $line[0];
							if(isset($line[1])) $order_data['order_id'] = $line[1];
							$voucher_line = $this->wpdb->get_row('SELECT * FROM '.$this->table_prefix.'myriad_voucher_code WHERE code="'.$line[0].'"');

							if(is_null($voucher_line)){
								$this->wpdb->insert($this->table_prefix . 'myriad_voucher_code', $order_data);
							} else {
								$this->wpdb->replace($this->table_prefix . 'myriad_voucher_code', (array('id' => $voucher_line->id) + $order_data));	
							}
						}
						break;
					case 'remove':
						foreach($csv as $line){
							$order_data = array('group_id' => $_GET['voucher_id']);
							if(isset($line[0])) $order_data['code'] = $line[0];
							
							$this->wpdb->delete($this->table_prefix . 'myriad_voucher_code', array('code' => $order_data['code']));
						}
						break;
					case 'replace':
						$this->wpdb->delete($this->table_prefix . 'myriad_voucher_code', array('group_id' => $$_GET['voucher_id']));
						foreach($csv as $line){
							$order_data = array('group_id' => $_GET['voucher_id']);
							if(isset($line[0])) $order_data['code'] = $line[0];
							if(isset($line[1])) $order_data['order_id'] = $line[1];

							if(is_null($voucher_line)){
								$this->wpdb->insert($this->table_prefix . 'myriad_voucher_code', $order_data);
							}
						}
						break;
				}
				wp_redirect('/wp-admin/options-general.php?page=myriad-voucher-code&state=view_voucher&voucher_id='.$_GET['voucher_id'].'&csv');
			}
		}

		// $template->assign('test', 'testertester');

		// register_setting( "myriad_settings", "MyriadSoapURL" );
		// register_setting( "myriad_settings", "MyriadSoapDD" );
		// add_settings_section('myriad_settings_main', 'Main Settings', array(&$this,'myriad_settings_render'), 'myriad_settings');
		// add_settings_field('myriad_settings_soapurl', 'Myriad SOAP API address', array(&$this,'plugin_setting_string'), 'myriad_settings', 'myriad_settings_main');
		// add_settings_field('myriad_settings_dd_start_options', 'Direct Debit start date options', array(&$this,'plugin_setting_dd_string'), 'myriad_settings', 'myriad_settings_main');
	}

	public function create_coupon_code($amount, $product_id){
		$coupon_code = md5(uniqid(rand(), true)); // Code
		$discount_type = 'percent';
							
		$coupon = array(
			'post_title'   => $coupon_code,
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => 1,
			'post_type'	   => 'shop_coupon'
		);
							
		$new_coupon_id = wp_insert_post( $coupon );
							
		// Add meta
		update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
		update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
		update_post_meta( $new_coupon_id, 'individual_use', 'yes' );
		update_post_meta( $new_coupon_id, 'product_ids', array($product_id) );
		update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
		update_post_meta( $new_coupon_id, 'usage_limit', 1 );
		update_post_meta( $new_coupon_id, 'expiry_date', '' );
		update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
		update_post_meta( $new_coupon_id, 'free_shipping', 'no' );

		return $coupon_code;
	}

	public function myriad_voucher_code_settings_render()
	{
		echo ('Voucher code settings.');
	}

	private function load_template_part($template_name, $part_name=null) {
	    ob_start();
		    get_template_part($template_name, $part_name);
		    $template_content = ob_get_contents();
	    ob_end_clean();

	    return $template_content;
	}
}


