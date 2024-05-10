<?php
$dirPath = __DIR__;
if (isset($argv[1])) {
    $dirPath = $argv[1];
}

$fileExt = ".md";
if (isset($argv[2])) {
    $fileExt = $argv[2];
}
$fileExtSize = strlen($fileExt);

function getWhiteList() {
    return [
        '.',
        '..',
        '.git',
        '.idea',
        'doc',
        'image'
    ];
}

function packByDepth($name, $path, $depth) {
    $r = "";
    while ($depth--) {
        $r .= "  ";
    }
    $path = str_replace(" ", "%20", $path);
    $r .= "- [$name]($path)" ."\n";
    return $r;
}

function scan($dir, $relativePrefix, $depth) {
    global $fileExtSize, $fileExt;

    $dirParts = explode("/", $dir);
    $dirName = end($dirParts);

    if (is_dir($dir)) {
        echo packByDepth($dirName, $relativePrefix, $depth);
    }

    if (is_file($dir)) {
        $fileNameWithExt = $dirName;
        $fileName = substr($fileNameWithExt, 0, -$fileExtSize);
        $isWanted = substr($fileNameWithExt, -$fileExtSize) === $fileExt;
        if ($isWanted && strtolower($fileName) != "readme")
        {
            echo packByDepth(substr($fileNameWithExt, 0, -$fileExtSize), $relativePrefix, $depth);
        }
        return;
    }

    $items = scandir($dir);
    if (in_array(".dir_ignore", $items)) {
        return;
    }

    foreach ($items as $item) {
        if (in_array($item, getWhiteList())) continue;
        scan($dir . "/" . $item, rtrim($relativePrefix, "/")  ."/" .$item, $depth+1);
    }
}

scan($dirPath, '/', 0);
