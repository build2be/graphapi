<?php

/**
 * @file
 * View template to render view fields as XML.
 *
 * - $view: The view in use.
 * - $rows: Array of row objects as rendered by _views_json_render_fields
 * - $attachment: not used currently
 * - $options: The options for the style passed in from the UI.
 *
 * @ingroup views_templates
 * @see views_xml.views.inc
 */
function template_preprocess_views_graphapi_style_graphapi(&$vars) {
  $view = $vars["view"];
  $rows = $vars["rows"];
  $options = $vars["options"];
  $base = $view->base_table;

  // We cannot use system_rebuild_module_data as this calls build_dependencies
  // which adds transitive dependencies which we don't want.
  // @see system_rebuild_module_data()
  $modules = _system_rebuild_module_data();
  ksort($modules);
  system_get_files_database($modules, 'module');

  // Weird dry alert (some modules declare deppendecy on a theme
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
        if (!isset($modules[$to_id])) {
          graphapi_set_node_data($graph, $to_id, array('background-color' => 'red'));
        }
        graphapi_add_link($graph, $from_id, $to_id);
      }
    }
  }

  $vars["xml"] = theme_graphapi_container($graph);
}
