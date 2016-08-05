<?php
/**
 * @package   Myriad Integration
 * @author    Simon Briggs (simonpbriggs@gmail.com)
 * @license   All Rights Reserved
 * @link      http://www.flymarketing.com
 * @copyright 2014 Fly Marketing
 *
 * @wordpress-plugin
 * Plugin Name:       Myriad Integration
 * Plugin URI:        http://www.flymarketing.com
 * Description:       Provides integration with Myriad subscription system
 * Version:           1.0.0
 * Author:            Fly Marketing
 * License:           All Rights Reserved
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

session_start();

if($_SERVER['REMOTE_ADDR'] == "178.62.30.195" //Harry
		|| $_SERVER['REMOTE_ADDR'] == "186.222.154.45" //Tim
		|| $_SERVER['REMOTE_ADDR'] == "90.217.189.228" //Martyn
		|| $_SERVER['REMOTE_ADDR'] == "86.152.67.14" //Jamie Home
		|| $_SERVER['REMOTE_ADDR'] == "109.231.227.162" //Jamie Work
  ){
	define('MYRIAD_DEBUG', true);
} else {
	define('MYRIAD_DEBUG', false);
}

// Autoload library classes from includes directory
spl_autoload_register(function($className)
		{
		$namespace=str_replace("\\","/",__NAMESPACE__);
		$className=str_replace("\\","/",$className);
		$class=plugin_dir_path( __FILE__ ) ."includes/".(empty($namespace)?"":$namespace."/")."{$className}.php";
		if (file_exists($class))
		{
		include_once($class);
		}
		});

use \theantichris\WpPluginFramework as wpf;

require_once('controllers/MyriadPublicationController.php');
require_once('controllers/MyriadCustomerController.php');
require_once('controllers/MyriadOrderController.php');

// Include any additional functionality classes
require_once('myriad-code-redemption.php');
require_once('myriad-marketing-questions.php');
require_once('myriad-login.php');

define('MYRIAD_MAGAZINE_TYPE', 5);

global $wpdb, $table_prefix;
if(mysql_num_rows(mysql_query("SHOW TABLES LIKE '".$table_prefix."myriad_soap_log'"))==1)
define('MYRIAD_SOAP_LOG_DB', true);
else
define('MYRIAD_SOAP_LOG_DB', false);

class myriad_integration_plugin
{
	public function __construct()
	{
		global $wpdb, $table_prefix;
		$this->wpdb =& $wpdb;
		$this->table_prefix = $table_prefix;
		$this->doInit();
	}

	public function doInit()
	{   
		 
		add_action( 'woocommerce_product_write_panel_tabs', array( &$this, 'create_myriad_admin_tab' ) );
		add_action('woocommerce_product_write_panels', array( &$this,'myriad_tab_options_spec'));
		add_action('woocommerce_process_product_meta', array( &$this,'myriad_process_product_meta'), 10, 2);
		add_action('woocommerce_no_available_payment_methods_message', array(&$this, 'custom_no_payment_message'));


		add_filter('woocommerce_add_cart_item_data', array(&$this, 'myriad_force_individual_cart_items'), 10, 2);
		add_filter('woocommerce_get_cart_item_from_session', array(&$this, 'myriad_get_cart_items_from_session'), 1, 3 );
		add_action('woocommerce_add_order_item_meta', array(&$this, 'myriad_convert_item_session_to_order_meta'), 10, 3 );

		add_filter( 'add_to_cart_redirect', array(&$this, 'myriad_add_to_cart_redirect') );

		add_action( 'wp_ajax_refresh_rates', array(&$this,'ajax_refresh_rates') );
		add_action( 'wp_ajax_current_rate_id', array(&$this,'ajax_current_rate_id') );
		add_action( 'wp_ajax_get_rate_price', array(&$this,'ajax_get_rate_price') );

		add_action( 'admin_enqueue_scripts', array(&$this,'load_admin_js' ));

		add_shortcode("select_subscription_issue", array(&$this, "select_issue_handler"));

		//Load the plugin 'plugins'
		$this->myriad_code_redemption_plugin = new myriad_code_redemption_plugin();
		$this->myriad_marketing_question_plugin = new myriad_marketing_questions_plugin();

		$myView = new wpf\View(plugin_dir_path( __FILE__ )."views/admintab.php");

		$pageArg = new wpf\PageArg('Myriad', $myView);
		$optionsPage = new wpf\OptionsPage($pageArg); 
		add_action( 'admin_init', array(&$this,'register_myriad_settings') );

	}

	function custom_no_payment_message($message)
	{
		return "Sorry, no payment method is available. If you have selected a direct debit product and a credit card product, please pay for these in separate orders";
	}

	function myriad_convert_item_session_to_order_meta( $item_id, $values, $cart_item_key ) {
		if(isset($values['_issue_start_id']))
		{
			$order_product_data = array();

			$cart_item_data = $values['_issue_start_id'];
			wc_add_order_item_meta( $item_id, '_issue_start_id', $cart_item_data );
		}

		if(isset($values['_voucher_code']))
		{
			$order_product_data = array();

			$cart_item_data = $values['_voucher_code'];
			wc_add_order_item_meta( $item_id, '_voucher_code', $cart_item_data );
		}
	}

	function myriad_add_to_cart_redirect() { 
		global $woocommerce;

		return $woocommerce->cart->get_checkout_url(); 
	}


	public function select_issue_handler($woocommerce_product) {
		if(MYRIAD_DEBUG){
			$_pf = new WC_Product_Factory();  
			$_product = $_pf->get_product($woocommerce_product['product_id']);
			$myriad_publication_id = $_product->myriad_linked_publication;

			$myriadPublication =  MyriadPublicationController::instance();
			$myriad_publication = $myriadPublication->publicationByID($myriad_publication_id);

			$current_issue = $myriad_publication->current_issue_id;

			$next_issues = $myriadPublication->getNextIssues($myriad_publication_id, $current_issue, 3);

			$issue_options = "";
			foreach($next_issues as $issue_id => $issue_title)
			{
				$issue_options .= "<option value=\"$issue_id\">".$issue_title."</option>";
			}

			return "<h4 style=\"margin-bottom: 0px;\">Select start issue</h4><select name=\"issue_start_id\" class=\"issue_selector\" style=\"margin-top: 15px;\">" . $issue_options . "</select>";
		} else {
			return '';
		}
	}

	//Get it from the session and add it to the cart variable
	function myriad_get_cart_items_from_session( $item, $values, $key ) {
		if ( array_key_exists( '_issue_start_id', $values ) )
			$item[ '_issue_start_id' ] = $values['_issue_start_id'];

		if ( array_key_exists( '_voucher_code', $values ) )
			$item[ '_voucher_code' ] = $values['_voucher_code'];

		return $item;
	}

	public function myriad_force_individual_cart_items($cart_item_data, $product_id)
	{
		global $woocommerce;
		$unique_cart_item_key = rand(5, 15);
		$cart_item_data['unique_key'] = $unique_cart_item_key;

		if(isset($_REQUEST['issue_start_id'])){
			$cart_item_data['_issue_start_id'] = $_REQUEST['issue_start_id'];
		}

		if(isset($_REQUEST['voucher_code'])){
			$cart_item_data['_voucher_code'] = $_REQUEST['voucher_code'];
		}

		return $cart_item_data;
	}

	public function load_admin_js()
	{
		wp_enqueue_script('jquery');

		wp_enqueue_script('myriad_js',  plugins_url( 'js/myriad.js' , __FILE__ ));
	}

	public function register_myriad_settings()
	{
		register_setting( "myriad_settings", "MyriadSoapURL" );
		register_setting( "myriad_settings", "MyriadSoapDD" );
		register_setting( "myriad_settings", "MyriadLoginFormID" );
		register_setting( "myriad_settings", "MyriadPreLoginFormID" );
		register_setting( "myriad_settings", "MyriadDDSiteName" );
		add_settings_section('myriad_settings_main', 'Main Settings', array(&$this,'myriad_settings_render'), 'myriad_settings');
		add_settings_field('myriad_settings_soapurl', 'Myriad SOAP API address', array(&$this,'plugin_setting_string'), 'myriad_settings', 'myriad_settings_main');
		add_settings_field('myriad_dd_site_name', 'Myriad DD Site Name', array(&$this,'plugin_setting_dd_name_string'), 'myriad_settings', 'myriad_settings_main');
		add_settings_field('myriad_settings_dd_start_options', 'Direct Debit start date options', array(&$this,'plugin_setting_dd_string'), 'myriad_settings', 'myriad_settings_main');
		add_settings_field('myriad_settings_login_form_id', 'Login Form ID', array(&$this,'plugin_form_id_setting_string'), 'myriad_settings', 'myriad_settings_main');
		add_settings_field('myriad_settings_pre_login_form_id', 'Post Login Form ID', array(&$this,'plugin_pre_form_id_setting_string'), 'myriad_settings', 'myriad_settings_main');
	}

	public function myriad_settings_render()
	{
		echo ('Set options for the Myriad integration here.');
	}

	public function plugin_setting_dd_name_string(){
		$options = get_option('MyriadDDSiteName');
		echo "<input id='myriad_dd_site_name' name='MyriadDDSiteName[text_string]' size='40' type='text' value='{$options['text_string']}' />";
	}

	public function plugin_setting_dd_string() {
		$options = get_option('MyriadSoapDD');
		echo "<select id='myriad_soap_dd_input_field' name='MyriadSoapDD[text_string]'><option value=\"none\" ".($options['text_string'] == "none" ? "SELECTED" : "").">No options provided</option><option value=\"firstandfifteenth\" ".($options['text_string'] == "firstandfifteenth" ? "SELECTED" : "").">1st and 15th of month</option><option value=\"every\" ".($options['text_string'] == "every" ? "SELECTED" : "").">Every Day</option></select>";
	}

	public function plugin_setting_string() {
		$options = get_option('MyriadSoapURL');
		echo "<input id='myriad_soap_url_input_field' name='MyriadSoapURL[text_string]' size='40' type='text' value='{$options['text_string']}' />";
	} 

	public function plugin_form_id_setting_string() {
		$options = get_option('MyriadLoginFormID');
		echo "<input id='myriad_soap_form_id_input_field' name='MyriadLoginFormID[text_string]' size='40' type='text' value='{$options['text_string']}' />";
	}

	public function plugin_pre_form_id_setting_string() {
		$options = get_option('MyriadPreLoginFormID');
		echo "<input id='myriad_soap_pre_form_id_input_field' name='MyriadPreLoginFormID[text_string]' size='40' type='text' value='{$options['text_string']}' />";
	}

	/* this creates the tab in the products section in the admin panel */
	public function create_myriad_admin_tab()
	{
		?>
			<li class="myriad_product_options_tab"><a href="#myriad_product_options"><?php _e('Myriad', 'woocommerce'); ?></a></li>
			<?php
	}

	public function myriad_tab_options_spec()
	{
		// error_reporting(E_ALL); ini_set('display_errors', '1');
		global $post;
		$pubController =  MyriadPublicationController::instance();
		$currentPublication = $pubController->publicationByID(get_post_meta(get_the_ID(), '_myriad_linked_publication')[0]);
		?>
			        <div id="myriad_product_options" class="panel woocommerce_options_panel">
			                <div class="options_group">
			<?php
			woocommerce_wp_checkbox( array( 'id' => '_myriad_link_enabled', 'label' => __('Enable link to Myriad product?', 'woothemes'), 'description' => __('Enable this option to enable linking this product to Myriad.', 'woothemes') ) ); 	
		woocommerce_wp_select( array( 'id' => '_myriad_linked_publication', 'label' => __( 'Linked Publication', 'woocommerce' ), 'options' => $pubController -> getPublications(true), 'value' => get_post_meta(get_the_ID(), '_myriad_linked_publication')[0], 'desc_tip' => true, 'description' => __( 'Myriad publication linked to this product', 'woocommerce' ) ) );
		woocommerce_wp_select( array( 'id' => '_myriad_collection_frequency', 'label' => __( 'Collection Frequency Override', 'woocommerce' ), 'options' => $pubController -> getCollectionFrequencies(), 'value' => get_post_meta(get_the_ID(), '_myriad_collection_frequency')[0], 'desc_tip' => true, 'description' => __( 'Myriad collection frequency override', 'woocommerce' ) ) );
		?>
			<div>If this product is a subscription</div>
			<?php
			woocommerce_wp_select( array( 'id' => '_myriad_linked_rate', 'label' => __( 'Linked Rate', 'woocommerce' ), 'value' => get_post_meta(get_the_ID(), '_myriad_linked_rate')[0], 'options' => $pubController -> getRates($currentPublication,true), 'desc_tip' => true, 'description' => __( 'Myriad rate linked to this product', 'woocommerce' ) ) );
		woocommerce_wp_select( array( 'id' => '_myriad_linked_promo', 'label' => __( 'Linked Promotion', 'woocommerce' ), 'value' => get_post_meta(get_the_ID(), '_myriad_linked_promo')[0], 'options' => $pubController -> getPromoRates($currentPublication,true), 'desc_tip' => true, 'description' => __( 'Myriad promotion linked to this product', 'woocommerce' ) ) );
		woocommerce_wp_select( array( 'id' => '_myriad_linked_back_issue', 'label' => __( 'Linked Back Issue', 'woocommerce' ), 'value' => get_post_meta(get_the_ID(), '_myriad_linked_back_issue')[0], 'options' => $pubController -> getBackIssues($currentPublication->title_id), 'desc_tip' => true, 'description' => __( 'Able to choose one of the next 3 issues to start subscriptions', 'woocommerce' ) ) );
		woocommerce_wp_checkbox( array( 'id' => '_myriad_select_backissue', 'label' => __( 'Enable user to select start issue?', 'woocommerce' ), 'value' => get_post_meta(get_the_ID(), '_myriad_select_backissue')[0], 'description' => __( 'Enable this option to allow the user to select their start issue date from the next 3 issues.', 'woocommerce' ) ) );
		//woocommerce_wp_text_input( array( 'id' => '_myriad_linked_back_issue', 'label' => __('Linked Back Issue', 'woocommerce'), 'value' => get_post_meta(get_the_ID(), '_myriad_linked_back_issue')[0]) );
		?>
			<div>or if this product is a circulation</div>
			<?php
			woocommerce_wp_select( array( 'id' => '_myriad_renewal_campaign', 'label' => __( 'Renewal Campaign', 'woocommerce' ), 'value' => get_post_meta(get_the_ID(), '_myriad_renewal_campaign')[0], 'options' => $pubController->getRenewalCampaigns(), 'desc_tip' => true, 'description' => __( 'Myriad Renewal Campaign  linked to this circulation', 'woocommerce' ) ) );
		woocommerce_wp_select( array( 'id' => '_myriad_despatch_mode', 'label' => __( 'Despatch Mode', 'woocommerce' ), 'value' => get_post_meta(get_the_ID(), '_myriad_despatch_mode')[0], 'options' => $pubController->getDespatchModes(), 'desc_tip' => true, 'description' => __( 'Myriad Despatch Mode linked to this circulation', 'woocommerce' ) ) );
		?>
			<div>If the product is using ABC Codes</div>
			<?php
			woocommerce_wp_select( array( 'id' => '_myriad_abc_code', 'label' => __( 'ABC code', 'woocommerce' ), 'value' => get_post_meta(get_the_ID(), '_myriad_abc_code')[0], 'options' => $pubController->getABCCodes(), 'desc_tip' => true, 'description' => __( 'Myriad ABC Code linked to this circulation', 'woocommerce' ) ) );
		?>
			<div id="updateMyriadPricing">Update product price from Myriad rate</div>
			</div>
			</div>
			<?php 
	} 

	public function myriad_process_product_meta( $post_id ) {
		global $post;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;

		update_post_meta( $post_id, '_myriad_link_enabled', ( isset($_POST['_myriad_link_enabled']) && $_POST['_myriad_link_enabled'] ) ? 'yes' : 'no' );
		update_post_meta( $post_id, '_myriad_linked_publication', $_POST['_myriad_linked_publication']);
		update_post_meta( $post_id, '_myriad_collection_frequency', $_POST['_myriad_collection_frequency']);
		update_post_meta( $post_id, '_myriad_linked_rate', $_POST['_myriad_linked_rate']);
		update_post_meta( $post_id, '_myriad_linked_promo', $_POST['_myriad_linked_promo']);
		update_post_meta( $post_id, '_myriad_select_backissue', ( isset($_POST['_myriad_select_backissue']) && $_POST['_myriad_select_backissue'] ) ? 'yes' : 'no' );
		update_post_meta( $post_id, '_myriad_renewal_campaign', $_POST['_myriad_renewal_campaign']);
		update_post_meta( $post_id, '_myriad_despatch_mode', $_POST['_myriad_despatch_mode']);
		update_post_meta( $post_id, '_myriad_abc_code', $_POST['_myriad_abc_code']);

		if((int)$_POST['_myriad_linked_promo'] > 0){
			//Loop through the products and ensure that Myriad products that require issues have one
			$linked_promo = $_POST['_myriad_linked_promo'];
			$pubController =  MyriadPublicationController::instance();
			$currentPublication = $pubController->publicationByID(get_post_meta(get_the_ID(), '_myriad_linked_publication')[0]);

			$subscriptions_issue = [];

			$promo_rates = $pubController -> getPromoRates($currentPublication,false);
			foreach($promo_rates as $promo_rate)
			{
				if($promo_rate["SubsPromotion_ID"] == $linked_promo){
					foreach($promo_rate['SubsPromotionDetails']['SubsPromotionDetail'] as $promotionDetail){
						if($promotionDetail['ProductType_ID'] != MYRIAD_MAGAZINE_TYPE){
							//Get the latest issue and set in array
							$title_issues = explode(';', end($pubController->getIssuesForTitle($promotionDetail['Title_ID'])));

							if(is_array($title_issues)){
								$subscriptions_issue[] = array(
										'Title_ID' => $promotionDetail['Title_ID'],
										'StartIssue' => array(
											'Issue_ID' => $title_issues[0]
											)
										);
							}
						}
					}
				}
			}

			update_post_meta( $post_id, '_myriad_linked_promo_issues', $subscriptions_issue);
		}
		update_post_meta( $post_id, '_myriad_linked_back_issue', $_POST['_myriad_linked_back_issue']);
	}

	// Called after successful payment. Determines if order contains Myriad items
	public function process_myriad_order($order_id)
	{
		global $woocommerce;

		// Retrieve the woocommerce order
		$order = new WC_Order( $order_id );
		echo "<pre>";
		
		//mail("simonpbriggs@gmail.com","Processing",print_r($order,true));

		if ( count( $order->get_items() ) > 0 ) {

			foreach( $order->get_items() as $item ) {
				$i = 1;
				while($i <= $item['qty']){
					$i++;
					// mail("h@hgs.so","Processing item",print_r($item,true));exit();

					if ( $item['product_id'] > 0 ) {
						$_product = $order->get_product_from_item( $item );
						// mail("h@hgs.so","Processing item",print_r($_product,true));exit();

						// If this is a Myriad order then process it accordingly
						if ( 'yes' == $_product->myriad_link_enabled) {
							$orderController =  MyriadOrderController::instance();
							$myriadOrder = new MyriadOrder();

							// Get the linked Myriad publication and rate
							$myriadOrder->title_id = $_product->myriad_linked_publication;
						
							$myriadOrder->rate_id = $_product->myriad_linked_rate;
							$myriadOrder->promo_id = @$_product->myriad_linked_promo;
							$myriadOrder->promo_issues = @$_product->myriad_linked_promo_issues;
							$myriadOrder->myriad_abc_code = @$_product->myriad_abc_code;
							$myriadOrder->collection_frequency = @$_product->myriad_collection_frequency;
							$myriadOrder->myriad_despatch_mode = $_product->myriad_despatch_mode;

							$myriadOrder->order_type = "subscription";
							if((int)$_product->myriad_renewal_campaign > 0)
							{
								$myriadOrder->order_type = "circulation";
								$myriadOrder->myriad_renewal_campaign = $_product->myriad_renewal_campaign;
								$myriadOrder->myriad_despatch_mode = $_product->myriad_despatch_mode;
							}

							if(isset($item['issue_start_id']) && (int)$item['issue_start_id'] > 0)
							{
								$myriadOrder->back_issue_id = $item['issue_start_id'];
							} else {
								$myriadOrder->back_issue_id = @$_product->myriad_linked_back_issue;
							}

							$myriadOrder->payment_reference = "";
							if(isset($item['voucher_code'])){
								$myriadOrder->payment_reference = $item['voucher_code'];

								$voucher = $this->wpdb->get_row(sprintf('SELECT * FROM `'.$this->table_prefix.'myriad_voucher_code` JOIN '.$this->table_prefix.'myriad_voucher on '.$this->table_prefix.'myriad_voucher_code.group_id='.$this->table_prefix.'myriad_voucher.id WHERE BINARY '.$this->table_prefix.'myriad_voucher_code.code="%s"', $item['voucher_code']));
								if(!is_null($voucher)){
									$myriadOrder->payment_type_id = $voucher->payment_type_id;

									$wpdb->update( 
											$this->table_prefix . 'myriad_voucher_code', 
											array( 
												'order_id' => $order_id,
											     ), 
											array( 'code' => $item['voucher_code'] ),
											array( 
												'%s',
											     ), 
											array( '%s' ) 
										     );
								}
							}

							if(isset($_SESSION['active_user_account']) && $_SESSION['active_user_account'] != ""){
								$custController = MyriadCustomerController::instance();

								$customer = $custController->getCustomerByID(lookupCustomerAccountByAccountCode($_SESSION['active_user_account']));
								$myriadOrder->billingcontact = $customer->contact_id;
							} else {
								// Set up the billing customer
								$custController = MyriadCustomerController::instance();
								$billing_customer = new MyriadCustomer();
								$billing_customer -> forename = $order->billing_first_name;
								$billing_customer -> surname = $order->billing_last_name;
								$billing_customer -> street = $order->billing_address_1 . " " . $order->billing_address_2;
								$billing_customer -> town = $order->billing_city;
								$billing_customer -> county = $order->billing_state;
								$billing_customer -> postcode = $order->billing_postcode;
								$billing_customer -> company = $order->billing_company;
								$myriadOrder->billingcontact = $custController -> createCustomer($billing_customer);

								$custController -> createCustomerPhone($myriadOrder->billingcontact, $order->billing_phone);
								$custController -> createCustomerEmailAddress($myriadOrder->billingcontact, $order->billing_email);

								$_SESSION['active_user_account'] = $myriadOrder->billingcontact;
								$_SESSION['active_user_surname'] = $billing_customer->surname;
							}

							// If billing and delivery the same then just copy 
							if ($order->get_formatted_shipping_address() == $order->get_formatted_billing_address())
							{
								$delivery_customer = $billing_customer;
								$myriadOrder->deliverycontact = $myriadOrder->billingcontact;
							}
							//Otherwise set up a new delivery customer too
							else
							{
								$delivery_customer = new MyriadCustomer();
								$delivery_customer -> forename = $order->shipping_first_name;
								$delivery_customer -> surname = $order->shipping_last_name;
								$delivery_customer -> street = $order->shipping_address_1 . " " . $order->shipping_address_2;
								$delivery_customer -> town = $order->shipping_city;
								$delivery_customer -> county = $order->shipping_state;
								$delivery_customer -> postcode = $order->shipping_postcode;
								$delivery_customer -> company = $order->shipping_company;

								if($myriadOrder->order_type != "circulation"){
									$myriadOrder->deliverycontact = $custController -> createCustomer($delivery_customer);
									$custController -> createCustomerPhone($myriadOrder->deliverycontact, $order->shipping_phone);
									$custController -> createCustomerEmailAddress($myriadOrder->deliverycontact, $order->shipping_email);
								}
							}
							// default values for paid and setToLive overriden later if Direct Debit
							$myriadOrder->paid = true;
							$myriadOrder->setToLive = true;
							$myriadOrder->amount = $item['line_subtotal'];
							// Set DD fields if appropriate
							if (get_post_meta( $order_id, 'dd_account_name', true )!="")
							{
								$myriadOrder->dd_accountname = get_post_meta( $order->id, 'dd_account_name', true );
								$myriadOrder->dd_sortcode = get_post_meta( $order->id, 'dd_account_sortcode', true );
								$myriadOrder->dd_accountnumber = get_post_meta( $order->id, 'dd_account_number', true );

								if(get_post_meta( $order->id, 'dd_payment_date', true ) != "")
								{
									$myriadOrder->dd_payment_date = get_post_meta( $order->id, 'dd_payment_date', true );
								}

								$myriadOrder->paid = false;
								$myriadOrder->setToLive = false;
							}

							//  mail("simonpbriggs@gmail.com","Processing Myriad order",print_r($myriadOrder,true));
							
							$myriad_order_id = $orderController->processOrder($myriadOrder);

							if ($myriad_order_id)
							{
								update_post_meta( $order->id, 'myriad-id', sanitize_text_field( $myriad_order_id ) );
							}

						}
					}
				}
			}
		}
	}

	public function ajax_refresh_rates()
	{
		global $wpdb; // this is how you get access to the database
		$newPublication = new MyriadPublication();
		$publicationController = MyriadPublicationController::instance();
		$newPublication->title_id = intval( $_POST['publicationID'] );
		echo wp_send_json($publicationController->getRates($newPublication,true));
		die();
	}

	public function ajax_current_rate_id()
	{
		$current_rate_id = get_post_meta( intval( $_POST['postID'] ), '_myriad_linked_rate')[0];
		echo ($current_rate_id);
		die();
	}

	public function ajax_get_rate_price()
	{
		global $wpdb; // this is how you get access to the database
		$newPublication = new MyriadPublication();
		$publicationController = MyriadPublicationController::instance();
		$newPublication->title_id = intval( $_POST['titleID'] );
		$publicationRates = $publicationController->getRates($newPublication);
		$price = 0;
		foreach ($publicationRates as $rate)
		{
			if ($rate['Rate_ID'] ==  $_POST['rateID'] )
			{
				$price = floatval ($rate['RateZeroRated']);
			}
		}
		echo ($price);
		die();
	}

} // class myriad_integration_plugin

$myriad = new myriad_integration_plugin(); 
add_action( 'woocommerce_payment_complete',array(&$myriad,'process_myriad_order') );

$custController = MyriadCustomerController::instance();

//$custController->createCustomerEmailAddress(250001,'test@example.com');

add_action( 'init', 'process_order', 1 );
function process_order() {
if(isset($_GET['process'])){
$myriad = new myriad_integration_plugin(); 
$myriad->process_myriad_order($_GET['process']);
} 
}
