<?php

/**
 * @file
 * Contains \Drupal\graphapi\Plugin\Filter\FilterTrivialGraphFormat.
 */

namespace Drupal\graphapi\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to limit allowed HTML tags.
 *
 * @Filter(
 *   id = "filter_tgf",
 *   module = "filter",
 *   title = @Translation("Trivial Graph Format"),
 *   type = FILTER_TYPE_TRANSFORM_IRREVERSIBLE,
 *   settings = {
 *     "formats" = "",
 *     "filter_html_help" = 1,
 *     "filter_html_nofollow" = 0
 *   },
 *   weight = -10
 * )
 */
class FilterTrivialGraphFormat extends FilterBase {

  static $TGF = '[tgf';
  var $text = NULL;
  var $start = -1;
  var $end = -1;
  var $meta = array();
  var $graph = NULL;

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $form['formats'] = array(
      '#type' => 'textfield',
      '#title' => t('What formats are allowed?'),
      '#default_value' => $this->settings['formats'],
      '#maxlength' => 1024,
      '#description' => t('Description.'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode, $cache, $cache_id) {
    drupal_set_message(print_r($text, TRUE));
    $this->text = $text;

    $this->start = strpos($this->text, FilterTrivialGraphFormat::$TGF);
    while ($this->start !== FALSE) {
      if ($this->parse()) {
        $this->replace($this->settings['formats']);
        $this->meta = array();
        $this->graph = NULL;
      }
      else {
        break;
      }
      $this->start = strpos($this->text, FilterTrivialGraphFormat::$TGF);
    }
    return $this->text;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return "output";
  }

  function parse() {
    $this->end = strpos($this->text, ']', $this->start);
    if ($this->start < $this->end) {
      $lines = substr($this->text, $this->start, $this->end - $this->start);
      $lines = explode("\n", $lines);
      // consume first line [tgf ...
      $meta = array_shift($lines);
      $this->parseMeta($meta);
      $graph = graphapi_new_graph();
      $mode = 'nodes';
      while ($line = array_shift($lines)) {
        $line = trim($line);
        if ($line == '#') {
          $mode = 'links';
        }
        elseif (empty($line)) {
          // Skip empty lines
        }
        else if ($mode == 'nodes') {
          $items = preg_split('/ /', $line);
          $id = array_shift($items);
          $title = trim(join(' ', $items));
          graphapi_add_node($graph, $id);
          if ($title) {
            graphapi_set_node_title($graph, $id, $title);
          }
        }
        else if ($mode == 'links') {
          $items = preg_split('/ /', $line);
          $from_id = array_shift($items);
          $to_id = array_shift($items);
          $title = trim(join(' ', $items));
          graphapi_add_link($graph, $from_id, $to_id);
          if ($title) {
            graphapi_set_link_title($graph, $from_id, $to_id, $title);
          }
        }
      }
      $this->graph = $graph;
      return TRUE;
    }
    return FALSE;
  }

  function replace($engine) {
    if ($this->start != $this->end) {
      $config = array(
        'engine' => $engine,
        'inline' => TRUE,
      );
      if (!empty($this->meta)) {
        $config = array_merge($config, $this->meta);
      }
      $g = theme('graphapi_dispatch', array('graph' => $this->graph, 'config' => $config));
      $this->text = substr($this->text, 0, $this->start) . $g . substr($this->text, $this->end + 1);
    }
  }

  /**
   * Process meta line to set engine etc.
   *
   * @param string $meta
   *   Contains '[tgf ...'
   */
  function parseMeta($meta) {
    // TODO: remove ugly escaping
    $result = array();
    $meta = preg_replace("/\\" . FilterTrivialGraphFormat::$TGF . "/", '', $meta, 1);
    $meta = trim($meta);
    $metas = preg_split("/ /", $meta);
    foreach ($metas as $key_value) {
      if (strpos($key_value, ':') !== FALSE) {
        list($key, $value) = preg_split('/:/', $key_value);
        if ($key == 'engine') {
          $result['engine'] = $value;
        }
      }
    }
    $this->meta = $result;
  }

}
