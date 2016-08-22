<?php

require_once('MyriadModel.php');


class MyriadCustomer extends MyriadModel
{
	public $contact_id;
	public $forename;
	public $surname;
	public $postcode;
	public $company;
	public $jobtitle_id;
	public $country_id;
	public $areacode_id;
	public $currency_id;
	public $invoice_subscription;
	public $invoicefrequency_id;
	public $housename;
	public $housenumber;
	public $street;
	public $locality;
	public $town;
	public $country;
	
    public function __construct()
    {
    	// Set some hardcoded values which will be the same for all contacts
    	$this->jobtitle_id = 1;
    	$this->currency_id = 1;
    	$this->invoice_subscription = true;
    	$this->invoicefrequency_id = 1;
    	$this->areacode_id = 1;
    	$this->country_id = 1;
    }
    

}

?>