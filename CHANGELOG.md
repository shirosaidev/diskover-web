# Diskover Web Change Log

# [1.3.3] - 2017-10-29
### added
- search history to simple search page
- file/directory pie chart in file manager can now be clicked to see results in search
- extension pie chart in file manager can now be clicked to see results in search
- last modified time bar chart in file manager can now be clicked to see results in search
- data change animation to last modified time bar chart in file manager
- 100y to 10y range to last modified time bar chart in file manager
- maxdepth buttons on treemap page
### changed
- colors of charts and treemap
- default maxdepth on treemap page is now set to 2
- removed directory search link (magnify glass icons) in file tree
- set scroll to fast for file tree charts
- setting filesize filter and mtime filter on filetree page affects treemap
- moved all filter buttons on filetree page to top right
### fixed
- long file name break word wrap in file view page
- on file tree page clicking on magnify glass icon for directories containing spaces caused no results to be found
- removed left scroll for charts

# [1.3.2] - 2017-10-19
### added
- query search input in top nav now shows query previously searched from simple search
- cookie functions in Diskover.php
- sort order is now saved in cookie when changed in search results
- filesize total to results
### changed
- modified file results human readable bytes format to match filetree
### fixed
- All Untagged button at bottom of search results not untagging all files

# [1.3.1] - 2017-10-17
### added
- pie chart for file extensions on file tree page
- bar chart for file modified times on file tree page

# [1.3.0] - 2017-10-10
### added
- Admin Panel page and Admin link to nav bar
- copy custom tag to all buttons on search results page
- added search button links to filetree directories
- new config entries to Constants.php
### changed
- increased size of pie chart in filetree
### fixed
- sorting results on search page using arrows properly updates url parameters
- clicking Select All buttons updates number of changes that need to be saved
- user/pass auth issues when running on AWS (Diskover.php)

# [1.2.5] - 2017-10-02
### added
- search results page tag buttons to top of page as well as bottom
- help page and help link on nav bar
- error handling for invalid search query
- search within text input now searches custom tag inputs
- alert box showing number of unsaved changes on search results page when tagging
- sort by arrows to search results field columns
### changed
- improved file tree directory browsing by loading data on mouseover (previously was onclick)
- switched to php header reload for tag files form submit, previously was javascript
- visual changes for results page and nav bar
- set default search results sort order to be path_parent then filename
### fixed
- white screen flash when tagging files

# [1.2.4] - 2017-09-25
### added
- Analytics drop down to nav bar
- Treemap d3 visualization to Analytics
- Treemap icon button to search results page
### changed
- moved File Tree link to Analytics drop down in nav bar

# [1.2.3] - 2017-09-24
### fixed
- file tree working with root / path
- mtime filter for files on filetree
- issue with opening file tree page with path that has spaces

# [1.2.2] - 2017-09-22
### added
- mtime filter dropdown on file tree page
- 1gb to size filter on file tree page
- untag button to remove del/arch/keep tag
- clicking file name on file tree page loads search results page with that file
### changed
- renamed Filter on file tree page to Size Filter
- optimized ES queries to reduce fields returns in results
- search results are now sorted by Parent Path by default
### fixed
- Directories with & in the name would not open on file tree page
- console error when collapsing directory on file tree page
- tag button sizes changing when clicking Select All

# [1.2.1] - 2017-09-16
### added
- bar chart under each file name on filetree page to show size percent
### changed
- removed sunburst and replaced with pie chart on filetree page
- removed maxdepth and set to load data on demand as directories traversed on filetree page
- sunburst.php renamed to filetree.php
### fixed
- not using session stored json data when refreshing File Tree page and
path didn't change

# [1.2.0] - 2017-09-10
### note
- *** REQUIRES index created with diskover >= v1.2.0 which adds directory type ***
### added
- sunburst now has breadcrumb navigation at bottom
- warning alert if file tree can't find any files in ES or other errors
- better tooltip for sunburst
- more file size filter options in file tree
- reload button on filetree page to grab new json data from Elasticsearch
- cacheing for json data on filetree page
- cookies for path, filters on filetree page
- hide threshhold dropdown on filetree page to limit items in sunburst
- d3 pie charts on dashboard for tag counts and file sizes
### changed
- swithed to using bootstrap dark theme "bootswatch slate"
- new sunburst visualization
- removed limit on max files for filetree/sunburst, switched to using maxdepth filter
- quick searches are now sorted
- file tree nav link now searches ES for top level path
- es data grabber for file tree/sunburst gets default max 3 depth from path
- moved styles in file tree to css file
- removed iframe for sunburst and moved to sunburst.js
- sunburst displays by size by default
### fixed
- issues with sunburst width and height from browser window size

# [1.1.6] - 2017-08-29
### added
- "File Tree" top nav link
- file count for each directory in tree and sunburst tooltip
- 512 KB to filter list
### changed
- optimized d3_data.php (elasticsearch data grabber for file tree/sunburst)
- sunburst now uses json data from file tree (previously did additional get request from d3_data.php)
- folder/file colors in file tree
- moved filter dropdown under path field

# [1.1.5] - 2017-08-21
### added
- rest api (api.php)
- sunburst now moves when window scrolled (long directory tree)
- input field to change path in sunburst directory tree
- search results "Parent Path" column now has filter by and view sunburst/dirtree buttons
- sunburst directory tree sizes are colored based on size
### changed
- sunburst directory tree now shows files as well as directories
- sunburst directory tree directories are sorted by name, files are sorted by size
### fixed
- removed word wrap for long paths in sunburst directory tree
- sunburst iframe scroll bars displaying sometimes
- root / path is now supported in sunburst directory tree

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
