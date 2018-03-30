<?php
/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

require '../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);


// Get total directory size, count, mtime from Elasticsearch (recursive) for path
function get_dir_info($client, $index, $path, $filter, $mtime) {
    $totalsize = 0;
    $totalcount = 0;
    $searchParams['body'] = [];

    // first try to get dir size and items from directory doc (faster)

    // Setup search query
    $searchParams['index'] = $index;
    $searchParams['type'] = 'directory';

    // escape any special characters in path
    $escapedpath = escape_chars($path);

    if ($path === '/') {  // root /
        $searchParams['body'] = [
            'size' => 1,
            '_source' => ["filesize","items"],
            'query' => [
                'query_string' => [
                    'query' => 'path_parent: ' . $escapedpath . ' AND filename: ""'
                ]
            ]
        ];
    } else {
        $p = escape_chars(dirname($path));
        $f = escape_chars(basename($path));
        $searchParams['body'] = [
            'size' => 1,
            '_source' => ["filesize","items","last_modified"],
            'query' => [
                'query_string' => [
                    'query' => 'path_parent: ' . $p . ' AND filename: ' . $f
                ]
            ]
        ];
    }

    // Send search query to Elasticsearch
    $queryResponse = $client->search($searchParams);

    // Get total count of directory and all subdirs
    $totalcount = (int)$queryResponse['hits']['hits'][0]['_source']['items'];

    // Get total size of directory and all subdirs
    $totalsize = (int)$queryResponse['hits']['hits'][0]['_source']['filesize'];


    // fallback to slower method to get total size and items
    if ($totalcount <= 1 && $totalsize == 0) {
        // Setup search query to find all files in the directory (recursive)
        $searchParams['index'] = $index;
        $searchParams['type'] = 'file';

        // escape any special characters in path
        $escapedpath = escape_chars($path);

        if ($escapedpath === '\/') {  // root /
            $searchParams['body'] = [
                'size' => 0,
                '_source' => [],
                    'query' => [
                        'query_string' => [
                            'query' => 'path_parent: ' . $escapedpath . '* AND filesize: >=' . $filter . '
                            AND last_modified: {* TO ' . $mtime . '}',
                            'analyze_wildcard' => 'true'
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
        } else {
            $searchParams['body'] = [
                'size' => 0,
                '_source' => [],
                    'query' => [
                        'query_string' => [
                            'query' => '(path_parent: ' . $escapedpath . ' OR
                            path_parent: ' . $escapedpath . '\/*) AND
                            filesize: >=' . $filter . ' AND last_modified: {* TO ' . $mtime . '}',
                            'analyze_wildcard' => 'true'
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
        }

        // Send search query to Elasticsearch
        $queryResponse = $client->search($searchParams);

        // Get total count of files (recursive)
        $totalcount = (int)$queryResponse['hits']['total'];

        // Get total size of directory and all subdirs (total file size)
        $totalsize = (int)$queryResponse['aggregations']['dir_size']['value'];

        // Get directory doc counts
        $searchParams['type'] = 'directory';

        if ($escapedpath === '\/') {  // root /
            $searchParams['body'] = [
                'size' => 0,
                '_source' => [],
                    'query' => [
                        'query_string' => [
                            'query' => 'path_parent: ' . $escapedpath . '*
                            AND last_modified: {* TO ' . $mtime . '}',
                            'analyze_wildcard' => 'true'
                        ]
                    ]
                ];
        } else {
            $searchParams['body'] = [
                'size' => 0,
                '_source' => [],
                    'query' => [
                        'query_string' => [
                            'query' => '(path_parent: ' . $escapedpath . ' OR
                            path_parent: ' . $escapedpath . '\/*) AND
                            last_modified: {* TO ' . $mtime . '}',
                            'analyze_wildcard' => 'true'
                        ]
                    ]
                ];
        }

        // Send search query to Elasticsearch
        $queryResponse = $client->search($searchParams);

        // Add total count of directories + 1 for itself
        $totalcount += (int)$queryResponse['hits']['total'] + 1;
    }

    // Create dirinfo list with total size (of all files), total count (items)
    $dirinfo = [$totalsize, $totalcount];

    return $dirinfo;
}

function get_files($client, $index, $path, $filter, $mtime) {
    // gets the 100 largest files in the current directory (path)
    $items = [];
    $searchParams['body'] = [];

    // Setup search query
    $searchParams['index'] = $index;
    $searchParams['type']  = 'file';

    // search size
    $searchParams['size'] = 100;

    $escapedpath = addcslashes($path, '+-&|!(){}[]^"~*?:\/ ');
    $searchParams['body'] = [
                '_source' => ["path_parent","filename","filesize"],
                'query' => [
                    'query_string' => [
                        'query' => 'path_parent: ' . $escapedpath . ' AND
                        filesize: >=' . $filter . ' AND last_modified: {* TO ' . $mtime . '}'
                    ]
                ],
                'sort' => [
                    'filesize' => [
                    'order' => 'desc'
                    ]
                ]
        ];

    // Send search query to Elasticsearch and get scroll id and first page of results
    $queryResponse = $client->search($searchParams);

    // Get files
    $results = $queryResponse['hits']['hits'];

    // Add files to items array
    foreach ($results as $result) {
        if ($path === '/') {  // root /
            $items[] = [
                "name" => $result['_source']['path_parent'] . $result['_source']['filename'],
                "size" => $result['_source']['filesize'],
                "type" => 'file'
            ];
        } else {
            $items[] = [
                "name" => $result['_source']['path_parent'] . '/' . $result['_source']['filename'],
                "size" => $result['_source']['filesize'],
                "type" => 'file'
            ];
        }
    }

    return $items;
}


function get_sub_dirs($client, $index, $path, $filter, $use_count) {
    // gets the largest sub dirs by filesize or item count (use_count true)
    // non-recursive
    $dirs = [];

    $searchParams['body'] = [];

    // Setup search query
    $searchParams['index'] = $index;
    $searchParams['type']  = "directory";

    // search size
    $searchParams['size'] = 100;

    // diff query if root path /
    if ($path === '/') {
        $query = '(path_parent: \/ NOT path_parent: \/*\/* NOT filename: "") AND filesize: >=' . $filter;
    } else {
        // escape special characters
        $escapedpath = addcslashes($path, '+-&|!(){}[]^"~*?:\/ ');
        $query = '(path_parent: ' . $escapedpath . ' NOT path_parent: ' . $escapedpath . '\/*) AND filesize: >=' . $filter;
    }

    $searchParams['body'] = [
        '_source' => ["path_parent", "filename"],
            'query' => [
                'query_string' => [
                'query' => $query
            ]
        ]
    ];

    // sort directories by size or file count
    if ($use_count === 1) {
        $searchParams['body']['sort'] = [
            'items' => [
                'order' => 'desc'
            ]
        ];
    } else {
        $searchParams['body']['sort'] = [
            'filesize' => [
                'order' => 'desc'
            ]
        ];
    }

    // Send search query to Elasticsearch and get results
    $queryResponse = $client->search($searchParams);

    // Get directories
    $results = $queryResponse['hits']['hits'];

    foreach ($results as $arr) {
        if ($path === '/') {
            $dirs[] = $arr['_source']['path_parent'] . $arr['_source']['filename'];
        } else {
            $dirs[] = $arr['_source']['path_parent'] . '/' . $arr['_source']['filename'];
        }
    }

    return $dirs;
}

function walk_tree($client, $index, $path, $filter, $mtime, $depth, $maxdepth, $use_count=0, $show_files=1) {
    $items = [];
    $subdirs = [];
    if ($depth === $maxdepth) {
        return $items;
    }

    // get files in current path (not recursive)
    if ($show_files === 1) {
        $items = get_files($client, $index, $path, $filter, $mtime);
    }

    // get directories in current path (not recursive)
    $subdirs = get_sub_dirs($client, $index, $path, $filter, $use_count);

    // return if there are no sub directories
    if (count($subdirs) === 0) {
        return $items;
    }

    // loop through all subdirs and add to subdirs_size and subdirs_count arrays
    $subdirs_size = [];
    $subdirs_count = [];
    //$subdirs_mtime = [];

    foreach ($subdirs as $d) {
        // get dir total size and file count
        $dirinfo = get_dir_info($client, $index, $d, $filter, $mtime);
        // if directory is empty don't show it in the tree
        if ($dirinfo[0] === 0 || $dirinfo[1] === 0) {
            continue;
        } else {
            $subdirs_size[$d] = $dirinfo[0];
            $subdirs_count[$d] = $dirinfo[1];
        }
    }

    // create new subdirs array with reverse sort by size or count
    $subdirs = ($use_count === 1) ? $subdirs_count : $subdirs_size;
    arsort($subdirs);

    // add subdirs to items array
    foreach ($subdirs as $key => $value) {
        $items[] = [
            "name" => $key,
            "size" => $subdirs_size[$key],
            "count" => $subdirs_count[$key],
            "type" => 'directory',
            "children" => walk_tree($client, $index, $key, $filter, $mtime, $depth+=1, $maxdepth, $use_count, $show_files)
        ];
        $depth-=1;
    }

    return $items;
}


function get_file_mtime($client, $index, $path, $filter, $mtime) {
    // gets file modified ranges in the current directory (path)
    $items = [];
    $searchParams['body'] = [];

    // Setup search query
    $searchParams['index'] = $index;
    $searchParams['type'] = 'file';

    $searchParams['body'] = [
        'size' => 0,
        'query' => [
            'bool' => [
                'must' => [
                        'wildcard' => [ 'path_parent' => $path . '*' ]
                ],
                'filter' => [
                    'bool' => [
                        'must' => [
                            'range' => [
                                'filesize' => [
                                    'gte' => $filter
                                ]
                            ]
                        ],
                        'should' => [
                            'range' => [
                                'last_modified' => [
                                    'lte' => $mtime
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'aggs' => [
            'mtime_ranges' => [
                'range' => [
                    'field' => 'last_modified',
                    'keyed' => true,
                    'ranges' => [
                        ['key' => 'today', 'from' => 'now/d', 'to' => 'now'],
                        ['key' => 'yesterday', 'from' => 'now-1d/d', 'to' => 'now/d'],
                        ['key' => '1-7days', 'from' => 'now-1w/d', 'to' => 'now-1d/d'],
                        ['key' => '8-30days', 'from' => 'now-1M/d', 'to' => 'now-1w/d'],
                        ['key' => '31-90days', 'from' => 'now-3M/d', 'to' => 'now-1M/d'],
                        ['key' => '91-180days', 'from' => 'now-6M/d', 'to' => 'now-3M/d'],
                        ['key' => '181-365days', 'from' => 'now-1y/d', 'to' => 'now-6M/d'],
                        ['key' => '1-2years', 'from' => 'now-2y/d', 'to' => 'now-1y/d'],
                        ['key' => '2-3years', 'from' => 'now-3y/d', 'to' => 'now-2y/d'],
                        ['key' => '3-5years', 'from' => 'now-5y/d', 'to' => 'now-3y/d'],
                        ['key' => '5-10years', 'from' => 'now-10y/d', 'to' => 'now-5y/d'],
                        ['key' => 'over 10 years', 'to' => 'now-10y/d']
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

    // Add mtimes to items array
    foreach ($results as $key => $result) {
        $items[] = [
                    "mtime" => $key,
                    "count" => $result['doc_count'],
                    "size" => $result['file_size']['value']
                    ];
    }

    return $items;
}

function get_file_sizes($client, $index, $path, $filter, $mtime) {
    // gets file size ranges in the current directory (path)
    $items = [];
    $searchParams['body'] = [];

    // Setup search query
    $searchParams['index'] = $index;
    $searchParams['type'] = 'file';

    $searchParams['body'] = [
        'size' => 0,
        'query' => [
            'bool' => [
                'must' => [
                        'wildcard' => [ 'path_parent' => $path . '*' ]
                ],
                'filter' => [
                    'bool' => [
                        'must' => [
                            'range' => [
                                'filesize' => [
                                    'gte' => $filter
                                ]
                            ]
                        ],
                        'should' => [
                            'range' => [
                                'last_modified' => [
                                    'lte' => $mtime
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'aggs' => [
            'filesize_ranges' => [
                'range' => [
                    'field' => 'filesize',
                    'keyed' => true,
                    'ranges' => [
                        ['key' => '0KB-1KB', 'from' => 0, 'to' => 1024],
                        ['key' => '1KB-4KB', 'from' => 1024, 'to' => 4096],
                        ['key' => '4KB-16KB', 'from' => 4096, 'to' => 16384],
                        ['key' => '16KB-64KB', 'from' => 16384, 'to' => 65536],
                        ['key' => '64KB-256KB', 'from' => 65536, 'to' => 262144],
                        ['key' => '256KB-1MB', 'from' => 262144, 'to' => 1048576],
                        ['key' => '1MB-4MB', 'from' => 1048576, 'to' => 4194304],
                        ['key' => '4MB-16MB', 'from' => 4194304, 'to' => 16777216],
                        ['key' => '16MB-64MB', 'from' => 16777216, 'to' => 67108864],
                        ['key' => '64MB-256MB', 'from' => 67108864, 'to' => 268435456],
                        ['key' => '256MB-1GB', 'from' => 268435456, 'to' => 1073741824],
                        ['key' => '1GB-4GB', 'from' => 1073741824, 'to' => 4294967296],
                        ['key' => '4GB-16GB', 'from' => 4294967296, 'to' => 17179869184],
                        ['key' => 'over 16GB', 'from' => 17179869184]
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
    $results = $queryResponse['aggregations']['filesize_ranges']['buckets'];

    // Add mtimes to items array
    foreach ($results as $key => $result) {
        $items[] = [
                    "filesize" => $key,
                    "count" => $result['doc_count'],
                    "size" => $result['file_size']['value']
                    ];
    }
    return $items;
}

function get_file_ext($client, $index, $path, $filter, $mtime) {
    // gets the top 10 file extensions in the current directory (path) recursive
    $items = [];
    $searchParams['body'] = [];

    // Setup search query
    $searchParams['index'] = $index;
    $searchParams['type']  = 'file';

    $searchParams['body'] = [
            'size' => 0,
            'query' => [
                'bool' => [
                    'must' => [
                            'wildcard' => [ 'path_parent' => $path . '*' ]
                    ],
                    'filter' => [
                        'bool' => [
                            'must' => [
                                'range' => [
                                    'filesize' => [
                                        'gte' => $filter
                                    ]
                                ]
                            ],
                            'should' => [
                                'range' => [
                                    'last_modified' => [
                                        'lte' => $mtime
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'aggs' => [
                'top_extensions' => [
                    'terms' => [
                        'field' => 'extension',
                        'order' => [
                            'ext_size' => 'desc'
                        ],
                        'size' => 100
                    ],
                    'aggs' => [
                        'ext_size' => [
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

    // Get file extensions
    $results = $queryResponse['aggregations']['top_extensions']['buckets'];

    // Add file extension to items array
    foreach ($results as $result) {
        $items[] = [
                    "name" => $result['key'],
                    "count" => $result['doc_count'],
                    "size" => $result['ext_size']['value']
                    ];
    }

    return $items;
}
