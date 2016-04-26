# Views

Using views in an application requires a suitable entry in the configuration.
The minimum information required is which adapter to use and the directory in
which the templates are located. Adapters may support other options, the
configuration block is passed to the adapter's constructor.

```php
// 'twig' here is a name used to access this particular configuration
// it is possible to have multiple view configurations in a single application,
// however most applications will require only a single view config
$config['views.twig'] = [

    // indicate we're using the TwigView adapter class, 'native' is the other
    // adapter included with Yolk
    'adapter' => 'twig',

    // this is the directory where the views for this configuration are found
    // usually this will just be the view path of the application
    'view_path' => $config['paths']['view'],

    // this is where the compiled templates are stored
    'cache_path' => $config['paths']['tmp']. "/cache/views",

    // pass the current debug setting to the view adapter
    'debug' => $config['debug'],

];
```

An instance of the view adapter can be obtained from the `ServiceContainer`
by using `view.<config_name>`. e.g. an instance using the above configuration
can be obtained via:

```php
$view = $container['view.twig'];
```

Once we have a `View` instance, we can obtain the resulting output by calling
the `render()` method and specifing a view template and a context (array of
variables accessible by the view):

```php
$html = $view->render(
	'admin/dashboard',
	[
		'request' => $request,
		'foo'     => $foo,
	]
);
```

The template name is expanded to an actual file name by:
* Prefixing the `view_path` specified in the configuration
* Suffixing the `file_extension` (default `.html`)
