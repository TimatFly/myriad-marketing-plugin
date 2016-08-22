<?php

require_once ('MyriadSoapClient.php');

// Class handling SOAP calls for the MyriadPublication model 

class MyriadCustomerSoapClient extends MyriadSoapClient
{

    public function __construct()
    {
		parent::__construct();
    }
    
    // Check for existence of one or more contacts matching the criteria of the given customer object
    public function exists($customer)
    {
    	$parameters = $this->getSoapParamsArray(['Surname' => $customer->surname, 'Postcode' => $customer->postcode, 'Forename' => $customer->forename]);
    	return $this->callSoapFunction('SOAP_checkContactExists', $parameters);	
    }
    
    // Search for customers matching the criteria of the given customer object
    public function search($customer)
    {
    	$parameters = $this->getSoapParamsArray(['Surname' => $customer->surname, 'Postcode' => $customer->postcode, 'Forename' => $customer->forename]);
    	return $this->callSoapFunction('SOAP_getContacts', $parameters);	
    }
   
	public function getContactByAccountCode($accountCode){
		$parameters = $this->getSoapParamsArray(["AccountCode" => $accountCode]);
		return $this->callSoapFunction('SOAP_getContactByAccountCode', $parameters);
	}
 
    // Retrieve a single customer by ID
    public function getCustomerByID($customerID)
	{
	    $parameters = $this->getSoapParamsArray(["Contact_ID" => $customerID]);
    	return $this->callSoapFunction('SOAP_getContactDetails', $parameters);
	}
	
	 public function createCustomer($customer)
	{
	    $parameters = $this->getCustomerSoapParameters($customer);
	    // Creating a new customer so don't send the contact id as there isn't one yet
	    unset($parameters['Contact_ID']);

    	return $this->callSoapFunction('SOAP_createContact', $parameters);	
	}
	
	 public function updateCustomer($customer)
	{
	 	$parameters = $this->getCustomerSoapParameters($customer);
    	return $this->callSoapFunction('SOAP_updateContact', $parameters);	
	}
	
	// Create a customer contact record
	// $customerID : myriad ID of customer to create contact record for
	// $despatchtype : 1=email, 3=phone - these are Myriad install specific and may change for other integrations
	// $value - the value to store for this contact record
	// $primary - bool - flag as primary contact
	public function createCustomerContact($customerID,$despatchtype,$value, $primary=true)
	{
		//Split the telephone number into blocks to cast a string
		if((int)$despatchtype == 3)
			$value = trim(wordwrap(trim($value), 5, ' ', true ));

		$parameters = $this -> getSoapParamsArray([
			'Contact_ID' => $customerID,
			'DespatchType_ID' => $despatchtype,
			'ContactCommunication' => $value,
			'PrimaryUse' => $this->castBoolean($primary)
		]);
		return $this->callSoapFunction('SOAP_createContactCommunication', $parameters);	
	}
	
	// Update a customer contact record
	// $customerID : myriad ID of customer to create contact record for
	// $contactrecordID : The ID of the customer contact record to update
		// $despatchtype : 1=email, 3=phone - these are Myriad install specific and may change for other integrations
	// $value - the value to store for this contact record
	// $primary - bool - flag as primary contact
	public function updateCustomerContact($customerID, $contactrecordID, $despatchtype,$value, $primary=true)
	{
		//Split the telephone number into blocks to cast a string
		if((int)$despatchtype == 3)
			$value = trim(wordwrap(trim($value), 5, ' ', true ));

		$parameters = $this -> getSoapParamsArray([
			'Contact_ID' => $customerID,
			'DespatchType_ID' => $despatchtype,
			'ContactCommunication' => $value,
			'PrimaryUse' => $this->castBoolean($primary),
			'ContactCommunication_ID' => $contactrecordID
		]);
		return $this->callSoapFunction('SOAP_updateContactCommunication', $parameters);	
	}
	
	// Create the appropriate SOAP parameters for a contact from a customer object
	private function getCustomerSoapParameters($customer)
	{
	   $parameters = $this->getSoapParamsArray([
	    	'Surname' => $customer->surname, 
	    	'Postcode' => $customer->postcode, 
	    	'Forename' => $customer->forename,
	    	'JobTitle_ID' => $customer->jobtitle_id,
	    	'Country_ID' => $customer->country_id,
	    	'AreaCode_ID' => $customer->areacode_id,
	    	'Currency_ID' => $customer->currency_id,
	    	'InvoiceSubscription' => $this->castBoolean($customer->invoicesubscription),
	    	'InvoiceFrequency_ID' => $customer->invoicefrequency_id,
	    	'Initials' => $customer->initials,
	    	'Contact_ID' => $customer->contact_id,
	    	'HouseName' => $customer->housename,
	    	'HouseNumber' => $customer->housenumber,
	    	'Street' => $customer->street,
	    	'Locality' => $customer->locality,
	    	'Town' => $customer->town,
	    	'County' => $customer->county,
	    	'Company' => $customer->company
	    	]);	
	    return $parameters;
	    
	}
	
    public function getCurrentSubs($customer)
	{
		if(is_object($customer))
			$customer = $custom->contact_id;

	    $parameters = $this->getSoapParamsArray(['Contact_ID' => $customer]);
    	return $this->callSoapFunction('SOAP_getSubscriptionOrderDetails', $parameters);
	}
	
	   public function getCurrentSubsForID($customerID)
	{
	    $parameters = $this->getSoapParamsArray(['Contact_ID' => $customerID]);
    	return $this->callSoapFunction('SOAP_getSubscriptionOrderDetails', $parameters);
	}
	
	// Retrieves the contact_ID associated with an account code
	public function getContactForAccount($accountCode)
	{
	    $parameters = $this->getSoapParamsArray(['AccountCode' => $accountCode]);
    	return $this->callSoapFunction('SOAP_getContactbyAccountCode', $parameters);		
	}
	
}


?>
