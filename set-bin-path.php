<?php

// returns the full path to the php binary
function phpBinPath() {
    $cmd = 'php -r "phpinfo(INFO_ENVIRONMENT);"';
    $output = shell_exec($cmd);
    preg_match("/_ =>(?P<path>.*)/", $output, $matches);
    if (count($matches) > 0)
        return trim($matches["path"]);
    return "";
}

// Since pcntl_exec requires a full path
// for the executable file, I have to go do
// all of this...


$binPath = phpBinPath();
$scriptName = "stpht.php";

if (!$binPath) {
    die("No php binary found");
}

echo "Found php binary: $binPath\n";

$s = preg_replace(
    '/(const PHP_BIN_PATH = ").*(";)/',
    '\1'.phpBinPath().'\2',
    file_get_contents($scriptName)
);
file_put_contents($scriptName, $s);

echo "Updated PHP_BIN_PATH on $scriptName\n";


?>
