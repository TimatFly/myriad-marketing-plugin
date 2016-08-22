<?php

function lookupCustomerAccountByAccountCode($accountCode){
	global $account_code_lookups;

	if(isset($account_code_lookups[$accountCode])){
		return $account_code_lookups[$accountCode];
	}

	$custController = MyriadCustomerController::instance();
	$customerAccount = $custController->getContactByAccountCode($accountCode);
	
	$customer_Id = $accountCode;
	if(is_array($customerAccount) && isset($customerAccount['Contacts']['Contact_ID']) && $customerAccount['Contacts']['Contact_ID'] != null){
		$customer_Id = $customerAccount['Contacts']['Contact_ID'];
		$account_code_lookups[$accountCode] = $customerAccount['Contacts']['Contact_ID'];
	}

	return $customer_Id;
}

function before_submission_login($form)
{
	$custController = MyriadCustomerController::instance();

	if(isset($_GET['logout']))
	{
		unset($_SESSION['active_user_account']);
		unset($_SESSION['active_user_surname']);
	}

	if(isset($_SESSION['active_user_account']) && $_SESSION['active_user_account'] != ""){
		$customer = $custController->getCustomerByID(lookupCustomerAccountByAccountCode($_SESSION['active_user_account']));
		print "You are already logged in as " . $customer->forename . " " . $customer->surname . " (".$customer->contact_id.")" . " - <a href=\"/subscriber-details/?account=" .  $_SESSION['active_user_account'] . '&surname=' . $_SESSION['active_user_surname'] . "\">Click here to go to your account</a> or <a href=\"/change-my-details/?logout\">Click here to logout.</a>";
	}

	return $form;
}

function after_submission_login($form)
{
	$custController = MyriadCustomerController::instance();
	
	$customer = $custController->getCustomerByID(lookupCustomerAccountByAccountCode(get_query_var('account')));

	if (strcasecmp($customer->surname,get_query_var('surname'))==0)
	{
		$_SESSION['active_user_account'] = get_query_var('account');
		$_SESSION['active_user_surname'] = get_query_var('surname');

	    foreach($form["fields"] as &$field)
	    {
	        if($field["id"] == 1){           
	            $field["defaultValue"] = $customer->forename;
	        }
	      if($field["id"] == 2){           
	            $field["defaultValue"] = $customer->surname;
	        }
	          if($field["id"] == 3){           
	            $field["defaultValue"] = $customer->housename;
	        }
	          if($field["id"] == 4){           
	            $field["defaultValue"] = $customer->housenumber;
	        }
	          if($field["id"] == 5){           
	            $field["defaultValue"] = $customer->street;
	        }
	          if($field["id"] == 6){           
	            $field["defaultValue"] = $customer->town;
	        }
	          if($field["id"] == 10){           
	            $field["defaultValue"] = $customer->locality;
	        }
	          if($field["id"] == 7){           
	            $field["defaultValue"] = $customer->county;
	        }
	          if($field["id"] == 9){           
	            $field["defaultValue"] = $customer->postcode;
	        }
        }
	}
	else
	{
		  $form["cssClass"]="hidden";
		  echo("<div>Sorry we could not locate your details</div>");
	}
	return $form;
}

// Populate the list of current user subscriptions
function populate_current_subs_list($value)
{
	$outputfields;
	// Check the login details
	$custController = MyriadCustomerController::instance();
	$customer = $custController->getCustomerByID(lookupCustomerAccountByAccountCode(get_query_var('account')));
	if (strcasecmp($customer->surname,get_query_var('surname'))==0)
	{
		$pubController = MyriadPublicationController::instance();
		$pubs = $pubController->getPublications();
		
		$currentsubs = $custController->getCurrentSubs($customer);
		{
			// Loop through each publication customer is subscribed to
			foreach($currentsubs['SubscriptionOrderDetail'] as $sub)
			{	
				$subfields = explode(';',$sub);
				$pub_title = "";
				foreach ($pubs as $pub)
				{
					if ($pub->title_id==$subfields[5])
					{	
						$pub_title=$pub->publication_title;
					}
				}
				
				
				$outputfields[] = $pub_title;
				$outputfields[] = date('d-m-Y' , strtotime($subfields[1]));
				$outputfields[] = $subfields[7];
			}
		}
	}
	return $outputfields;
}

	
function after_submission_update($entry, $form)
{
	// Check the user submitted the right details at login
	$custController = MyriadCustomerController::instance();
	$customer = $custController->getCustomerByID(lookupCustomerAccountByAccountCode(get_query_var('account')));
	if (strcasecmp($customer->surname,get_query_var('surname'))==0)
	// If login ok, update the Myriad entry
	{
		$custController = MyriadCustomerController::instance();
		$customer = $custController->getCustomerByID(lookupCustomerAccountByAccountCode(get_query_var('account')));
		$customer -> forename = $entry['1'];
		$customer -> surname = $entry['2'];
		$customer -> housename = $entry['3'];
		$customer -> housenumber = $entry['4'];
		$customer -> street = $entry['5'];
		$customer -> town = $entry['6'];
		$customer -> locality = $entry['10'];
		$customer -> county = $entry['7'];
		$customer -> postcode = $entry['9'];
		$custController -> updateCustomer($customer);
	}

}


function disable_notification($is_disabled, $notification, $form, $entry){
    return true;
}

function disable_post_creation($is_disabled, $form, $entry){
    return true;
}

function add_query_vars_filter( $vars ){
  $vars[] = "account";
  $vars[] = "surname";

  return $vars;
}

function myriad_login_before_checkout_form() {
	$custController = MyriadCustomerController::instance();

	if(isset($_GET['logout']))
	{
		unset($_SESSION['active_user_account']);
		unset($_SESSION['active_user_surname']);
	}

	if(isset($_SESSION['active_user_account']) && $_SESSION['active_user_account'] != ""){
		$customer = $custController->getCustomerByID(lookupCustomerAccountByAccountCode($_SESSION['active_user_account']));
		print 'You are currently logged in as ' . $customer->forename . ' ' . $customer->surname . ' (' . $customer->contact_id . ') - to continue your order a new customer please <a href="/checkout/?logout">click here to logout.</a><br /><br />';
	}
}

function custom_override_checkout_fields( $fields ) {

	$custController = MyriadCustomerController::instance();

	if(isset($_SESSION['active_user_account']) && $_SESSION['active_user_account'] != ""){
		$customer = $custController->getCustomerByID(lookupCustomerAccountByAccountCode($_SESSION['active_user_account']));

		$fields['billing']['billing_first_name']['default'] = $customer->forename;
		$fields['billing']['billing_last_name']['default'] = $customer->surname;

		$fields['billing']['billing_address_1']['default'] = $customer->housename . " " . $customer->housenumber;
		$fields['billing']['billing_address_2']['default'] = $customer->street;
		$fields['billing']['billing_city']['default'] = $customer->town;
		$fields['billing']['billing_state']['default'] = $customer->locality;
		$fields['billing']['billing_postcode']['default'] = $customer->postcode;
	}
	 
 return $fields;
}

add_action( 'woocommerce_before_checkout_form', 'myriad_login_before_checkout_form', 9 );	
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

add_filter( 'query_vars', 'add_query_vars_filter' );

// Process the login form
add_filter("gform_pre_render_" . get_option('MyriadPreLoginFormID', 2)['text_string'], "after_submission_login");
add_filter("gform_pre_render_" . get_option('MyriadLoginFormID', 2)['text_string'], "before_submission_login");

// Process customer update form
add_action("gform_after_submission_" . get_option('MyriadPreLoginFormID', 2)['text_string'], "after_submission_update", 10, 2);
add_filter("gform_field_value_currentSubs", "populate_current_subs_list");