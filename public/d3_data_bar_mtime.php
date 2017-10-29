<?php

require __DIR__ . '/../vendor/autoload.php';
use diskover\Constants;

error_reporting(E_ALL ^ E_NOTICE);
require __DIR__ . "/../src/diskover/Diskover.php";

// Connect to Elasticsearch
$client = connectES();

// return utc time in ES format
function getmtime($mtime) {
    // default 0 days mtime filter
    if (empty($mtime) || $mtime == 0) {
        $mtime = gmdate("Y-m-d\TH:i:s", strtotime("now"));
    } elseif ($mtime == "1m") {
        $mtime = gmdate("Y-m-d\TH:i:s", strtotime("-1 month"));
    } elseif ($mtime == "3m") {
        $mtime = gmdate("Y-m-d\TH:i:s", strtotime("-3 months"));
    } elseif ($mtime == "6m") {
        $mtime = gmdate("Y-m-d\TH:i:s", strtotime("-6 months"));
    } elseif ($mtime == "1y") {
        $mtime = gmdate("Y-m-d\TH:i:s", strtotime("-1 year"));
    } elseif ($mtime == "2y") {
        $mtime = gmdate("Y-m-d\TH:i:s", strtotime("-2 years"));
    } elseif ($mtime == "3y") {
        $mtime = gmdate("Y-m-d\TH:i:s", strtotime("-3 years"));
    } elseif ($mtime == "10y") {
        $mtime = gmdate("Y-m-d\TH:i:s", strtotime("-10 years"));
    } elseif ($mtime == "100y") {
        $mtime = gmdate("Y-m-d\TH:i:s", strtotime("-100 years"));
    }
    return $mtime;
}

// Get total directory size and count from Elasticsearch (recursive)
function get_dir_info($client, $path, $filter, $mtime) {
    $totalsize = 0;
    $totalcount = 0;
    $searchParams['body'] = [];

    // Setup search query
    $searchParams['index'] = Constants::ES_INDEX;
    $searchParams['type']  = Constants::ES_TYPE;

    $path = addcslashes($path, '+-&&||!(){}[]^"~*?:\/ ');
    $searchParams['body'] = [
        'size' => 0,
        'query' => [
            'query_string' => [
                'query' => '"' . $path . '" AND filesize: >' . $filter . ' AND last_modified: {* TO ' . $mtime . '}'
            ]
        ],
        'aggs' => [
            'dir_size' => [
                'sum' => [
                    'field' => 'filesize'
                ]
            ]
        ]
    ];

    // Send search query to Elasticsearch
    $queryResponse = $client->search($searchParams);

    // Get total count of directory and all subdirs
    $totalcount = $queryResponse['hits']['total'];

    // Get total size of directory and all subdirs
    $totalsize = $queryResponse['aggregations']['dir_size']['value'];

    // Create dirinfo list with size and count
    $dirinfo = [$totalsize, $totalcount];

    return $dirinfo;
}

function get_file_mtime($client, $path, $filter, $mtime) {
    // gets file modified ranges in the current directory (path)
    $items = [];
    $searchParams['body'] = [];

    // Setup search query
    $searchParams['index'] = Constants::ES_INDEX;
    $searchParams['type'] = Constants::ES_TYPE;

    $path = addcslashes($path, '+-&&||!(){}[]^"~*?:\/ ');
    $searchParams['body'] = [
        'size' => 0,
        'query' => [
            'query_string' => [
                'query' => 'path_parent: ' . $path . '* AND filesize: >' . $filter . ' AND last_modified: {* TO ' . $mtime . '}',
                'analyze_wildcard' => 'true'
            ]
        ],
        'aggs' => [
            'mtime_ranges' => [
                'range' => [
                    'field' => 'last_modified',
                    'keyed' => true,
                    'ranges' => [
                        ['key' => '1m-now', 'from' => getmtime('1m'), 'to' => getmtime(0)],
                        ['key' => '3m-1m', 'from' => getmtime('3m'), 'to' => getmtime('1m')],
                        ['key' => '6m-3m', 'from' => getmtime('6m'), 'to' => getmtime('3m')],
                        ['key' => '1y-6m', 'from' => getmtime('1y'), 'to' => getmtime('6m')],
                        ['key' => '2y-1y', 'from' => getmtime('2y'), 'to' => getmtime('1y')],
                        ['key' => '3y-2y', 'from' => getmtime('3y'), 'to' => getmtime('2y')],
                        ['key' => '10y-3y', 'from' => getmtime('10y'), 'to' => getmtime('3y')],
                        ['key' => '*-10y', 'from' => getmtime('100y'), 'to' => getmtime('10y')]
                    ]
                ],
                'aggs' => [
                    'file_size' => [
                        'sum' => [
                            'field' => 'filesize'
                        ]
                    ]
                ]
            ]
        ]
    ];

    // Send search query to Elasticsearch and get scroll id and first page of results
    $queryResponse = $client->search($searchParams);

    // Get mtime ranges
    $results = $queryResponse['aggregations']['mtime_ranges']['buckets'];

    // Add file extension to items array
    foreach ($results as $key => $result) {
        $items[] = [
                    "mtime" => $key,
                    "count" => $result['doc_count'],
                    "size" => $result['file_size']['value']
                    ];
    }

    return $items;
}

function get_es_path($client) {
    // try to get a top level path from ES

    $searchParams['body'] = [];

    // Setup search query
    $searchParams['index'] = Constants::ES_INDEX;
    $searchParams['type']  = "directory";

    // number of results to return
    $searchParams['size'] = 10;

    $searchParams['body'] = [
        '_source' => ["path"],
        'query' => [
            'match_all' => (object) []
        ],
        'sort' => [
            'path' => [
                'order' => 'asc'
            ]
        ]
    ];

    // Send search query to Elasticsearch and get results
    $queryResponse = $client->search($searchParams);

    // Get directories
    $results = $queryResponse['hits']['hits'];

    // set path to first path found
    $path = $results[0]['_source']['path'];

    return $path;
}

$path = $_GET['path'];
$filter = $_GET['filter']; // file size
$mtime = $_GET['mtime']; // file mtime

// default 1 MB min file size filter
if (empty($filter)) {
    $filter = 1048576;
}

$mtime = getmtime($mtime);

// check if no path (grab one from ES)
if (empty($path)) {
    $path = get_es_path($client);
}

// get dir total size and file count
$dirinfo = get_dir_info($client, $path, $filter, $mtime);

// check for error
if ($dirinfo[0] == 0) {
    echo json_encode([ "error" => "nothing found" ]);
    exit;
}

$data = [
    "name" => $path,
    "size" => $dirinfo[0],
    "count" => $dirinfo[1],
    "children" => get_file_mtime($client, $path, $filter, $mtime)
];

echo json_encode($data);
