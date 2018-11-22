## Services

### EntityService

The main service. All new services need to extend from it.

#### setRepository($repository)
This method setting repository for entity. Need to use repository's methods from service. 
$repository - valid class name of repository class.


### HttpRequestService
Service to working with http protocol. It is wrapper on Guzzle.

To on debug mode you need to add "http_service_debug" key to configs/defaults.php file, by default - debug is off.
If debug is on - information about all queries will write to log.

####sendGet($url, $data, $headers)
Method send GET query to $url.
- $url - string, target url;
- $data - array with get parameters; 
- $headers - array with headers;

Return response.

####sendPost
Method send POST query to $url.
- $url - string, target url;
- $data - array with body parameters; 
- $headers - array with headers;

Return response.

don't forget to set content-type/application-json header if you want that $data will send as json

####sendDelete
Method send DELETE query to $url.
- $url - string, target url;
- $headers - array with headers;

Return response.

####sendPut 
Method send PUT query to $url.
- $url - string, target url;
- $data - array with body parameters; 
- $headers - array with headers;

Return response.

####parseJsonResponse($response)
Get response data and parse it to associative array.
- $response - response object from sendGet/sendPost/sendDelete/sendDelete functions;

####saveCookieSession()
Saving cookies from request.

####getCookie()
Return saved cookies.