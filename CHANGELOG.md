# Diskover Web Change Log

# [1.1.5] - 2017-08-20
### added
- rest api (api.php)
- sunburst now moves when window scrolled (long directory tree)
### fixed
- removed word wrap for long paths in sunburst directory tree

# [1.1.4] - 2017-08-17
### fixed
- improved searching for paths in simple search
- bug with & character in path_parent causing sunburst to not display correctly

# [1.1.3] - 2017-08-13
### added
- sunburst tip shows MB, GB, etc instead of just bytes
- directory size to file tree on sunburst page
### changed
- search results shows MB, GB etc instead of just bytes

# [1.1.2] - 2017-08-12
### changed
- improved page load time of dashboard index page
### fixed
- bug with dashboard showing 0 for duplicate file size total

## [1.1.1] - 2017-08-12
### added
- custom tagging
- filesize filter for sunburst to reduce load times
### changed
- sunburst can handle up to 100,000 files now (previously was 10,000)
- optimized sunburst code to improve load times
- sunburst chart resizes better based on browser window size

## [1.1.0] - 2017-08-05
### added
- d3.js directory tree and sunburst chart view when clicking on parent path links
### changed
- improved page load time of dashboard
### fixed
- bug with entering paths in search

## [1.0.5] - 2017-06-25
### changed
- improved pagination code
- default sort order for results is now by filename
### fixed
- bug with scroll results

## [1.0.4] - 2017-06-24
### fixed
- bug with quick search for last access

## [1.0.3] - 2017-06-22
### added
- can change es index using env variable or in Constants.php
### changed
- improved pagination on search results page
### fixed
- error when clicking tag button with no files selected

## [1.0.2] - 2017-06-20
### added
- sort by/order in advanced search
### changed
- using elasticsearch scroll api to retrieve file results
### fixed
- fatal error when searching for more than 10,000 files

## [1.0.1] - 2017-06-01
### changed
- wording for quick search drop down items and fileview page
