# diskover-web

diskover-web is the file manager and storage analytics web app for [diskover](https://shirosaidev.github.io/diskover). It will help you quickly search Elasticsearch for files in your diskover indices and files can be tagged for deletion, archival or keeping. Custom file tags are also supported.

A built-in REST API can also be used to list or update file tags in diskover indices.

It is written in PHP, Javascript, [jQuery](https://jquery.com/), [Bootstrap](http://getbootstrap.com/) and [D3.js](https://d3js.org).

## Screenshots

![diskover-web dashboard](docs/diskover-web-dashboard-screenshot.png?raw=true)
![diskover-web file tree](docs/diskover-web-filetree-screenshot.png?raw=true)
![diskover-web treemap](docs/diskover-web-treemap-screenshot.png?raw=true)
![diskover-web simple search](docs/diskover-web-simplesearch-screenshot.png?raw=true)
![diskover-web advanced file view](docs/diskover-web-advancedsearch-screenshot.png?raw=true)
![diskover-web search results](docs/diskover-web-searchresults-screenshot.png?raw=true)

## Installation Guide

### Requirements

* `Linux or OS X/MacOS` (tested on Ubuntu 16.04, OS X 10.11.6)
* `PHP 7.0` (tested on PHP 7.0.15, 7.0.19)
* `Composer Dependency Manager for PHP`
* `PHP client for Elasticsearch` (elasticsearch-php)
* `Elasticsearch` (tested on Elasticsearch 5.3.0, 5.4.2)
* `Apache or Nginx` (if you don't want to use PHP built-in web server)
* `diskover` (Elasticsearch index created by diskover >= 1.2.0)

### Download

```sh
$ git clone https://github.com/shirosaidev/diskover-web.git
$ cd diskover-web
```
[Download latest version](https://github.com/shirosaidev/diskover-web/releases/latest)

### Install application dependencies

```sh
$ cd diskover-web
$ composer install
```


## User Guide

[Read the wiki](https://github.com/shirosaidev/diskover-web/wiki).


## License

See the [license file](https://github.com/shirosaidev/diskover-web/LICENSE).
