<?php

$lib_path = dirname(__FILE__) . '/../lib/';

require_once $lib_path . 'Graph.class.php';
require_once $lib_path . 'DirectedGraph.class.php';
require_once $lib_path . 'AcyclicDirectedGraph.class.php';

use GraphAPI\Component\Graph\Graph;
use GraphAPI\Component\Graph\DirectedGraph;
use GraphAPI\Component\Graph\AcyclicDirectedGraph;

testGraph();
testDirectedGraph();
testAcyclicDirectedGraph();

echo "\n\n";

function testGraph() {
  $g = new Graph();
  buildCyclicGraph($g);
  dumpGraph($g, 'Cyclic');

  $g = new Graph();
  buildACyclicGraph($g);
  dumpGraph($g, 'A-Cyclic');

  $g = new Graph();
  buildDisjunctCyclicGraph($g);
  dumpGraph($g, 'Disjunct Cyclic');

  $g = new Graph();
  buildDisjunctACyclicGraph($g);
  dumpGraph($g, 'Disjunct A-Cyclic');
}

function testDirectedGraph() {
  $g = new DirectedGraph();
  buildCyclicGraph($g);
  dumpGraph($g, 'Cyclic');

  $g = new DirectedGraph();
  buildACyclicGraph($g);
  dumpGraph($g, 'A-Cyclic');

  $g = new DirectedGraph();
  buildDisjunctCyclicGraph($g);
  dumpGraph($g, 'Disjunct Cyclic');

  $g = new DirectedGraph();
  buildDisjunctACyclicGraph($g);
  dumpGraph($g, 'Disjunct A-Cyclic');
}

function testAcyclicDirectedGraph() {
  $g = new AcyclicDirectedGraph();
  try {
    buildCyclicGraph($g);
  }
  catch (Exception $exc) {
    echo "\n\nException: " . $exc->getMessage() . "\n\n";
  }

  $g = new AcyclicDirectedGraph();
  buildACyclicGraph($g);
  dumpGraph($g, 'A-Cyclic');

  $g = new AcyclicDirectedGraph();
  try {
    buildDisjunctCyclicGraph($g);
    dumpGraph($g, 'Disjunct Cyclic');
  }
  catch (Exception $exc) {
    dumpGraph($g, 'Disjunct Cyclic');
    echo "\n" . $exc->getMessage() . "\n\n";
  }

  $g = new AcyclicDirectedGraph();
  buildDisjunctACyclicGraph($g);
  dumpGraph($g, 'Disjunct A-Cyclic');

  $g = new AcyclicDirectedGraph();
  buildModuleVersionDependency($g);
  dumpGraph($g, 'Module version dependency');
}

function buildModuleVersionDependency(Graph $g) {
  $g->addLink('views', 'ctools', array('testing'), '>=2.x');
  $g->addLink('views_ui', 'views', array('testing'), '>=2.x');
}

function dumpGraph(Graph $g, $message) {
  $c = get_class($g);
  echo "\n\n\n====== $message ======\n\n";
  echo $g . "\n";
  $ids = array('a', 'b', 'c', 'p', 'q', 'z');
  foreach ($ids as $id) {
    dumpLink($g, $id);
    dumpParticipants($g, $id);
  }
  dumpParticipants($g);
  if ($g instanceof DirectedGraph) {
    dumpReverse($g);
  }
  if ($g instanceof AcyclicDirectedGraph) {
    dumpTSL($g);
  }
}

function dumpReverse(DirectedGraph $g) {
  echo "\nReversed: " . $g->getReversedGraph() . "\n";
}

function dumpTSL(AcyclicDirectedGraph $g) {
  echo "\nTSL: " . join(',', $g->getTSL()) . "\n";
}

function dumpLink(Graph $g, $id) {
  $arr = $g->getLinks($id);
  if (is_null($arr)) {
    echo "\nLinks $id: NULL";
  }
  else {
    echo "\nLinks $id: " . join(',', $arr);
  }
}

function dumpParticipants(Graph $g, $id = NULL) {
  if (is_null($id)) {
    echo "\nParticipants ALL: " . join(',', $g->getParticipants());
  }
  else {
    echo "\nParticipants $id: " . join(',', $g->getParticipants(array($id)));
  }
}

function buildCyclicGraph(Graph $g) {
  $g->addLink('a', 'b');
  $g->addLink('b', 'c');
  $g->addLink('c', 'a');
  $g->addLink('a', 'b', 'DATA','KEY');
}

function buildACyclicGraph(Graph $g) {
  $g->addLink('a', 'b');
  $g->addLink('b', 'c');
  $g->addLink('a', 'c');
  $g->addLink('a', 'b', 'DATA','KEY');
}

/**
 * Graph has disconnected subgraphs.
 *
 * @param type $g
 */
function buildDisjunctCyclicGraph(Graph $g) {
  buildCyclicGraph($g);
  $g->addLink('p', 'q');
}

function buildDisjunctACyclicGraph(Graph $g) {
  buildACyclicGraph($g);
  $g->addLink('p', 'q');
}
