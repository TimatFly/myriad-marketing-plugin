<?php
// Base class for controllers using Myriad
// Singleton

require_once(plugin_dir_path( __FILE__ ) . '../soap/MyriadOrderSoapClient.php');
require_once(plugin_dir_path( __FILE__ ) . '../models/MyriadOrder.php');

require_once(plugin_dir_path( __FILE__ ) . 'MyriadController.php');


class MyriadOrderController extends MyriadController
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
    		$inst = new MyriadOrderController();
    		return $inst;
    	} 		
    }
    
	protected function __construct()
    {
        	parent::__construct();

        $this->soapClient = new MyriadOrderSoapClient();
    }
    
    // Process a new order by sending it to Myriad
    public function processOrder($newOrder)
    {
    	$returnvalue = $this -> soapClient -> sendToMyriad($newOrder);

        if (array_key_exists('Order_ID',$returnvalue))
        {
            return $returnvalue['Order_ID'];
        }
        else
        {
            return false;
        }
    }
    
    // Process a new order by sending it to Myriad
    public function getPaymentTypes($publisherID)
    {
        $paymentTypes = $this->soapClient->getPaymentTypes($publisherID);

        $return_types = array();
        foreach($paymentTypes['PaymentType'] as $paymentType)
        {
            $paymentType = explode(';', $paymentType);
            
            $paymentTypeID = $paymentType[0];
            unset($paymentType[0]);

            $paymentTypeName = implode('-', $paymentType);

            $return_types[$paymentTypeID] = $paymentTypeName;
        }

        return $return_types;
        
    }

    public function getDemographicAnswers($DemographicQuestion_ID){
        $demographicAnswers = $this->soapClient->getDemographicAnswers($DemographicQuestion_ID);

        $return_demographicAnswers = array();
        foreach($demographicAnswers['DemographicAnswer'] as $demographicAnswer)
        {
            $demographicAnswer = explode(';', $demographicAnswer);

            if($demographicAnswer[2] != "DID NOT ANSWER"){
               $return_demographicAnswers[$demographicAnswer[0]] = $demographicAnswer[2];
            }
        }

        return $return_demographicAnswers;
    }

    public function getDemographicQuestions(){
        $demographicQuestions = $this->soapClient->getDemographicQuestions();

        $return_demographicQuestion = [];
        foreach($demographicQuestions['DemographicQuestion'] as $demographicQuestion)
        {
            /*
             * 0 - Question ID
             * 1 - Data Type ID
             * 2 - Demographic Usage
             * 3 - Demographic Question
             */
            $demographicQuestion = explode(';', $demographicQuestion);

            $return_demographicQuestion[$demographicQuestion[0]] = array($demographicQuestion[1], $demographicQuestion[3]);
        }

        return $return_demographicQuestion;
    }

    // Note, the order ID is an internal ID in Myriad and not the Myriad Order Number or the WooCommerce order number (because that would be simple). The order needs to be captured from the return value of SOAP_createOrder. If not, you're a bit stuffed as I can't see any other way to retrieve it.
    public function setDemographicAnswer($orderID, $demographicID, $demographicAnswer)
    {
        return $this->soapClient->setDemographicAnswer($orderID, $demographicID, $demographicAnswer);
    }

    public function setContactDemographicAnswer($contactID, $demographicID, $demographicAnswer)
    {
        return $this->soapClient->setContactDemographicAnswer($contactID, $demographicID, $demographicAnswer);   
    }

    public function getOrderBasic($orderID)
    {
        return $this->soapClient->getOrderBasic($orderID);
    }
}

?>