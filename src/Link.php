<?php

namespace Jay\Test;

class Link
{
    private $fromNode;
    private $toNode;
    private $latency;
    private $active = false;

    public function __construct(Node $from, Node $to, $latency = 1)
    {
        $this->fromNode = $from;
        $this->toNode = $to;
        $this->latency = $latency;
    }

    /**
     * @return Node
     */
    public function getToNode()
    {
        return $this->toNode;
    }

    /**
     * @return int|mixed
     */
    public function getLatency()
    {
        return $this->latency;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    public function activate()
    {
        $this->active = true;
    }

}
