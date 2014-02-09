<?php

/**
 * @file
 * Contains \Drupal\graphapi\Plugin\Filter\FilterTrivialGraphFormat.
 */

namespace Drupal\graphapi\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter for rendering Trivial Graph Format.
 *
 * @Filter(
 *   id = "filter_tgf",
 *   module = "graphapi",
 *   title = @Translation("Trivial Graph Format"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
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
    return self::help($long);
  }

  static function help($long = FALSE) {
    if ($long) {
      return 'With Trivial Graph Format you can create inline graphs.<br/>'
          . '<code>[tgf<br/>a Title a<br/>b Title b<br/>#<br/>a b Connecting a to b<br/>]</code><br/>'
          . 'See for more info <a href="http://drupal.org/project/graphapi">Graph API</a>.'
          . '<img src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIKICJodHRwOi8vd3d3LnczLm9yZy9HcmFwaGljcy9TVkcvMS4xL0RURC9zdmcxMS5kdGQiPgo8IS0tIEdlbmVyYXRlZCBieSBncmFwaHZpeiB2ZXJzaW9uIDIuMzAuMSAoMjAxMzA2MDYuMDcyNikKIC0tPgo8IS0tIFRpdGxlOiBHIFBhZ2VzOiAxIC0tPgo8c3ZnIHdpZHRoPSIxNDNwdCIgaGVpZ2h0PSIxMzRwdCIKIHZpZXdCb3g9IjAuMDAgMC4wMCAxNDMuMTUgMTM0LjAwIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIj4KPGcgaWQ9ImdyYXBoMCIgY2xhc3M9ImdyYXBoIiB0cmFuc2Zvcm09InNjYWxlKDEgMSkgcm90YXRlKDApIHRyYW5zbGF0ZSg0IDEzMCkiPgo8dGl0bGU+RzwvdGl0bGU+Cjxwb2x5Z29uIGZpbGw9IndoaXRlIiBzdHJva2U9IndoaXRlIiBwb2ludHM9Ii00LDUgLTQsLTEzMCAxNDAuMTQ4LC0xMzAgMTQwLjE0OCw1IC00LDUiLz4KPCEtLSBhIC0tPgo8ZyBpZD0ibm9kZTEiIGNsYXNzPSJub2RlIj48dGl0bGU+YTwvdGl0bGU+CjxnIGlkPSJhX25vZGUxIj48YSB4bGluazpocmVmPSJhIiB4bGluazp0aXRsZT0iVGl0bGUgYSI+CjxlbGxpcHNlIGZpbGw9Im5vbmUiIHN0cm9rZT0iYmxhY2siIGN4PSIzNiIgY3k9Ii0xMDgiIHJ4PSIzNS40NTM3IiByeT0iMTgiLz4KPHRleHQgdGV4dC1hbmNob3I9Im1pZGRsZSIgeD0iMzYiIHk9Ii0xMDIuNCIgZm9udC1mYW1pbHk9IlRpbWVzLHNlcmlmIiBmb250LXNpemU9IjE0LjAwIj5UaXRsZSBhPC90ZXh0Pgo8L2E+CjwvZz4KPC9nPgo8IS0tIGIgLS0+CjxnIGlkPSJub2RlMiIgY2xhc3M9Im5vZGUiPjx0aXRsZT5iPC90aXRsZT4KPGcgaWQ9ImFfbm9kZTIiPjxhIHhsaW5rOmhyZWY9ImIiIHhsaW5rOnRpdGxlPSJUaXRsZSBiIj4KPGVsbGlwc2UgZmlsbD0ibm9uZSIgc3Ryb2tlPSJibGFjayIgY3g9IjM2IiBjeT0iLTE4IiByeD0iMzYuMjY4MiIgcnk9IjE4Ii8+Cjx0ZXh0IHRleHQtYW5jaG9yPSJtaWRkbGUiIHg9IjM2IiB5PSItMTIuNCIgZm9udC1mYW1pbHk9IlRpbWVzLHNlcmlmIiBmb250LXNpemU9IjE0LjAwIj5UaXRsZSBiPC90ZXh0Pgo8L2E+CjwvZz4KPC9nPgo8IS0tIGEmIzQ1OyZndDtiIC0tPgo8ZyBpZD0iZWRnZTEiIGNsYXNzPSJlZGdlIj48dGl0bGU+YSYjNDU7Jmd0O2I8L3RpdGxlPgo8cGF0aCBmaWxsPSJub25lIiBzdHJva2U9ImJsYWNrIiBkPSJNMzYsLTg5LjYxNEMzNiwtNzcuMjQwMyAzNiwtNjAuMzY4NiAzNiwtNDYuMjE5OCIvPgo8cG9seWdvbiBmaWxsPSJibGFjayIgc3Ryb2tlPSJibGFjayIgcG9pbnRzPSIzOS41MDAxLC00Ni4wNTA0IDM2LC0zNi4wNTA0IDMyLjUwMDEsLTQ2LjA1MDQgMzkuNTAwMSwtNDYuMDUwNCIvPgo8dGV4dCB0ZXh0LWFuY2hvcj0ibWlkZGxlIiB4PSI4NS41NzQyIiB5PSItNTcuNCIgZm9udC1mYW1pbHk9IlRpbWVzLHNlcmlmIiBmb250LXNpemU9IjE0LjAwIj5Db25uZWN0aW5nIGEgdG8gYjwvdGV4dD4KPC9nPgo8L2c+Cjwvc3ZnPgo=" />';
    }
    else {
      return "Use trivial graph format to generate an inline graph.";
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
          $items = preg_split('/ /', $line, 2);
          $id = array_shift($items);
          $title = trim(join(' ', $items));
          // id|uri , id, uri
          $items = preg_split('/\|/', $id, 2);
          $id = array_shift($items);
          $uri = array_shift($items);
          if (empty($uri)) {
            $uri = $id;
          }
          graphapi_add_node($graph, $id);
          if ($title) {
            graphapi_set_node_title($graph, $id, $title);
          }
          if ($uri) {
            if (\strpos($uri, '/') === 0 || !is_null(parse_url($uri, PHP_URL_SCHEME))) {
              graphapi_set_node_uri($graph, $id, $uri);
            }
          }
        }
        else if ($mode == 'links') {
          $items = preg_split('/ /', $line);
          $from_id = array_shift($items);
          $to_id = array_shift($items);
          $title = trim(join(' ', $items));
          $edge = graphapi_add_link($graph, $from_id, $to_id);
          if ($title) {
            graphapi_set_link_title($edge, $title);
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
    $result = array();
    // The start is done by a [ which must be escaped for the regex: \\[
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
