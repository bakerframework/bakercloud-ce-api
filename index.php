<?php
require 'Slim/Slim.php';

// Pimple Dependency Injection Container
require_once 'Pimple.php';

\Slim\Slim::registerAutoloader();

$app = new Slim\Slim(array(
	'mode' => 'production',
	'debug' => false
));

// Set Content Type and Encoding.
$app->contentType('text/html; charset=utf-8');

// Set Default Timezone to America/Los_Angeles as this is what all App Store dates are in.
date_default_timezone_set('America/Los_Angeles');

// Create Pimple Dependency Injection Container for DB Connection.
$dbContainer = new Pimple();

// Global Development Mode Setting
$developmentMode = '';

// Global iTunes Production Level Setting
$iTunesProductionLevel = '';

// Global iTunes Receipt Validation Caching Setting
$iTunesCachingDuration = -1;

// Global iTunes Receipt Validation Caching Setting
$subscriptionBehavior = '';

// API Version
$apiVersion = '1.1.1';

// DB Setup for Pimple Container
// BakerCloud API SETUP CONFIGURATION SETTING
// ************************************************************
$dbContainer['db.options'] = array(
	'host' => 'localhost',						// CONFIGURE TO YOUR DB HOSTNAME					
	'username' => 'bakerc_ce',					// CONFIGURE TO YOUR DB USERNAME		
	'password' => 'baker',						// CONFIGURE TO YOUR DB USERNAME'S PASSWORD
	'dbname' => 'bakerc_cloudce'				// CONFIGURE TO YOUR DB INSTANCE NAME
);
//*************************************************************

// Using "share" method makes sure that the function is only called when 'db' is retrieved the first time.
$dbContainer['db'] = $dbContainer->share(function () use($dbContainer)
{
	// Get DB handle and create new PDO DB object using configuration settings
	$dbHandle = new PDO('mysql:host=' . $dbContainer['db.options']['host'] . ';dbname=' . $dbContainer['db.options']['dbname'], $dbContainer['db.options']['username'], $dbContainer['db.options']['password']);
	
	// Set Character Set for DB connection to UTF-8
	$dbHandle -> exec("SET CHARACTER SET utf8");
	
	// Return DB handle
	return $dbHandle;
});

// ************************************************
// SLIM PHP Methods for handling REST API Methods
// ************************************************

// GET route
$app->get('/', function () {

	$SCRIPT_LOCATION = str_replace('index.php','',$_SERVER['SCRIPT_NAME']);
	
    $template = "
			<!DOCTYPE html>
			<html>
			<head>
			<meta charset='utf-8'/>
			<title>Baker Cloud Console (CE) REST API</title>
			<style>
			html,body,div,span,object,iframe,
			h1,h2,h3,h4,h5,h6,p,blockquote,pre,
			abbr,address,cite,code,
			del,dfn,em,img,ins,kbd,q,samp,
			small,strong,sub,sup,var,
			b,i,
			dl,dt,dd,ol,ul,li,
			fieldset,form,label,legend,
			table,caption,tbody,tfoot,thead,tr,th,td,
			article,aside,canvas,details,figcaption,figure,
			footer,header,hgroup,menu,nav,section,summary,
			time,mark,audio,video{margin:0;padding:0;border:0;outline:0;font-size:100%;vertical-align:baseline;background:transparent;}
			body{line-height:1;}
			article,aside,details,figcaption,figure,
			footer,header,hgroup,menu,nav,section{display:block;}
			nav ul{list-style:none;}
			blockquote,q{quotes:none;}
			blockquote:before,blockquote:after,
			q:before,q:after{content:'';content:none;}
			a{margin:0;padding:0;font-size:100%;vertical-align:baseline;background:transparent;}
			ins{background-color:#ff9;color:#000;text-decoration:none;}
			mark{background-color:#ff9;color:#000;font-style:italic;font-weight:bold;}
			del{text-decoration:line-through;}
			abbr[title],dfn[title]{border-bottom:1px dotted;cursor:help;}
			table{border-collapse:collapse;border-spacing:0;}
			hr{display:block;height:1px;border:0;border-top:1px solid #cccccc;margin:1em 0;padding:0;}
			input,select{vertical-align:middle;}
			html{ background: #EDEDED; height: 100%; }
			body{background:#FFF;margin:0 auto;min-height:100%;padding:0 30px;width:440px;color:#666;font:14px/23px Arial,Verdana,sans-serif;}
			h1,h2,h3,p,ul,ol,form,section{margin:0 0 20px 0;}
			h1{color:#333;font-size:20px;}
			h2,h3{color:#333;font-size:14px;}
			h3{margin:0;font-size:12px;font-weight:bold;}
			ul,ol{list-style-position:inside;color:#999;}
			ul{list-style-type:square;}
			code,kbd{background:#EEE;border:1px solid #DDD;border:1px solid #DDD;border-radius:4px;-moz-border-radius:4px;-webkit-border-radius:4px;padding:0 4px;color:#666;font-size:12px;}
			pre{background:#EEE;border:1px solid #DDD;border-radius:4px;-moz-border-radius:4px;-webkit-border-radius:4px;padding:5px 10px;color:#666;font-size:12px;}
			pre code{background:transparent;border:none;padding:0;}
			a{color:#0068ae;}
			header{padding: 30px 0;text-align:center;}
			</style>
			</head>
			<body>
			<header>
			<a href='http://www.bakerframework.com'><img src='data:image/png;base64,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' alt='Baker Cloud (CE)'/></a>
			</header>
			<h1>Welcome to Baker Cloud Console (CE) API!</h1>
			<p>
			Congratulations! Your Baker Cloud Console (CE) API is up and running.  However, you should test the Database connectivity below.  If you get a success message, then you should be good to go!
			</p>
			<section> 
			<h2>Get Started</h2>
			<ol>
			<li><a href='" . $SCRIPT_LOCATION . "checkinstall' target='_blank'>Check DB Connectivity</a></li>
			<li>Read the <a href='http://www.bakerframework.com/' target='_blank'>online documentation</a></li>
			<li>Follow <a href='http://www.twitter.com/bakerframework' target='_blank'>@bakerframework</a> on Twitter</li>
			</ol>
			</section>
			<section>
			<h2>Baker Framework Community</h2>
			
			<h3>Support Forum and Knowledge Base</h3>
			<p>
			Visit the <a href='http://www.github.com/bakerframework/' target='_blank'>Online Support Community</a>
			to read announcements, chat with fellow Baker Framework users, ask questions, help others, or show off your cool
			Baker Newsstand apps.
			</p>
			
			<h3>Twitter</h3>
			<p>
			Follow <a href='http://www.twitter.com/bakerframework' target='_blank'>@bakerframework</a> on Twitter to receive the very latest news
			and updates about the framework.
			</p>
			</section>
			</body>
			</html>";
    echo $template;
});

// Check DB Connectivity
// *Makes connection to BakerCloud DB and tries to make a select from the PUBLICATION table
$app->get('/checkinstall/', function ()
{  
	try {	
		global $dbContainer;
		$db = $dbContainer['db'];
	
		$result = $db->prepare("SELECT * FROM PUBLICATION");
	
		$result->execute();
		$checkInstall = $result->fetchAll();
		
		echo '{"BakerCloud API":{"Success":"Database Connection Test Successful"}}';
	}
	catch(PDOException $e) {
		echo '{"BakerCloud API":{"Error":"' . $e->getMessage() . '"}}';
	}
});

// Output Debug Information
// *Outputs the settings for a given publication that the API is registering, useful when debugging
$app->get('/debuginformation/:app_id', function ($app_id)
{  
	global $apiVersion;
	
	try {	
		echo 'Baker Cloud CE API: Debug Information';
		echo '<BR>API Version: ' . $apiVersion;
		echo '<BR>Development Mode: ' . isInDevelopmentMode($app_id);
		echo '<BR>Subscription Behavior: ' . getSubscriptionBehavior($app_id);
		echo '<BR>iTunes Production Level: ' . getiTunesProductionLevel($app_id);
		echo '<BR>iTunes Caching Duration: ' . getiTunesCachingDuration($app_id);
	}
	catch(Exception $e) {
	}
});

// Issues List
// *Retrieves a list of available issues for the App ID, for population of Baker Shelf
$app->get('/issues/:app_id/:user_id', function ($app_id, $user_id)
{
	global $dbContainer;
	$db = $dbContainer['db'];
	
	$SCRIPT_LOCATION = str_replace('index.php','',$_SERVER['SCRIPT_NAME']);
    
    // Lookup Issue Download Security condition for Publication, if true, create secured API Issue download links 
	$result = $db->query("SELECT ISSUE_DOWNLOAD_SECURITY FROM PUBLICATION WHERE APP_ID = '$app_id' LIMIT 0, 1");	
	$issueDownloadSecurity = $result->fetchColumn();
	
	if(isInDevelopmentMode($app_id)=="TRUE"){logMessage(LogType::Info,"Retrieving Issues for APP ID: " . $app_id . " USER ID: " . $user_id);}
	
	// Query all issues for the incoming APP_ID
	$sql = "SELECT * FROM ISSUES WHERE APP_ID = '$app_id' AND AVAILABILITY = 'published'";
	
	try {	
		$IssuesArray = array();
		$i = 0;
		foreach($db->query($sql) as $row) {
			$IssuesArray[$i]['name'] = $row['NAME'];
			$IssuesArray[$i]['title'] = $row['TITLE'];
			$IssuesArray[$i]['info'] = $row['INFO'];
			$IssuesArray[$i]['date'] = $row['DATE'];
			$IssuesArray[$i]['cover'] = $row['COVER'];
			
			if ($issueDownloadSecurity == "TRUE") {
				
				$IssuesArray[$i]['url'] = "http://" . $_SERVER['HTTP_HOST'] . $SCRIPT_LOCATION . "issue/" . $app_id . "/" . $user_id . "/" . $row['NAME'];
			}
			else{
				$IssuesArray[$i]['url'] = $row['URL'];
			}
			if($row['PRICING'] != 'free')
			{
				$IssuesArray[$i]['product_id'] = $row['PRODUCT_ID'];
			}
			$i++;
		}
	
		logAnalyticMetric(AnalyticType::ApiInteraction,1,NULL,$app_id,$user_id);
	
		echo json_encode($IssuesArray);
	}
	catch(PDOException $e) {
		logMessage(LogType::Error, $e->getMessage());
		echo '{"error":{"text":"' . $e->getMessage() . '"}}';
	}
});

// Issue Download
// *Validates availability of download of a specific named issue, redirects to download if available
$app->get('/issue/:app_id/:user_id/:name', function ($app_id, $user_id, $name) use($app)
{
	global $dbContainer;
	$db = $dbContainer['db'];

	try {
			$result = $db->prepare("SELECT * FROM ISSUES WHERE APP_ID = '$app_id' AND NAME = '$name' LIMIT 0,1");

			$result->execute();
			$issue = $result->fetch();
	
			// The Issue is not found for App_ID and Name.  Throw 404 not found error.
			if (!$issue) {
				header('HTTP/1.1 404 Not Found');
				die();
			}
	
			// Retrieve issue Issue Product ID to cross check with purchases
			$product_id = $issue['PRODUCT_ID'];

			// Default to not allow download.		
			$allow_download = false;
			
			// Validate that the Product ID (from Issue Name) is an available download for given user		
			if ($product_id && $issue['PRICING'] != 'free') {
				// Allow download if the issue is marked as purchased
				$result = $db->query("SELECT COUNT(*) FROM PURCHASES 
													WHERE APP_ID = '$app_id' AND USER_ID = '$user_id' AND PRODUCT_ID = '$product_id'");		
														
				$allow_download = ($result->fetchColumn() > 0);
			} else if ($issue['PRICING'] == 'free') {
				// Issue is marked as free, allow download
				$allow_download = true;
			}
		
			if ($allow_download) {
				
				if((isInDevelopmentMode($app_id)=="TRUE") && !($app->request()->isHead())){logMessage(LogType::Info,"Downloading ISSUE: " . $name . " for APP ID: " . $app_id . " USER ID: " . $user_id);}
				
				logAnalyticMetric(AnalyticType::ApiInteraction,1,NULL,$app_id,$user_id);
				if(!($app->request()->isHead())){logAnalyticMetric(AnalyticType::Download,1,$name,$app_id,$user_id);}
				
				// Redirect to the downloadable file, nothing else needed in API call
				$app->response()->redirect($issue['URL'], 303);
			}
			else {
				header('HTTP/1.1 403 Forbidden');
				die();
			}
		}
		catch(PDOException $e) {
			// Handle exception
			logMessage(LogType::Error, $e->getMessage());
		}
});

// Purchases List
// *Returns a list of Purchased Product ID's
$app->get('/purchases/:app_id/:user_id', function ($app_id, $user_id)
{
	global $dbContainer;
	$db = $dbContainer['db'];
	$purchased_product_ids = array();
	
	if(isInDevelopmentMode($app_id)=="TRUE"){logMessage(LogType::Info,"Checking purchases for APP ID: " . $app_id . " USER ID: " . $user_id);}
				
	try {
		$subscribed = false;

		// Retrieve latest receipt for Auto-Renewable-Subscriptions for the APP_ID, USER_ID combination
		$result = $db->query("SELECT BASE64_RECEIPT FROM RECEIPTS
									     WHERE APP_ID = '$app_id' AND USER_ID = '$user_id' AND TYPE = 'auto-renewable-subscription'
									     ORDER BY TRANSACTION_ID DESC LIMIT 0, 1");
		
		$base64_latest_receipt = $result->fetchColumn();
		if($base64_latest_receipt)
		{
			$userSubscription = checkSubscription($app_id, $user_id);
			$dateLastValidated = new DateTime($userSubscription["LAST_VALIDATED"]);
			$dateExpiration = new DateTime($userSubscription["EXPIRATION_DATE"]);
			$dateCurrent = new DateTime('now');
			$interval = $dateCurrent->diff($dateLastValidated);
	
			if(isInDevelopmentMode($app_id)=="TRUE"){logMessage(LogType::Info,"Time since last validating receipt for APP ID: " . $app_id . " USER ID: " . $user_id . " = "  . $interval->format('%h hours %i minutes') );}
			
			// Only refresh and re-verify receipt if greater than the iTunesCachingDuration - or greater than 1 whole day
			if ((getiTunesCachingDuration($app_id) == -1) || ($interval->format('%h') > getiTunesCachingDuration($app_id)) || ($interval->format('%a') > 1)) {
				// Check the latest receipt from the subscription table
	
				if ($base64_latest_receipt) {		
					// Verify Receipt - with logic to fall back to Sandbox test if Production Receipt fails (error code 21007)
                try{
                        $data = verifyReceipt($base64_latest_receipt, $app_id, $user_id);
                }
                catch(Exception $e) {
                        if($e->getCode() == "21007"){
                                logMessage(LogType::Info,"Confirming purchase for APP ID - Sandbox Receipt used in Production, retrying against Sandbox iTunes API: " . $app_id . " USER ID: " . $user_id . " TYPE: " . $type);
                                $data = verifyReceipt($base64_latest_receipt, $app_id, $user_id, TRUE);
                    }
                }    

					markIssuesAsPurchased($data, $app_id, $user_id);
	
					// Check if there is an active subscription for the user.  Status=0 is true.
					$subscribed = ($data->status == 0);
				}
				else {
					// There is no receipt for this user, there is no active subscription
					$subscribed = false;
				}
			}
			else {
				// We aren't going to re-verify the receipt now, but we should determine if the Expiration Date is beyond now
				if ($dateCurrent > $dateExpiration) {
					$subscribed = false;
				}
				else {
					$subscribed = true;
				}
			}
		}
		else
		{
			// There is no Auto-Renewable-Subscription for the APP_ID, USER_ID combination - check if there is a Free-Subscription
			$result = $db->query("SELECT BASE64_RECEIPT FROM RECEIPTS
									     WHERE APP_ID = '$app_id' AND USER_ID = '$user_id' AND TYPE = 'free-subscription'
									     ORDER BY TRANSACTION_ID DESC LIMIT 0, 1");
		
			$base64_latest_receipt = $result->fetchColumn();
			
			// If there is a receipt for a free-subscription then we will return that the USER_ID is subscribed.  Since a free
			// subscription really doesn't have and valid term then we will just ignore any dates in the Apple receipt.
			// However the list of purchased product_ids can still be blank because the issues should be marked as free.
			if($base64_latest_receipt){
				$subscribed = true;
			}			
		}
	
		// Return list of purchased product_ids for the user
		$result = $db->query("SELECT PRODUCT_ID FROM PURCHASES
								WHERE APP_ID = '$app_id' AND USER_ID = '$user_id'");
			
		$purchased_product_ids = $result->fetchAll(PDO::FETCH_COLUMN);
		
		logAnalyticMetric(AnalyticType::ApiInteraction,1,NULL,$app_id,$user_id);
		
		echo json_encode(array(
			'issues' => $purchased_product_ids,
			'subscribed' => $subscribed
		));
	}
	catch(PDOException $e) {
		logMessage(LogType::Error, $e->getMessage());
		echo '{"error":{"text":"' . $e->getMessage() . '"}}';
	}
});

// iTunes List
// *Returns a list of Issues in an iTunes ATOM Feed XML Format.  This can be hooked up to the FEED URL within
//  iTunes connect to display up to date information in the Newsstand App Store listing
$app->get('/itunes/:app_id', function ($app_id)
{
	global $dbContainer;
	$db = $dbContainer['db'];

	try {
		$result = $db->query("SELECT ITUNES_UPDATED FROM PUBLICATION WHERE APP_ID = '$app_id' LIMIT 0, 1");	
		
		$ITUNES_UPDATED = $result->fetchColumn();

		if(!$ITUNES_UPDATED)
		{
			if($ITUNES_UPDATED == NULL)
				$ITUNES_UPDATED = date("Y-m-d H:i:s");
			else
				throw new Exception('Invalid APP ID');
		}	
			
		$iTunesUpdateDate = new DateTime($ITUNES_UPDATED);
				
		$sql = "SELECT * FROM ISSUES WHERE APP_ID = '$app_id' AND AVAILABILITY = 'published'";

		$AtomXML = "<?xml version=\"1.0\" encoding=\"UTF-8\"" . "?>";
		$AtomXML.= "<feed xmlns=\"http://www.w3.org/2005/Atom\" xmlns:news=\"http://itunes.apple.com/2011/Newsstand\">";
		$AtomXML.= "<updated>" . date_format($iTunesUpdateDate, DateTime::ATOM) . "</updated>";
		foreach($db->query($sql) as $row) {
			$iTunesIssueUpdateDate = new DateTime($row['ITUNES_UPDATED']);
			$iTunesPublishedDate = new DateTime($row['DATE']);
			$AtomXML.= "<entry>";
			$AtomXML.= "<id>" . $row['NAME'] . "</id>";
			$AtomXML.= "<updated>" . date_format($iTunesIssueUpdateDate, DateTime::ATOM) . "</updated>";
			$AtomXML.= "<published>" . date_format($iTunesPublishedDate, DateTime::ATOM) . "</published>";
			$AtomXML.= "<summary>" . $row['ITUNES_SUMMARY'] . "</summary>";
			$AtomXML.= "<news:cover_art_icons>";
			$AtomXML.= "<news:cover_art_icon size=\"SOURCE\" src=\"" . $row['ITUNES_COVERART_URL'] . "\"/>";
			$AtomXML.= "</news:cover_art_icons>";
			$AtomXML.= "</entry>";
		}
		$AtomXML.= "</feed>";
		
		logAnalyticMetric(AnalyticType::ApiInteraction,1,NULL,$app_id,$user_id);
		
		echo utf8_encode($AtomXML);
	}
	catch(Exception $e) {
		logMessage(LogType::Error, $e->getMessage());
		echo '{"error":{"text":"' . $e->getMessage() . '"}}';
	}
});

// Confirm Purchase
// *Confirms the purchase by validating the Receipt_Data received for the in app purchase.  Records the receipt data
//  in the database and adds the available issues to the user's Purchased List
$app->post('/confirmpurchase/:app_id/:user_id', function ($app_id, $user_id) use($app)
{
	global $dbContainer;
	$db = $dbContainer['db'];
	
	$body = $app->request()->getBody();
	$receiptdata = $app->request()->post('receipt_data');
	$type = $app->request()->post('type');
	
	if(isInDevelopmentMode($app_id)=="TRUE"){logMessage(LogType::Info,"Confirming purchase for APP ID: " . $app_id . " USER ID: " . $user_id . " TYPE: " . $type);}
	
	try {
		// Verify Receipt - with logic to fall back to Sandbox test if Production Receipt fails (error code 21007)
		try{
		       $iTunesReceiptInfo = verifyReceipt($receiptdata, $app_id, $user_id);
		}
		catch(Exception $e) {
		       if($e->getCode() == "21007"){
		               logMessage(LogType::Info,"Confirming purchase for APP ID - Sandbox Receipt used in Production, retrying against Sandbox iTunes API: " . $app_id . " USER ID: " . $user_id . " TYPE: " . $type);
		               $iTunesReceiptInfo = verifyReceipt($receiptdata, $app_id, $user_id, TRUE);
		   }
		}   
		
		$sql = "INSERT IGNORE INTO RECEIPTS (APP_ID, QUANTITY, PRODUCT_ID, TYPE, TRANSACTION_ID, USER_ID, PURCHASE_DATE, 
	 		    			ORIGINAL_TRANSACTION_ID, ORIGINAL_PURCHASE_DATE, APP_ITEM_ID, VERSION_EXTERNAL_IDENTIFIER, BID, BVRS, BASE64_RECEIPT) 
	 		    			VALUES (:app_id, :quantity, :product_id, :type, :transaction_id, :user_id, :purchase_date, :original_transaction_id,
	 		    					  :original_purchase_date, :app_item_id, :version_external_identifier, :bid, :bvrs, :base64_receipt)";
	    // Jailbroken Device Hack Check
	    // Jailbroken devices often try to spoof purchases by using fake receipts
	    // Compare expected APP_ID to the Receipt (BID) Bundle Identifier.
	    if($app_id == $iTunesReceiptInfo->receipt->bid)
	    {
			try {
				$stmt = $db->prepare($sql);
				$stmt->bindParam("app_id", $app_id);
				$stmt->bindParam("quantity", $iTunesReceiptInfo->receipt->quantity);
				$stmt->bindParam("product_id", $iTunesReceiptInfo->receipt->product_id);
				$stmt->bindParam("type", $type);
				$stmt->bindParam("transaction_id", $iTunesReceiptInfo->receipt->transaction_id);
				$stmt->bindParam("user_id", $user_id);
				$stmt->bindParam("purchase_date", $iTunesReceiptInfo->receipt->purchase_date);
				$stmt->bindParam("original_transaction_id", $iTunesReceiptInfo->receipt->original_transaction_id);
				$stmt->bindParam("original_purchase_date", $iTunesReceiptInfo->receipt->original_purchase_date);
				$stmt->bindParam("app_item_id", $iTunesReceiptInfo->receipt->item_id);
				$stmt->bindParam("version_external_identifier", $iTunesReceiptInfo->receipt->version_external_identifier);
				$stmt->bindParam("bid", $iTunesReceiptInfo->receipt->bid);
				$stmt->bindParam("bvrs", $iTunesReceiptInfo->receipt->bvrs);
				$stmt->bindParam("base64_receipt", $receiptdata);
				$stmt->execute();
	
				// If successful, record the user's purchase
				if($type == 'auto-renewable-subscription'){
					markIssuesAsPurchased($iTunesReceiptInfo,$app_id,$user_id);
				}else if($type == 'issue'){
					markIssueAsPurchased($iTunesReceiptInfo->receipt->product_id, $app_id, $user_id);				
				}else if($type == 'free-subscription'){
					// Nothing to do, as the server assumes free subscriptions don't need to be handled in this way			
				}				
	
				logAnalyticMetric(AnalyticType::ApiInteraction,1,NULL,$app_id,$user_id);
	
			}
			catch(PDOException $e) {
				logMessage(LogType::Error, $e->getMessage());
				echo '{"error":{"text":"' . $e->getMessage() . '"}}';
			}
		}
		else{
			logMessage(LogType::Error, "Invalid Receipt Bundle Identifier: " . $iTunesReceiptInfo->receipt->bid);
			header('HTTP/1.1 404 Not Found');
			die();
		}
	}
	catch(Exception $e) {
		logMessage(LogType::Error, $e->getMessage());
		echo '{"error":{"text":"' . $e->getMessage() . '"}}';
	}
});

// APNS Token
// *Stores the APNS Token in the database for the given App ID and User ID
$app->post('/apns/:app_id/:user_id', function ($app_id, $user_id) use($app)
{
	global $dbContainer;
	$db = $dbContainer['db'];
	
	$apns_token = $app->request()->post('apns_token');
	
	if(isInDevelopmentMode($app_id)=="TRUE"){logMessage(LogType::Info,"Storing APNS Token for APP ID: " . $app_id . " USER ID: " . $user_id);}

	$sql = "INSERT IGNORE INTO APNS_TOKENS (APP_ID, USER_ID, APNS_TOKEN) 
 		    			VALUES (:app_id, :user_id, :apns_token)";
 		    			
	try {
		$stmt = $db->prepare($sql);
		$stmt->bindParam("app_id", $app_id);
		$stmt->bindParam("user_id", $user_id);
		$stmt->bindParam("apns_token", $apns_token);
		$stmt->execute();
		
		logAnalyticMetric(AnalyticType::ApiInteraction,1,NULL,$app_id,$user_id);
		
		echo '{"success":{"message":"' . $apns_token . '"}}';
	}
	catch(PDOException $e) {
		logMessage(LogType::Error, $e->getMessage());
		echo '{"error":{"text":"' . $e->getMessage() . '"}}';
	}
});

// ************************************************
// Utility Functions
// ************************************************

// Log Error Messages for tracking and debugging purposes, also displayed in the BakerCloud Console for issue debugging
function logMessage($logType, $logMessage)
{
	global $dbContainer;
	$db = $dbContainer['db'];
	
	$sql = "INSERT INTO SYSTEM_LOG (TYPE, MESSAGE) 
		    			VALUES (:logtype, :logmessage)";
		    			
	try {
		$stmt = $db->prepare($sql);
		$stmt->bindParam("logtype", $logType);
		$stmt->bindParam("logmessage", $logMessage);
		$stmt->execute();
	}
	catch(PDOException $e) {
		// Error occurred, just ignore because if it failed in this logMessage method not much we can do, ignore
	}
}

// Log Analytic Metrics for tracking purposes and basic display in the dashboard
function logAnalyticMetric($analytic_type, $analytic_value, $metadata, $app_id, $user_id)
{
	global $dbContainer;
	$db = $dbContainer['db'];
	
	$sql = "INSERT INTO ANALYTICS (APP_ID, USER_ID, TYPE, VALUE, METADATA) 
		    			VALUES (:app_id, :user_id, :analytic_type, :analytic_value, :metadata)";
		    			
	try {
		$stmt = $db->prepare($sql);
		$stmt->bindParam("app_id", $app_id);	
		$stmt->bindParam("user_id", $user_id);
		$stmt->bindParam("analytic_type", $analytic_type);
		$stmt->bindParam("analytic_value", $analytic_value);
		$stmt->bindParam("metadata", $metadata);						
		$stmt->execute();
	}
	catch(PDOException $e) {
		// Error occurred, just ignore because if it failed in this logAnalyticMetric method not much we can do, ignore
	}
}

// Check if this publication is in development mode, useful for installation and non-production debugging
function isInDevelopmentMode($app_id)
{
	global $developmentMode;
	global $dbContainer;
	$db = $dbContainer['db'];
			
	if($developmentMode != ""){
		return $developmentMode;
	}
	else{
		$result = $db->query("SELECT DEVELOPMENT_MODE FROM PUBLICATION WHERE APP_ID = '$app_id' LIMIT 0, 1");	
		return $result->fetchColumn();
	}
}

// Retrieve the iTunes Production Level for Apple API calls
function getiTunesProductionLevel($app_id)
{
	global $iTunesProductionLevel;
	global $dbContainer;
	$db = $dbContainer['db'];
			
	if($iTunesProductionLevel != ""){
		return $iTunesProductionLevel;
	}
	else{
		$result = $db->query("SELECT ITUNES_PRODUCTION_LEVEL FROM PUBLICATION WHERE APP_ID = '$app_id' LIMIT 0, 1");	
		return $result->fetchColumn();
	}
}

// Retrieve the Subscription Behavior setting for marking issues as purchased
function getSubscriptionBehavior($app_id)
{
	global $subscriptionBehavior;
	global $dbContainer;
	$db = $dbContainer['db'];
			
	if($subscriptionBehavior != ""){
		return $subscriptionBehavior;
	}
	else{
		$result = $db->query("SELECT SUBSCRIPTION_BEHAVIOR FROM PUBLICATION WHERE APP_ID = '$app_id' LIMIT 0, 1");	
		return $result->fetchColumn();
	}
}

// Retrieve the iTunes Caching Duration for re-validating Apple Receipts
function getiTunesCachingDuration($app_id)
{
	global $iTunesCachingDuration;
	global $dbContainer;
	$db = $dbContainer['db'];
			
	if($iTunesCachingDuration != -1){
		return $iTunesCachingDuration;
	}
	else{
		$result = $db->query("SELECT ITUNES_REVALIDATION_DURATION FROM PUBLICATION WHERE APP_ID = '$app_id' LIMIT 0, 1");	
		return $result->fetchColumn();
	}
}

// Mark all available (paid) issues as purchased for a given user
function markIssuesAsPurchased($app_store_data, $app_id, $user_id)
{
	global $dbContainer;
	$db = $dbContainer['db'];
	
	if(isInDevelopmentMode($app_id)=="TRUE"){logMessage(LogType::Info,"Marking Issues as Purchased for APP ID: " . $app_id . " USER ID: " . $user_id);}
		
	$receipt = $app_store_data->receipt;
	$startDate = new DateTime($receipt->purchase_date_pst);
	
	if ($app_store_data->status == 0) {
		$endDate = new DateTime($app_store_data->latest_receipt_info->expires_date_formatted_pst);
	}
	else
	if ($app_store_data->status == 21006) {
		$endDate = new DateTime($app_store_data->latest_expired_receipt_info->expires_date_formatted_pst);
	}

	// Now update the Purchases table with all Issues that fall within the subscription start and expiration date
	$startDateFormatted = $startDate->format('Y-m-d H:i:s');
	$endDateFormatted = $endDate->format('Y-m-d H:i:s');
	
	// Get First Day of the Month that the Receipt was generated for (Start)
	$issuesStartDateFormatted = $startDate->format('Y-m-01 00:00:00');
	// Get Last Day of the Month that the Receipt was generated for (Expiration)
	$issuesEndDateFormatted = $endDate->format('Y-m-t 23:59:59');

	// Update Subscriptions Table for user with current active subscription start and expiration date
	updateSubscription($app_id, $user_id, $startDateFormatted, $endDateFormatted);

	// If we are in Sandbox Mode, unlock all issues by default for testing purposes	
	if(getiTunesProductionLevel($app_id)=="sandbox"){

		$result = $db->query("SELECT PRODUCT_ID FROM ISSUES
		  							 WHERE APP_ID = '$app_id'
		  							 AND PRICING = 'paid'");
	}
	else{
		// If we are in Production - determine based on Subscription Behavior setting
		
		if(getSubscriptionBehavior($app_id)=="all"){
		
			$result = $db->query("SELECT PRODUCT_ID FROM ISSUES
		  							 WHERE APP_ID = '$app_id'
		  							 AND PRICING = 'paid'");
	  							 
		}else if(getSubscriptionBehavior($app_id)=="term"){
		
			$result = $db->query("SELECT PRODUCT_ID FROM ISSUES
								WHERE APP_ID = '$app_id'
								AND `DATE` >= '$issuesStartDateFormatted'
								AND `DATE` <= '$issuesEndDateFormatted'
								AND PRICING = 'paid'
								AND AVAILABILITY = 'published'");			
		}else{
		
			//Default to 'term' if for some reason the above fails
			$result = $db->query("SELECT PRODUCT_ID FROM ISSUES
								WHERE APP_ID = '$app_id'
								AND `DATE` >= '$issuesStartDateFormatted'
								AND `DATE` <= '$issuesEndDateFormatted'
								AND PRICING = 'paid'
								AND AVAILABILITY = 'published'");	
		}
	}

	$product_ids_to_mark = $result->fetchAll(PDO::FETCH_COLUMN);
	
	$insert = "INSERT IGNORE INTO PURCHASES (APP_ID, USER_ID, PRODUCT_ID)
						VALUES ('$app_id', '$user_id', :product_id)";
						
	$stmt = $db->prepare($insert);
	
	foreach($product_ids_to_mark as $key => $product_id) {
		$stmt->bindParam('product_id', $product_id);
		$stmt->execute();
	}
}

// Mark all available issues as purchased for a given user
function markIssueAsPurchased($product_id, $app_id, $user_id)
{
	global $dbContainer;
	$db = $dbContainer['db'];
	
	if(isInDevelopmentMode($app_id)=="TRUE"){logMessage(LogType::Info,"Marking single issue as purchased for APP ID: " . $app_id . " USER ID: " . $user_id . " PRODUCT ID: " . $product_id);}
	
	$sql = "INSERT IGNORE INTO PURCHASES (APP_ID, USER_ID, PRODUCT_ID) 
	    			VALUES (:app_id, :user_id, :product_id)";
	try {
		$stmt = $db->prepare($sql);
		$stmt->bindParam("app_id", $app_id);
		$stmt->bindParam("user_id", $user_id);
		$stmt->bindParam("product_id", $product_id);
		$stmt->execute();
	}
	catch(PDOException $e) {
		logMessage(LogType::Error, $e->getMessage());
		echo '{"error":{"text":"' . $e->getMessage() . '"}}';
	}
}

// Update the Subscription Record for a specific user with Effective Date and Expiration Date
function updateSubscription($app_id, $user_id, $effective_date, $expiration_date)
{
	global $dbContainer;
	$db = $dbContainer['db'];
	
	$currentDate = new DateTime('now');
	$lastValidated = $currentDate->format('Y-m-d H:i:s');
	
	if(isInDevelopmentMode($app_id)=="TRUE"){logMessage(LogType::Info,"Updating subscription effective dates for APP ID: " . $app_id . " USER ID: " . $user_id);}
	
	$sql = "INSERT INTO SUBSCRIPTIONS (APP_ID, USER_ID, EFFECTIVE_DATE, EXPIRATION_DATE, LAST_VALIDATED) 
	    			VALUES (:app_id, :user_id, :effective_date, :expiration_date, :last_validated)
	    			ON DUPLICATE KEY UPDATE EFFECTIVE_DATE=:effective_date, EXPIRATION_DATE=:expiration_date, LAST_VALIDATED=:last_validated";
	
	try {
		$stmt = $db->prepare($sql);
		$stmt->bindParam("app_id", $app_id);
		$stmt->bindParam("user_id", $user_id);
		$stmt->bindParam("effective_date", $effective_date);
		$stmt->bindParam("expiration_date", $expiration_date);
		$stmt->bindParam("last_validated", $lastValidated);
		$stmt->execute();
	}
	catch(PDOException $e) {
		logMessage(LogType::Error, $e->getMessage());
		echo '{"error":{"text":"' . $e->getMessage() . '"}}';
	}
}

// Check if the user has a current active Subscription and determine expiration date
function checkSubscription($app_id, $user_id)
{
	global $dbContainer;
	$db = $dbContainer['db'];
	
	if(isInDevelopmentMode($app_id)=="TRUE"){logMessage(LogType::Info,"Checking subscription for APP ID: " . $app_id . " USER ID: " . $user_id);}
	
	$result = $db->prepare("SELECT EFFECTIVE_DATE, EXPIRATION_DATE, LAST_VALIDATED FROM SUBSCRIPTIONS
										WHERE APP_ID = '$app_id' AND USER_ID = '$user_id' LIMIT 0,1");
	$result->execute();
	$data = $result->fetch();
	
	return $data;
}

// Validate InApp Purchase Receipt, by calling the Apple iTunes verifyReceipt method
// *Note that this seems to take between 2-4 seconds on average
function verifyReceipt($receipt, $app_id, $user_id, $sandbox_override = FALSE)
{
	global $dbContainer;
	$db = $dbContainer['db'];
	
	if(isInDevelopmentMode($app_id)=="TRUE"){logMessage(LogType::Info,"Verifying receipt with Apple for APP ID: " . $app_id . " USER ID: " . $user_id);}
	
	// Lookup shared secret from Publication table
	$result = $db->query("SELECT ITUNES_SHARED_SECRET FROM PUBLICATION WHERE APP_ID = '$app_id' LIMIT 0, 1");	
	$sharedSecret = $result->fetchColumn();
	
	if (getiTunesProductionLevel($app_id)=="sandbox" || $sandbox_override == TRUE) {
		$endpoint = 'https://sandbox.itunes.apple.com/verifyReceipt';
	}
	else {
		$endpoint = 'https://buy.itunes.apple.com/verifyReceipt';
	}

	// If no shared secret exists, don't send it to the verifyReceipt call, however it should exist!
	if($sharedSecret){
		$postData = json_encode(array(
		'receipt-data' => $receipt,
		'password' => $sharedSecret));
	}else{
		$postData = json_encode(array(
		'receipt-data' => $receipt));
	}
	
	$ch = curl_init($endpoint);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	$response = curl_exec($ch);
	$errno = curl_errno($ch);
	$errmsg = curl_error($ch);
	curl_close($ch);
	
	if ($errno != 0) {
		throw new Exception($errmsg, $errno);
	}

	$data = json_decode($response);

	if (!is_object($data)) {
		throw new Exception('Invalid Response Data');
	}

	if (!isset($data->status) || ($data->status != 0 && $data->status != 21006)) {
		logMessage(LogType::Warning, "Invalid receipt for APP ID: " . $app_id . " USER ID: " . $user_id . " STATUS: " . $data->status);
		throw new Exception('Invalid Receipt', $data->status);
	}

	return $data;
}

/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();

// PHP doesn't support Enums so make a simple class for LogType
abstract class LogType
{
	const Info = 'Info';
	const Warning = 'Warning';
	const Error = 'Error';	
}

// PHP doesn't support Enums so make a simple class for AnalyticType
abstract class AnalyticType
{
	const Download = 'download';
	const ApiInteraction = 'api_interaction';	
}

// Timer class for debugging and logging use
class timer
{
	var $start;
	var $pause_time;
	/*  start the timer  */
	function timer($start = 0)
	{
		if ($start) {
			$this->start();
		}
	}
	/*  start the timer  */
	function start()
	{
		$this->start = $this->get_time();
		$this->pause_time = 0;
	}
	/*  pause the timer  */
	function pause()
	{
		$this->pause_time = $this->get_time();
	}
	/*  unpause the timer  */
	function unpause()
	{
		$this->start+= ($this->get_time() - $this->pause_time);
		$this->pause_time = 0;
	}
	/*  get the current timer value  */
	function get($decimals = 8)
	{
		return round(($this->get_time() - $this->start) , $decimals);
	}
	/*  format the time in seconds  */
	function get_time()
	{
		list($usec, $sec) = explode(' ', microtime());
		return ((float)$usec + (float)$sec);
	}
}

?>