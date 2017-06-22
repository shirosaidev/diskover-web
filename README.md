# diskover web

diskover web is the web panel for [diskover](https://github.com/shirosaidev/diskover), allowing you to search your indexed files and tag them for deletion, archival or keeping. It is written in PHP, [Bootstrap](http://getbootstrap.com/) and [jQuery](https://jquery.com/).

## Screenshots

![diskover web dashboard](https://raw.githubusercontent.com/shirosaidev/diskover/master/docs/diskover-web-dashboard-screenshot.png)
![diskover web simple search](https://raw.githubusercontent.com/shirosaidev/diskover/master/docs/diskover-web-simplesearch-screenshot.png)
![diskover web advanced file view](https://raw.githubusercontent.com/shirosaidev/diskover/master/docs/diskover-web-advancedsearch-screenshot.png)
![diskover web search results](https://raw.githubusercontent.com/shirosaidev/diskover/master/docs/diskover-web-searchresults-screenshot.png)
![diskover web file view](https://raw.githubusercontent.com/shirosaidev/diskover/master/docs/diskover-web-fileview-screenshot.png)

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

See the [license file](https://github.com/shirosaidev/diskover-web/blob/master/LICENSE).
