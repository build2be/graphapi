# Introduction

This API for managing Graph data and rendering these by internal or external engines.

This Drupal 8 version needs a composer update ran in the module root.

$ cd graphapi
$ drush dl composer
$ drush composer update

# GraphViz executable

In order to generate PNG or SVG output your must make sure the graphviz
executable is available.

- check for the path to the dot executable
  - $ which dot
- admin/config/system/graphapi
  - select Graph API settings
- visit /admin/config/system/graphapi/settings
  - fill in the path to the dot executable ie : /usr/local/bin/dot

# Test your configuration

- visit admin/config/system/graphapi
- select Graph API Formats
  - check the output of all available formats
