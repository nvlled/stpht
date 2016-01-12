#!/usr/bin/php
<?php 

// $fn : function($relpath, $handle)
function eachFiles($dirname, $fn) {
    $handle = opendir($dirname);
    if (!$handle)
        return $handle;
    
    while (($entry = readdir($handle)) !== false) {
        if ($entry == "." || $entry == "..")
            continue;

        $relpath = "$dirname/$entry";
        if (is_dir($relpath))
            eachFiles($relpath, $fn);
        $fn($relpath, realpath($relpath));
    }

    closedir($handle);
}

// removePrefix("abcd", "ab")       == "cd"
// removePrefix("abcd", "abcd")     == ""
// removePrefix("abcd", "abcdef")   == "abcd"
function removePrefix($s, $prefix) {
    if (strlen($prefix) > strlen($s))
        return $s;

    $result = "";
    $i = 0;
    while (@$s[$i] && @$s[$i] == @$prefix[$i])
        $i++;
    return substr($s, $i);
}

function usage($argv) {
    echo "{$argv[0]} <project-dir> <dest-dir>\n";
    die();
}

function startPHPServer($port, $docroot) {
    $arg = array();
    // TODO: enable allow_url_open on php
    // TODO: handle previously binded port
    //$proc = proc_open("php -S localhost:8000 -t $docroot", $arg, $arg);
    $proc = proc_open("php -S localhost:$port -t .", $arg, $arg, $docroot);
    sleep(1);
    return $proc;
}

$serverPort = "8000";

function main() {
    global $argc;
    global $argv;
    global $serverPort;

    if ($argc <= 2) {
        usage($argv);
    }

    $projectDir = realpath($argv[1]);
    $destDir = $argv[2];
    $host = "http://localhost:$serverPort";

    @mkdir($destDir);
    $destDir = realpath($destDir);

    $proc = startPHPServer($serverPort, $projectDir);

    eachFiles($projectDir, function($relpath, $abspath) use ($projectDir, $destDir, $host) {
        $s = removePrefix($abspath, $projectDir);
        $destFile = "$destDir$s";
        echo "creating file: $destFile\n";

        if (!is_dir($relpath)) {
            @mkdir(dirname("$destFile"));

            $contents = file_get_contents("$host$s", "r");
            $dest = ereg_replace("\.php$", ".html", "$destFile");
            file_put_contents($dest, $contents);
        }
    });

    // TODO: proc_terminate is not working...
    //       proc_open has the wrong pid.....
    system("pkill -9 php");
}

main();

?>
