<?php
//开启缓存需要安装apcu扩展（0关，1开）
$config=array(
    "cache"        => 0,                        // int    文件链接缓存
    "cacheexpired" => 2000,                     // int    文件链接缓存时间（秒，当无法获取到过期时间时使用）
    "foldercache"  => 0,                        // int    缓存文件夹
    "auto-switch"  => $_REQUEST["auto"]??1,     // int    自动切换获取方式
    "mode"         => $_REQUEST["mode"]??"pc",  // String 请求方式 (pc/mobile)
);
