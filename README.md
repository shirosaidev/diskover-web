# diskover-web

diskover-web is the file manager and disk analysis web app for [diskover](https://github.com/shirosaidev/diskover). It will help you quickly search for files on your storage (using diskover Elasticsearch indices) and files can be tagged for deletion, archival or keeping. Custom file tags are also supported.

A built-in REST API can also be used to list or update file tags in diskover indices.

diskover-web was originally designed to help vfx studios manage their data. It is written in PHP, [Bootstrap](http://getbootstrap.com/), [jQuery](https://jquery.com/) and [D3.js](https://d3js.org).

## Screenshots

![diskover-web dashboard](docs/diskover-web-dashboard-screenshot.png?raw=true)
![diskover-web file tree](docs/diskover-web-filetree-screenshot.png?raw=true)
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
