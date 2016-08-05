<?php

// Base class handling basic SOAP functions

class MyriadSoapClient
{
	// URL of Myriad SOAP endpoint for this Wordpress site
	public $myriadSoapURL;

    public function __construct()
    {
    if (function_exists("get_option") && get_option('MyriadSoapURL'))
    	{
    		$this->myriadSoapURL= get_option('MyriadSoapURL')['text_string'];
    	}
    	else
    	{ 
    		$this->myriadSoapURL = "http://intermediagp.cloudapp.net:8100/soap";
    	}

    	$options = array ('location' => $this->myriadSoapURL,
    						'uri' => 'http://www.w3.org/2001/12/soap-envelope',
    						'trace' => 1,
    						'features' => SOAP_SINGLE_ELEMENT_ARRAYS
    						);
    }
    
    protected function callSoapFunction($function_name, $options = array())
    {
        global $wpdb, $table_prefix;
    	try
    	{
    		$xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"utf-8\"?>
<env:Envelope 
	xmlns:xsi=\"http://www.w3.org/1999/XMLSchema-instance\" 
	xmlns:xsd=\"http://www.w3.org/1999/XMLSchema\" 
	xmlns:enc=\"http://www.w3.org/2001/09/soap-encoding\" 
	env:encodingStyle=\"http://schemas.xmlsoap.org/soap/encoding/\" 
	xmlns:env=\"http://schemas.xmlsoap.org/soap/envelope/\">
</env:Envelope>");
$bodyxml = $xml -> addChild('Body');
$this->array_to_xml([$function_name => $options],$bodyxml);

// echo '<pre>' . print_r($options,1) . '</pre>';
// echo '<pre>' . print_r($xml->asXML(),1) . '</pre>';
  
     $headers = array(
                        "Content-type: text/xml;charset=\"utf-8\"",
                        "Accept: text/xml",
                        "Cache-Control: no-cache",
                        "Pragma: no-cache",
                        "SOAPAction: " .  $this->myriadSoapURL , 
                        "Content-length: ".strlen($xml->asXML()),
                    ); 
                    
           // PHP cURL  for https connection with auth
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->myriadSoapURL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml->asXML()); // the SOAP request]
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            
            // Debugging XML sent to API

//       // Log the XML we send    
//           $file = plugin_dir_path( __FILE__ ) .'log.txt';
// // Open the file to get existing content
// $current = file_get_contents($file);
// // Append a new person to the file
// $current .= $xml->asXML(). "\n\n";
// // Write the contents back to the file
// file_put_contents($file, $current);


            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			curl_setopt($ch, CURLOPT_VERBOSE, true);
          	// Send the SOAP request and store the raw xml response
   	        $response = curl_exec($ch); 
            if(is_object($wpdb)){
                $wpdb->insert( $table_prefix . 'myriad_soap_log', array( 'time' => time(), 'method' => $function_name, 'send' => $bodyxml->asXML(), 'receive' => $response ), array( '%d', '%s', '%s', '%s' ) );
            }

             //print '<pre>' . print_r($xml->asXML(),1) . '</pre>';
             //print '<pre>' . print_r($response,1) . '</pre>';

   	        // Load the XML response into a SimpleXMLElement
             $parser = simplexml_load_string($response);
                          
             // Debugging XML reponse

//       // Log the XML we get back    
//           $file = plugin_dir_path( __FILE__ ) .'log.txt';
// // Open the file to get existing content
// $current = file_get_contents($file);
// // Append a new person to the file
// $current .= $parser->asXML(). "\n\n";
// // Write the contents back to the file
// file_put_contents($file, $current);

                         
             // Locate the SOAP envelope node, take all the children of the response body and convert to array by JSON encoding then decoding
			$json = json_encode($parser->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->children()[0]);
			return $this->myriad_json_decode($json);
    	}
    	catch (Exception $e)
    	{
/*    	
  $file = plugin_dir_path( __FILE__ ) .'log.txt';
// Open the file to get existing content
$current = file_get_contents($file);
// Append a new person to the file
$current .= $e->getMessage() . "\n\n";
// Write the contents back to the file
file_put_contents($file, $current);
*/
    	} 
    }
    
    // Patched version of json_decode which changes empty arrays into blank strings
    function myriad_json_decode($json)
    {
    	$decoded = json_decode($json,TRUE);
    	foreach($decoded as $decoded_key=>$decoded_value)
    	{
    		if (is_array($decoded_value) && (sizeof($decoded_value)==0))
    		{
    			$decoded[$decoded_key]="";
    		}
    	}
    	return $decoded;
    }
    

        protected function getSoapParamsArray($params = array())
    {
    	return $params;
    }
    
    
    // Cast a boolean value to the appropriate string to be passed to the SOAP service
    protected function castBoolean($value)
    {
    	return ($value?'true':'false');
    }
    
    // function to convert (nested) array to xml
    protected function array_to_xml($array, &$xml) {
    foreach($array as $key => $value) {
        if(is_array($value)) {
            if(!is_numeric($key)){
                $subnode = $xml->addChild("$key");
                $this->array_to_xml($value, $subnode);
            }
            else{
                $subnode = $xml/*$xml->addChild("item$key")*/;
                $this->array_to_xml($value, $subnode);
            }
        }
        else {
            $xml->addChild("$key",htmlspecialchars("$value"));
        }
    }
}
}


?>