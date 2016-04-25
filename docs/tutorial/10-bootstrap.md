
# Bootstrapping

This section outlines how to setup a new Yolk web application from scratch.

## Directory Structure

Yolk applications can be structured ina multitude of different ways depending on requirements;
however, the recommend structure for most applications is:


* `myapp` - project root
	* `app` - source files
		* `controllers`
		* `static` - static web assets
			* `css`
			* `less`
			* `img`
			* `js`
		* `tasks` - background tasks, cron jobs, etc
		* `views`
	* `config` - application and environment configuration
	* `entrypoints` - document root for webserver
	* `static` - static web assets for webserver
	* `tmp` - temp files
		* `cache`
			* `views` - compiled view templates
		* `logs`
	* `vendor` - composer packages

## Composer

Once the directory structure is in place we need to install the dependencies using Composer.

First we need a `composer.json` file:

```json
{
	"description": "My first Yolk web application",
	"minimum-stability": "dev",
	"prefer-stable": true,
	"autoload": {
		"psr-4": {
			"myapp\\": "app"
		}
	},
	"require": {
		"gamernetwork/yolk":	"dev-develop",
		"twig/twig":    	    "1.*",
		"twig/extensions":		"1.*",
	}
}
```

Currently the main Yolk repository is private, so `composer.json` needs a `repositories` section
to tell it where the package lives:

```json
	"repositories": [
		{
			"type": "vcs",
			"url": "git@github.com:gamernetwork/yolk.git"
		},
		{
			"type": "vcs",
			"url": "git@github.com:gamernetwork/yolk-orm.git"
		}
	]
```

Now we can install the dependencies: `composer install`

## Entrypoints

The `entrypoints` directory is the document root for the web server,
usually this will only contain `index.php` and optionally `local.php`.
although other files permitted to be accessed externally may also be
located here.

`index.php` is the primary entrypoint for the application and as such
should be a fairly minimalist file. It's primary responsibility is to
instantiate an instance of the application, run it and return any response
to the client.

```php
use yolk\Yolk;
use yolk\app\BaseRequest;

// set the timezone and default character encoding
date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

// tell PHP where our dependencies are
require '../vendor/autoload.php';

// include any local environment settings if found
file_exists(__DIR__. '/local.php') && include(__DIR__. '/local.php');

// create and run the application for the current request
$response = Yolk::run(
	new \myapp\Application(),
	Request::createFromGlobals()
);

// if we have a response then send it to the client
if( !empty($response) )
	$response->send();
```

As noted above, any setup specific to the local environment can be
included in the `local.php` file and will be loaded just before the
application is run.

The most common use-case for this is to enable Yolk's debug flag:

```php
use yolk\Yolk;

Yolk::setDebug(true);
```

**Next**: [Creating the Application class](20-application.md)
