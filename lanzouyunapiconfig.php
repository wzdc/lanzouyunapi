<?php
//缓存配置（需要安装apcu扩展）
$cacheconfig=array(
    "cache"        => 0,      // int    文件链接缓存开关（0关，1开）
    "cacheexpired" => 2000,   // int    文件链接缓存时间（秒，当无法获取到过期时间时使用）
    "foldercache"  => 0,      // int    缓存文件夹开关
);
