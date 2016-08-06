#!/usr/bin/php
<?php

//
// Set this to where the php binary is placed.
// On windows, this will be something like "C:\php5\php.exe"
const PHP_BIN_PATH = "/usr/bin/php";
// To automatically search and update PHP_BIN_PATH,
// run: php set-bin-path.php
// (php binary has to be in your PATH)

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
    $name = basename($argv[0]);
    echo "stpht: converts php sites into static sites,\n";
    echo "       no further configuration needed\n";
    echo "\n";
    echo "usage: $name <project-dir> <dest-dir>\n";
    echo "  where\n";
    echo "      project-dir is the project directory containing the website\n";
    echo "      dest-dir is where the generated static files will be placed\n";
    die();
}

function startPHPServer($port, $docroot) {
    $pid = pcntl_fork();
    if ($pid == -1)
        die("there is only a spoon");

    if ($pid) {
        sleep(1);
        return $pid;
    } else {
        chdir($docroot);
        pcntl_exec(PHP_BIN_PATH,  array("-S", "localhost:$port", "-t", "."));
    }
}

function isSubstr($s, $sub) {
    return strpos($s, $sub) === 0;
}

function validateDirectories($projectDir, $destDir) {
    if ($projectDir == "") {
        echo "directory not found: {$argv[1]}\n";
        exit(-1);
    }
    if ($projectDir == $destDir) {
        echo "error: <project-dir> and <dest-dir> must not be the same\n";
        exit(-1);
    }

    if (isSubstr(dirname($destDir), $projectDir)) {
        echo "warning: <dest-dir> should not be in the <project-dir>\n";
        echo "  because <dest-dir> will accumulate nested directories in each run\n";
        echo "\n";
        echo "if this is what you want, enter yes: ";
        $s = fgets(STDIN);
        if (trim($s) !== "yes")
            exit(-1);
    }
}

$serverPort = "8000";

// TODO: add exclude list
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
    validateDirectories($projectDir, $destDir);

    $serverPID = startPHPServer($serverPort, $projectDir);

    eachFiles($projectDir, function($relpath, $abspath) use ($projectDir, $destDir, $host) {
        $s = removePrefix($abspath, $projectDir);
        $destFile = "$destDir$s";
        echo "creating file: $destFile\n";

        // TODO: Fix link generation
        //       * Change absolute to relative
        //       * Add index.html to URLs ending with slash or files with no extension
        // href="/path/"         -> href="path/index.html"
        // href="/path"          -> href="path/index.html"
        // href="test.html"      -> href="test.html"
        if (!is_dir($relpath)) {
            @mkdir(dirname("$destFile"));

            // rewrite href= and src=
            $contents = file_get_contents("$host$s", "r");
            $dest = preg_replace("/\.php$/i", ".html", "$destFile");
            file_put_contents($dest, $contents);
        }
    });

    posix_kill($serverPID, SIGKILL);
}
main();
?>
