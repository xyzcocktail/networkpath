<?php

namespace Jay\Test;

class Networkpath
{

    protected $pool;

    protected $latency;

    protected $direction;

    protected $queue;

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
                    $idxSrc = trim($data[0]);
                    $idxTgt = trim($data[1]);
                    if (!isset($pool[$idxSrc])) {
                        $pool[$idxSrc] = [$idxTgt => intval($data[2])];
                    } else {
                        $pool[$idxSrc][$idxTgt] = intval($data[2]);
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
     * @param null $data
     * @return false|string[]
     */
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

    /**
     * @param $source
     * @param $target
     * @param $latency
     * @return void|null
     */
    public function findPath($source, $target, $latency)
    {
        // echo "********** findPath {$origin}, {$target}, {$latency} ********** \n";
        if ($source == null || $target == null || $latency <= 0) {
            echo "- Error: Please enter valid values \n";
            return null;
        }
        $this->direction = ($source < $target) ? "+" : "-";

        $this->latency = array_fill_keys(array_keys($this->pool), INF);
        $this->latency[$source] = 0;

        $this->previous = array_fill_keys(array_keys($this->pool), array());

        $this->queue = array($source => 0);
        while (!empty($this->queue)) {
            $this->queue = [$source => 0];
            while (!empty($this->queue)) {
                // Process the closest vertex
                $closest = array_search(min($this->queue), $this->queue);
                if (!empty($this->pool[$closest])) {
                    foreach ($this->pool[$closest] as $neighbor => $cost) {
                        if (!isset($this->previous[$neighbor])) {
                            $this->previous[$neighbor] = [$closest];
                            $this->latency[$neighbor] = $this->latency[$closest] + $cost;
                        }
                        if (isset($this->latency[$neighbor])) {
                            if ($this->latency[$closest] + $cost < $this->latency[$neighbor]) {
                                // A shorter path was found
                                $this->latency[$neighbor] = $this->latency[$closest] + $cost;
                                $this->previous[$neighbor] = array($closest);
                                $this->queue[$neighbor] = $this->latency[$neighbor];
                            } else if ($this->latency[$closest] + $cost === $this->latency[$neighbor]) {
                                // An equally short path was found
                                $this->previous[$neighbor][] = $closest;
                                $this->queue[$neighbor] = $this->latency[$neighbor];
                            }
                        }
                    }
                }
                unset($this->queue[$closest]);
            }
        }

        if ($source === $target) {
            echo "- {$source} => 0 \n\n";
        } else if (empty($this->previous[$target])) {
            echo "- Path not found! \n\n";
        } else if (!empty($this->latency[$target]) && $this->latency[$target] > $latency)  {
            echo "- Path not found! \n\n";
        } else {
            $paths = [[$target]];
            for ($key = 0; isset($paths[$key]); ++$key) {
                $path = $paths[$key];
                if (!empty($this->previous[$path[0]])) {
                    foreach ($this->previous[$path[0]] as $previous) {
                        $copy = $path;
                        array_unshift($copy, $previous);
                        $paths[] = $copy;
                    }
                    unset($paths[$key]);
                }
            }
            echo join(" => ", array_values($paths)[0])." => ".$this->latency[$target]." \n\n";
        }
        return;
    }

}
