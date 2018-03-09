<?php
/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

namespace diskover;

class Constants {
    // diskover-web version
    const VERSION = '1.5.0-beta.3';
    // set to your Elasticsearch host or ip
    const ES_HOST = 'localhost';
    // set to your Elasticsearch port, default 9200
    const ES_PORT = 9200;
    // set following two lines if using X-Pack http-auth
    const ES_USER = '';
    const ES_PASS = '';
    // login auth for diskover-web
    const LOGIN_REQUIRED = FALSE;
    const DISKOVER_USER = 'diskover';
    const DISKOVER_PASS = 'darkdata';
    // set to true if using AWS
    const AWS = false;
    // diskover.py socket listener
    const SOCKET_LISTENER_HOST = '127.0.0.1';
    const SOCKET_LISTENER_PORT = 9999;
    // default min file size (bytes) filter
    const FILTER = 1;
    // default mtime filter
    const MTIME = 0;
    // default maxdepth filter
    const MAXDEPTH = 2;
    // default don't use count (use size)
    const USE_COUNT = 0;
    // default show files on analytics pages
    const SHOW_FILES = 1;
    // default results per search page
    const SEARCH_RESULTS = 50;
}
