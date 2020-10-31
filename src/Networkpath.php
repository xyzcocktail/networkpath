<?php

namespace Jay\Test;

class Networkpath
{
    protected $pool;

    private $nodes;

    /**
     * Networkpath constructor.
     * @param null $csvFile
     */
    public function __construct($csvFile = null)
    {
        if ($csvFile !== null) {
            $this->setPoolByCSV($csvFile);
        }
    }

    /**
     * @param null $csvFile
     * @return bool
     */
    public function setPoolByCSV($csvFile = null)
    {
        if ($csvFile === null)
            return false;
        try {
            $pool = [];
            if (($handle = fopen($csvFile, "r")) !== false) {
                while(($data = fgetcsv($handle, 1000, ",")) !== false) {
                    $idxSrc     = trim($data[0]);
                    $idxTgt     = trim($data[1]);
                    $latency    = intval($data[2]);
                    if (!isset($pool[$idxSrc])) {
                        $pool[$idxSrc] = [$idxTgt => $latency];
                    } else {
                        $pool[$idxSrc][$idxTgt] = $latency;
                    }
                }
                $this->setPool($pool);
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            echo "- Exception: {$e->getMessage()}";
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getPool()
    {
        return $this->pool;
    }

    /**
     * @param array $pool
     */
    public function setPool($pool = [])
    {
        $this->pool = $pool;
    }

    public function setInitNodes()
    {
        $this->nodes = [];
        if (!empty($this->pool)) {
            foreach($this->pool as $v => $adj) {
                $this->addNode(new Node($v));
            }
            foreach($this->pool as $v => $adj) {
                if (!empty($adj)) {
                    foreach($adj as $n => $latency) {
                        $chk = $this->getNode($n);
                        if (!$chk) $this->addNode(new Node($n));
                    }
                }
            }
            foreach($this->pool as $v => $adj) {
                $sNode = $this->getNode($v);
                if ($sNode && !empty($adj)) {
                    foreach($adj as $neighbor => $latency) {
                        $tNode = $this->getNode($neighbor);
                        if ($tNode) $sNode->connectTo($tNode, $latency);
                    }
                }
            }
        }
    }

    public function validateInputData($data = null)
    {
        if ($data == null)
            return false;
        $data = explode(" ", trim($data));
        if (empty($data) || count($data) < 3) {
            return false;
        } else {
            return $data;
        }
    }

    public function addNode($node)
    {
        $this->nodes[] = $node;
    }

    /**
     * @param $name
     * @return false|mixed
     */
    public function getNode($name, $create = false)
    {
        if (!empty($this->nodes)) {
            foreach ($this->nodes as $node) {
                if ($node->getName() == $name) {
                    return $node;
                }
            }
        }
        return ($create === true) ? new Node($name) : false;
    }

    /**
     * @return array
     */
    public function getNodes()
    {
        return $this->nodes;
    }

    public function findPath(Node $from, Node $to)
    {
        $from->makePrimary();
        $to->setIsLast();

        $current = $from;
        $visited = [$from->getName() => $from];
        $unvisited = $this->getAllNodeExcept($from);

        while(true) {
            $this->calculateNeighbours($current, $visited);

            $current = $this->determineLowestUnvisited($unvisited, $to);
            if ($current === null) {
                throw new Exception('unable to determine highest unvisited');
            }

            unset($unvisited[$current->getName()]);
            $visited[$current->getName()] = $current;

            if ($current->getName() === $to->getName()) {
                // found shortest path. return it
                break;
            }
        }
        return $to;
    }

    public function printPath(Node $sNode, Node $tNode, $latency = 0)
    {
        $totLatency = 0;
        $results = [];
        $current_node = $tNode;
        $path = [];
        while (true) {
            $path[] = $current_node;
            if ($current_node->getParentNodeForQuickestRoute() === null) {
                break;
            }
            $new_node = $current_node->getParentNodeForQuickestRoute();

            // find the link
            foreach($new_node->getLinks() as $link) {
                if ($link->getToNode() === $current_node) {
                    $link->activate();
                }
            }
            $current_node = $new_node;
        }

        foreach ($this->getNodes() as $key => $node) {
            foreach ($node->getLinks() as $link) {
                if ($link->isActive()) {
                    $k = $node->getName();
                    $results[$k] = [
                        'Primary' => $node->getIsPrimary(),
                        'To' => $link->getToNode()->getName(),
                        'Latency' => $link->getLatency()
                    ];
                    $totLatency += intval($link->getLatency());
                }
            }
        }

        if ($sNode->getName() < $tNode->getName()) {
            ksort($results);
        } else {
            krsort($results);
        }
        // print_r( $results );

        if ($latency > 0 && $totLatency > $latency) {
            echo "Path not found! \n";
        } else {
            $results[$tNode->getName()] = $tNode->getName();
            $results[$totLatency] = $totLatency;
            echo implode(" => ", array_keys($results));
            echo "\n";
        }
    }

    private function calculateNeighbours(Node $current, $visited = [])
    {
        // find the lowest weighted neighbour
        $links = $current->getLinks();

        //update the values of the neighbours
        foreach ($links as $link) {
            $toNode = $link->getToNode();
            if ($current->getName() === $toNode->getName()) {
                continue;
            }
            if (in_array($toNode->getName(), array_keys($visited))) {
                continue;
            }

            $latency = $current->getTentativeLatency() + $link->getLatency();
            if (null === $toNode->getTentativeLatency() || $latency < $toNode->getTentativeLatency()) {
                $toNode->setTentativeLatency($latency);
                $toNode->setParentNodeForQuickestRoute($current);
            }
        }
    }

    private function getAllNodeExcept(Node $current_node)
    {
        $set= [];
        foreach ($this->nodes as $node) {
            if ($node->getName() !== $current_node->getName()) {
                $set[$node->getName()] = $node;
            }
        }
        return $set;
    }

    private function determineLowestUnvisited($unvisited, Node $target_node)
    {
        /** @var Node $lowest_node */
        $lowest_node = null;
        foreach ($unvisited as $node) {
            if ($node->getTentativeLatency() == null) {
                continue;
            }
            if ($lowest_node === null) {
                $lowest_node = $node;
            } else if ($node->getTentativeLatency() <= $lowest_node->getTentativeLatency()) {
                if ($node->getName() === $target_node->getName() ||
                    $node->getTentativeLatency() < $lowest_node->getTentativeLatency()) {
                    $lowest_node = $node;
                }
            }
        }
        return $lowest_node;
    }
}
