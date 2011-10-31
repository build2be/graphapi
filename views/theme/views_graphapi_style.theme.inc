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
  $row = $view->result[0];
//  dsm($view->field, __FUNCTION__);
  if ($base == 'system') {
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
  else {
    // We need to check for the correct field labels
    $handler = $view->display_handler->get_handlers('field');
    $found_fields = array();

    $our_fields = array(
      'from_id' => TRUE,
      'from_label' => FALSE,
      'to_id' => TRUE,
      'to_label' => FALSE,
    );
    foreach (array_keys($handler) as $key => $field) {
      $found_fields[$handler[$field]->label()] = $handler[$field]->field_alias;
    }
    dsm($found_fields);
    $test_fields = _graphapi_style_check_fields($our_fields, $found_fields);
    if ($test_fields) {
      $vars["xml"] = $test_fields;
      return;
    }

    $from_id_field = $found_fields['from_id'];
    $from_label_field = $found_fields['from_label'];
    $to_id_field = $found_fields['to_id'];
    $to_label_field = $found_fields['to_label'];

    //merlinofchaos>	ClemensTolboom: $view->field[$field_id]->field_alias should then match 'comment_node_cid'.
    //merlinofchaos>	ClemensTolboom: Though in general you should try to avoid ever referencing what's in $row directly if you can.
    //merlinofchaos>	ClemensTolboom: There's always things like $view->style_plugin->rendered_fields[] and $view->field[$id]->get_value()
    $graph = graphapi_new_graph();
    foreach ($view->result as $id => $row) {
      //dsm($row);
      $from_id = $row->{$from_id_field};
      $from_title = isset($row->{$from_label_field}) ? $row->{$from_label_field} : $from_id;

      if (isset($row->{$found_fields['to_id']})) {
        $to_id = $row->{$found_fields['to_id']};
        $to_title = isset($row->{$to_label_field}) ? $row->{$to_label_field} : $from_id;
      }
      graphapi_add_node($graph, $from_id);
      if (isset($from_title)) {
        graphapi_set_node_title($graph, $from_id, $from_title);
      }
      if (isset($to_id)) {
        graphapi_add_node($graph, $to_id);
        graphapi_set_node_title($graph, $to_id, $to_title);
        graphapi_add_link($graph, $from_id, $to_id);
      }
    }
    //dsm($graph);
    $vars["xml"] = theme_graphapi_container($graph);
  }
}

function _graphapi_style_check_fields($our_fields, $found_fields) {
  $invalid = FALSE;
  foreach ($our_fields as $key => $value) {
    if ($value) {
      // Required field
      if (!isset($found_fields[$key])) {
        $invalid = TRUE;
      }
    }
  }
  if ($invalid) {
    return "Make sure fields are named " . join(", ", array_keys($our_fields)) . "<br />Found keys: " . join(", ", array_keys($found_fields));
  }
}
