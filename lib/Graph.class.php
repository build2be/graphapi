<?php

namespace GraphAPI\Component\Graph;

/**
 * @file
 *   Provides Graph related Classes.
 *
 * The following classes are available:
 * - Graph which is a basic graph.
 * - DirectedGraph which is based on Graph.
 *
 * Future extensions could provide:
 * - Tree which is a special Directed Acyclic Graph.
 */

/**
 * A graph is a list of links between nodes.
 *
 * Each link is bidirectional.
 */
class Graph {

  const GRAPH_LINKS = '_links';
  const GRAPH_DATA = '_data';
  const GRAPH_LINK_NO_KEY = '_link_no_key';

  /**
   * _list contains a pair of (node id, array of links to node id)
   *
   *  A graph like a -- b -- c -- a can be stored as
   *  (a, (b))
   *  (b, (c))
   *  (c, (a))
   *
   * but also like
   *  (a, (b,c))
   *  (b, (c))
   *  (c, ())
   *
   * @var array()
   *
   */
  protected $_list = array();

  /**
   * Adds id to the list of nodes.
   *
   * @param String $id
   *   The Id of the node to add.
   * @return Graph
   */
  public function add($id, $data = NULL) {
    if (!isset($this->_list[$id])) {
      $this->_list[$id] = array();
      $this->_list[$id][Graph::GRAPH_LINKS] = array();
      $this->_list[$id][Graph::GRAPH_DATA] = $data;
    }
    return $this;
  }

  public function getNodeIds() {
    return array_keys($this->_list);
  }

  public function getNodeData($id) {
    return $this->_list[$id][Graph::GRAPH_DATA];
  }

  /**
   * Adds a link between two node ids.
   *
   * @param String $from_id
   *   The start point of the link.
   * @param String $to_id
   *   The end point of the link.
   * @return Graph
   */
  public function addLink($from_id, $to_id, $data = NULL, $key = GRAPH::GRAPH_LINK_NO_KEY) {
    $this->add($from_id);
    $this->add($to_id);
    $this->_addLink($from_id, $to_id, $data, $key);
    return $this;
  }

  public function getLinkKeys($from_id, $to_id) {
    return array_keys($this->_list[$from_id][Graph::GRAPH_LINKS][$to_id]);
  }

  public function getLinkData($from_id, $to_id, $key = GRAPH::GRAPH_LINK_NO_KEY) {
    return $this->_list[$from_id][Graph::GRAPH_LINKS][$to_id][$key][GRAPH::GRAPH_DATA];
  }

  /**
   * Implementation of bidirection links between the given node ids.
   *
   * @param string $from_id
   * @param string $to_id
   * @param any $data
   *   Can hold anything. Not it get's duplicate unless it's a reference.
   * @param string $key
   *   Unique key to identify this particular link relation.
   */
  protected function _addLink($from_id, $to_id, $data, $key) {
    $this->_list[$from_id][Graph::GRAPH_LINKS][$to_id][$key]['_id'] = $to_id;
    $this->_list[$from_id][Graph::GRAPH_LINKS][$to_id][$key][GRAPH::GRAPH_DATA] = $data;

    $this->_list[$to_id][Graph::GRAPH_LINKS][$from_id][$key]['_id'] = $from_id;
    $this->_list[$to_id][Graph::GRAPH_LINKS][$from_id][$key][GRAPH::GRAPH_DATA] = $data;
  }

  /**
   * Adds a list of links to a node.
   *
   * @param string $from_id
   * @param array $to_ids
   *
   * @return Graph
   */
  public function addLinks($from_id, $to_ids) {
    foreach ($to_ids as $to_id) {
      $this->addLink($from_id, $to_id);
    }
    return $this;
  }

  /**
   * Returns all links from the give node id.
   *
   * @param string $from_id
   * @return array of node ids leaving the given node id.
   */
  public function getLinks($id) {
    if (isset($this->_list[$id])) {
      return array_keys($this->_list[$id][Graph::GRAPH_LINKS]);
    }
  }

  /**
   * Gives all participants related to the given node(s).
   *
   * @param array $list
   *   The list of Ids interested in.
   * @return array
   *   All Ids related to the given list.
   */
  public function getParticipants($list = array()) {
    if (empty($list)) {
      return array_keys($this->_list);
    }
    $visited = array();
    $agenda = array_values($list);
    while ($id = array_shift($agenda)) {
      // Prevent infinite looping
      if (!isset($visited[$id])) {
        $visited[$id] = TRUE;
        $links = $this->getLinks($id);
        if (is_array($links)) {
          $agenda = array_merge($agenda, $links);
        }
      }
    }
    return array_keys($visited);
  }

  public function isCircularMember($id) {
    $route = $this->getParticipants(array($id));
    foreach ($route as $visited_id) {
      $links = $this->getLinks($visited_id);
      if (is_array($links) && in_array($id, $links)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  public function __toString() {
    $result = array();
    foreach (array_keys($this->_list) as $id) {
      $row = $id . '(';
      $links = $this->getLinks($id);
      if (is_array($links)) {
        $row .= join(',', $links);
      }
      $row .= ')';
      $result[] = $row;
    }
    return get_class($this) . ': ' . join(",", $result);
  }

}
