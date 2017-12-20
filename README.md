# diskover-web

[![License](https://img.shields.io/github/license/shirosaidev/diskover-web.svg?label=License&maxAge=86400)](./LICENSE.txt)
[![Release](https://img.shields.io/github/release/shirosaidev/diskover-web.svg?label=Release&maxAge=60)](https://github.com/shirosaidev/diskover-web/releases/latest)
[![Donate Patreon](https://img.shields.io/badge/Donate%20%24-Patreon-brightgreen.svg)](https://www.patreon.com/diskover)
[![Donate PayPal](https://img.shields.io/badge/Donate%20%24-PayPal-brightgreen.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CLF223XAS4W72)

diskover-web is the web file manager and storage analytics application for [diskover](https://github.com/shirosaidev/diskover). It is designed to help you quickly search your file system (using Elasticsearch diskover indices), file tagging and provide detailed disk usage analytics for your file system.

diskover-web allows exporting of file lists in json/csv, also REST API can be used to view or update data in diskover indices.

It is written in HTML5, CSS3, PHP, Javascript, [jQuery](https://jquery.com/), [Bootstrap](http://getbootstrap.com/) and [D3.js](https://d3js.org).

## Screenshots

![diskover-web dashboard](docs/diskover-web-dashboard-screenshot.png?raw=true)
![diskover-web file tree](docs/diskover-web-filetree-screenshot.png?raw=true)
![diskover-web treemap](docs/diskover-web-treemap-screenshot.png?raw=true)
![diskover-web treemap](docs/diskover-web-heatmap-screenshot.png?raw=true)
![diskover-web simple search](docs/diskover-web-simplesearch-screenshot.png?raw=true)
![diskover-web advanced search](docs/diskover-web-advancedsearch-screenshot.png?raw=true)
![diskover-web search results](docs/diskover-web-searchresults-screenshot.png?raw=true)
![diskover-web admin panel](docs/diskover-web-adminpanel-screenshot.png?raw=true)

## Installation Guide

### Requirements

* `Linux or OS X/MacOS` (tested on Ubuntu 16.04, OS X 10.11.6)
* `PHP 7.0` (tested on PHP 7.1.10)
* `Composer Dependency Manager for PHP`
* `PHP client for Elasticsearch` ([elasticsearch-php](https://github.com/elastic/elasticsearch-php), tested on 5.3.2)
* `Elasticsearch` (tested on Elasticsearch 5.4.2, 5.6.4)
* `Apache or Nginx` (if you don't want to use PHP built-in web server)
* `diskover` (Elasticsearch index created by diskover)

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
