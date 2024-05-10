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

$isBuildTitle = false;
if (isset($argv[3])) {
    $isBuildTitle = $argv[3] === "true";
}

$wantedFiles = [];

function findWantedFiles(&$wantedFiles, $dir) {
    global $fileExtSize, $fileExt;
    $items = scandir($dir);
    foreach ($items as $item) {
        if (in_array($item, getWhiteList())) continue;
        if (is_dir($dir . "/" . $item)) {
            findWantedFiles($wantedFiles, $dir . "/" . $item);
        }
        if (is_file($dir . "/" . $item)) {
            $fileNameWithExt = $item;
            $fileName = substr($fileNameWithExt, 0, -$fileExtSize);
            $isWanted = substr($fileNameWithExt, -$fileExtSize) === $fileExt;
            if ($isWanted && strtolower($fileName) != "readme")
            {
                $wantedFiles[] = $dir . "/" . $item;
            }
        }
    }
}

function isDirContainsWantedFiles($dir) {
    global $wantedFiles;
    foreach ($wantedFiles as $file) {
        if (strpos($file, $dir) === 0) {
            return true;
        }
    }
    return false;
}

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

function buildFileTitle($file) {
    #read first line of file as title, strip # and space
    $handle = fopen($file, "r");
    $title = fgets($handle);
    fclose($handle);
    $title = trim($title);
    $title = ltrim($title, "#");
    $title = trim($title);
    return $title;
}

function scan($dir, $relativePrefix, $depth) {
    global $fileExtSize, $fileExt, $isBuildTitle;

    $dirParts = explode("/", $dir);
    $dirName = end($dirParts);

    if (is_dir($dir) && isDirContainsWantedFiles($dir)) {
        echo packByDepth($dirName, $relativePrefix, $depth);
    }

    if (is_file($dir)) {
        $fileNameWithExt = $dirName;
        $fileName = substr($fileNameWithExt, 0, -$fileExtSize);
        $isWanted = substr($fileNameWithExt, -$fileExtSize) === $fileExt;
        if ($isWanted && strtolower($fileName) != "readme")
        {
            $title = $fileName;
            if ($isBuildTitle) {
                $title = buildFileTitle($dir);
                if (empty($title)) {
                    $title = $fileName;
                }
            }
            echo packByDepth($title, $relativePrefix, $depth);
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

findWantedFiles($wantedFiles, $dirPath);
scan($dirPath, '/', 0);
