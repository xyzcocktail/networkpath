<?php

namespace Jay\Test;

class Node
{
    private $links = [];
    private $name;
    private $tentativeLatency;
    private $isPrimary;
    private $isLast;
    private $parentNodeForQuickestRoute;

    public function __construct($name, $tentativeLatency = null)
    {
        $this->name = $name;
        $this->tentativeLatency = $tentativeLatency;

        return $this;
    }

    function __toString()
    {
        return $this->getName();
    }

    public function connectTo(Node $node, $latency = 1, $inversed = false)
    {
        $this->links[] = new Link($this, $node, $latency);
        if (!$inversed) {
            $node->connectTo($this, $latency, true);
        }
    }

    public function makePrimary()
    {
        $this->tentativeLatency = 0;
        $this->isPrimary = true;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getLinks()
    {
        return $this->links;
    }

    public function setTentativeLatency($latency)
    {
        $this->tentativeLatency = $latency;
    }

    public function getTentativeLatency()
    {
        return $this->tentativeLatency;
    }

    public function getParentNodeForQuickestRoute()
    {
        return $this->parentNodeForQuickestRoute;
    }

    public function setParentNodeForQuickestRoute($parentNodeForQuickestRoute)
    {
        $this->parentNodeForQuickestRoute = $parentNodeForQuickestRoute;
    }

    public function getIsPrimary()
    {
        return $this->isPrimary;
    }

    public function getIsLast()
    {
        return $this->isLast;
    }

    public function setIsLast()
    {
        $this->isLast = true;
    }

}
