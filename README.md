Want to work for Gamer Network? [We are hiring!](http://www.gamesindustry.biz/jobs/gamer-network)

# Yolk Application

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gamernetwork/yolk-application/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/gamernetwork/yolk-application/?branch=develop)

The foundation of Yolk web applications. Request/Response handling, routing, middleware etc.

## Requirements

* PHP 5.4 or later
* Yolk Contracts (`gamernetwork/yolk-contracts`)
* Yolk Core (`gamernetwork/yolk-core`)
* Yolk Support (`gamernetwork/yolk-support`)

## Installation

It is installable and autoloadable via Composer as `gamernetwork/yolk-application`.

Alternatively, download a release or clone this repository, and add the `\yolk\app` namespace to an autoloader.

## License

Yolk Application is open-sourced software licensed under the MIT license.

## Overview

This package contains the components and services required to construct a basic Yolk web application.

Applications extend the `BaseApplication` class, which in-turn extends the `BaseDispatcher` class
and use a `Router` in order to pass a `Request` through middlewares to a handler (usually extending `BaseController`). 
Handlers return a `Response` (usually a subclass of `BaseResponse`) which is sent to the client.

## Further Reading


* [Requests and Responses](docs/request-response.md)
* [Dispatch and Routing](docs/dispatch-routing.md)
* [Middleware](docs/middleware.md)
* [Service Container](docs/services.md)
