<?php
/*
Copyright (C) Chris Park 2017-2018
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

require '../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require "../src/diskover/Diskover.php";
require "d3_inc.php";


// number of items to get
if (!empty($_GET['numdocs'])) {
    $num = $_GET['numdocs'];
} else {
    $num = 50;
}

// Get search results from Elasticsearch for crawl stats showing the
// directories that took the most amount of time to crawl

$results = [];
$searchParams = [];
$slowestcrawlers = [];
$sizes = [];
$items = [];
$filecount = [];
$directorycount = [];
$paths = [];
$dirnames = [];
$crawltimes = [];

$searchParams['index'] = $esIndex;
$searchParams['type'] = 'directory';
$searchParams['body'] = [
    '_source' => ['path_parent', 'filename', 'crawl_time', 'items', 'items_files', 'items_subdirs', 'filesize'],
    'size' => $num,
    'query' => [
        'match_all' => (object) []
     ],
     'sort' => [
         'crawl_time' => [
             'order' => 'desc'
         ]
     ]
];
$queryResponse = $client->search($searchParams);
foreach ($queryResponse['hits']['hits'] as $key => $value) {
    $fullpath = $value['_source']['path_parent'] . '/' . $value['_source']['filename'];
    if ($fullpath !== $_SESSION['rootpath']) {
        $elapsed = number_format($value['_source']['crawl_time'], 3);
        $slowestcrawlers[] = ['path' => $fullpath, 'crawltime' => (float)$elapsed, 'filesize' => $value['_source']['filesize'], 'items' => $value['_source']['items'], 'directorycount' => $value['_source']['items_subdirs'], 'filecount' => $value['_source']['items_files']];
        $directorycount[] = $value['_source']['items_subdirs'];
        $filecount[] = $value['_source']['items_files'];
        $sizes[] = $value['_source']['filesize'];
        $items[] = $value['_source']['items'];
        $dirnames[] = basename($fullpath);
        $paths[] = $fullpath;
        $crawltimes[] = (float)$elapsed;
    }
}


// get first crawl index time
$searchParams['type']  = 'directory,file';
$searchParams['body'] = [
    '_source' => ['indexing_date'],
    'size' => 1,
    'query' => [
            'match_all' => (object) []
     ],
     'sort' => [
         'indexing_date' => [
             'order' => 'asc'
         ]
     ]
];
$queryResponse = $client->search($searchParams);

$firstcrawltime = $queryResponse['hits']['hits'][0]['_source']['indexing_date'];

// get last crawl index time
$searchParams['body'] = [
    '_source' => ['indexing_date'],
    'size' => 1,
    'query' => [
            'match_all' => (object) []
     ],
     'sort' => [
         'indexing_date' => [
             'order' => 'desc'
         ]
     ]
];
$queryResponse = $client->search($searchParams);

$lastcrawltime = $queryResponse['hits']['hits'][0]['_source']['indexing_date'];

// get total crawl elapsed time (cumulative)
$searchParams['type']  = 'directory';
$searchParams['body'] = [
   'size' => 0,
    'aggs' => [
      'total_elapsed' => [
        'sum' => [
          'field' => 'crawl_time'
        ]
      ]
    ],
    'query' => [
            'match_all' => (object) []
     ]
];
$queryResponse = $client->search($searchParams);

// Get total elapsed time (in seconds) of crawl(s)
$crawlelapsedtime = $queryResponse['aggregations']['total_elapsed']['value'];


// get worker bulk time and crawl times
$searchParams['type']  = 'worker';
$searchParams['body'] = [
   'size' => $num,
    'query' => [
            'match_all' => (object) []
     ],
    'sort' => [
            'crawl_time' => 'desc'
    ]
];
$queryResponse = $client->search($searchParams);

$workertopcrawltimes = [];
foreach ($queryResponse['hits']['hits'] as $key => $value) {
    $workertopcrawltimes[] = ['Worker Name' => $value['_source']['worker_name'], 'Dir Count' => $value['_source']['dir_count'], 
                                'File Count' => $value['_source']['file_count'], 'Crawl Time (sec)' => $value['_source']['crawl_time'], 
                                'Bulk Time (sec)' => $value['_source']['bulk_time'], 'Indexed At' => $value['_source']['indexing_date']];
};

$searchParams['body'] = [
   'size' => $num,
    'query' => [
            'match_all' => (object) []
     ],
     'sort' => [
            'bulk_time' => 'desc'
    ]
];
$queryResponse = $client->search($searchParams);

$workertopbulktimes = [];
foreach ($queryResponse['hits']['hits'] as $key => $value) {
    $workertopbulktimes[] = ['Worker Name' => $value['_source']['worker_name'], 'Dir Count' => $value['_source']['dir_count'], 
                                'File Count' => $value['_source']['file_count'], 'Crawl Time (sec)' => $value['_source']['crawl_time'], 
                                'Bulk Time (sec)' => $value['_source']['bulk_time'], 'Indexed At' => $value['_source']['indexing_date']];
};


// get first and last crawl index time
$searchParams['type']  = 'directory,file';
$searchParams['body'] = [
    '_source' => ['indexing_date'],
    'size' => 1,
    'query' => [
            'match_all' => (object) []
     ],
     'sort' => [
         'indexing_date' => [
             'order' => 'asc'
         ]
     ]
];
$queryResponse = $client->search($searchParams);
$firstcrawltime = $queryResponse['hits']['hits'][0]['_source']['indexing_date'];

$searchParams['body'] = [
    '_source' => ['indexing_date'],
    'size' => 1,
    'query' => [
            'match_all' => (object) []
     ],
     'sort' => [
         'indexing_date' => [
             'order' => 'desc'
         ]
     ]
];
$queryResponse = $client->search($searchParams);
$lastcrawltime = $queryResponse['hits']['hits'][0]['_source']['indexing_date'];

// Get search results from Elasticsearch for index doc counts over time

$rangesecs = [];

// Create datetime object from time string
$firstdate = DateTime::createFromFormat('Y-m-d\TH:i:s.u', $firstcrawltime);
$firstdate = $firstdate->sub(new DateInterval('PT2S'));
$lastdate = DateTime::createFromFormat('Y-m-d\TH:i:s.u', $lastcrawltime);
$lastdate = $lastdate->add(new DateInterval('PT2S'));
// Calc time diff in seconds
$duration = $firstdate->diff($lastdate);
$interval = $duration->format('%H:%I:%S');
$dt = new DateTime("1970-01-01 $interval", new DateTimeZone('UTC'));
$seconds = (int)$dt->getTimestamp();

// Create date ranges
$i = 1;
$nextdate = $lastdate;
$prevdate = clone $nextdate;
while ($i <= $seconds) {
    // Get previous time 1 sec ago
    $prevdate = $prevdate->sub(new DateInterval('PT1S'))->format('Y-m-d\TH:i:s');
    $nextdate = $nextdate->format('Y-m-d\TH:i:s');
    $rangesecs[] = [ 'from' => $prevdate, 'to' => $nextdate, 'key' => $nextdate."Z" ];
    $prevdate = DateTime::createFromFormat('Y-m-d\TH:i:s', $prevdate);
    $nextdate = clone $prevdate;
    $i += 1;
}

$results = [];
$searchParams = [];
$DocCountDateRangeD3Data = [];

$searchParams['type']  = 'worker';

$searchParams['body'] = [
   'size' => 0,
    'aggs' => [
      'range' => [
        'date_range' => [
          'field' => 'indexing_date',
          'ranges' => $rangesecs,
          'keyed' => 'true'
        ],
        'aggs' => [
        'total_count' => [
            'sum' => [
                'field' => 'dir_count',
                'field' => 'file_count'
            ]
        ]
      ]
      ]
    ]
];
$queryResponse = $client->search($searchParams);
$DocsDateRange = $queryResponse['aggregations']['range']['buckets'];

// add to data array for d3
foreach ($DocsDateRange as $key => $value) {
    $DocCountDateRangeD3Data[] = [ 'date' => $key, 'docs' => $DocsDateRange[$key]['total_count']['value'] ];
}


$data = [
    "slowestcrawlers" => $slowestcrawlers,
    "filecount" => $filecount,
    "directorycount" => $directorycount,
    "dirnames" => $dirnames,
    "paths" => $paths,
    "crawltimes" => $crawltimes,
    "sizes" => $sizes,
    "items" => $items,
    "bulkdocs" => $DocCountDateRangeD3Data,
    "workertopcrawltimes" => $workertopcrawltimes,
    "workertopbulktimes" => $workertopbulktimes
];

echo json_encode($data);

?>
