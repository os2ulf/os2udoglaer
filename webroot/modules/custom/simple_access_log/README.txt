CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Installation
 * Configuration
 * Notes
 * Maintainers



INTRODUCTION
------------
Simple Access Log replicates the basic features/functions of the Core Access Log functions that were included
as part of the statistics module, that were not ported to Drupal 8.

Read more about the features and use of Simple Access Log at its Drupal.org project page at
[https://www.drupal.org/project/simple_access_log](https://www.drupal.org/project/simple_access_log)

INSTALLATION
------------

The Simple Access Log module can be installed like other Drupal modules by placing this directory
in the Drupal file system (modules directory) and enabling on
the Drupal Modules page (/admin/modules).

KNOWN ISSUES
------------

This module  has a known compatibility issue with the Drupal Core "Internal Page Cache" module. When the Internal Page
Cache module is enabled the simple access log is not able to retrieve information regarding page visits from anonymous
users. Internal Page Cache does not affect this module's ability to log page visits for logged in users. There is no
current work around for this issue.



CONFIGURATION
------------

Simple Access Log can be configured on your Drupal site at Administration - Configuration
(/admin/config/system/simple_access_log).

### Options
* Don't log UID 0 - Do not log Anonymous page visits
* Don't log UID 1 - Do not log Super User (UID 1) page visits
* Don't log Admin users -Do not log for users with "Administrator" role
* Don't log Admin paths - Skip logging for any admin paths. 
  * Any paths that start with "/admin/*".
  * Enabled by default.
* Log retention period - Length of time to keep access logs.
  * 4 Months (16 Weeks)
  * 4 Weeks (default value)
  * 2 Weeks
  * 1 Week
  * 3 Days
  * 1 Day
* Exclude Paths - Specify paths (including wildcard) to exclude from being logged.
* Disable warnings about the internal page cache module - Prevents warnings from appearing on the status report. See Known Issues for more information.

NOTES
------------

## Cron Data Cleanup

On each cron run any data older than the Log retention period setting (4 weeks by default) will be permanently deleted 
from the database.

## Performance

This module runs early in the Drupal execution process, before most other modules and page content is generated. It adds
 an extra database insert to every page request that is received. While this should be a negligible increase in load times
 it will have an impact. Using the configuration options to disable logging for certain users may be able to accelerate 
 pages if performance issues arise.

 MAINTAINERS
 -----------

 Current maintainer:
  * Benjamin Townsend (benjaminarthurt) - https://drupal.org/user/2501220

 This project has been sponsored by:
  * Townsend Consulting Services
    https://www.townsendservices.com