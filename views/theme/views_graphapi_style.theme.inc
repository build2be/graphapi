<?php

function _views_graphapi_style_build_graph_data(&$vars) {
  $view = $vars["view"];
  $rows = $vars["rows"];
  $options = $vars["options"];

  $base = $view->base_table;
  if ($base == 'system') {
    // We cannot use system_rebuild_module_data as this calls build_dependencies
    // which adds transitive dependencies which we don't want.
    // @see system_rebuild_module_data()
    $modules = _system_rebuild_module_data();
    ksort($modules);
    system_get_files_database($modules, 'module');

    // Weird dry alert (some modules declare dependency on a theme
    $themes = system_rebuild_theme_data();
    foreach ($themes as $theme => $info) {
      $themes[$theme]->info['dependencies'] = array();
      if (isset($theme->info['base theme'])) {
        $themes[$theme]->info['dependencies'][] = $theme->info['base theme'];
      }
    }
    $modules += $themes;

    $graph = graphapi_new_graph();
    foreach ($view->result as $id => $row) {
      //if ($id > 20) break;
      $from_id = $row->system_name;
      $extra = $modules[$from_id];

      if ($extra->status) {
        $color = 'green';
      }
      else {
        $color = 'yellow';
      }
      if (!isset($extra->info['hidden']) || !$extra->info['hidden']) {
        graphapi_set_node_data($graph, $from_id, array('background-color' => $color));
        foreach ($extra->info['dependencies'] as $to_id) {
          // We need to cleanup the dependency. @see drupal_parse_dependency()
          $parts = explode('(', $to_id, 2);
          $to_id = trim($parts[0]);
          if (!isset($modules[$to_id])) {
            graphapi_set_node_data($graph, $to_id, array('background-color' => 'red'));
          }
          graphapi_add_link($graph, $from_id, $to_id);
        }
      }
    }
  }
  else {
    $view = $vars['view'];
    $options = $vars['options'];

    if (isset($view->build_info['substitutions']) && is_array($view->build_info['substitutions'])) {
      _views_graphapi_ugly_hack_replace_args($vars['settings'], $view->build_info['substitutions']);
    }

    $mapping = $options['mapping'];
    // Fix label field if not set yet
    if (empty($mapping['from']['label'])) {
      $mapping['from']['label'] = $mapping['from']['id'];
    }
    if (empty($mapping['to']['label'])) {
      $mapping['to']['label'] = $mapping['to']['id'];
    }

    $graph = graphapi_new_graph();

    $side = $mapping['from'];

    $mapping_from_id = $side['id'];
    $mapping_from_title = $side['label'];
    if (empty($mapping_from_title)) {
      $mapping_from_title = $mapping_from_id;
    }
    $mapping_from_uri = $side['uri'];
    $mapping_from_content = $side['content'];

    $side = $mapping['to'];
    $mapping_to_id = $side['id'];
    $mapping_to_title = $side['label'];
    if (empty($mapping_to_title)) {
      $mapping_to_title = $mapping_to_id;
    }
    $mapping_to_uri = $side['uri'];
    $mapping_to_content = $side['content'];

    foreach ($view->result as $count => $row) {
      $view->row_index = $count;
      // Node values
      $from_id = $view->field[$mapping_from_id]->render($row);
      $from_title = $view->field[$mapping_from_title]->render($row);
      graphapi_add_node($graph, $from_id);
      graphapi_set_node_title($graph, $from_id, $from_title);

      if (!empty($mapping_from_uri)) {
        $from_uri = $view->field[$mapping_from_uri]->render($row);
        graphapi_set_node_uri($graph, $from_id, $from_uri);
      }
      if (!empty($mapping_from_content)) {
        $from_content = $view->field[$mapping_from_content]->render($row);
        graphapi_set_node_content($graph, $from_id, $from_content);
      }

      $to_id = $view->field[$mapping_to_id]->render($row);
      // Make sure only add a link when exists
      // NOTE: a value of 0 or '0' is OK so cannot use empty
      if (!is_null($to_id) && $to_id != '') {
        $to_title = $view->field[$mapping_to_title]->render($row);
        graphapi_add_node($graph, $to_id);
        graphapi_set_node_title($graph, $to_id, $to_title);
        if (!empty($mapping_to_uri)) {
          $to_uri = $view->field[$mapping_to_uri]->render($row);
          graphapi_set_node_uri($graph, $to_id, $to_uri);
        }
        if (!empty($mapping_to_content)) {
          $to_content = $view->field[$mapping_to_content]->render($row);
          graphapi_set_node_content($graph, $to_id, $to_content);
        }

        graphapi_add_link($graph, $from_id, $to_id);
      }
    }
  }
  $vars['graph'] = $graph;
  $vars['graph-config'] = $vars['settings'];
}

/**
 * Replace all views placeholders.
 *
 * This is a quick hack http://drupal.org/node/1597928 but very useful
 *
 * @param string $value
 * @param type $substitutions
 */
function _views_graphapi_ugly_hack_replace_args(&$value, &$substitutions) {
  if (is_array($value)) {
    foreach( $value as $key => $dummy) {
      _views_graphapi_ugly_hack_replace_args($value[$key], $substitutions);
    }
  }
  else {
    $value = str_replace(array_keys($substitutions), $substitutions, $value);
  }
}
