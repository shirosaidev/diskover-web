# diskover web

diskover web is the front-end for [diskover](https://github.com/shirosaidev/diskover), allowing you to search your indexed files and tag them for deletion, archival or keeping. It is written in PHP, [Bootstrap](http://getbootstrap.com/) and [jQuery](https://jquery.com/).

## Screenshots

![diskover web dashboard](docs/diskover-web-dashboard-screenshot.png?raw=True)
![diskover web simple search](docs/diskover-web-simplesearch-screenshot.png?raw=True)
![diskover web advanced file view](docs/diskover-web-advancedsearch-screenshot.png?raw=True)
![diskover web search results](docs/diskover-web-searchresults-screenshot.png?raw=True)
![diskover web file view](docs/diskover-web-fileview-screenshot.png?raw=True)

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

```
This software is licensed under the Apache 2 license, quoted below.

Copyright 2017 Chris Park

Licensed under the Apache License, Version 2.0 (the "License"); you may not
use this file except in compliance with the License. You may obtain a copy of
the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
License for the specific language governing permissions and limitations under
the License.
```
