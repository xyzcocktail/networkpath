<?php
require_once "src/Networkpath.php";

if (empty($argv) || count($argv) < 2) {
    echo "- Usage: php run.php [CSV FILE PATH] \n";
    exit;
}

if (!file_exists($argv[1])) {
    echo "- Error: File not found \n";
    exit;
} else {
    try {
        $networkPath = new \Jay\Test\Networkpath($argv[1]);
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
                        $result = $networkPath->findPath(strtoupper($data[0]), strtoupper($data[1]), intval($data[2]));
                        print_r($result);
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
