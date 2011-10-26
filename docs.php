<?php

/**
 * generate documentation for ellipsis applications
 *
 */

$base = dirname(__FILE__) . '/wwwroot';
$dirs = scandir($base);
$apps = array();
$ellipsis = false;
foreach($dirs as $dir){
    if (is_dir($base . '/' . $dir) && preg_match('/^\.[^\.]+$/', $dir)){
        if ($dir == '.ellipsis'){
            $ellipsis = true;
        }
        $apps[] = $dir;
    }
}
if ($ellipsis){
    $bin = dirname(__FILE__) . '/utils/docblox/bin/docblox.php';
    $cmd = "php $bin run -v --force --title \"Ellipsis Docs\" --defaultpackagename \"unknown\" --ignore \"*/temp/*,*/tmp/*,*.bak*\" -d \"$base/" . implode("\",\"$base/", $apps) . "\" -t \"$base/docs\"";
    //echo $cmd;exit;
    system($cmd);
}

