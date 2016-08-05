<?php
// Base class for controllers using Myriad
// Singleton

require_once(plugin_dir_path( __FILE__ ) . '../soap/MyriadCustomerSoapClient.php');
require_once(plugin_dir_path( __FILE__ ) . '../models/MyriadCustomer.php');

require_once(plugin_dir_path( __FILE__ ) . 'MyriadController.php');

class MyriadCustomerController extends MyriadController
{
	private $soapClient;
    
    public static function instance()
    {
    	static $inst;

    	if ($inst)
    	{
    		return $inst;
    	}
    	else
    	{
    		$inst = new MyriadCustomerController();
    		return $inst;
    	} 		
    }
    
	protected function __construct()
    {
        	parent::__construct();

        $this->soapClient = new MyriadCustomerSoapClient();
    }
    
    public function exists($customer)
    {
		$soapResponse =  $this->soapClient->exists($customer);
		return $soapResponse['Exists']=="True"?true:false;
    } 
    
    // Searches for all customers matching any of the criteria of the customer object passed in
    public function search($customer)
    {
    	$soapResponse =  $this->soapClient->search($customer);
    	return $soapResponse;
    }
    
	public function getContactByAccountCode($accountCode){
		$soapResponse = $this->soapClient->getContactByAccountCode($accountCode);
		if(count($soapResponse['Contacts']) > 1)
			return false; 
			return $soapResponse;
	}   
 
    // Retrieve a single customer by ID
    public function getCustomerByID($customerID)
    {
    	$soapResponse =  $this->soapClient->getCustomerByID($customerID);
		$customer = new MyriadCustomer();
		$customer->forename = $soapResponse['Forename'];
		$customer->surname = $soapResponse['Surname'];
		$customer->contact_id  = $soapResponse['Contact_ID'];
    	$customer->postcode = $soapResponse['Postcode'];
    	$customer->company = $soapResponse['Company'];
    	$customer->jobtitle_id = $soapResponse['JobTitle_ID'];
    	$customer->country_id = $soapResponse['Country_ID'];
    	$customer->areacode_id = $soapResponse['AreaCode_ID'];
    	$customer->currency_id = $soapResponse['Currency_ID'];
    	$customer->invoice_subscription = $soapResponse['Invoice_Subscription'];
    	$customer->invoicefrequency_id = $soapResponse['InvoiceFrequency_ID'];
		$customer->housename = $soapResponse['HouseName'];
    	$customer->housenumber = $soapResponse['HouseNumber'];
    	$customer->street = $soapResponse['Street'];
    	$customer->locality = $soapResponse['Locality'];
    	$customer->town = $soapResponse['Town'];
    	$customer->county = $soapResponse['County'];
		$customer->postcode = $soapResponse['PostCode'];

//IP31 3BD
    	return $customer;
    }
    
    // Create new customer in Myriad. Return contact_id if successful or false if not
    public function createCustomer($customer)
    {
    	$soapResponse =  $this->soapClient->createCustomer($customer);

    	if ($soapResponse['Success'] == true)
    	{
    		return $soapResponse['Contact_ID'];
    	}
    	else
    	{
    		return false;
    	}    	
    }
    
    public function createCustomerEmailAddress($customerID, $email)
    {
    	$soapResponse = $this->soapClient->createCustomerContact($customerID, 1, $email);
    	if ($soapResponse['Success'] == true)
    	{
    		return $soapResponse['ContactCommunication_ID'];
    	}
    	else
    	{
    		return false;
    	}    
    }
    
    public function updateCustomerEmailAddress($customerID, $contactrecordID, $email)
    {
    	$soapResponse = $this->soapClient->updateCustomerContact($customerID,$contactrecordID, 1, $email);
    	if ($soapResponse['Success'] == true)
    	{
    		return true;
    	}
    	else
    	{
    		return false;
    	}    
    }
    
  public function createCustomerPhone($customerID, $phone)
    {
    	$soapResponse = $this->soapClient->createCustomerContact($customerID, 3, $phone);
    	if ($soapResponse['Success'] == true)
    	{
    		return $soapResponse['ContactCommunication_ID'];
    	}
    	else
    	{
    		return false;
    	}    
    }

      
  public function updateCustomerPhone($customerID, $contactrecordID, $phone)
    {
    	$soapResponse = $this->soapClient->createCustomerContact($customerID, $contactrecordID, 3, $phone);
    	if ($soapResponse['Success'] == true)
    	{
    		return $soapResponse['ContactCommunication_ID'];
    	}
    	else
    	{
    		return false;
    	}    
    }  
    
 	// Update an existing customer. Return true on success
     public function updateCustomer($customer)
     {
    	$soapResponse =  $this->soapClient->updateCustomer($customer);
    	return ($soapResponse == true);
    }
    
        // Hacky, hacky function for some last minute functionality requirements
    public function getCurrentSubs($customer)
    {
     	$soapResponse =  $this->soapClient->getCurrentSubs($customer);
    	return $soapResponse;   	
    }
    
}

?>
