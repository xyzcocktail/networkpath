<?php

use \Jay\Test\Networkpath;

require_once 'vendor/autoload.php';

if (empty($argv) || count($argv) < 2) {
    echo "- Usage: php run.php [CSV FILE PATH] \n";
    exit;
}

if (!file_exists($argv[1])) {
    echo "- Error: File not found \n";
    exit;
} else {
    try {
        $networkPath = new Networkpath($argv[1]);
        if ($networkPath) {
            while(true) {
                echo "***** Please enter a data (e.g - A F 1000 followed by ENTER key) ***** \n";
                $handle = fopen ("php://stdin","r");
                $line   = fgets($handle);
                if (strtoupper(trim($line)) == 'QUIT') {
                    echo "- Program has been terminated! \n";
                    exit;
                } else {
                    if (($data = $networkPath->validateInputData($line))) {
                        $networkPath->setInitNodes();
                        $networkPath->findPath(
                            $networkPath->getNode(strtoupper($data[0])),
                            $networkPath->getNode(strtoupper($data[1]))
                        );
                        $networkPath->printPath(
                            $networkPath->getNode(strtoupper($data[0])),
                            $networkPath->getNode(strtoupper($data[1])),
                            intval($data[2])
                        );
                    } else {
                        echo "- Error: invalid input data! \n\n";
                    }
                }
            }
        }
    } catch (Exception $e) {
        echo "- Exception: {$e->getMessage()} \n";
    }
    exit;
}
