<?php

/**
 * Sample SOAP client for requesting stock status info on Digital Content SOAP interface v2.4
 *
 * Use on the command line the following way (Linux): `php getStockStatus.php secret productRef1 [ productRef2 [ ... ] ]`
 * where "secret" is the key given to you by Nexway onboarding team
 * and "productRefX" is the product you want cross-sell and upsel advise for.
 * You can place multiple product refs, but not fewer than one.
 *
 * Upon successful update a JSON structure representing server response will be returned to stdout.
 *
 * For full API documentation see http://wsdocs.nexway.com/APIGuide/
 *
 * @author Nexway
 * @license proprietary
 */

// Program exit codes
define("E_OK", 0);
define("E_BAD_PARAMS", -1);
define("E_SOAP_ERROR", -2);
define("E_REQUEST_ERROR", -3);

// Command line parameters
if ($argc < 3) {
    fprintf(STDERR, "Required parameters missing: secret, productRef");
    exit(E_BAD_PARAMS);
}
define("SECRET", $argv[1]);
$productRefs = array_slice($argv, 2);

// Service parameters
define("HOST", getenv("NEXWAY_CONNECT_HOST") ? getenv("NEXWAY_CONNECT_HOST") : "ws.prep.websizing.com");
define("WSDL_URL", "http://" . HOST . "/global/order/v2.4/soap?wsdl");
define("WSDL_CACHE", false);
define("WSDL_TRACE", true);

// Instantiate service client
ini_set("soap.wsdl_cache_enabled", (bool)WSDL_CACHE);
$options = [
    "wsdl_cache" => WSDL_CACHE,
    "trace"      => WSDL_TRACE,
    "features"   => SOAP_SINGLE_ELEMENT_ARRAYS,
];
$soap = new SoapClient(WSDL_URL, $options);

// Prepare payload
$payload = array(
    "secret"  => SECRET,
    "request" => array(
        "productRef" => $productRefs,
    ),
);

// Send "getCrossUpSell" request with defined payload
fprintf(STDOUT, "Requesting stock status information for product(s) %s\n", implode(", ", $productRefs));
fprintf(STDOUT, "Calling endpoint %s\n", WSDL_URL);
try {
    $response = $soap->getStockStatus($payload);
} catch (Exception $e) {
    fprintf(STDOUT, "SOAP error: %s\n", $e->getMessage());
    fprintf(STDOUT, "Response code: %s\n", $e->faultcode);
    fprintf(STDOUT, "Response message: %s\n", $e->faultstring);
    exit(E_SOAP_ERROR);
}

// Check for errors in order processing
if ($response->out->responseCode != 0) {
    fprintf(STDOUT, "Request error\n");
    fprintf(STDOUT, "Response code: %s\n", $response->out->responseCode);
    fprintf(STDOUT, "Response message: %s\n", $response->out->responseMessage);
    exit(E_REQUEST_ERROR);
}

// Print retrieved order creation data
fprintf(STDOUT, "Request ok\n");
fprintf(STDOUT, json_encode($response->out, JSON_PRETTY_PRINT) . "\n");
exit(E_OK);