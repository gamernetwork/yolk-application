# Middleware

Currently our `AdminController` is publically accessible to all;
obviously not ideal so let's fix that by requiring an authenticated user
using HTTP Basic Auth.

In our `MyApplication` class we can create and register some middleware
to perform the authentication:

```php
protected function init() {

  // must call the parent or nothing will work
  parent::init();

  $this->addMiddleare(function( Request $request, Closure $next = null ) {
  
    // we only care if the uri begins with '/admin'
    if( preg_match('/^\/admin/', $request->uri()) ) {

      // the Request automatically makes HTTP Basic Auth information available
      $user = $request->authUser(); 
      $pass = $request->authPassword(); 

      // hardcoded user/password - swap for something a little more robust
      if( ($user != 'admin') || ($pass != 'foobar') )
        // simply throw an exception - this will be converted into a "401 Not Authenticate" response
        // by the application's exception handler
        throw new NotAuthenticatedException();
      }

    }
  
  });

}
```
