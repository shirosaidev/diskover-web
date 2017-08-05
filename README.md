# diskover-web

diskover-web is the web file manager for [diskover](https://github.com/shirosaidev/diskover), allowing you to search Elasticsearch for your indexed files and tag them for deletion, archival or keeping. It is written in PHP, [Bootstrap](http://getbootstrap.com/) and [jQuery](https://jquery.com/).

## Screenshots

![diskover-web dashboard](docs/diskover-web-dashboard-screenshot.png?raw=true)
![diskover-web sunburst](docs/diskover-web-sunburst-screenshot.png?raw=true)
![diskover-web simple search](docs/diskover-web-simplesearch-screenshot.png?raw=true)
![diskover-web advanced file view](docs/diskover-web-advancedsearch-screenshot.png?raw=true)
![diskover-web search results](docs/diskover-web-searchresults-screenshot.png?raw=true)
![diskover-web file view](docs/diskover-web-fileview-screenshot.png?raw=true)

## Installation Guide

### Requirements

* `Linux` (tested on Ubuntu 16.04)
* `PHP 7.0` (tested on PHP 7.0.15)
* `Composer Dependency Manager for PHP`
* `PHP client for Elasticsearch` (elasticsearch-php)
* `Elasticsearch` (tested on Elasticsearch 5.3.0)
* `Apache or Nginx` (if you don't want to use PHP built-in web server)

### Download

```sh
$ git clone https://github.com/shirosaidev/diskover-web.git
$ cd diskover-web
```

### Install application dependencies

```sh
$ cd diskover-web
$ composer install
```


## User Guide

[Read the wiki](https://github.com/shirosaidev/diskover-web/wiki).


## License

See the [license file](https://github.com/shirosaidev/diskover-web/LICENSE).
