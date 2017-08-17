<?php

namespace diskover;

class Constants {
    // set to your Elasticsearch host or ip
    const ES_HOST = 'localhost';
    // Elasticsearch index you want to use or diskover-* for all
    const ES_INDEX = 'diskover-*';
    const ES_TYPE = 'file';
    // set following two lines if using X-Pack http-auth
    const ES_USER = '';
    const ES_PASS = '';
}
