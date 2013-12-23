<?php

/**
 * @file
 * Contains \Drupal\graphapi\Controller\GraphAPIController.
 */

namespace Drupal\graphapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Fhaculty\Graph\Graph;

/**
 * Returns responses for devel module routes.
 */
class GraphAPIController extends ControllerBase {

  /**
   * Returns a list of all currently defined user functions in the current
   * request lifecycle, with links their documentation.
   */
  public function functionReference() {
    $functions = get_declared_classes();
    sort($functions);
    $links = array();
    foreach ($functions as $function) {
      $links[] = $function;
    }
    return theme('item_list', array('items' => $links));
  }

  public function engines() {
    $engines = $this->getEngines();
    if (empty($engines)) {
      return "No modules found.";
    }
    $links = array();
    foreach ($engines as $engine => $title) {
      $links[] = l($title, "admin/config/system/graphapi/engines/$engine");
    }
    return theme('item_list', array('items' => $links));
  }

  public function engine($engine) {

    $engines = $this->getEngines();
    if (key_exists($engine, $engines)) {
      graphapi_loader();
      $graph = $this->demoGraph();
      $options = array(
        'engine' => $engine,
      );
      $vars = array(
        'graph' => $graph,
        'config' => $options,
      );
      return theme_graphapi_dispatch($vars);
    }
    else {
      return "Error";
    }
    $types = _graphapi_class_services();
    return graphapi_class_build($types, FALSE);
  }

  /**
   * Get all modules implementing graphapi_formats
   * 
   * @todo: should this be protected?
   * @return type
   */
  private function getEngines() {
    // Change notice https://drupal.org/node/1894902
    $module_handler = \Drupal::moduleHandler();
    return $module_handler->invokeAll('graphapi_formats', $args = array());
  }

  function getImplementations() {
    return array(
      'boo',
      'graphapi_formats',
    );
  }

  public function demoGraph() {
    $graph = graphapi_new_graph();

    graphapi_set_node_content($graph, 'graphapi', $this->demoModuleContent('graphapi'));
    graphapi_set_node_uri($graph, 'graphapi', 'http://drupal.org/project/graphapi');

    graphapi_set_node_content($graph, 'thejit', $this->demoModuleContent('thejit'));

    graphapi_set_node_content($graph, 'views', $this->demoModuleContent('views', 'drupal'));
    graphapi_set_node_uri($graph, 'views', 'http://drupal.org/project/drupal');

    graphapi_set_node_content($graph, 'views_ui', $this->demoModuleContent('views_ui', 'drupal'));

    $edge = graphapi_add_link($graph, 'graph_phyz', 'graphapi');
    graphapi_set_link_title($edge, "Render DOM + Canvas");

    $edge = graphapi_add_link($graph, 'thejit', 'thejit_spacetree');
    graphapi_set_link_title($edge, "One of many renderings");

    graphapi_add_link($graph, 'thejit', 'thejit_forcedirected');

    graphapi_add_link($graph, 'thejit_spacetree', 'graphapi');
    graphapi_add_link($graph, 'thejit_forcedirected', 'graphapi');

    graphapi_add_link($graph, 'graphapi', 'views');
    graphapi_add_link($graph, 'views_ui', 'views');


//    $sub = $this->demoSubgraph();
//    graphapi_add_sub_graph($graph, 'S', $sub);
//    graphapi_set_node_title($graph, 'S', 'Subgraph if supported');
//    graphapi_add_link($graph, 'graphapi', 'A');
//    graphapi_add_link($graph, 'A', 'S');

    return $graph;
  }

  private function demoSubgraph() {
    $graph = graphapi_new_graph();

    graphapi_add_link($graph, 'A', 'B');
    graphapi_add_link($graph, 'B', 'C');
    return $graph;
  }

  private function demoModuleContent($id, $project_id = NULL) {
    $text = '';
    if (is_null($project_id)) {
      $text .= "Project: " . $id . '<br/>';
      $project_id = $id;
    }
    else {
      $text .= "$id is part of project: " . $project_id . '<br/>';
    }
    $text .= 'See ' . l("$project_id", 'http://drupal.org/project/' . $project_id) . ' on drupal.org';
    return $text;
  }

}
