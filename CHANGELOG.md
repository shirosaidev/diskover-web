# Diskover Web Change Log

# [1.5.0-beta.10] - 2018-03-01
### notice
- requires index created with diskover >= v1.5.0-beta.5
- this is pre-release beta for v1.5.0
### fixed
- reloading page issue when submitting forms on Admin page (Chrome)
- filter recursive on view file/directory page

# [1.5.0-beta.9] - 2018-03-30
### notice
- requires index created with diskover >= v1.5.0-beta.5
- this is pre-release beta for v1.5.0
### fixed
- file tree min size filter not applying to directories
- filter recursive dropdown on search results page

# [1.5.0-beta.8] - 2018-03-29
### notice
- requires index created with diskover >= v1.5.0-beta.5
- this is pre-release beta for v1.5.0
### changed
- file tree loads faster now by using directory doc size/items first (if already calculated)
### fixed
- bug with elapsed and cumulative times on dashboard page when crawl > 24 h

# [1.5.0-beta.7] - 2018-03-22
### notice
- requires index created with diskover >= v1.5.0-beta.5
- this is a beta pre-release for v1.5.0
### changed
= moved VERSION variable into Diskover.php out of Constants.php
- Constants.php (config file) has been renamed to Constants.php.sample to help with updating (copy Constants.php.sample to Constants.php if you don't have)

# [1.5.0-beta.6] - 2018-03-21
### notice
- requires index created with diskover >= v1.5.0-beta.5
- this is a beta pre-release for v1.5.0
### added
- elapsed time to dashboard, as well as cumulative crawl time
### changed
= improved worker bot chart on dashboard
- improved crawl stats analytics page

# [1.5.0-beta.5] - 2018-03-21
### notice
- requires index created with diskover >= v1.5.0-beta.5
- this is a beta pre-release for v1.5.0
### changed
- modified code for index changes in diskover 1.5.0-beta.5
### fixed
- various bugs

# [1.5.0-beta.4] - 2018-03-14
### notice
- requires index created with diskover >= 1.5.0
- this is a beta pre-release for v 1.5.0
### addded
- ENABLE_SOCKET_CLIENT to Constants.php to enable/disable diskover-web socket client to talk to diskover (default is FALSE disabled)
### changed
- removed vars_inc.php and moved it into Diskover.php
- better handling of global vars
- file tree page's tree now updates items/sizes based on filters, not just show the size/items of directory doc (filters set on filetree page also affect treemap, heatmap, top50 etc)
- search results page no longer checks if can connect to socket server unless ENABLE_SOCKET_CLIENT is set to TRUE in Constants.php
### fixed
- selectindices php warning for headers already sent when running in Apache
- various bugs with filetree page

# [1.5.0-beta.3] - 2018-03-09
### notice
- requires index created with diskover >= 1.5.0
- this is a beta pre-release for v 1.5.0
### added
- option in Constants.php to disable logins
- new dashboard layout
- new crawl thread usage chart with auto-refresh on dashboard (d3_data_threads.php)
- new Crawl Stats analytics page with auto-refresh (for parallel crawling) (crawlstats.php, d3_data_crawlstats.php)
- top 10 directories on dashboard
- crawl time to directory view pages (for parallel crawling)
### fixed
- total crawl time when running parallel crawls
- bug with smartsearch and using "\" escape for regular es query

# [1.5.0-beta.1] - 2018-03-06
### notice
- requires index created with diskover >= 1.5.0
- this is a beta pre-release for v 1.5.0
### added
- login page (edit Constants.php to change if login is required (default is TRUE) and username and password from default (user: diskover, pass: darkdata); user will be logged out after 1 hour if inactive
- show files option (checkbox) to file tree, treemap, heatmap pages for hiding files and just show directories, this helps to speed up treemap and heatmap
- Auth.php (in ../src/diskover/) to handle auth check and require login
- vars_inc.php - sets important vars like index,index2,path and added as require include at top of each php page
- build_url function in Diskover.php to help clean up code in php pages
- indexed percent bar chart to dashboard page in disk space overview
- improved rest api (see wiki or Help page for how-to and examples)
- you can now disable smartsearch with "\" key before entering in query
- improved crawl stats and select indices page for handling parallel crawls
- optimize indices section to Admin page (index optimization is done by expunging deleted docs which come from any doc updates or deletes), try this if your index sizes are large and have high deleted doc count (shown on admin page)
- toggle buttons for only showing files/directories on Tags analytics page
- "Untagged + no custom tag" options to quick search menu
- new d3 colors for analytics (switched to category20b)
- Smart Searches analytics page now remembers "show other files" setting (cookie)
- optimized d3_inc.php for faster load times of analytics pages
- improved filetree page for empty directories
- img extension to smartsearches !discimg
- better default colors for custom tag templates (customtags.txt)
### changed
- scroll bars colors now match theme
- improved ui on help and admin pages
- improved ui on all analytics pages
- improved chart layout and filetree for browser rendering/size on filetree page
- treemap page now changes paths to parent directory on click (same as heatmap) instead of zooming in
- improved results info box on search results page
- improved dashboard
- improved predictive search/keyword highlighting
- rest api = you can now tag directory and all items (recursive) using tagdir (see Help page or wiki for command examples)
- improved ui on smarsearches, tags and dupes analytics pages
- changed crawlstats es mapping and add_crawl_stats function to use only crawlstat doctype
- removed check for dirsizes since it is done during diskover crawl now
- removed calc dir sizes buttons for diskover socket server
- removed es search queries in d3_inc.php for directories that are 0 filesize/items since dir sizes are calculated during crawl now (diskover >= 1.5.0)
### fixed
- selectindices and admin pages would not load indexes if using ES authentication (X-pack)
- * wildcard search would show no results (predictive search)
- "duplicate files" and "untagged files" links on dashboard
- bug with export
- tags analytics page not showing sizes for directories which are tagged
- untagged links on Tags analytics page not showing just untagged files
- bug with quick search dropdown and "with any tag" option
- bug with Up level button on file tree page

# [1.4.6] - 2018-02-19
### added
- improved predictive search
- more info for searches on simple search and Help page
- improved load time of dupes analytics page
- improved default smart searches to include more extensions
- added Smart Searches analytics page
### changed
- improved Tags analytics page
- improved Dupes analytics page
- show up to 40 threads in crawl thread usage chart on dashboard (prev was 20)
- improved using paths on simple and nav search
- heatmap page will show info message when no index2 selected (prev just took you to select index page)
- File tree now shows total items (file/directory) instead of just files for file tree and pie chart
- you can click on directory and file icons on search results pages now to search the directory or view file info
- you can click on directory and file icons on Top 50 pages now to change the directory or view file info
- tags with 0 value are hidden in Tags analytics charts
- Dupes analytics page now hides md5's with total file size percent < 0.9 of total dupes file size
- items table is now hidden when \_type:file is in search query
### fixed
- when index path was / (root) would case ES error when loading dashboard page
- when index path is / (root) view page would show double slashes
- when index path is / (root) File tree page would show incorrect directory sizes
- when index path is / (root) Treemap and Heatmap page would not show directory path near Up level button
- path filter and analytics buttons when index path is / (root)
- top 50 pages when index path is / (root)
- predictive search bug when searching paths /dir1/dir2 or escaped paths \/dir1\/dir2
- nav search field not showing search query after submitting advanced search
- console errors (javascript) in treemap and heatmap for negative size values for rect width/height
- advanced search now uses >= and <= (previously was > and <) for low/high numbers when searching for filesize, mtime, atime, hardlinks ranges
- File tree page bar charts when clicked would load both directory and file doctype. Now loads just file.
- crawl time on dashboard when crawl elapsed time > 24 hours

# [1.4.5] - 2018-02-15
### notice
- requires index created with diskover >= v1.4.5.
### added
- support for diskover v1.4.5 which removed "untagged" from all file/directory tags, and changed is_dupe field to dupe_md5
- improved search including predictive search and keyword highlight results on simple search page and nav search
- smart search. You can now use !doc, !img, etc for quick shortcut searching. smart searches can be edited on Admin page and are stored in smartsearches.txt
- New analytics page "Tags" showing all tag counts and file sizes for each tag and custom tags.
- New analytics page "Dupes" showing all dupes counts and dupes file sizes for each dupe_md5 md5 sum field (requires running diskover with --tagdupes after crawl to tag dupe files)
- improved Advanced search page
- searches in advanced page are now saved to simple search history list
- escape_chars function to Diskover.php for escaping any paths sent to ES
- custom tags are now stored in customtags.txt
- form area to edit custom tags (customtags.txt) on Admin page
- default 50 results per search page (previously 100) in Constants.php (can change on search results page which will store cookie)
- filesize change % and items change % (directory) on search results pages when index2 has been selected (data comparison)
- when adding additional custom fields from plugins on admin page, fields will display up in file/directory view pages as well as on search results pages
### changed
- improved tagging system and tagging ui
- improved search results page and ui
- improved file view page
- improved nav search
- improved index select page including indication if "newest" index is still crawling. Also select dropdown is now sorted by index creation date.
- improved Top 50 analytics pages
- improved search results exporting
- improved Quick Search nav menu
- improved dashboard page
- made default search doctype to be "all", previously was file type
- updated help page
- improved Admin page and added "Edit custom tags" and "Edit smart searches" forms
### fixed
- index and index2 not in url after submitting nav bar search
- tag counts pie chart on dashboard now includes directories
- picking none on index select page for index2 causes ES error
- XSS auditor refused to execute a script error in browser console when searching
- sort arrows now properly highlight for default sort
- additional directory fields for diskover plugins not showing up on "additional fields for search results" dropdown lists on Admin page

# [1.4.2] - 2018-02-06
### notice
- diskover project is now accepting donations on PayPal. Please consider supporting if you are using diskover :) https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CLF223XAS4W72
- to see directory sizes run diskover again after crawl finishes with -S to calculate directory sizes, this will also help to speed up the analytics pages
### added
- index and index2 variables are now in url when navigating pages, you can set these to change the index
- warning on dashboard if directory sizes not calculated
### changed
- improved performance of File Tree page
- improved load time on Top 50 pages
- set index in url for api.php
### fixed
- crawl thread usage chart on dashboard page y-axis labels getting cut off when file counts very high
- es index and doctype issues in api.php
- bug with calculating directory sizes in Analytics pages when path names are similar

# [1.4.1] - 2017-12-15
### notice
- diskover project is now accepting donations on Patreon. Please consider supporting if you are using diskover :) https://www.patreon.com/diskover
### added
- notification on dashboard if no duplicate files found
- Export button to bottom of search results page to export json or csv
- crawl thread usage to dashboard (files/directories indexed per thread (queue items))
- sort by two fields in search results (previously could only sort by one)
- newest option to index select page to select the index with most recent creation date
- reindex and calculate directory size/items buttons to directory search results table (requires diskover socket server running)
- alert if no files tagged before clicking tag files submit button
### changed
- new dark theme (Bootswatch Cyborg)
- nav bar is now fixed and stays on top when scrolling
- improved dashboard layout
- removed EXTRA_FIELDS variable from Constants.php and added to Admin page to add extra fields to search results
- improved admin page
- any absolute link paths have been changed to relative
- switched from using server sent events (SSE) to XMLHttpRequests for diskover socket server connections
- renamed run_command.php to sockethandler.php for handling XMLHttpRequests to diskover socket server
### fixed
- slow load times on dashboard
- incorrect total number of files displayed on dashboard
- advanced search not returning both files and directories when picking all for doctype
- incorrect port number on admin page for socket listener status
- file tree page folder icons not pre-fetching data from Elasticsearch

# [1.4.0] - 2017-12-01
### notice
- requires index created with diskover >= 1.4.0
### added
- doctype (file/directory/all) select input to search pages/nav bar to be able to search for file or directory docs
- ability to search for and tag directory docs
- Top 10 directories to dashboard
- Top 50 to analytics nav bar drop down menu (top 50 largest, oldest, newest and users)
- top 50 button to each result row on search results page
- additional file sizes to min size dropdown filter on file tree page
- additional ranges to mtime bar chart on filetree page
- all files with custom tag to nav bar quick search menu
- new clickable filesize range chart to filetree analytics page
- path label to treemap and heatmap pages
- up level button to analytics pages
- last crawl stats to dashboard including started/finished/elapsed time
- notification on dashboard if crawl is still running
- added defaults for mtime,filter,maxdepth,use_count to Constants.php
### changed
- improved ES queries for analytics pages to return more accurate doc counts/sums
- filetree on file tree analytics page is now sorted by size/count
- better scrolling in filetree page using scrolling div for file tree
- improved filetree sorting and count/size display
- submitting file tags now goes to confirmation page
- moved common used functions for analytics to d3_inc.php and referenced file in d3_data.php, d3_data_tm.php and d3_data_hm.php
- added error detection if index deleted or no diskover indices found
- removed ES_TYPE from Constants.php
- names of column headers on search results table
- improved top10 files list on dashboard
- filetree button icon on search results page
- string comparisons from == to === (php and javascript)
- mtime dates on file tree bar chart to midnight
- getmtime function in d3_inc.php to use Elasticsearch formatted date ranges
- removed format_date function in filetree_d3_visuals.js
- using Elasticsearch formatted date ranges in filetree_d3_visuals.js
- 10 to 100 top extensions for extension pie chart on filetree page
- > to >= for filters on filetree page
- more options to mtime filter on filetree page
- clicking on heatmap will reload heatmap with parent path of rectangle clicked
- search history on simple search page is now stored in php session instead of browser cookie
- clicking directory name label in pie chart returns to parent directory on file tree page
- set default min file size filter to 1 byte (previously was 1 mb)
### fixed
- heatmap max default values and slider causing the hot areas to be more accurate to the change percent
- clicking file in filetree page loads file in search results
- improved ES queries for analytics pages
- Elasticsearch queries on analytics pages not returning exact aggregate sums from Elasticsearch
- search history not being saved
- error when index2 is deleted and still set as cookie
- clicking files in different directories than current directory on file tree page would cause search to return no results

# [1.3.5] - 2017-11-17
### added
- disk space overview and chart to dashboard showing total, used, free and available space for crawled path, also shows comparison if two indices selected
- index selector page to change elasticsearch indices
- selected indices to admin page
- Heatmap to analytics nav menu (d3 treemap + js simpleheat heatmap showing files/directories which have changed between index and index2)
- index select selectindices.php page
### changed
- elasticsearch index variable no longer stored in Constants.php, selected by env var or from new selectindices.php page
- when loading any page, if no cookies or env var for indices are found will be redirected to selectindicies.php page
- file tree pie chart now displays full path on mouseover
- clicking file tree pie chart now expands directory in file tree
- collapsing directory in file tree now updates charts
### fixed
- admin page if using host other than localhost
- d3 errors on treemap for negative rect width

# [1.3.4] - 2017-11-12
### added
- results per page select input to search results page to control number of results to return per page
- ability to add additional custom ES fields to search results table (created by diskover plugins)
- EXTRA_FIELDS to Constants.php (for custom ES fields)
- diskover icon to nav bar
- top 10 largest files to dashboard page
- more options to quick search dropdown including "Recommended files to remove" based on modified and access file times (>3 months, >1 mb)
### changed
- appearance of search results table and tag highlight
- dashboard layout
- moved page nav buttons at bottom of page to left side near results per page select input
- search history will show * when searching for all by not entering anything into search box
- nav bar search box dynamically resizes
### fixed
- prevented forms from Cross Site Scripting XSS attacks

# [1.3.3] - 2017-10-30
### added
- search history to simple search page
- file/directory pie chart in file manager can now be clicked to see results in search
- extension pie chart in file manager can now be clicked to see results in search
- last modified time bar chart in file manager can now be clicked to see results in search
- data change animation to last modified time bar chart in file manager
- 100y to 10y range to last modified time bar chart in file manager
- maxdepth buttons on treemap page
- escapeHTML special characters function to diskover.js
### changed
- improved treemap
- colors of charts and treemap
- default maxdepth on treemap page is now set to 2
- removed directory search link (magnify glass icons) in file tree
- set scroll to fast for file tree charts
- setting filesize filter and mtime filter on filetree page affects treemap
- moved all filter buttons on filetree page to top right
- cursor to pointer type for charts and filetree
### fixed
- long file name break word wrap in file view page
- on file tree page clicking on magnify glass icon for directories containing spaces caused no results to be found
- removed left scroll for charts
- path links with special characters not working on file view page

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
