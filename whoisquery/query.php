<?php
# This file brings in a few constants we need
require_once '../dbconnect.php';
# Setup include dir
$include_path = ROOTDIR .'/modules/registrars/COCCAepp';
set_include_path($include_path . PATH_SEPARATOR . get_include_path());
# Include EPP stuff we need
require_once 'COCCAepp.php';
# Additional functions we need
require_once ROOTDIR .'/includes/functions.php';
# Include registrar functions aswell
require_once ROOTDIR .'/includes/registrarfunctions.php';


# Grab module parameters
$params = getregistrarconfigoptions('COCCAepp');

//Get the variable sent from WHMCS
$domain=$_GET["domain"];
//Is IDN enabled?	
   if (!empty($params['IDN']) && $params['IDN'] == 'on') {
      require 'Punycode.php';
      // Import Punycode
     
// Use UTF-8 as the encoding
     mb_internal_encoding('utf-8');
      $Punycode = new True\Punycode();
      $domain = $Punycode->encode($domain);
# Get client instance
	try {
		$client = _COCCAepp_Client();

		# Get list of nameservers for domain
		$result = $client->request($xml ='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <check>
         <domain:check xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
           <domain:name>'.$domain.'</domain:name>
         </domain:check>
       </check>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>
');
		# Parse XML result
		$doc = new DOMDocument();
		$doc->loadXML($result);
		

		# Pull off status
		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
		# Check the result is ok
		if($coderes != '1000') {
			$error = $msg;
			echo $error;
		}
      
      
      $availability = $doc->getElementsByTagName('name')->item(0)->getAttribute('avail');
      
      if ($availability==1)
      {
      echo "Available";
      
      }else{
	
      echo  "The domain is already registered";
      }

	} catch (Exception $e) {
		$error = $e->getMessage();
		echo $error;
	}
?>
