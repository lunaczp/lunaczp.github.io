<?php
$dirPath = __DIR__;

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

function packByDepth($string, $depth) {
    $r = "";
    while ($depth--) {
        $r .= " ";
    }
    $r .= "- [$string]($string)" ."\n";
    return $r;
}

function scan($dir, $depth) {
    $dirParts = explode("/", $dir);
    $dirName = end($dirParts);

    if (is_dir($dir)) {
        echo packByDepth($dirName, $depth);
    }

    if (is_file($dir)) {
        $fileNameWithExt = $dirName;
        if (substr($fileNameWithExt, -3) === '.md'
            && strtolower($fileNameWithExt) != "readme.md")
        {
            echo packByDepth(substr($fileNameWithExt, 0, -3), $depth);
        }
        return;
    }

    $items = scandir($dir);
    if (in_array(".dir_ignore", $items)) {
        return;
    }

    foreach ($items as $item) {
        if (in_array($item, getWhiteList())) continue;
        scan($dir . "/" . $item, $depth+1);
    }
}

scan($dirPath, 0);
