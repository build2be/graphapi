<?php

/**
 * @file
 * This file contains no working PHP code; it exists to provide additional
 * documentation for doxygen as well as to document hooks in the standard
 * Drupal manner.
 */

/**
 * @defgroup graphapi Graph API module integrations.
 *
 * Module integrations with the graphapi module.
 *
 */

/**
 * Defines Graph display formats.
 *
 * @return
 *   An array with key value pair defining the callback and display name.
 */
function hook_graphapi_formats() {
  return array(
    'graphapi_graphviz_filter' => 'Graphviz Filter',
  );
}

/**
 * Provides the engine settings form.
 *
 * The settings form is used by the views display format but also by the
 * engines config pages or other unforseen places.
 *
 * @return
 *   A Drupal sub-form.
 * @see graphapi_global_settings_form()
 * @see graphapi_settings_form()
 * @see _graphapi_engine_form()
 * @see graph_phyz_graphapi_settings_form()
 */
function hook_graphapi_settings_form() {
  // tbd
}

// @see http://drupal.org/node/1513198
function hook_graphapi_default_settings() {
  // tbd
}

/**
 * Example function: creates a graph of user logins by day.
 */
function user_last_login_by_day($n=40) {
  $query = db_select('users');
  $query->addField('users', 'name');
  $query->addField('users', 'uid');
  $query->addField('users', 'created');
  $query->condition('uid', 0, '>');
  $query->orderBy('created', 'DESC');
  $query->range(0, $n);
  $result = $query->execute();
  $g = graphapi_new_graph();
  $now = time();
  $days = array();
  foreach ($result as $user) {
    $uid = $user->uid;
    $user_id = 'user_' . $uid;

    $day = intval(($now - $user->created) / (24 * 60 * 60));
    $day_id = 'data_' . $day;
    graphapi_set_node_title($g, $user_id, l($user->name, "user/" . $uid));
    graphapi_set_node_title($g, $day_id, "Day " . $day);
    graphapi_set_link_data($g, $user_id, $day_id, array('color' => '#F0F'));
  }
  $options = array(
    'width' => 400,
    'height' => 400,
    'item-width' => 50,
    'engine' => 'graph_phyz'
  );
  return theme('graphapi_dispatch', array('graph' => $g, 'config' => $options));
}
