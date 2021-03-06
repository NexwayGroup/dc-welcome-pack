<?php

/**
 * Sample SOAP client for requesting order cancellation on Digital Content SOAP interface v2.4
 *
 * Use on the command line the following way (Linux): `php cancel.php secret orderNumber`
 * where "secret" is the key given to you by Nexway onboarding team
 * and "orderNumber" is the partner's (your) order number as passed at "cancel" call.
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
    fprintf(STDERR, "Required parameters missing: secret, orderNumber");
    exit(E_BAD_PARAMS);
}
define("SECRET", $argv[1]);
define("ORDER_NUMBER", $argv[2]);

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
$payload = [
    "secret"  => SECRET,
    "request" => [
        "partnerOrderNumber" => ORDER_NUMBER,
        "reasonCode"         => 2,
        "comment"            => "Order cancelled",
    ],
];

// Send "cancel" request with defined payload
fprintf(STDOUT, "Requesting cancellation of order %s\n", ORDER_NUMBER);
fprintf(STDOUT, "Calling endpoint %s\n", WSDL_URL);
try {
    $response = $soap->Cancel($payload);
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