<?php

/**
 * @file
 * Contains \Drupal\graphapi\Controller\GraphAPIController.
 */

namespace Drupal\graphapi_class\Controller;

use Drupal\Core\Controller\ControllerBase;
use Fhaculty\Graph\Graph;

/**
 * Returns responses for devel module routes.
 */
class GraphAPIClassController extends ControllerBase {

  /**
   * List all found classes, interfaces and traits.
   */
  public function listAll() {
    $all = graphapi_class_get_all();
    $links = array_values($all);
    asort($links);
    return theme('item_list', array('items' => $links));
  }

}
