<?php

header("content-type:text/html; charset=utf-8");
ob_start();

function delsvn($dir)
{
    $dh = opendir($dir);
    // 找出所有".svn“ 的文件夹：
    while($file = readdir($dh)) {
        if($file != "." && $file != "..") {
            $fullpath = $dir."/".$file;
            if(is_dir($fullpath)) {
                if($file == ".svn") {
                    delsvndir($fullpath);
                } else {
                    delsvn($fullpath);
                }
            }
        }
    }
    closedir($dh);
}

function delsvndir($svndir)
{
    // 先删除目录下的文件：
    $dh = opendir($svndir);
    while($file = readdir($dh)) {
        if($file != "." && $file != "..") {
            $fullpath = $svndir."/".$file;
            if(is_dir($fullpath)) {
                delsvndir($fullpath);
            } else {
                chmod($fullpath, 0777);
                unlink($fullpath);
                echo $fullpath, "\r\n";
            }
        }
    }
    closedir($dh);
    // 删除目录文件夹
    chmod($svndir, 0777);
    if(rmdir($svndir)) {
        echo $svndir, "\r\n";
        return true;
    } else {
        return false;
    }
}

$dir = dirname('/Users/xuyakun/wwwroot/test/delete_svn');
delsvn($dir);

// echo "/Users/xuyakun/wwwroot/test/delete_svn/svn/svn";

$output = ob_get_contents();
ob_end_clean();

file_put_contents('delete_svn.txt', $output);

$error_delete_file = array();

$files = file('delete_svn.txt');
foreach($files as $file) {
    if(strpos($file, '.svn') === false) {
        $error_delete_file[] = $file;
    }
}

echo "<pre>";

echo "误删除的文件或目录<br>";
print_r($error_delete_file);

echo "<br>所有删除的文件或目录<br>";
print_r($files);
