<?php

namespace GraphAPI\Component\Graph;

use GraphAPI\Component\Graph\DirectedGraph;

class AcyclicDirectedGraph extends DirectedGraph {

  /**
   * Prevent adding a cycle.
   *
   * @param type $from_id
   * @param type $to_id
   */
  function addLink($from_id, $to_id) {
    $sub = $this->subGraph(array($to_id));
    $p = $sub->getParticipants();
    if (in_array($from_id, $p)) {
      throw new \Exception("Cannot add Cycle '$from_id' -> '$to_id' to " . $this);
    }
    else {
      parent::addLink($from_id, $to_id);
    }
  }

  /**
   * Calculates the Topological Sorted List.
   *
   * A Topological Sorted List is a Depth First Search ordered
   * list of participants.
   *
   * TODO: Do we need a Directed Acyclic Graph?
   * If there are cycles/loops then the algorithme does not loop forever.
   * But the TSL is not really a TSL.
   *
   * The algorithme is based on the Iterator example from
   * the book Higher Order Perl where a recusive function
   * can be rewritten into a loop.
   *
   * @param array $ids
   *   List of nodes interested in.
   * @return array
   *   The TSL ordered list of participants
   */
  public function getTSL($ids = array()) {
    $g = $this->subGraph($ids);
    // By adding a root the DFS is more cleaner/predictable for tests
    $g->addRoot();
    $agenda = array($g->getRoot());
    $visited = array();
    $tsl = array();
    while ($inspect = array_pop($agenda)) {
      if (!isset($visited[$inspect])) {
        $visited[$inspect] = TRUE;
        $links = $g->getLinks($inspect);
        if (!empty($links)) {
          array_push($agenda, $inspect);
          //$agenda = array_merge( $agenda, array_diff( $links, array_keys( $visited)));
          foreach ($links as $id) {
            if (!isset($visited[$id])) {
              $agenda[] = $id;
            }
          }
        }
        else {
          // We are done with this node.
          $tsl[] = $inspect;
        }
      }
      else {
        // Already inspected so spit it out.
        $tsl[] = $inspect;
      }
    }
    return array_diff($tsl, array($g->getRoot()));
  }

}
