<?php

// Class representing an order in Myriad

require_once('MyriadModel.php');

class MyriadOrder extends MyriadModel
{
	public $title_id;
	public $rate_id;
	public $ordertype_id;
	public $paid;
	public $setToLive;
	//public $cardnumber;
	//public $cvv;
	//public $expirydate;
	public $amount;
	public $billingcontact;
	public $deliverycontact;
	public $dd_sortcode;
	public $dd_accountnumber;
	public $dd_accountname;
	public $cc_authcode;
}

?>