
# Requests and Responses

`Request` and `Response` instances provide a simple OO layer around the PHP super-globals ($_GET, $_POST, etc) 
and output functions (echo, header, setcookie, etc). 

## Request

The most common way to create a `Request` instance is to base it on the PHP super-globals:

```php
use yolk\app\BaseRequest;

$request = BaseRequest::createFromGlobals();
 ```

The more verbose, but more flexible way is via a `__construct()` call:

```php
use yolk\app\BaseRequest;

$request = new BaseRequest(
	$method,		// the request method (GET, POST, etc)
	$uri,			// the uri of the request
	$options,		// array of query string data, usually from $_GET
	$data,			// array of request data, usually from $_POST
	$cookies,		// array of request cookies, usually from $_COOKIES
	$files,			// array of uploaded files, usually from $_FILES
	$headers,		// array of request headers, extracted from $_SERVER
	$environment	// array of environment settings, extracted from $_SERVER
);
```

### Accessing Request Data

The information held within a `Request` object can be accessed via various public methods.

* `method()` - returns the request method
* `uri()` - returns the request uri minus any defined prefixes
* `fullURI()` - returns the actual uri requested
* `queryString()` - returns the query string
* `option()` - returns items from the query string, xss filtered by default
* `data()` - returns items from the request body, xss filtered by default
* `header()` - returns request headers
* `cookie()` - returns request cookies
* `file()` - returns uploaded files
* `environment()` - returns environment settings - contents of $_SERVER that aren't already used elsewhere
* `extra()` - returns "extra" information provided by the route/application
* `messages()` - returns any "flash" messages passed via the YOLK_MESSAGES cookie
* `ip()` - returns the IP address of the request
* `authUser()` - returns the user name supplied by the request
* `authPassword()` - returns the user password supplied by the request
* `country()` - returns the country the request originated from (requires the function: geoip_country_code_by_name)
* `continent()` - returns the country the request originated from (requires the function: geoip_continent_code_by_name)
* `isBot()` - returns true if the request was likely made by a bot
* `isAjax()` - returns true if the request is an XMLHttpRequest
* `isSecure()` - returns true if the request was made over a secure connection (HTTPS)
* `isGet()` - returns true if the request method was GET
* `isPost()` - returns true if the request method was POST
* `isPut()` - returns true if the request method was PUT
* `isDelete()` - returns true if the request method was DELETE

## Response

A `Response` object holds all the information that needs to be send back to the client for a given request,
by default responses have a 200 OK status, an empty body and a content-type of text/html; utf-8.

The response may be manipulated after creation:

```php
use yolk\app\BaseResponse;

$response = new BaseResponse();

$response

	// change the status code and message
	->status(404, 'Thing Not Found')

	// set headers
	->header('X-Yolk-Version', '3.0')

	// set cookies - name, value, expiry in seconds, path, domain
	->cookie('token', md5(rand()), 86400)

	// set the response body
	->body("<html><body><h1>Thing Not Found</h1></body></html>")
	->setCharset('ISO-8859-1')

	// set a "flash" message to be returned with the next request
	->message('Hello World')

	// send the complete response to the client
	->send();
```

### Redirects

The `redirect()` method can be used to quickly generate a redirect response:

```php
use yolk\app\BaseResponse;

$response = new BaseResponse();

// tempory redirect (302)
$response->redirect('/news');

// permanent redirect (301)
$response->redirect('/news', true);
```
