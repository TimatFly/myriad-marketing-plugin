<?php

require_once ('MyriadSoapClient.php');

// Class handling SOAP calls for the MyriadPublication model 

class MyriadPublicationSoapClient extends MyriadSoapClient
{

    public function __construct()
    {
		parent::__construct();
    }
    
    // Gets magazine titles
    public function getTitles()
    {	
    	// Filter out anything except ProductType_ID=5 which are the actual titles
    	// otherwise we get all the promotions too.
		$parameters = $this->getSoapParamsArray(['ProductType_ID' => '5']);
    	return $this->callSoapFunction('SOAP_getTitlesForProductType', $parameters);
    }
    
    public function getRates($publication_ID)
    {
		$parameters = $this->getSoapParamsArray(['Title_ID' => $publication_ID]);
    	return $this->callSoapFunction('SOAP_getSubscriptionRatesEx', $parameters);		
    }

    public function getBookRates($publication_ID)
    {
        $parameters = $this->getSoapParamsArray(['Title_ID' => $publication_ID]);
        return $this->callSoapFunction('SOAP_getBookRatesEx', $parameters);     
    }

    public function getPromoRates($publication_ID)
    {
        $parameters = $this->getSoapParamsArray(['Publisher_ID' => 1, 'Active' => $this->castBoolean(1)]);
        return $this->callSoapFunction('SOAP_getSubsPromotions', $parameters);
    }

    public function getIssuesForTitle($title_id)
    {
        $parameters = $this->getSoapParamsArray(['Title_ID' => $title_id]);
        return $this->callSoapFunction('SOAP_getIssuesForTitle', $parameters);
    }

    public function getCollectionFrequencies(){
        $parameters = $this->getSoapParamsArray([]);
        return $this->callSoapFunction('SOAP_getCollectionFrequencies', $parameters);
    }

    public function getRenewalCampaigns(){
        $parameters = $this->getSoapParamsArray(['Publisher_ID' => 1]);
        return $this->callSoapFunction('SOAP_getRenewalCampaigns', $parameters);
    }

    public function getDespatchModes(){
        $parameters = $this->getSoapParamsArray([]);
        return $this->callSoapFunction('SOAP_getDespatchModes', $parameters);
    }

    public function getABCCodes(){
        $parameters = $this->getSoapParamsArray([]);
        return $this->callSoapFunction('SOAP_getABCCodes', $parameters);
    }
}


?>