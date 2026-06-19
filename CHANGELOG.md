# Changelog
## [Unreleased] - yyyy-mm-dd

### Added

### Changed

### Fixed

### Updated

## [10.3.5] - 2026-06-19


### Added
- request sanitazion

## [10.3.4] - 2026-06-18


### Changed
- hook and filter name update
- hook and filter name update
- hook and filter name update
- prefix all hooks with plugin name

### Fixed
- scheduled tasks

## [10.3.3] - 2026-06-15


## [10.3.2] - 2026-06-15


## [10.3.1] - 2026-06-15


## [10.3.0] - 2026-06-15


### Fixed
- parm bug

## [10.2.9] - 2026-06-15


## [10.2.8] - 2026-06-13


## [10.2.7] - 2026-06-13


### Changed
- prefix meta key in get_users

### Fixed
- shared code loader
- activation hook
- use correct shortcodes on auto created pages

## [10.2.6] - 2026-06-11


### Added
- placeholder for textdomain
- user, post and rest_meta prefixing

### Changed
- prefixed post metas and shortcodes

### Fixed
- prefix meta_query

## [10.2.5] - 2026-06-09


## [10.2.4] - 2026-06-09


### Added
- usage of wpdb->prepare for all queries
- shared functionality loader

### Changed
- comply to coding standards
- code layout
- namespaced all constants
- sanitize all posts and get vars

### Fixed
- spacing problem
- space before dot bug

## [10.2.3] - 2026-06-03


### Added
- escaping functions
- echo escapaping

### Changed
- html to dom elements
- use gmdate instead of date

## [10.2.2] - 2026-06-01


### Changed
- merged hooks.md into readme.md

### Fixed
- added domain to __ function

## [10.2.1] - 2026-06-01


### Changed
- use named params for userSelect function

## [10.2.0] - 2026-05-30


### Changed
- do not store get_plugin_data in global variable

## [10.1.9] - 2026-05-29


### Added
- wp_unslash
- wp_unslash

## [10.1.8] - 2026-05-28


### Fixed
- empty setting bug

## [10.1.7] - 2026-05-27


### Fixed
- empty array bugs

## [10.1.6] - 2026-05-24


### Fixed
- array index bug

## [10.1.5] - 2026-05-23


### Fixed
- emptt start_date error

## [10.1.4] - 2026-05-22


### Fixed
- bugs

## [10.1.3] - 2026-05-21


### Fixed
- define 

## [10.1.2] - 2026-05-16


### Fixed
- after update

## [10.1.1] - 2026-05-14


### Changed
- date( to gmdate(

## [10.1.0] - 2026-05-12


### Changed
- permission callback for rest api

## [10.0.7] - 2026-05-11


### Changed
- added permission_callback

### Updated
- js

## [10.0.6] - 2026-05-08


### Added
- cancel button in popup

## [10.0.5] - 2026-05-07


### Changed
- replaced sweetalert

## [10.0.4] - 2026-05-06


### Changed
- changed tsjippy-table class to tsjippy table

## [10.0.3] - 2026-05-06


## [10.0.1] - 2026-05-03


### Changed
- removed the redirection at activation as it is done by the share plugin
- use shared github workflows

## [10.0.0] - 2026-05-01


### Added
- trailing slash to pluginpath connstant
- redirection to settings page on plugin activation

### Changed
- main plugin name from sim-base to tsjippy-shared-functionality
- from module to plugin
- sim prefix to tsjippy prefix
- base namespace to TSJIPPY
- filternames to include tsjippy
- block apt to version 3
- PLUGINCONSTANT value
- lib updates
- table columns
- recurrence selector code
- exclude .vscode from releases
- updated github workflow versions

## [8.3.5] - 2026-03-04


### Changed
- do not try to create events that are in the past

## [8.3.4] - 2025-12-12


### Changed
- sim_before_saving_formdata filter to sim_before_submitting_formdata

## [8.3.3] - 2025-11-21


### Changed
- formresults to submission

### Fixed
- double classes attribute

## [8.3.2] - 2025-11-04


### Changed
- clearer data attributes

## [8.3.1] - 2025-11-03


### Changed
- stop listening to events if we have a match

## [8.3.0] - 2025-10-31


### Fixed
- getting family picture

## [8.2.9] - 2025-10-30


### Changed
- new format for frontendcontent
- use upgrade.php not install-helper.php
- use new family class
- reaction to family data

## [8.2.8] - 2025-10-25


### Fixed
- ajax search
- db query

## [8.2.7] - 2025-10-20


### Changed
- using array_filter

## [8.2.6] - 2025-10-16


### Fixed
- databse queiries
- storing event end dates

## [8.2.5] - 2025-10-13


### Changed
- element names and classes
- data attribute names
- page maintenance
- dataset names

### Fixed
- bugs

## [8.2.4] - 2025-10-06


### Changed
- classname

## [8.2.3] - 2025-09-26


### Changed
- classnames replace _ with -

## [8.2.2] - 2025-09-25


### Changed
- js generated loader

### Fixed
- hide loaders

## [8.2.1] - 2025-09-24


### Changed
- cleaner interface while adding schedules
- loader image
- loader size

## [8.2.0] - 2025-08-07


### Added
- 'sim-events-event-reminder' action
- 'sim-theme-archive-page-title' filter

## [8.1.8] - 2025-07-03


### Added
- exclude private events from search

## [8.1.7] - 2025-04-28


### Fixed
- base picture path

## [8.1.6] - 2025-04-27


### Fixed
- check for valid user when creating events

## [8.1.5] - 2025-02-24


### Added
- administror has admin role by default

### Fixed
- repeated yearly events

## [8.1.4] - 2025-02-13


### Changed
- use site date and time format

## [8.1.3] - 2025-02-11


### Changed
- sim_module_updated filter to new format

## [8.1.2] - 2025-02-10


### Changed
- removed personnelinfo role

## [8.1.1] - 2025-02-09


### Fixed
- 0 years celebrations

## [8.1.0] - 2025-01-17


### Added
- missing_events shortcode

### Changed
- after update hook

## [8.0.9] - 2024-12-12


### Fixed
- editing time slots

## [8.0.8] - 2024-11-27


### Fixed
- typo
- php memory exhaustion

## [8.0.6] - 2024-11-22


### Fixed
- duplicate function names

## [8.0.5] - 2024-11-22


### Changed
- removed anonymous functions

## [8.0.4] - 2024-11-19


### Changed
- removed anomynous functions

## [8.0.3] - 2024-10-17


### Added
- 'sim-events-event-url' filter

### Changed
- readme

### Updated
- blocks
- blocks

## [8.0.2] - 2024-10-11


### Changed
- upgrade deps
- enqueing of styles and scripts

## [8.0.0] - 2024-10-04


## [8.0.0] - 2024-10-03
