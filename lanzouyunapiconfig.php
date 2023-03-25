<?php
//缓存配置（需要安装apcu扩展）
$cacheconfig=array(
    "cache"  => 0,      // int    缓存开关（0关，1开）
    "verify" => 1,      // int    验证链接是否有效
    "time"   => 1800,   // int    缓存时间（秒）
);