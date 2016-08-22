<?php

require_once ('MyriadSoapClient.php');

// Class handling SOAP calls for the MyriadOrder model 

class MyriadOrderSoapClient extends MyriadSoapClient
{

    public function __construct()
    {
		parent::__construct();
    }
    
        
    // Process a new order by making a SOAP request to send it to Myriad
    public function sendToMyriad($newOrder)
    {
    	$package = [
    		'OrderPackageType_ID' => 5,
    		'DespatchMode_ID' => 1
    	];

        if((int)$newOrder->promo_id > 0){
            $package['SubsPromotion_ID'] = $newOrder->promo_id;
        } else {
            $package['Rate_ID'] = $newOrder->rate_id;
            $package['Title_ID'] = $newOrder->title_id;
            if((int)$newOrder->back_issue_id > 0){
                $package['StartIssue'] =  array(
                    'Issue_ID' => $newOrder->back_issue_id
                );
            }
        }
    	
    	
    	$packages = array(
    		array('Package' =>  $package)
    	);

        if((int)$newOrder->promo_id > 0){
            foreach($newOrder->promo_issues as $issue)
            {
                $packages[] = array('Package' => $issue);
            }
        }

    	// Credit card order
    	if($newOrder->dd_sortcode=="")
    	{
            $cardpayment = [
                'PaymentType_ID' => get_option('MyriadCardPaymentID',16),
                'Amount' => $newOrder->amount,
                'Currency_ID' => 1,
                'CreditCardToken' => $newOrder->cc_authcode
            ];

            $cardpayment['PaymentReference'] = $newOrder->payment_reference;

            if(isset($newOrder->payment_type_id) && $newOrder->payment_type_id!="" && (int)$newOrder->payment_type_id > 0)
            {
                $cardpayment['PaymentType_ID'] = $newOrder->payment_type_id;
            }

        	$payment = [
        		'PrePaid' => $cardpayment
        	];
    	}
    	else
    	// Direct Debit order
    	{
    		$ddpayment = [
    			'PaymentType_ID' => 8,
    			'Amount' => $newOrder->amount,
    			'Currency_ID' => 1,
    			'BankSortCode' => preg_replace("/[^0-9]/","", $newOrder->dd_sortcode),
    			'BankAccountNumber' => $newOrder->dd_accountnumber,
    			'BankAccountName' => $newOrder->dd_accountname,
    			'CollectionFrequency_ID' => ((int)$newOrder->collection_frequency > 0 ? $newOrder->collection_frequency : 3)
    		];

	        $dd_date_status = get_option('MyriadSoapDD');
            if($dd_date_status != "" && $dd_date_status != "none" && $newOrder->dd_payment_date != ""){
                $ddpayment['FirstCollectionDate'] = $newOrder->dd_payment_date;
            }
		
			$payment = [
        		'DDPayment' => $ddpayment
        	];
    	}
    
        if($newOrder->order_type == "subscription")
        {
        	$parameters = $this->getSoapParamsArray([
            	'OrderType_ID' => 1,
            	'Paid' => $this -> castBoolean($newOrder -> paid),
            	'SetToLive' => $this -> castBoolean($newOrder -> setToLive),
            	'Packages' => $packages,
            	'Payment' => $payment,
            	'InvoiceContact_ID' => $newOrder -> billingcontact,
            	'DespatchContact_ID' => $newOrder -> deliverycontact,
        	]);

            $order_id = $this->callSoapFunction('SOAP_createOrder',$parameters);
        } 
        else if($newOrder->order_type == "circulation")
        {
            $circulationParams = [
                'Contact_ID' => $newOrder->billingcontact,
                'ApplicationDate' => date('Y-m-d'),
                'Title_ID' => $newOrder->title_id,
                'Copies' => 1,
                'DespatchMode_ID' => $newOrder->myriad_despatch_mode,
                'RenewalCampaign_ID' => $newOrder->myriad_renewal_campaign,
            ];
            if((int)$newOrder->myriad_abc_code > 0)
            {
                $circulationParams['ABC_ID'] = @$newOrder->myriad_abc_code;
            }
            $parameters = $this->getSoapParamsArray($circulationParams);
            
            $existing_order = false;
            $orders = $this->getCirculationOrderDetailsEx($newOrder->billingcontact);
            if(isset($orders['Order']))
            {
                if(isset($orders['Order']['Order_ID']))
                {
                    $existing_order = true;
                }
            }

            if($existing_order){
                $parameters = array(
                    'Order_ID' => $orders['Order']['Order_ID'],
                    'RenewalCampaign_ID' => $newOrder->myriad_renewal_campaign,
                    'ABC_ID' => @$newOrder->myriad_abc_code,
                    'ApplicationDate' => date('Y-m-d'),
                    'Copies' => 1,
                    'DespatchMode_ID' => $newOrder->myriad_despatch_mode
                );
                $order_id = $this->callSoapFunction('SOAP_reregisterCirculationOrder', $parameters);
            } else {
                $order_id = $this->callSoapFunction('SOAP_createCirculationOrder', $parameters);
            }
        }

        return $order_id;

    }

    public function getDemographicQuestions(){
        $parameters = $this->getSoapParamsArray();
        return $this->callSoapFunction('SOAP_getDemographicQuestions', $parameters);   
    }

    public function getDemographicAnswers($DemographicQuestion_ID){
        $parameters = $this->getSoapParamsArray(['DemographicQuestion_ID' => $DemographicQuestion_ID]);
        return $this->callSoapFunction('SOAP_getDemographicAnswers', $parameters);   
    }
    
    // Set demographic answer for order
    // Only works for 'select one' or 'select many' demographic answers
    public function setDemographicAnswer($orderID, $demographicID, $demographicAnswer)
	{
	    $parameters = $this->getSoapParamsArray([
            'Order_ID' => $orderID,
			'DemographicQuestion_ID' => $demographicID,
			'DemographicQuestList_IDs' => $demographicAnswer
		]);
    	return $this->callSoapFunction('SOAP_createOrderDemographic', $parameters);
	}

    // Only works for 'select one' or 'select many' demographic answers
    public function setContactDemographicAnswer($contactID, $demographicID, $demographicAnswer)
    {
        $parameters = $this->getSoapParamsArray([
            'Contact_ID' => $contactID,
            'DemographicQuestion_ID' => $demographicID,
            'DemographicQuestList_IDs' => $demographicAnswer
        ]);
        return $this->callSoapFunction('SOAP_createContactDemographic', $parameters);
    }

    public function getOrderBasic($orderID)
    {
        $parameters = $this->getSoapParamsArray(['Order_ID' => $orderID]);
        return $this->callSoapFunction('SOAP_getOrderBasic', $parameters);
    }

    public function getCirculationOrderDetailsEx($contactID)
    {
        $parameters = $this->getSoapParamsArray(['Contact_ID' => $contactID]);
        return $this->callSoapFunction('SOAP_getCirculationOrderDetailsEx', $parameters);
    }

    public function getPaymentTypes($publisherID)
    {
        $parameters = $this->getSoapParamsArray(['Publisher_ID' => $publisherID]);
        return $this->callSoapFunction('SOAP_getPaymentTypes', $parameters);
    }
    
}


?>