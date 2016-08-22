<?php


require_once(plugin_dir_path( __FILE__ ) . '../soap/MyriadPublicationSoapClient.php');
require_once(plugin_dir_path( __FILE__ ) . '../models/MyriadPublication.php');

require_once(plugin_dir_path( __FILE__ ) . 'MyriadController.php');


class MyriadPublicationController extends MyriadController
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
    		$inst = new MyriadPublicationController();
    		return $inst;
    	} 		
    }
    
	protected function __construct()
    {
        	parent::__construct();

        $this->soapClient = new MyriadPublicationSoapClient();
    }
    
	public function getCollectionFrequencies($as_array_for_dropdown = false){
		$collectionFrequencies = $this->soapClient->getCollectionFrequencies();

		$collectionFrequencies = $collectionFrequencies['CollectionFrequency'];

		$return_array = [];
		$return_array[] = "--None Set--"; 
		foreach($collectionFrequencies as $collectionFrequency){
			$collectionFrequency = explode(';', $collectionFrequency);
			$return_array[$collectionFrequency[0]] = $collectionFrequency[1];
		}
		return $return_array;
	}

    // Get an array of MyriadPublications from Myriad
	public function getPublications($as_array_for_dropdown = false)
	{
		 $soapTitles = ($this->soapClient -> getTitles());

		 if(is_string($soapTitles["Title"])){
		 	 $soapTitles["Title"] = array($soapTitles["Title"]);
		 }

		 if($soapTitles["Title"]!=null)
		 {
		 	foreach ($soapTitles["Title"] as $soapTitle)
		 	{
		 		$publications[]=$this->publicationFromResponseItem($soapTitle);
		 	}
		 }
		 if ($as_array_for_dropdown)
		 {
		 	 foreach ($publications as $publication)
			{
				$return_array[$publication->title_id] = $publication->publication_title;
			}
			return $return_array;
		 }
		 else
		 {
			 return $publications;
			 }
	}
	
	// Get a single publication by ID
	// Returns a single publication object or null if no match
	public function publicationByID($publication_id)
	{
		$returnedPublication = null;
		$allPublications = $this->getPublications();
		foreach ($allPublications as $singlePublication)
		{
			if ($singlePublication->title_id==$publication_id)
			{
				$returnedPublication = $singlePublication;
			}
		} 
		return $returnedPublication;
	}
	
	// Create a MyriadPublication from the comma separated values returned from Myriad
	public function publicationFromResponseItem($responseItem)
	{
		$params = explode(";",$responseItem);
		$newPublication = new MyriadPublication($params[0],$params[1],$params[4],$params[2],$params[3]);
		$newPublication->title_id = $params[0];
		$newPublication->publication_title = $params[1];
		$newPublication->active = $params[4];
		$newPublication->producttype_id = $params[2];
		$newPublication->current_issue_id = $params[3];
		return $newPublication;
	}
	
	// Get the rates (subscription offers) for a given MyriadPublication
	// Optionally, output as an array for display in a dropdown selector
	public function getRates($publication, $as_array_for_dropdown = false)
	{
		$publication_rates = null;
		if ($publication->rates!=null)
		{
			$publication_rates = $publication->rates;
		}
		else
		{
			$publication->rates = $this->soapClient -> getRates($publication->title_id)["SubscriptionRate"];
			$publication_rates = $publication->rates;
		}
		if ($as_array_for_dropdown)
		{
				$return_array = [];
			$return_array[] = "--None Set--";
			foreach ($publication_rates as $publication_rate)
			{
				$return_array[$publication_rate["Rate_ID"]] = $publication_rate["Rate"];
			}
			return $return_array;
		}
		else
		{
			return $publication_rates;
		}
	}

	// Get the rates (Book Products) for a given MyriadPublication
	// Optionally, output as an array for display in a dropdown selector
	public function getBookRates($publication, $as_array_for_dropdown = false)
	{
		$book_rates = null;
		if ($publication->book_rates!=null)
		{
			$book_rates = $publication->book_rates;
		}
		else
		{
			$publication->book_rates = $this->soapClient -> getBookRates()["BookRate"];
			$book_rates = $publication->book_rates;
		}
		if ($as_array_for_dropdown)
		{
				$return_array = [];
			$return_array[] = "--None Set--";
			foreach ($book_rates as $publication_rate)
			{
				$return_array[$publication_rate["Rate_ID"]] = $publication_rate["Rate"];
			}
			return $return_array;
		}
		else
		{
			return $book_rates;
		}
	}

	// Get the rates (subscription offers) for a given MyriadPublication
	// Optionally, output as an array for display in a dropdown selector
	public function getPromoRates($publication, $as_array_for_dropdown = false)
	{
		$publication_promo_rates = null;
		if ($publication->promo_rates!=null)
		{
			$publication_promo_rates = $publication->rates;
		}
		else
		{
			$publication->promo_rates = $this->soapClient -> getPromoRates($publication->title_id)["SubsPromotion"];
			$publication_promo_rates = $publication->promo_rates;
		}
		if ($as_array_for_dropdown)
		{
			$return_array = [];
			$return_array[] = "--None Set--";
			foreach ($publication_promo_rates as $publication_promo_rate)
			{
				$return_array[$publication_promo_rate["SubsPromotion_ID"]] = $publication_promo_rate["Code"];
			}
			return $return_array;
		}
		else
		{
			return $publication_promo_rates;
		}
	}
	
	public function getIssuesForTitle($title_id){
		return $this->soapClient->getIssuesForTitle($title_id);
	}

	public function getNextIssues($title_id, $current_issue_id, $limit){
		$issues = $this->soapClient->getIssuesForTitle($title_id)['Issue'];

		$return_issues = []; $display = false; $i=0;
		foreach($issues as $issue)
		{
			$issue = explode(';', $issue);

			if($issue[0] == $current_issue_id)
				$display = true;

			if($i == $limit && $i != 0){
				break;
			} else 
			if($display){
				$return_issues[$issue[0]] = $issue[2];
				$i++;
			}
 		}

 		return $return_issues;
	}

	public function getBackIssues($title_id){
		$issues = $this->soapClient->getIssuesForTitle($title_id)['Issue'];

		$return_issues = [];
		$return_issues[] = "--None Set--";
		foreach($issues as $issue)
		{
			$issue = explode(';', $issue);
			$return_issues[$issue[0]] = $issue[2];
 		}

 		return $return_issues;
	}

	public function getRenewalCampaigns($publisher_id){
		$renewalCampaigns = $this->soapClient->getRenewalCampaigns($publisher_id);

		$return_campaigns = [];
		$return_campaigns[] = "--None Set--";
		foreach($renewalCampaigns['RenewalCampaign'] as $renewalCampaign)
		{
			$return_campaigns[$renewalCampaign['RenewalCampaign_ID']] = $renewalCampaign['RenewalCampaign'];
		}

		return $return_campaigns;
	}

	public function getDespatchModes(){
		$despatchTypes = $this->soapClient->getDespatchModes();

		$return_despatch_types = [];
		$return_despatch_types[] = "--None Set--";
		foreach($despatchTypes['DespatchMode'] as $despatchType)
		{
			$despatchType = explode(';', $despatchType);

			$return_despatch_types[$despatchType[0]] = $despatchType[3] . " - " . $despatchType[1];
		}

		return $return_despatch_types;

	}

	public function getABCCodes(){
		$abc_codes = $this->soapClient->getABCCodes();

		$return_abc_codes = array();
		foreach($abc_codes['ABCCode'] as $abc_code){
			$abc_code = explode(";", $abc_code);
			
			$return_abc_codes[$abc_code[0]] = $abc_code[1] . " - " . $abc_code[3];
		}

		return $return_abc_codes;
	}

}

?>