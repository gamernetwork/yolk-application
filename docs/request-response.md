
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

```php
use yolk\app\BaseRequest;

$request = BaseRequest::createFromGlobals();

$request->method();			// Returns Request::METHOD_GET
$request->fullURI();		// Returns the requested URI (excluding the query string)
$request->queryString();	// Returns the full query string

$request->option();			// returns an array containing all query string key/values
$request->option('foo');	// returns the value of the query string parameter 'foo'
							// second parameters specifies a default value

$request->data();			// returns an array containing all POST body key/values
$request->data('foo');		// returns the value of the POST parameter 'foo', or null
							// second parameters specifies a default value

// return an array of all items (name/value)
$request->header();
$request->cookie();
$request->file();
$request->environment();
$request->extra();			// data supplied by the route/application

// return the value of a specific item
$request->header('content-type');
$request->cookie('session_id');
$request->file('avatar');
$request->environment('SERVER_ADDR');
$request->extra('foo');

$request->ip();		// returns the IP address of the request

// if the GeoIP extension is available the following methods will return correct values
$request->country();	// returns the country the request originated from
$request->continent();	// returns the continent the request originated from


$request->isBot()		// returns true if the request was likely made by a bot
$request->isAjax()		// returns true if the request is an XMLHttpRequest
$request->isSecure()	// returns true if the request was made over a secure connection (HTTPS)
$request->isGet()		// returns true if the request method is GET
$request->isPost()		// returns true if the request method is POST
$request->isPut()		// returns true if the request method is PUT
$request->isDelete()	// returns true if the request method is DELETE
```

### Input Filtering

Both `option()` and `data()` will filter the user supplied values for XSS vunerabilities.
The following transformations are performed:
* remove control characters
* fix and decode entities (handles missing terminator)
* normalise line endings (to `LF`)
* remove any attributes starting with `on` or `xmlns`
* remove `javascript:` and `vbscript:` protocols and `-moz-binding` CSS property
* remove namespaced elements
* remove `data` URIs
* remove unwanted tags: `applet`, `base`, `bgsound`, `blink`, `body`, `embed`, `frame`,
  `frameset`, `head`,`html`, `iframe`, `ilayer`, `layer`, `link`, `meta`, `object`,
  `script`, `style`, `title`, `xml`

### URIs

Talk about prefixes

### Authorisation

If the request contains a HTTP Basic Auhthorisation header, the username and password
will be available by calling the `authUser()` and `authPassword()` methods respectively.

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

## "Flash" Messages

A "flash" message is a message or piece of information passed from one request
to another via a cookie.

Messages are set on a response object and transmitted to the client as a cookie:

```php
// Args: $text, $type = 'info', $title = ''
$response->message('Hello World');
$response->message('Something bad happened', 'error', 'Ooops');
```

They can then be accessed on the following request:

```php
// returns an array of messages, each message being an array with key/values for text, type and title.
$messages = $request->messages();
```


