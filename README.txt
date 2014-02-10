# Introduction

This API for managing Graph data and rendering these by internal or external engines.

Graph API used PHP libraries from various places. These dependencies must be
installed manually for the time being.

This Drupal 8 version needs a composer install ran from the modules directoy.

  $ cd graphapi
  $ composer install --no-dev

If you do not have composer installed follow the instruction on https://getcomposer.org/

# GraphViz executable

In order to generate PNG or SVG output your must make sure the graphviz
executable is available.

Check for the path to the dot executable

  $ which dot

Visit admin/config/system/graphapi:

- Select Graph API settings
- Visit /admin/config/system/graphapi/settings
  - Fill in the path to the dot executable ie : /usr/local/bin/dot

# Test your configuration

Visit admin/config/system/graphapi

- Select Graph API Formats
  - Check the output of all available formats
