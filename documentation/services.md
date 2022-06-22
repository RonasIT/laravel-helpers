[<< Traits][1]
[Iterators >>][2]

# Services

## EntityService

The base class for service classes related to database entities. It allows to use pseudo inheritance
between the service class and related repository class.

### setRepository($repository)

Associate service class with the repository class which will using in pseudo inheritance.
- $repository - valid class name of repository class.

## HttpRequestService

Service to working with http/https protocols based on Guzzle library.

Features:
- service can be injected via the `app()` helper, which allows to mock it in testing;
- debug mode, write all requests into the log file. Enabling by setting config `defaults.http_service_debug` to `true`.

### sendGet($url, $data, $headers)

Method to send `GET` request to $url.

Return response.

### sendPost($url, $data, $headers)

Method to send `POST` request to $url.

### sendDelete($url, $headers)

Method to send `DELETE` request to $url.

### sendPut($url, $data, $headers)

Method to send `PUT` request to $url.

### parseJsonResponse($response)

Get response data and parse it to associative array.
- $response - response object received from the `sendGet`/`sendPost`/`sendDelete`/`sendDelete` methods.

### saveCookieSession()

Saving cookies from request.

### getCookie()
Return saved cookies.

[<< Traits][1]
[Iterators >>][2]

[1]:traits.md
[2]:iterators.md
