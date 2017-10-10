<?php

namespace diskover;

class Constants {
		// diskover-web version
		const VERSION = '1.3.0';
    // set to your Elasticsearch host or ip
    const ES_HOST = 'localhost';
    // set to your Elasticsearch port, default 9200
    const ES_PORT = 9200;
    // Elasticsearch index you want to use or diskover-* for all
    const ES_INDEX = 'diskover-*';
    const ES_TYPE = 'file';
    // set following two lines if using X-Pack http-auth
    const ES_USER = '';
    const ES_PASS = '';
    // set to true if using AWS
    const AWS = false;
    // diskover.py socket listener
    const SOCKET_LISTENER_HOST = '127.0.0.1';
    const SOCKET_LISTENER_PORT = 9999;
}