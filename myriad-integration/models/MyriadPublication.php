<?php

// Class modelling a publication

require_once('MyriadModel.php');

class MyriadPublication extends MyriadModel
{
	public $title_id;
	public $publication_title;
	public $active;
	public $_rates;
	public $producttype_id;
	public $current_issue_id;
    
    public function __construct()
    {
    	parent::__construct();

    }

}

?>