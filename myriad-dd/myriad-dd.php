<?php
/**
 * @package   Myriad Integration
 * @author    Simon Briggs (simonpbriggs@gmail.com)
 * @license   All Rights Reserved
 * @link      http://www.flymarketing.com
 * @copyright 2014 Fly Marketing
 *
 * @wordpress-plugin
 * Plugin Name:       Myriad Direct Debit Payment Gateway
 * Plugin URI:        http://www.flymarketing.com
 * Description:       Provides payment gateway for Myriad Direct Debits
 * Version:           1.0.0
 * Author:            Fly Marketing
 * License:           All Rights Reserved

 */
 
 add_action( 'plugins_loaded', 'init_myriad_DD_gateway_class' );

function init_myriad_DD_gateway_class() {
	class WC_Gateway_Myriad_DD_Gateway extends WC_Payment_Gateway {
	
		public function __construct()
		{
			$this->id = "myriad_dd";
			$this->icon =  plugins_url("direct_debit_curved.png", __FILE__);
			$this->has_fields = true;
			$this->method_title = "Myriad Direct Debit";
			$this->method_description = "Direct debit payment handled by Myriad";
			
			 // Load the settings
        	 $this->init_form_fields();
 	         $this->init_settings();
 	         
 	         $this->title = $this->get_option( 'title' );
		}

		public function init_form_fields() {
				$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable/Disable', 'woocommerce' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Myriad Direct Debit Payment', 'woocommerce' ),
				'default' => 'yes'
			),
			'title' => array(
				'title' => __( 'Title', 'woocommerce' ),
				'type' => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default' => __( 'Direct Debit', 'woocommerce' ),
				'desc_tip'      => true,
			),
			'description' => array(
				'title' => __( 'Customer Message', 'woocommerce' ),
				'type' => 'textarea',
				'default' => ''
			)
);
		}
		
		public function payment_fields() {

			$date_options = get_option('MyriadSoapDD');

			$dd_account_fields[] = '<label for="dd_account_name">Account holder\'s name</label><input name="dd_account_name" id="dd_account_name" type="text">';
			$dd_account_fields[] = '<label for="dd_account_sortcode">Account sort code</label><input name="dd_account_sortcode" id="dd_account_sortcode" type="text">';
			$dd_account_fields[] = '<label for="dd_account_number">Account number</label><input name="dd_account_number" id="dd_account_number" type="text">';

			if(isset($date_options['text_string']))
			{
				switch($date_options['text_string']){
					case 'every':
						$begin = new DateTime( '+10 days' );
						$end = new DateTime( '+10 days' );
						$end = $end->modify( '+1 month' );

						$interval = new DateInterval('P1D');
						$daterange = new DatePeriod($begin, $interval, $end);

						$field = '<label for="dd_payment_date">Direct Debit date</label><select name="dd_payment_date" id="dd_payment_date">';
						
						foreach($daterange as $date)
						{
							$field .= "<option value=\"".$date->format('Y-m-d')."\">". $date->format('F jS Y') ."</option>";
						}

						$dd_account_fields[] = $field . '</select>';
						break;
					case 'firstandfifteenth':
						$field = '<label for="dd_payment_date">Direct Debit date</label><select name="dd_payment_date" id="dd_payment_date">';
						
						$date = time();
						$end_date = strtotime(date('Y-m-d', $date) . ' +11 days');

						if(date('m', $date) == date('m', $end_date)){
						  if(date('d', $end_date) >= 15) {
						  //1st of next month and 15th of next month
						    $field .= "<option value=\"" . date('Y-m', strtotime(date('Y-m-d', $date) . " +1 month")) . "-1\">1st " . date('F Y', strtotime(date('Y-m-d', $date) . " +1 month")) . "</option>";
						    $field .= "<option value=\"" . date('Y-m', strtotime(date('Y-m-d', $date) . " +1 month")) . "-15\">15th " . date('F Y', strtotime(date('Y-m-d', $date) . " +1 month")) . "</option>";
						  } else {
						  //15th of next month and 
						    $field .= "<option value=\"" . date('Y-m', $date) . "-15\">15th " . date('F Y', $date) . "</option>";
						    $field .= "<option value=\"" . date('Y-m', strtotime(date('Y-m-d', $date) . " +1 month")) . "-1\">1st " . date('F Y', strtotime(date('Y-m-d', $date) . " +1 month")) . "</option>";
						  }
						} else {
						    $field .= "<option value=\"" . date('Y-m', strtotime(date('Y-m-d', $date) . " +1 month")) . "-15\">15th " . date('F Y', strtotime(date('Y-m-d', $date) . " +1 month")) . "</option>";
						    $field .= "<option value=\"" . date('Y-m', strtotime(date('Y-m-d', $date) . " +2 months")) . "-1\">1st " . date('F Y', strtotime(date('Y-m-d', $date) . " +2 months")) . "</option>";
						}

						$dd_account_fields[] = $field . '</select>';
						break;
				}
			}

			$site_name = get_option('MyriadDDSiteName', 'APL Media Ltd')['text_string'];

			$dd_account_fields[] = '<label for="dd_confirm">Please confirm you are the account holder and you are the only person required to authorise direct debits from this account.</label><input name="dd_confirm" id="dd_confirm" type="checkbox"><br />';
			$dd_account_fields[] = '<label for="dd_mandate">Instruction to your Bank or Building Society
Please pay Debit Finance Collections plc ('.$site_name.') Direct Debits from the account detailed in this Instruction subject to the safeguards assured by the Direct Debit Guarantee. I understand that this Instruction may remain with Debit Finance Collections plc ('.$site_name.') and, if so, details will be passed electronically to my Bank/Building Society.

Banks and Building Societies may not accept Direct Debit Instructions for some types of account

This Guarantee is offered by all banks and building societies that accept instructions to pay Direct Debits<br><br>

• If there are any changes to the amount, date or frequency of your Direct Debit, Debit Finance Collections plc ('.$site_name.') will notify you 5 working days in advance of your account being debited or as otherwise
agreed. If you request Debit Finance Collections plc ('.$site_name.')  to collect a payment, confirmation of the amount and date will be given to you at the time of the request.<br>
• If an error is made in the payment of your Direct Debit, by Debit Finance Collections plc ('.$site_name.') or your bank or building society you are entitled to a full and immediate refund of the amount paid from your bank or
building society<br>
– If you receive a refund you are not entitled to, you must pay it back when Debit Finance Collections plc ('.$site_name.')<br>
• You can cancel a Direct Debit at any time by simply contacting your bank or building society. Written
confirmation may be required. Please also notify us.</label>';
			echo('<ul>');
			foreach ($dd_account_fields as $field)
			{
				echo '<br>'.$field;
			}
			echo('</ul>');
		}
		
		public function validate_fields() {
			global $woocommerce;
			if ($_POST['dd_account_name']=="" || $_POST['dd_account_number']=="" || $_POST['dd_account_sortcode']=="")
			{
				wc_add_notice('Please complete all the direct debit account details', 'error'); 
				return false;
			}
			else
			{
				if(!is_numeric($_POST['dd_account_sortcode']) || strlen($_POST['dd_account_sortcode']) != 6){
					wc_add_notice('Please enter a valid 6 digit sort code', 'error');
					return false;
				}

				if(!is_numeric($_POST['dd_account_number']) || !(strlen($_POST['dd_account_number']) >= 8 && strlen($_POST['dd_account_number']) <= 10)){
					wc_add_notice('Please enter a valid 8 - 10 digit account number', 'error');
					return false;
				}
				return true;
			}
		} 
		
		public function process_payment( $order_id )
		{
			update_post_meta( $order_id, 'dd_account_name', sanitize_text_field( $_POST['dd_account_name'] ) );
			update_post_meta( $order_id, 'dd_account_sortcode', sanitize_text_field( $_POST['dd_account_sortcode'] ) );
			update_post_meta( $order_id, 'dd_account_number', sanitize_text_field( $_POST['dd_account_number'] ) );
			update_post_meta( $order_id, 'dd_payment_date', sanitize_text_field( $_POST['dd_payment_date'] ) );
			$order = new WC_Order( $order_id );
			$order->payment_complete();

			// Return thank you page redirect
			return array(
				'result' => 'success',
				'redirect' => $this->get_return_url( $order )
			);
		}
	}
}

function add_myriad_DD_gateway_class( $methods ) {
	$methods[] = 'WC_Gateway_Myriad_DD_Gateway'; 
	return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_myriad_DD_gateway_class' );

/**
 * Display field value on the order edit page
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'display_dd_order_meta', 20, 1 );
 
function display_dd_order_meta($order){
	if (get_post_meta( $order->id, 'dd_account_name', true )!="")
	{
    echo '<p><strong>Account name :</strong> ' . get_post_meta( $order->id, 'dd_account_name', true ) . '<br>';
    echo '<strong>Account number :</strong> ' . get_post_meta( $order->id, 'dd_account_number', true ) . '<br>';
    echo '<strong>Account sortcode :</strong> ' . get_post_meta( $order->id, 'dd_account_sortcode', true ) . '</p>';
   }
}
 
 ?>