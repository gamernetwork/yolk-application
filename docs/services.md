# Service Container

Yolk Applications use an extended version of [Pimple](http://pimple.sensiolabs.org/) as their DI container.

The extensions are focused on providing shortcuts between configuration and implementation and allow
several types of services to be easily accessed:

* Databases (`db`)
* Caches (`cache`)
* Logs (`log`)
* Views (`view`)

The implementation of items specified in those configuration sections can be accessed by using keys
in the format `<type>.<name>`. e.g. a database connection named `main` can be accessed by the key `db.main`.

```php
$config = [
	'databases' => [
		'main'   => 'mysql://localhost/app?charset=utf8',
	],
	'caches' => [
		'redis'  => 'redis://localhost:6379',
	],
	'views' => [
		'twig'   => [
			'adapter'       => 'twig',
			'view_path'     => __DIR__. '/views',
			'cache_path'    => __DIR__. '/cache/views',,
		]
	],
	'logs' => [
		'debug'   => 'file://'. __DIR__. '/logs/debug.log',
	],
];

$container['db.main'];      // return a DatabaseConnection instance utilising MySQL on localhost
$container['cache.redis'];  // return a Cache instance utilising Redis on localhost
$container['view.twig'];    // return a View instance utilising Twig as the backend
$container['log.debug'];    // return a Logger instance utilising the file 'debug.log'
```
