<?php
# Configuration array
function COCCAepp_getConfigArray() {
	$configarray = array(
		"Username" => array( "Type" => "text", "Size" => "20", "Description" => "Enter your username here" ),
		"Password" => array( "Type" => "password", "Size" => "20", "Description" => "Enter your password here" ),
		"Server" => array( "Type" => "text", "Size" => "20", "Description" => "Enter EPP Server Address" ),
		"Port" => array( "Type" => "text", "Size" => "20", "Description" => "Enter EPP Server Port" ),
		"SSL" => array( "Type" => "yesno" ),
		"Certificate" => array( "Type" => "text", "Description" => "Path of certificate .pem" )
	);
	return $configarray;
}

function COCCAepp_AdminCustomButtonArray() {
	$buttonarray = array(
		"Approve Transfer" => "ApproveTransfer",
		"Cancel Transfer Request" => "CancelTransferRequest",
		"Reject Transfer" => "RejectTransfer",		
	);
	return $buttonarray;
}

# Function to return current nameservers
function COCCAepp_GetNameservers($params) {
	# Grab variables
	$sld = $params["sld"];
	$tld = $params["tld"];
	$domain = "$sld.$tld";
	

	# Get client instance
	try {
		$client = _COCCAepp_Client();

		# Get list of nameservers for domain
		$result = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <info>
         <domain:info
          xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
           <domain:name hosts="all">'.$domain.'</domain:name>
         </domain:info>
       </info>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>
');

		# Parse XML result
		$doc = new DOMDocument();
		$doc->loadXML($result);
		logModuleCall('COCCAepp', 'GetNameservers', $xml, $result);

		# Pull off status
		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
		# Check the result is ok
		if($coderes != '1000') {
			$values["error"] = "GetNameservers/domain-info($domain): Code ($coderes) $msg";
			return $values;
		}

		# Grab hostObj array
	$ns = $doc->getElementsByTagName('hostObj');
	# Extract nameservers & build return result
	$i = 1;	$values = array();
	foreach ($ns as $nn) {
		$values["ns{$i}"] = $nn->nodeValue;
		$i++;
	}

	$values["status"] = $msg;

	return $values;

	} catch (Exception $e) {
		$values["error"] = 'GetNameservers/EPP: '.$e->getMessage();
		return $values;
	}


	return $values;
}



# Function to save set of nameservers
function COCCAepp_SaveNameservers($params) {
	# Grab variables
	$sld = $params["sld"];
	$tld = $params["tld"];
	$domain = "$sld.$tld";

# Generate array of new nameservers
     $nameservers=array();
    if (!empty($params["ns1"]))
    array_push($nameservers,$params["ns1"]);
    if (!empty($params["ns2"]))
    array_push($nameservers,$params["ns2"]);
    if(!empty($params["ns3"]))
    array_push($nameservers,$params["ns3"]);
    if(!empty($params["ns4"])) 
    array_push($nameservers,$params["ns4"]);
    if(!empty($params["ns5"])) 
    array_push($nameservers,$params["ns5"]);

	# Get client instance
	try {
		$client = _COCCAepp_Client();

		for($i=0; $i < count($nameservers); $i++) {
# Get list of nameservers for domain
	$result = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <info>
         <host:info
          xmlns:host="urn:ietf:params:xml:ns:host-1.0">
           <host:name>'.$nameservers[$i].'</host:name>
         </host:info>
       </info>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>');
	# Parse XML result
	$doc = new DOMDocument();
	$doc->preserveWhiteSpace = false;
	$doc->loadXML($result);
	logModuleCall('COCCAepp', 'GetNameservers', $xml, $result);

	# Pull off status
	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
	# Check if the nameserver exists in the registry...if not, add it
	if($coderes == '2303') {
		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <create>
         <host:create
          xmlns:host="urn:ietf:params:xml:ns:host-1.0">
           <host:name>'.$nameservers[$i].'</host:name>        
         </host:create>
       </create>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>
');



	# Parse XML result
	$doc= new DOMDocument();
	$doc->loadXML($request);
	logModuleCall('COCCAepp', 'SaveNameservers', $xml, $request);

	# Pull off status
	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
	# Check if result is ok
	if($coderes != '1000') {
		$values["error"] = "Could not Create host($nameservers[$i]): Code ($coderes) $msg";
		return $values;
	}
	}

      
       
     } 
     # Generate XML for nameservers to add
	if ($nameserver1 = $params["ns1"]) { 
		$add_hosts = '

	<domain:hostObj>'.$nameserver1.'</domain:hostObj>

';
	}
	if ($nameserver2 = $params["ns2"]) { 
		$add_hosts .= '

	<domain:hostObj>'.$nameserver2.'</domain:hostObj>

';
	}
	if ($nameserver3 = $params["ns3"]) { 
		$add_hosts .= '

	<domain:hostObj>'.$nameserver3.'</domain:hostObj>

';
	}
	if ($nameserver4 = $params["ns4"]) { 
		$add_hosts .= '

	<domain:hostObj>'.$nameserver4.'</domain:hostObj>
';
	}
	if ($nameserver5 = $params["ns5"]) { 
		$add_hosts .= '

	<domain:hostObj>'.$nameserver5.'</domain:hostObj>
';
	}
	
	# Grab list of current nameservers
	$request = $client->request($xml='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <info>
         <domain:info
          xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
           <domain:name hosts="all">'.$domain.'</domain:name>
         </domain:info>
       </info>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>
');


	# Parse XML result
	$doc= new DOMDocument();
	$doc->loadXML($request);
	# Pull off status
	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
	# Check if result is ok
	if($coderes != '1000') {
		$values["error"] = "SaveNameservers/domain-info($sld.$tld): Code ($coderes) $msg";
		return $values;
	}

	$values["status"] = $msg;

	# Generate list of nameservers to remove
	$hostlist = $doc->getElementsByTagName('hostObj');
	foreach ($hostlist as $host) {
		$rem_hosts .= '

	<domain:hostObj>'.$host->nodeValue.'</domain:hostObj>

';
	}


# Build request
	$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <update>
         <domain:update
          xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
				<domain:name>'.$sld.'.'.$tld.'</domain:name>
				<domain:add>
					<domain:ns>'.$add_hosts.' </domain:ns>
				</domain:add>								  
				<domain:rem>
					<domain:ns>'.$rem_hosts.'</domain:ns>
				</domain:rem>
			</domain:update>
		</update>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>
');


	# Parse XML result
	$doc= new DOMDocument();
	$doc->loadXML($request);
	logModuleCall('COCCAepp', 'SaveNameservers', $xml, $request);
        
	# Pull off status
	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
	# Check if result is ok
	if($coderes != '1000') {
		$values["error"] = "SaveNameservers/domain-update($sld.$tld): Code ($coderes) $msg";
		return $values;
	}

	$values['status'] = "Domain update Successful";

	
	} catch (Exception $e) {
		$values["error"] = 'SaveNameservers/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}



# NOT IMPLEMENTED
function COCCAepp_GetRegistrarLock($params) {
	# Grab variables
	$sld = $params["sld"];
	$tld = $params["tld"];
// Not Implemented

	# Get lock status
	$lock = 0;
	if ($lock=="1") {
		$lockstatus="locked";
	} else {
		$lockstatus="unlocked";
	}
	return $lockstatus;
}

# NOT IMPLEMENTED
function COCCAepp_SaveRegistrarLock($params) {
//Not Implemented
	return $values;
}

# Function to register domain
function COCCAepp_RegisterDomain($params) {
	# Grab varaibles
	$sld = $params["sld"];
	$tld = $params["tld"];
	$regperiod = $params["regperiod"];

	# Get registrant details
	$RegistrantFirstName = $params["firstname"];
	$RegistrantLastName = $params["lastname"];
	$RegistrantAddress1 = $params["address1"];
	$RegistrantAddress2 = $params["address2"];
	$RegistrantCity = $params["city"];
	$RegistrantStateProvince = $params["state"];
	$RegistrantPostalCode = $params["postcode"];
	$RegistrantCountry = $params["country"];
	$RegistrantEmailAddress = $params["email"];
	$RegistrantPhone = $params["fullphonenumber"];
	#Generate Handle
	$regHandle = generateHandle();
	# Get admin details
	$AdminFirstName = $params["adminfirstname"];
	$AdminLastName = $params["adminlastname"];
	$AdminAddress1 = $params["adminaddress1"];
	$AdminAddress2 = $params["adminaddress2"];
	$AdminCity = $params["admincity"];
	$AdminStateProvince = $params["adminstate"];
	$AdminPostalCode = $params["adminpostcode"];
	$AdminCountry = $params["admincountry"];
	$AdminEmailAddress = $params["adminemail"];
	$AdminPhone = $params["adminphonenumber"];
	#Generate Handle
	$admHandle = generateHandle();
	
	
	
       # Generate array of new nameservers
        $nameservers=array();
        if (!empty($params["ns1"]))
       array_push($nameservers,$params["ns1"]);
       if (!empty($params["ns2"]))
       array_push($nameservers,$params["ns2"]);
       if(!empty($params["ns3"]))
       array_push($nameservers,$params["ns3"]);
      if(!empty($params["ns4"])) 
      array_push($nameservers,$params["ns4"]);
      if(!empty($params["ns5"])) 
     array_push($nameservers,$params["ns5"]);

# Get client instance
	try {
		$client = _COCCAepp_Client();
        for($i=0; $i < count($nameservers); $i++) {
# Get list of nameservers for domain
	$result = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <info>
         <host:info
          xmlns:host="urn:ietf:params:xml:ns:host-1.0">
           <host:name>'.$nameservers[$i].'</host:name>
         </host:info>
       </info>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>');
	# Parse XML result
	$doc = new DOMDocument();
	$doc->preserveWhiteSpace = false;
	$doc->loadXML($result);
	logModuleCall('COCCAepp', 'GetNameservers', $xml, $result);

	# Pull off status
	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
	# Check the result is ok
	if($coderes == '2303') {
		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <create>
         <host:create
          xmlns:host="urn:ietf:params:xml:ns:host-1.0">
           <host:name>'.$nameservers[$i].'</host:name>        
         </host:create>
       </create>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>
');



	# Parse XML result
	$doc= new DOMDocument();
	$doc->loadXML($request);
	logModuleCall('COCCAepp', 'SaveNameservers', $xml, $request);


	# Pull off status
	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
	# Check if result is ok
	if($coderes != '1000') {
		$values["error"] = "Could not Create host($nameservers[$i]): Code ($coderes) $msg";
		return $values;
	}
	}

      
       
     }


// End create nameservers  /////////


	# Generate XML for nameservers
	if ($nameserver1 = $params["ns1"]) { 
		$add_hosts = '

	<domain:hostObj>'.$nameserver1.'</domain:hostObj>

';
	}
	if ($nameserver2 = $params["ns2"]) { 
		$add_hosts .= '

	<domain:hostObj>'.$nameserver2.'</domain:hostObj>

';
	}
	if ($nameserver3 = $params["ns3"]) { 
		$add_hosts .= '

	<domain:hostObj>'.$nameserver3.'</domain:hostObj>

';
	}
	if ($nameserver4 = $params["ns4"]) { 
		$add_hosts .= '

	<domain:hostObj>'.$nameserver4.'</domain:hostObj>
';
	}
	if ($nameserver5 = $params["ns5"]) { 
		$add_hosts .= '

	<domain:hostObj>'.$nameserver5.'</domain:hostObj>
';
	}

	

	# Create Registrant
	$request = $client->request($xml ='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
	<command>
		<create>
			<contact:create
          xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
				<contact:id>'.$regHandle.'</contact:id>
				<contact:postalInfo type="int">
					<contact:name>'.$RegistrantFirstName.' '.$RegistrantLastName.'</contact:name>
					<contact:org>Example Inc.</contact:org>
					<contact:addr>
						<contact:street>'.$RegistrantAddress1.'</contact:street>
						<contact:street>'.$RegistrantAddress2.'</contact:street>
						<contact:city>'.$RegistrantCity.'</contact:city>
						<contact:sp>'.$RegistrantStateProvince.'</contact:sp>
						<contact:pc>'.$RegistrantPostalCode.'</contact:pc>
						<contact:cc>'.$RegistrantCountry.'</contact:cc>
					</contact:addr>
				</contact:postalInfo>
				<contact:voice x="">'.$params["phonenumber"].'</contact:voice>
				<contact:fax></contact:fax>
				<contact:email>'.$RegistrantEmailAddress.'</contact:email>
				<contact:authInfo>
					<contact:pw>Afri-'.rand().rand().'</contact:pw>
				</contact:authInfo>
			</contact:create>
		</create>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>
');

	# Parse XML result
	$doc= new DOMDocument();
	$doc->loadXML($request);
	# Pull off status
	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
	if($coderes == '1000') {
		$values['contact'] = 'Contact Created';
	} else if($coderes == '2302') { 
		$values['contact'] = 'Contact Already exists';
	} else { 
		$values["error"] = "RegisterDomain/Reg-create($contactid): Code ($coderes) $msg";
		return $values;
	}

	$values["status"] = $msg;
	
	//Create Domain Admin
	$request = $client->request($xml ='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <create>
         <contact:create
          xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
				<contact:id>'.$admHandle.'</contact:id>
				<contact:postalInfo type="int">
					<contact:name>'.$AdminFirstName.' '.$AdminLastName.'</contact:name>
					<contact:addr>
						<contact:street>'.$AdminAddress1.'</contact:street>
						<contact:street>'.$AdminAddress2.'</contact:street>
						<contact:city>'.$AdminCity.'</contact:city>
						<contact:sp>'.$AdminStateProvince.'</contact:sp>
						<contact:pc>'.$AdminPostalCode.'</contact:pc>
						<contact:cc>'.$AdminCountry.'</contact:cc>
					</contact:addr>
				</contact:postalInfo>
				<contact:voice>'.$AdminPhone.'</contact:voice>
				<contact:fax></contact:fax>
				<contact:email>'.$AdminEmailAddress.'</contact:email>
				<contact:authInfo>
					<contact:pw>Afri-'.rand().rand().'</contact:pw>
				</contact:authInfo>
			</contact:create>
		</create>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>
');

	# Parse XML result
	$doc= new DOMDocument();
	$doc->loadXML($request);
	# Pull off status
	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
	if($coderes == '1000') {
		$values['contact'] = 'Contact Created';
	} else if($coderes == '2302') { 
		$values['contact'] = 'Contact Already exists';
	} else { 
		$values["error"] = "RegisterDomain/Admin Contact-create($contactid): Code ($coderes) $msg";
		return $values;
	}

	$values["status"] = $msg;
   //Create the Domain
	$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <create>
         <domain:create
          xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
				<domain:name>'.$sld.'.'.$tld.'</domain:name>
<domain:period unit="y">'.$regperiod.'</domain:period>
				<domain:ns>'.$add_hosts.'</domain:ns>
				<domain:registrant>'.$regHandle.'</domain:registrant>
				<domain:contact type="admin">'.$admHandle.'</domain:contact>
				<domain:contact type="tech">'.$admHandle.'</domain:contact>
				<domain:contact type="billing">'.$admHandle.'</domain:contact>
				<domain:authInfo>
					<domain:pw>COCCA'.rand().rand().'</domain:pw>
				</domain:authInfo>
			</domain:create>
		</create>
	<clTRID>'.mt_rand().mt_rand().'</clTRID>	
	</command>
</epp>
');

	$doc= new DOMDocument();
	$doc->loadXML($request);
	logModuleCall('COCCAepp', 'RegisterDomain', $xml, $request);

	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
	if($coderes != '1000') {
		$values["error"] = "RegisterDomain/domain-create($sld.$tld): Code ($coderes) $msg";
		return $values;
	}

	$values["status"] = $msg;

	return $values;

			
	} catch (Exception $e) {
		$values["error"] = 'RegisterDomain/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}



# Function to transfer a domain
function COCCAepp_TransferDomain($params) {
	# Grab variables
	$testmode = $params["TestMode"];
	$sld = $params["sld"];
	$tld = $params["tld"];

	# Domain info
	$regperiod = $params["regperiod"];
	$transfersecret = $params["transfersecret"];
	$nameserver1 = $params["ns1"];
	$nameserver2 = $params["ns2"];
	# Registrant Details
	$RegistrantFirstName = $params["firstname"];
	$RegistrantLastName = $params["lastname"];
	$RegistrantAddress1 = $params["address1"];
	$RegistrantAddress2 = $params["address2"];
	$RegistrantCity = $params["city"];
	$RegistrantStateProvince = $params["state"];
	$RegistrantPostalCode = $params["postcode"];
	$RegistrantCountry = $params["country"];
	$RegistrantEmailAddress = $params["email"];
	$RegistrantPhone = $params["fullphonenumber"];
		
	
	# Get client instance
	try {
		$client = _COCCAepp_Client();

		# Initiate transfer
		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
	<command>
		<transfer op="request">
			<domain:transfer xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
				<domain:name>'.$sld.'.'.$tld.'</domain:name>
				<domain:authInfo><domain:pw>'.$transfersecret.'</domain:pw></domain:authInfo>
			</domain:transfer>
		</transfer>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>');

		# Parse XML result
		$doc= new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'TransferDomain', $xml, $request);

		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
		# We should get a 1001 back
		if($coderes != '1001') {
			$values["error"] = "TransferDomain/domain-transfer($sld.$tld): Code ($coderes) $msg";
			return $values;
		}

		$values["status"] = $msg;

		
	} catch (Exception $e) {
		$values["error"] = 'TransferDomain/EPP: '.$e->getMessage();
		return $values;
	}

	$values["status"] = $msg;

	return $values;
}



# Function to renew domain
function COCCAepp_RenewDomain($params) {
	# Grab variables
	$sld = $params["sld"];
	$tld = $params["tld"];
	$regperiod = $params["regperiod"];

	# Get client instance
	try {
		$client = _COCCAepp_Client();

		# Send renewal request
		$request = $client->request($xml='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <info>
         <domain:info
          xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
				<domain:name>'.$sld.'.'.$tld.'</domain:name>
			</domain:info>
		</info>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>
');

		# Parse XML result
		$doc= new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'RenewDomain', $xml, $request);

		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
		if($coderes != '1000') {
			$values["error"] = "RenewDomain/domain-info($sld.$tld)): Code ($coderes) $msg";
			return $values;
		}

		$values["status"] = $msg;

		# Sanitize expiry date
		$expdate = substr($doc->getElementsByTagName('exDate')->item(0)->nodeValue,0,10);
		if (empty($expdate)) {
			$values["error"] = "RenewDomain/domain-info($sld.$tld): Domain info not available";
			return $values;
		}

		# Send request to renew
	$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <renew>
         <domain:renew
          xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
				<domain:name>'.$sld.'.'.$tld.'</domain:name>
				<domain:curExpDate>'.$expdate.'</domain:curExpDate>
				<domain:period unit="y">'.$regperiod.'</domain:period>
			</domain:renew>
		</renew>
	</command>
	<clTRID>'.mt_rand().mt_rand().'</clTRID>
</epp>
');

		# Parse XML result
		$doc= new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'RenewDomain', $xml, $request);

		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
		if($coderes != '1000') {
			$values["error"] = "RenewDomain/domain-renew($sld.$tld,$expdate): Code (".$coderes.") ".$msg;
			return $values;
		}

		$values["status"] = $msg;

	} catch (Exception $e) {
		$values["error"] = 'RenewDomain/EPP: '.$e->getMessage();
		return $values;
	}

	# If error, return the error message in the value below
	return $values;
}



# Function to grab contact details
function COCCAepp_GetContactDetails($params) {
	# Grab variables
	$sld = $params["sld"];
	$tld = $params["tld"];
	
	
	# Get client instance
	try {
		if (!isset($client)) {
			$client = _COCCAepp_Client();
		}

		# Grab domain info
		$result = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <command>
    <info>
      <domain:info xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
        <domain:name>'.$sld.'.'.$tld.'</domain:name>
      </domain:info>
    </info>
    <clTRID>'.mt_rand().mt_rand().'</clTRID>
  </command>
</epp>
');

	# Parse XML result		
	$doc= new DOMDocument();
	$doc->loadXML($result);
	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
	# Check result
	if($coderes != '1000') {
		$values["error"] = "GetContactDetails/domain-info($sld.$tld): Code (".$coderes.") ".$msg;
		return $values;
	}
   logModuleCall('COCCAepp', 'Get Contact Details', $xml, $request);

	# Grab contact Handles
	$registrant = $doc->getElementsByTagName('registrant')->item(0)->nodeValue;
	if (empty($registrant)) {
		$values["error"] = "GetContactDetails/domain-info($sld.$tld): Registrant info not available";
		return $values;
	}
	$b=count($doc->getElementsByTagName('contact'));
	$domaininfo=array();
    for ($i=0; $i<=2; $i++) {
    $x=$doc->getElementsByTagName('contact')->item($i);
    if(!empty($x)){
   // $domaininfo=$doc->getElementsByTagName('contact')->item($i)->getAttribute('type')=$doc->getElementsByTagName('contact')->item($i)->nodeValue;
    $domaininfo[$doc->getElementsByTagName('contact')->item($i)->getAttribute('type')]=$doc->getElementsByTagName('contact')->item($i)->nodeValue;
}
else{
break;
}}
    $Contacts["admin"]=$domaininfo["admin"];
    $Contacts["tech"]=$domaininfo["tech"];
  	$Contacts["billing"]=$domaininfo["billing"];

     
	# Grab contact info
	$result = $client->request($xml = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
                                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                                xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
                                    <command>
                                        <info>
                                            <contact:info xmlns:contact="urn:ietf:params:xml:ns:contact-1.0"
                                            xsi:schemaLocation="urn:ietf:params:xml:ns:contact-1.0
                                            contact-1.0.xsd">
                                                <contact:id>'.$registrant.'</contact:id>
                                            </contact:info>
                                        </info>
                                        <clTRID>'.mt_rand().mt_rand().'</clTRID>
                                    </command>
                            </epp>
');

	# Parse XML result
	$doc= new DOMDocument();
	$doc->loadXML($result);
	logModuleCall('COCCAepp', 'GetContactDetails', $xml, $result);

	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
	# Check result
	if($coderes != '1000') {
		$values["error"] = "GetContactDetails/contact-registrant($registrant): Code (".$coderes.") ".$msg;
		return $values;
	}

	# Setup return values
	$values["Registrant"]["Contact Name"] = $doc->getElementsByTagName('name')->item(0)->nodeValue;
	$values["Registrant"]["Organisation"] = $doc->getElementsByTagName('org')->item(0)->nodeValue;
	$values["Registrant"]["Address line 1"] = $doc->getElementsByTagName('street')->item(0)->nodeValue;
	$values["Registrant"]["Address line 2"] = $doc->getElementsByTagName('street')->item(1)->nodeValue;
	$values["Registrant"]["TownCity"] = $doc->getElementsByTagName('city')->item(0)->nodeValue;
	$values["Registrant"]["State"] = $doc->getElementsByTagName('sp')->item(0)->nodeValue;
	$values["Registrant"]["Zip code"] = $doc->getElementsByTagName('pc')->item(0)->nodeValue;
	$values["Registrant"]["Country Code"] = $doc->getElementsByTagName('cc')->item(0)->nodeValue;
	$values["Registrant"]["Phone"] = $doc->getElementsByTagName('voice')->item(0)->nodeValue;
	$values["Registrant"]["Email"] = $doc->getElementsByTagName('email')->item(0)->nodeValue;
	
	#Get Org, Adm and Tech Contacts
    foreach ($Contacts as $type => $value) {
    if ($value!=""){
   $request =  $client->request($xml ='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <info>
         <contact:info
          xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
          <contact:id>'.$value.'</contact:id>
         </contact:info>
       </info>
    <clTRID>'.mt_rand().mt_rand().'</clTRID>
   </command>
   </epp>');

                    

                   # Parse XML result
	$doc= new DOMDocument();
	$doc->loadXML($request);
	logModuleCall('COCCAepp', 'GetContactDetails', $xml, $request);

	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;

	# Check results
                    if($coderes != '1000') {
			$values["error"] = "GetContactDetails/contact-info($type): Code (".$coderes.") ".$msg;
		return $values;
                    }
    
	$values["$type"]["Contact Name"] = $doc->getElementsByTagName('name')->item(0)->nodeValue;
	$values["$type"]["Organisation"] = $doc->getElementsByTagName('org')->item(0)->nodeValue;
	$values["$type"]["Address line 1"] = $doc->getElementsByTagName('street')->item(0)->nodeValue;
	$values["$type"]["Address line 2"] = $doc->getElementsByTagName('street')->item(1)->nodeValue;
	$values["$type"]["TownCity"] = $doc->getElementsByTagName('city')->item(0)->nodeValue;
	$values["$type"]["State"] = $doc->getElementsByTagName('sp')->item(0)->nodeValue;
	$values["$type"]["Zip code"] = $doc->getElementsByTagName('pc')->item(0)->nodeValue;
	$values["$type"]["Country Code"] = $doc->getElementsByTagName('cc')->item(0)->nodeValue;
	$values["$type"]["Phone"] = $doc->getElementsByTagName('voice')->item(0)->nodeValue;
	$values["$type"]["Email"] = $doc->getElementsByTagName('email')->item(0)->nodeValue;
                    }else{
                    $values["$type"]["Contact Name"] = "";
	$values["$type"]["Organisation"] = "";
	$values["$type"]["Address line 1"] = "";
	$values["$type"]["Address line 2"] = "";
	$values["$type"]["TownCity"] = "";
	$values["$type"]["State"] = "";
	$values["$type"]["Zip code"] = "";
	$values["$type"]["Country Code"] = "";
	$values["$type"]["Phone"] = "";
	$values["$type"]["Email"] = "";
                    }
                    }  

	return $values;

} catch (Exception $e) {
		$values["error"] = 'GetContactDetails/EPP: '.$e->getMessage();
		return $values;
	}
}



# Function to save contact details
function COCCAepp_SaveContactDetails($params) {
	# Grab variables
	$tld = $params["tld"];
	$sld = $params["sld"];
	
	
	
		# Registrant details
	$registrant_name = $params["contactdetails"]["Registrant"]["Contact Name"];
	$registrant_org = $params["contactdetails"]["Registrant"]["Organisation"];
	$registrant_address1 =  $params["contactdetails"]["Registrant"]["Address line 1"];
	$registrant_address2 = $params["contactdetails"]["Registrant"]["Address line 2"];
	$registrant_town = $params["contactdetails"]["Registrant"]["TownCity"];
	$registrant_state = $params["contactdetails"]["Registrant"]["State"];
	$registrant_zipcode = $params["contactdetails"]["Registrant"]["Zip code"];
	$registrant_countrycode = $params["contactdetails"]["Registrant"]["Country Code"];
	$registrant_phone = $params["contactdetails"]["Registrant"]["Phone"];
	#$registrant_fax = '',
	$registrant_email = $params["contactdetails"]["Registrant"]["Email"];
    //Billing Details
    # Billing Details
    $billing_name = $params["contactdetails"]["billing"]["Contact Name"];
    $billing_org = $params["contactdetails"]["billing"]["Organisation"];
    $billing_address1 =  $params["contactdetails"]["billing"]["Address line 1"];
    $billing_address2 = $params["contactdetails"]["billing"]["Address line 2"];
    $billing_town = $params["contactdetails"]["billing"]["TownCity"];
    $billing_state = $params["contactdetails"]["billing"]["State"];
    $billing_zipcode = $params["contactdetails"]["billing"]["Zip code"];
    $billing_countrycode = $params["contactdetails"]["billing"]["Country Code"];
    $billing_phone = $params["contactdetails"]["billing"]["Phone"];
    #$registrant_fax = '',
    $billing_email = $params["contactdetails"]["billing"]["Email"];
    
    //Admin Details
    $admin_name = $params["contactdetails"]["admin"]["Contact Name"];
    $admin_org = $params["contactdetails"]["admin"]["Organisation"];
    $admin_address1 =  $params["contactdetails"]["admin"]["Address line 1"];
    $admin_address2 = $params["contactdetails"]["admin"]["Address line 2"];
    $admin_town = $params["contactdetails"]["admin"]["TownCity"];
    $admin_state = $params["contactdetails"]["admin"]["State"];
    $admin_zipcode = $params["contactdetails"]["admin"]["Zip code"];
    $admin_countrycode = $params["contactdetails"]["admin"]["Country Code"];
    $admin_phone = $params["contactdetails"]["admin"]["Phone"];
    #$registrant_fax = '',
    $admin_email = $params["contactdetails"]["admin"]["Email"];
    
    $tech_name = $params["contactdetails"]["tech"]["Contact Name"];
    $tech_org = $params["contactdetails"]["tech"]["Organisation"];
    $tech_address1 =  $params["contactdetails"]["tech"]["Address line 1"];
    $tech_address2 = $params["contactdetails"]["tech"]["Address line 2"];
    $tech_town = $params["contactdetails"]["tech"]["TownCity"];
    $tech_state = $params["contactdetails"]["tech"]["State"];
    $tech_zipcode = $params["contactdetails"]["tech"]["Zip code"];
    $tech_countrycode = $params["contactdetails"]["tech"]["Country Code"];
    $tech_phone = $params["contactdetails"]["tech"]["Phone"];
    #$registrant_fax = '',
    $tech_email = $params["contactdetails"]["tech"]["Email"];


	# Get client instance
	try {
		$client = _COCCAepp_Client();

		# Grab domain info
		$request = $client->request($xml = '
<epp:epp xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:epp="urn:ietf:params:xml:ns:epp-1.0"
		xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
	<epp:command>
		<epp:info>
			<domain:info xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
				<domain:name hosts="all">'.$sld.'.'.$tld.'</domain:name>
			</domain:info>
		</epp:info>
	</epp:command>
</epp:epp>
');
	# Parse XML	result
	$doc= new DOMDocument();
	$doc->loadXML($request);
	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
	if($coderes != '1000') {
		$values["error"] = "SaveContactDetails/domain-info($sld.$tld): Code (".$coderes.") ".$msg;
		return $values;
	}

	$values["status"] = $msg;
# Grab Registrant contact Handles
	$registrant = $doc->getElementsByTagName('registrant')->item(0)->nodeValue;
	if (empty($registrant)) {
		$values["error"] = "GetContactDetails/domain-info($sld.$tld): Registrant info not available";
		return $values;
	}
	$domaininfo=array();
    for ($i=0; $i<=2; $i++) {
    $x=$doc->getElementsByTagName('contact')->item($i);
    if(!empty($x)){
    $domaininfo[$doc->getElementsByTagName('contact')->item($i)->getAttribute('type')]=$doc->getElementsByTagName('contact')->item($i)->nodeValue;
}
else{
break;
}}
      $Contacts["admin"]=$domaininfo["admin"];
      $Contacts["tech"]=$domaininfo["tech"];
  	$Contacts["billing"]=$domaininfo["billing"];
  	 	// Build contacts to remove
  if(isset($Contacts["admin"])){
		$rem_conts .= '

	<domain:contact type="admin">'.$Contacts["admin"].'</domain:contact>
';
	}
	if(isset($Contacts["tech"])){
		$rem_conts .= '

	<domain:contact type="tech">'.$Contacts["tech"].'</domain:contact>
';
	}
	if(isset($Contacts["billing"])){
		$rem_conts .= '

	<domain:contact type="billing">'.$Contacts["billing"].'</domain:contact>
';
	}
	# Save Registrant contact details
	$request = $client->request($xml ='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <update>
         <contact:update
          xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
				<contact:id>'.$registrant.'</contact:id>
				<contact:chg>
					<contact:postalInfo type="int">
						<contact:name>'.$registrant_name.'</contact:name>
						<contact:org>'.$registrant_org.'</contact:org>
						<contact:addr>
							<contact:street>'.$registrant_address1.'</contact:street>
							<contact:street>'.$registrant_address2.'</contact:street>
							<contact:city>'.$registrant_town.'</contact:city>
							<contact:sp>'.$registrant_state.'</contact:sp>
							<contact:pc>'.$registrant_zipcode.'</contact:pc>
							<contact:cc>'.$registrant_countrycode.'</contact:cc>
						</contact:addr>
						</contact:postalInfo>
						<contact:voice>'.$registrant_phone.'</contact:voice>
						
						<contact:email>'.$registrant_email.'</contact:email>
				</contact:chg>
			</contact:update>
		</update>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>
');

	# Parse XML result
	$doc= new DOMDocument();
	$doc->loadXML($request);
	logModuleCall('COCCAepp', 'SaveContactDetails', $xml, $request);

	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
	if($coderes != '1000') {
		$values["error"] = "SaveContactDetails/contact-update(registrant): Code ($coderes) $msg";
		return $values;
	}
	
	# Save Admin contact details
	//Create Domain Admin
	
	$admHandle = generateHandle();
	$result = $client->request($xml ='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <create>
         <contact:create
          xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
				<contact:id>'.$admHandle.'</contact:id>
				<contact:postalInfo type="int">
					<contact:name>'.$admin_name.'</contact:name>
					<contact:org>'.$admin_org.'</contact:org>
					<contact:addr>
						<contact:street>'.$admin_address1.'</contact:street>
						<contact:street>'.$admin_address2.'</contact:street>
						<contact:city>'.$admin_town.'</contact:city>
						<contact:sp>'.$admin_state.'</contact:sp>
						<contact:pc>'.$admin_zipcode.'</contact:pc>
						<contact:cc>'.$admin_countrycode.'</contact:cc>
					</contact:addr>
				</contact:postalInfo>
				<contact:voice x="">'.$admin_phone.'</contact:voice>
				
				<contact:email>'.$admin_email.'</contact:email>
				<contact:authInfo>
					<contact:pw></contact:pw>
				</contact:authInfo>
			</contact:create>
		</create>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>
');

	# Parse XML result
	$doc = new DOMDocument();
	$doc->loadXML($request);
	logModuleCall('COCCAepp', 'SaveContactDetails', $xml, $request);

	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
	
	if($coderes != '1000') {
		$values["error"] = "SaveContactDetails/contact-update(Admincontact): Code ($coderes) $msg";
		return $values;
	}
	
	//Create Billing Contacts
	$bilHandle = generateHandle();
	
	$request = $client->request('<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <create>
         <contact:create
          xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
				<contact:id>'.$bilHandle.'</contact:id>
				<contact:postalInfo type="int">
					<contact:name>'.$billing_name.' </contact:name>
					 <contact:org>'.$billing_org.'</contact:org>
					<contact:addr>
						<contact:street>'.$billing_address1.'</contact:street>
						<contact:street>'.$billing_address1.'</contact:street>
						<contact:city>'.$billing_town.'</contact:city>
						<contact:sp>'.$billing_state.'</contact:sp>
						<contact:pc>'.$billing_zipcode.'</contact:pc>
						<contact:cc>'.$billing_countrycode.'</contact:cc>
					</contact:addr>
				</contact:postalInfo>
				<contact:voice>'.$billing_phone.'</contact:voice>
				
				<contact:email>'.$billing_email.'</contact:email>
				<contact:authInfo>
					<contact:pw>Afri-'.rand().rand().'</contact:pw>
				</contact:authInfo>
			</contact:create>
		</create>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>
');

	# Parse XML result
	$doc = new DOMDocument();
	$doc->loadXML($request);
	logModuleCall('COCCAepp', 'SaveContactDetails', $xml, $request);

	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
	if($coderes != '1000') {
		$values["error"] = "SaveContactDetails/contact-update(Billing contact): Code ($coderes) $msg";
		return $values;
	}


	# Save Technical contact details
	//Create Domain Technical Contacts
	$tecHandle = generateHandle();
	
$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <create>
         <contact:create
          xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
				<contact:id>'.$tecHandle.'</contact:id>
				<contact:postalInfo type="int">
					<contact:name>'.$tech_name.'</contact:name>
					<contact:org>'.$tech_org.'</contact:org>
					<contact:addr>
						<contact:street>'.$tech_address1.'</contact:street>
						<contact:street>'.$tech_address2.'</contact:street>
						<contact:city>'.$tech_town.'</contact:city>
						<contact:sp>'.$tech_state.'</contact:sp>
						<contact:pc>'.$tech_zipcode.'</contact:pc>
						<contact:cc>'.$tech_countrycode.'</contact:cc>
					</contact:addr>
				</contact:postalInfo>
				<contact:voice>'.$tech_phone.'</contact:voice>
				
				<contact:email>'.$tech_email.'</contact:email>
				<contact:authInfo>
					<contact:pw>Afri-'.rand().rand().'</contact:pw>
				</contact:authInfo>
			</contact:create>
		</create>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>
');

	# Parse XML result
	$doc= new DOMDocument();
	$doc->loadXML($request);
	logModuleCall('COCCAepp', 'SaveContactDetails', $xml, $request);

	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
	if($coderes != '1000') {
		$values["error"] = "SaveContactDetails/contact-update(Technical Contact): Code ($coderes) $msg";
		return $values;
	}
	# change the domain contacts
	
	$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
  <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
    <command>
      <update>
        <domain:update
         xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
           <domain:name>'.$sld.'.'.$tld.'</domain:name>
           <domain:add>
             <domain:contact type="tech">'.$tecHandle.'</domain:contact>
             <domain:contact type="admin">'.$admHandle.'</domain:contact>
             <domain:contact type="billing">'.$bilHandle.'</domain:contact>
           </domain:add>
           <domain:rem>
             '.$rem_conts.'
           </domain:rem>
            </domain:update>
       </update>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>

');
$doc= new DOMDocument();
	$doc->loadXML($request);
	logModuleCall('COCCAepp', 'SaveContactDetails', $xml, $request);

	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
	if($coderes != '1000') {
		$values["error"] = "Domain contact update error: Code ($coderes) $msg";
		return $values;
	}

	$values["status"] = $msg;

	
	} catch (Exception $e) {
		$values["error"] = 'SaveContactDetails/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}



# NOT IMPLEMENTED
function COCCAepp_GetEPPCode($params) {
	# Grab variables
	$username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$sld = $params["sld"];
	$tld = $params["tld"];

	$values["eppcode"] = '';

	# If error, return the error message in the value below
	//$values["error"] = 'error';
	return $values;
}



# Function to register nameserver
function COCCAepp_RegisterNameserver($params) {
	# Grab variables
	$username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$sld = $params["sld"];
	$tld = $params["tld"];
	$nameserver = $params["nameserver"];
	$ipaddress = $params["ipaddress"];
	

	# Grab client instance
	try {
		$client = _COCCAepp_Client();

		# Register nameserver
		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <create>
         <host:create
          xmlns:host="urn:ietf:params:xml:ns:host-1.0">
           <host:name>'.$nameserver.'</host:name>
           <host:addr ip="v4">'.$ipaddress.'</host:addr>           
         </host:create>
       </create>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>
');
		# Parse XML result
		$doc= new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'RegisterNameserver', $xml, $request);

		# Pull off status
		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
		logModuleCall('COCCAepp', 'SaveHost', $xml, $request);
		# Check if result is ok
		if($coderes != '1001') {
			$values["error"] = "RegisterNameserver($nameserver): Code ($coderes) $msg";
			return $values;
		}

		$values['status'] = $msg;

	} catch (Exception $e) {
		$values["error"] = 'SaveNameservers/EPP: '.$e->getMessage();
		return $values;
	}


	return $values;
}



# Modify nameserver
function COCCAepp_ModifyNameserver($params) {
	# Grab variables
	$username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$tld = $params["tld"];
	$sld = $params["sld"];
	$nameserver = $params["nameserver"];
	$currentipaddress = $params["currentipaddress"];
	$newipaddress = $params["newipaddress"];

	
	# Grab client instance
	try {
		$client = _COCCAepp_Client();

		# Modify nameserver
		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <update>
         <host:update
          xmlns:host="urn:ietf:params:xml:ns:host-1.0">
           <host:name>'.$nameserver.'</host:name>
           <host:add>
             <host:addr ip="v4">'.$newipaddress.'</host:addr>
               </host:add>
           <host:rem>
             <host:addr ip="v4">'.$currentipaddress.'</host:addr>
           </host:rem>           
         </host:update>
       </update>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>
');
		# Parse XML result
		$doc= new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'ModifyNameserver', $xml, $request);

		# Pull off status
		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
		# Check if result is ok
		if($coderes != '1001') {
			$values["error"] = "ModifyNameserver/domain-update($nameserver): Code ($coderes) $msg";
			return $values;
		}

		$values['status'] = $msg;

	} catch (Exception $e) {
		$values["error"] = 'SaveNameservers/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}


# Delete nameserver
function COCCAepp_DeleteNameserver($params) {
	# Grab variables
	$username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$tld = $params["tld"];
	$sld = $params["sld"];
	$nameserver = $params["nameserver"];
	

	# Grab client instance
	try {
		$client = _COCCAepp_Client();

		

		# Delete nameserver
		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <delete>
         <host:delete
          xmlns:host="urn:ietf:params:xml:ns:host-1.0">
           <host:name>'.$nameserver.'</host:name>
         </host:delete>
       </delete>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>
');
		# Parse XML result
		$doc= new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'DeleteNameserver', $xml, $request);

		# Pull off status
		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
		# Check if result is ok
		if($coderes != '1001') {
			$values["error"] = "DeleteNameserver/domain-update($sld.$tld): Code ($coderes) $msg";
			return $values;
		}

		$values['status'] = $msg;

	} catch (Exception $e) {
		$values["error"] = 'SaveNameservers/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}


# Function to return meaningful message from response code
function _COCCAepp_message($code) {

	return "Code $code";

}

# Function to create internal EPP request
function _COCCAepp_Client() {
	# Setup include dir
	$include_path = ROOTDIR . '/modules/registrars/COCCAepp';
	set_include_path($include_path . PATH_SEPARATOR . get_include_path());
	# Include EPP stuff we need
	require_once 'Net/EPP/Client.php';
	require_once 'Net/EPP/Protocol.php';

	# Grab module parameters
	$params = getregistrarconfigoptions('COCCAepp');
	# Check if module parameters are sane
	if (empty($params['Username']) || empty($params['Password'])) {
		throw new Exception('System configuration error(1), please contact your provider');
	}
	
	# Create SSL context
	$context = stream_context_create();
	# Are we using ssl?
	$use_ssl = false;
	if (!empty($params['SSL']) && $params['SSL'] == 'on') {
		$use_ssl = true;
	}
	# Set certificate if we have one
	if ($use_ssl && !empty($params['Certificate'])) {
		if (!file_exists($params['Certificate'])) {
			throw new Exception("System configuration , please contact your provider");
		}
		# Set client side certificate
		stream_context_set_option($context, 'ssl', 'local_cert', $params['Certificate']);
	}

	# Create EPP client
	$client = new Net_EPP_Client();

	# Connect
	$res = $client->connect($params['Server'], $params['Port'], 10, $use_ssl, $context);

	# Perform login
	$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <login>
         <clID>'.$params['Username'].'</clID>
         <pw>'.$params['Password'].'</pw>
         <options>
           <version>1.0</version>
           <lang>en</lang>
         </options>
         <svcs>
           <objURI>urn:ietf:params:xml:ns:obj1</objURI>
           <objURI>urn:ietf:params:xml:ns:obj2</objURI>
           <objURI>urn:ietf:params:xml:ns:obj3</objURI>
           <svcExtension>
             <extURI>http://custom/obj1ext-1.0</extURI>
           </svcExtension>
         </svcs>
       </login>
       <clTRID>'.mt_rand().mt_rand().'</clTRID>
     </command>
   </epp>
');
	logModuleCall('COCCAepp', 'Connect', $xml, $request);

	return $client;
}

function COCCAepp_TransferSync($params) {
	$domainid = $params['domainid'];
	$domain = $params['domain'];
	$sld = $params['sld'];
	$tld = $params['tld'];
	$registrar = $params['registrar'];
	$regperiod = $params['regperiod'];
	$status = $params['status'];
	$dnsmanagement = $params['dnsmanagement'];
	$emailforwarding = $params['emailforwarding'];
	$idprotection = $params['idprotection'];

	# Other parameters used in your _getConfigArray() function would also be available for use in this function

	# Grab domain info
	try {
		$client = _COCCAepp_Client();
		# Grab domain info
		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <info>
         <domain:info
          xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
				<domain:name hosts="all">'.$sld.'.'.$tld.'</domain:name>
			</domain:info>
		</info>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>
');

		$doc= new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'TransferSync', $xml, $request);

		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
		# Check result
		if ($coderes == '2303') {
			$values['error'] = "TransferSync/domain-info($domain): Domain not found";
			return $values;
		} else if ($coderes != '1000') {
			$values['error'] = "TransferSync/domain-info($domain): Code("._COCCAepp_message($coderes).") $msg";
			return $values;
		}

		# Check if we can get a status back
		if ($doc->getElementsByTagName('status')->item(0)) {
			$statusres = $doc->getElementsByTagName('status')->item(0)->getAttribute('s');
			$createdate = substr($doc->getElementsByTagName('crDate')->item(0)->nodeValue,0,10);
			$nextduedate = substr($doc->getElementsByTagName('exDate')->item(0)->nodeValue,0,10);
		} else {
			$values['error'] = "TransferSync/domain-info($domain): Domain not found";
			return $values;
		}

		$values['status'] = $msg;

		# Check status and update
		if ($statusres == "ok") {
			$values['completed'] = true;

		} else {
			$values['error'] = "TransferSync/domain-info($domain): Unknown status code '$statusres'";
		}

		$values['expirydate'] = $nextduedate;

	} catch (Exception $e) {
		$values["error"] = 'TransferSync/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}

function COCCAepp_Sync($params) {
	$domainid = $params['domainid'];
	$domain = $params['domain'];
	$sld = $params['sld'];
	$tld = $params['tld'];
	$registrar = $params['registrar'];
	$regperiod = $params['regperiod'];
	$status = $params['status'];
	$dnsmanagement = $params['dnsmanagement'];
	$emailforwarding = $params['emailforwarding'];
	$idprotection = $params['idprotection'];

	# Other parameters used in your _getConfigArray() function would also be available for use in this function

	# Grab domain info
	try {
		$client = _COCCAepp_Client();
		# Grab domain info
		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <info>
         <domain:info
          xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
				<domain:name>'.$sld.'.'.$tld.'</domain:name>
			</domain:info>
		</info>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>
');

		$doc= new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'Sync', $xml, $request);

		# Initialize the owningRegistrar which will contain the owning registrar
		# The <domain:clID> element contains the unique identifier of the registrar that owns the domain.
		$owningRegistrar = NULL;

		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
		# Check result
		if ($coderes == '2303') {
			# Code 2303, domain not found
			$values['error'] = "TransferSync/domain-info($domain): Domain not found";
			return $values;
		} else if ($coderes == '1000') {
			# Code 1000, success
			if (
				$doc->getElementsByTagName('infData') &&
				$doc->getElementsByTagName('infData')->item(0)->getElementsByTagName('ns')->item(0) &&
				$doc->getElementsByTagName('infData')->item(0)->getElementsByTagName('clID')
			) {
				$owningRegistrar = $doc->getElementsByTagName('infData')->item(0)->getElementsByTagName('clID')->item(0)->nodeValue;
			}
		} else {
			$values['error'] = "Sync/domain-info($domain): Code("._COCCAepp_message($coderes).") $msg";
			return $values;
		}

		# Check if we can get a status back
		if ($doc->getElementsByTagName('status')->item(0)) {
			$statusres = $doc->getElementsByTagName('status')->item(0)->getAttribute('s');
			$createdate = substr($doc->getElementsByTagName('crDate')->item(0)->nodeValue,0,10);
			$nextduedate = substr($doc->getElementsByTagName('exDate')->item(0)->nodeValue,0,10);
		} else if (!empty($owningRegistrar) && $owningRegistrar != $username) {
			# If we got an owningRegistrar back and we're not the owning registrar, return error
			$values['error'] = "Sync/domain-info($domain): Domain belongs to a different registrar, (owning registrar: $owningRegistrar, your registrar: $username)";
			return $values;
		} else {
			$values['error'] = "Sync/domain-info($domain): Domain not found";
			return $values;
		}

		$values['status'] = $msg;

		# Check status and update
		if ($statusres == "ok") {
			$values['active'] = true;

		} elseif ($statusres == "pendingUpdate") {

		} elseif ($statusres == "serverHold") {

		} elseif ($statusres == "expired" || $statusres == "pendingDelete" || $statusres == "inactive") {
			$values['expired'] = true;

		} else {
			$values['error'] = "Sync/domain-info($domain): Unknown status code '$statusres' ";
		}

		$values['expirydate'] = $nextduedate;

	} catch (Exception $e) {
		$values["error"] = 'Sync/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}


function COCCAepp_RequestDelete($params) {
	$sld = $params['sld'];
	$tld = $params['tld'];
	
	# Grab domain info
	try {
		$client = _COCCAepp_Client();

		# Grab domain info
		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
	<epp:command>
		<epp:delete>
			<domain:delete xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
				<domain:name>'.$sld.'.'.$tld.'</domain:name>
			</domain:delete>
		</epp:delete>
		 <clTRID>'.mt_rand().mt_rand().'</clTRID>
	</epp:command>
</epp:epp>
');

		# Parse XML result
		$doc = new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'RequestDelete', $xml, $request);

		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;

		# Check result
		if($coderes != '1001') {
			$values['error'] = 'RequestDelete/domain-info('.$sld.'.'.$tld.'): Code('._COCCAepp_message($coderes).") $msg";
			return $values;
		}

		$values['status'] = $msg;

	} catch (Exception $e) {
		$values["error"] = 'RequestDelete/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}

function COCCAepp_ApproveTransfer($params) {
	$sld = $params['sld'];
	$tld = $params['tld'];
	
	# Grab domain info
	try {
		$client = _COCCAepp_Client();

		# Grab domain info
		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
	<command>
		<transfer op="approve">
			<domain:transfer xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
				<domain:name>'.$sld.'.'.$tld.'</domain:name>
			</domain:transfer>
		</transfer>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>
');

		# Parse XML result
		$doc = new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'ApproveTransfer', $xml, $request);

		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;

		# Check result
		if($coderes != '1000') {
			$values['error'] = 'ApproveTransfer/domain-info('.$sld.'.'.$tld.'): Code('._COCCAepp_message($coderes).") $msg";
			return $values;
		}

		$values['status'] = $msg;

	} catch (Exception $e) {
		$values["error"] = 'ApproveTransfer/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}


function COCCAepp_CancelTransferRequest($params) {
	$sld = $params['sld'];
	$tld = $params['tld'];
	
	# Grab domain info
	try {
		$client = _COCCAepp_Client();

		# Grab domain info
		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
	<command>
		<transfer op="cancel">
			<domain:transfer xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
				<domain:name>'.$sld.'.'.$tld.'</domain:name>
			</domain:transfer>
		</transfer>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>
');

		# Parse XML result
		$doc = new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'CancelTransferRequest', $xml, $request);

		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;

		# Check result
		if($coderes != '1000') {
			$values['error'] = 'CancelTransferRequest/domain-info('.$sld.'.'.$tld.'): Code('._COCCAepp_message($coderes).") $msg";
			return $values;
		}

		$values['status'] = $msg;

	} catch (Exception $e) {
		$values["error"] = 'CancelTransferRequest/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}


function COCCAepp_RejectTransfer($params) {
	$sld = $params['sld'];
	$tld = $params['tld'];
	
	# Grab domain info
	try {
		$client = _COCCAepp_Client();

		# Grab domain info
		$request = $client->request($xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
	<command>
		<transfer op="reject">
			<domain:transfer xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
				<domain:name>'.$sld.'.'.$tld.'</domain:name>
			</domain:transfer>
		</transfer>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
</epp>
');

		# Parse XML result
		$doc = new DOMDocument();
		$doc->loadXML($request);
		logModuleCall('COCCAepp', 'RejectTransfer', $xml, $request);

		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
		$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;

		# Check result
		if($coderes != '1000') {
			$values['error'] = 'RejectTransfer/domain-info('.$sld.'.'.$tld.'): Code('._COCCAepp_message($coderes).") $msg";
			return $values;
		}

		$values['status'] = $msg;

	} catch (Exception $e) {
		$values["error"] = 'RejectTransfer/EPP: '.$e->getMessage();
		return $values;
	}

	return $values;
}

function generateHandle() {
    $stamp = date("Ymdhis"); 
    $handle = "$stamp"; 
    sleep(1);
    return $handle;
}

function array_push_assoc($array, $key, $value){
	$array[$key] = $value;
	return $array;
}
