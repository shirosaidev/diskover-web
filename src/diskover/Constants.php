<?php

namespace diskover;

class Constants {
    // diskover-web version
    const VERSION = '1.4.0';
    // set to your Elasticsearch host or ip
    const ES_HOST = 'localhost';
    // set to your Elasticsearch port, default 9200
    const ES_PORT = 9200;
    // set following two lines if using X-Pack http-auth
    const ES_USER = '';
    const ES_PASS = '';
    // set to true if using AWS
    const AWS = false;
    // diskover.py socket listener
    const SOCKET_LISTENER_HOST = '127.0.0.1';
    const SOCKET_LISTENER_PORT = 9999;
    // additional custom ES fields to display in search results
    const EXTRA_FIELDS = "";  // ['md5_checksum' => 'MD5 Checksum']
    // default min file size (bytes) filter
    const FILTER = 1;
    // default mtime filter
    const MTIME = 0;
    // default maxdepth filter
    const MAXDEPTH = 2;
    // default don't use count (use size)
    const USE_COUNT = 0;
}
