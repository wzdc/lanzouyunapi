<?php
// 配置 （开启缓存需要安装apcu扩展）
$config = array(
    "cache"        => false,  // 文件链接缓存
    "cacheexpired" => 2000,  // 文件链接缓存时间（秒，当无法获取到过期时间时使用）
    "foldercache"  => false,  // 缓存文件夹参数
    "auto-switch"  => true,  // 自动切换获取方式
    "mode"        => "pc",  // 请求方式 (pc/mobile)
    "experimental" => false, // 实验性功能（会带来便利和不稳定性）
);
?>
