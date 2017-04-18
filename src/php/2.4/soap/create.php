<?php

/**
 * Sample SOAP client for requesting order creation on Digital Content SOAP interface v2.4
 *
 * Use on the command line the following way (Linux): `php create.php secret productRef`
 * where "secret" is the key given to you by Nexway onboarding team
 * and "productRef" is the reference number for a product taken from your feed.
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
define("PRODUCT_REF", $argv[2]);

// Partner defined values
define("CURRENCY", "EUR");
define("ORDER_DATE", "2012-03-21T15:19:21+01:00");

// Service parameters
define("WSDL_URL", "http://ws.prep.websizing.com/global/order/v2.3/soap?wsdl");
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
    "secret" => SECRET,
    "request" => [
        "customer" => [
            // Customer details
            "locationInvoice" => [
                "title"       => 1,
                "firstName"   => "John",
                "lastName"    => "Doe",
                "address1"    => "Hell Boulevard,24",
                "companyName" => null,
                "zipCode"     => null,
                "city"        => "Nanterre",
                "country"     => "FR",
            ],
            "email"    => "jdoe@mail.com",
            "language" => "fr_FR",
        ],
        "partnerOrderNumber" => (string)rand(100000, 10000000), // Provide your system order number
        "marketingProgramId" => null,
        "orderLines" => [
            "createOrderLinesType" => [
                [
                    "vatRate"       => null,
                    "amountTotal"   => null,
                    "productRef"    => PRODUCT_REF, // Product ref must be present in your product feed
                    "amountDutyFree"=> null,
                    "quantity"      => 1,
                ],
                // Subsequent order lines may go here
            ]
        ],
        "currency"  => CURRENCY,
        "orderDate" => ORDER_DATE,
    ],
];

// Send "create" request with defined payload
fprintf(STDOUT, "Requesting order creation for product %s\n", PRODUCT_REF);
try {
    $response = $soap->create($payload);
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