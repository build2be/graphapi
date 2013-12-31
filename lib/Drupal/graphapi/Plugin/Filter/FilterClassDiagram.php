<?php

/**
 * @file
 * Contains \Drupal\graphapi\Plugin\Filter\FilterClassDiagram.
 */

namespace Drupal\graphapi\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter for rendering Trivial Graph Format.
 *
 * @Filter(
 *   id = "filter_classdiagram",
 *   module = "graphapi",
 *   title = @Translation("UML Class Diagram"),
 *   type = FILTER_TYPE_TRANSFORM_IRREVERSIBLE,
 *   weight = -10
 * )
 */
class FilterClassDiagram extends FilterBase {

  static $START_TOKEN = '[classdiagram';
  var $text = NULL;
  var $start = -1;
  var $end = -1;
  var $meta = array();
  var $names = NULL;

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode, $cache, $cache_id) {
    $this->text = $text;
//return $text;
    $this->start = strpos($this->text, FilterClassDiagram::$START_TOKEN);
    while ($this->start !== FALSE) {
      if ($this->parse()) {
        try {
          $this->replace();
        }
        catch (Exception $exc) {
          drupal_set_message($exc->getMessage());
        }

        $this->meta = array();
        $this->graph = NULL;
      }
      else {
        break;
      }
      $this->start = strpos($this->text, FilterClassDiagram::$START_TOKEN);
    }
    return $this->text;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return self::help($long);
  }

  static function help($long = FALSE) {
    if ($long) {
      $items = array();
      foreach (self::getMetas() as $key => $data) {
        $items[] = $data['example'] . ' ' . $data['description'];
      }
      $metas = theme('item_list', array('items' => $items, 'title' => 'Optional configurations are'));

      return 'Generate an UML Class Diagram by adding a list of \\ started package + class paths <br/>'
          . '<code>[classdiagram<br/>\Drupal\graphapi\Plugin\Filter\FilterClassDiagram<br/>]</code><br/>'
          . $metas
      ;
    }
    else {
      return 'Generate an UML Class Diagram';
    }
  }

  function parse() {
    $this->end = strpos($this->text, ']', $this->start);
    if ($this->start < $this->end) {
      $lines = substr($this->text, $this->start, $this->end - $this->start);
      $lines = explode("\n", $lines);
      // consume first line [tgf ...
      $meta = array_shift($lines);
      $this->parseMeta($meta);
      $names = array();
      $mode = 'nodes';
      while ($line = array_shift($lines)) {
        $line = trim($line);
        if (empty($line)) {
          // Skip empty lines
        }
        else if ($mode == 'nodes') {
          $items = preg_split('/ /', $line, 2);
          $id = array_shift($items);
          $name = $id;
          // Make sure $name is a proper class loader name
          $m = '';
          // Wrong seperator
          if (strpos($name, '/') !== FALSE) {
            $name = strtr($name, '/', '\\');
            $m .= ' toggling / into \\';
          }
          // Missing inital \
          if ('\\' != $name[0]) {
            $name = '\\' . $name;
            $m .= ' prepending a \\';
          }
          if ($id !== $name) {
            drupal_set_message("We changed your input ID from $id into $name by" . $m);
          }
          $names[] = $name;
        }
      }
      $this->names = $names;
      return TRUE;
    }
    return FALSE;
  }

  function replace() {
    if ($this->start != $this->end) {
      $diagram = graphapi_uml_class_diagram($this->names, $this->meta);
      $this->text = substr($this->text, 0, $this->start) . $diagram . substr($this->text, $this->end + 1);
    }
  }

  /**
   * Process meta line to set engine etc.
   *
   * @param string $meta
   *   Contains '[tgf ...'
   */
  function parseMeta($meta) {
    $defaults = array();
    foreach (self::getMetas() as $key => $data) {
      $defaults[$key] = $data['default'];
    }
    $result = array();
    // The start is done by a [ which must be escaped for the regex: \\[
    $meta = preg_replace("/\\" . FilterClassDiagram::$START_TOKEN . "/", '', $meta, 1);
    $meta = trim($meta);
    $metas = preg_split("/ /", $meta);
    foreach ($metas as $key_value) {
      if (strpos($key_value, ':') !== FALSE) {
        list($key, $value) = preg_split('/:/', $key_value);
        if (isset($defaults[$key])) {
          $result[$key] = $value;
        }
      }
    }
    $this->meta = $result + $defaults;
  }

  static function getMetas() {
    // Options available for ClassDiagramBuilder
    $meta = array(
      'add-parents' => array(
        'default' => true,
        'description' => 'whether to show add parent classes or interfaces',
      ),
      'only-self' => array(
        'default' => true,
        'description' => 'whether to only show methods/properties that are actually defined in this class (and not those merely inherited from base)',
      ),
      'show-private' => array(
        'default' => false,
        'description' => 'whether to also show private methods/properties',
      ),
      'show-protected' => array(
        'default' => true,
        'description' => 'whether to also show protected methods/properties',
      ),
      'show-constants' => array(
        'default' => true,
        'description' => 'whether to show class constants as readonly static variables (or just omit them completely)',
      ),
    );

    // Our input filter options.
    $meta['generate-script'] = array(
      'description' => 'Adds the script for the Class Diagram',
      'default' => FALSE,
    );
    $meta['generate-image'] = array(
      'description' => 'Adds the image for the Class Diagram',
      'default' => TRUE,
    );

    foreach ($meta as $key => &$data) {
      if (is_bool($data['default'])) {
        if ($data['default']) {
          $data['example'] = "$key:0 or 1 (default)";
        }
        else {
          $data['example'] = "$key:1 or 0 (default)";
        }
      }
      else {
        $data['example'] = "$key:" . $data['default'];
      }
    }
    return $meta;
  }

}
