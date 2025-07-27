<?php
/*
 * @package lanzouyunapi
 * @author wzdc
 * @version 1.3.2
 * @Date 2025-7-27
 * @link https://github.com/wzdc/lanzouyunapi
 */

// 允许跨站请求
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, HEAD');
header("Access-Control-Allow-Headers: *");
header("Access-Control-Max-Age: 2592000");

include 'lanzouyunapiconfig.php'; // 导入配置文件
error_reporting(0); // 不显示错误
if(!isset($_REQUEST["url"]) || !$_REQUEST["url"]) exit(response(-4,"缺少参数",null));
$id = preg_match("/^(?:https?:\/\/)?[aA-zZ0-9.-]+\.com\/(?:tp\/)?(.+)/",$_REQUEST["url"],$id) ? $id[1] : $_REQUEST["url"]; // 路径或链接
if(!$id) exit(response(-4,"参数错误",null));
$pw = $_REQUEST["pw"] ?? ""; //密码
$type = $_REQUEST["type"] ?? ""; //需要响应的数据类型
$page = (isset($_REQUEST["page"]) && (int)$_REQUEST["page"] > 1) ? (int)$_REQUEST["page"] : 1; //文件夹页数
$fid = preg_match("/^[^?]+/",$id,$fid) ? $fid[0] : null; //用于存储缓存的分享路径
$ch = curl_init();
$mobileua[] = "User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36";
$desktopua[] = "User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36";

//读取缓存
if($config["cache"] && $data3=apcu_fetch("file".$fid)) {
    header("X-APCu-Cache: HIT");
    response(0,"成功",$data3);
} else if($config["foldercache"] && $data3=apcu_fetch("folder".$fid)) { //读取缓存（文件夹）
    $parameter = $data3[1];
    $parameter["pg"] = $page;
    $t = $parameter["t"] - $data3[2] - time() + $page;
    if($page!=1 && $t>=0) sleep($t);
    if($pw) $parameter["pwd"] = $pw;
    f($data3[0],$parameter);
} else if($config["mode"] == "mobile") {
    mobile();    
} else {
    pc();
}

//使用手机UA获取
function mobile() { 
    global $id,$pw,$ch,$mobileua;
    $data = preg_replace('/<!--.*?-->/s', '', request("https://www.lanzoui.com/$id","GET",null,$mobileua,"data",$ch));
    if(!$data) exit(response(-3,"获取失败",null)); 
    $js = preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $data, $js) ? trim(implode("\n", $js[1])) : "";
    if(strpos($js,"/filemoreajax.php")) exit(folder($data,$js)); //是否为文件夹
    $data2 = null;
    $datar = $data;
    
    if(preg_match("/(?<=')\?.+(?=')/",$js,$url)) {
        $url = $url[0];
    } else if (
        (
            (
              (preg_match('/https?:\/\/waf\.woozooo\.com\/tp\/.+\.js/',$data,$jstpurl)) &&
              preg_match('/(?<=tp\/)[\w?&=]+/', request($jstpurl[0],"GET",null,$mobileua,"data"), $id2)
            ) || 
            preg_match('/(?<=tp\/)[\w?&=]+/', $data, $id2) || 
            (
               ($redirecturl = request("https://www.lanzoui.com/$id","GET",null,["User-Agent: MicroMessenger"],"info",$ch)["redirect_url"]) &&
               preg_match('/(?<=i\.com\/)[\w?&=]+/',request($redirecturl, "GET", null, $mobileua, "info")["redirect_url"],$id2)
            )
        ) &&
        $data2 = preg_replace('/<!--.*?-->/s', '', request("https://www.lanzoui.com/tp/".$id2[0], "GET", null, $mobileua, "data", $ch))
    ) {
        $datar = $data2;
        $js = preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $data2, $js) ? trim(implode("\n", $js[1])) : null;
        $url = preg_match("/(?<=')\?.+(?=')/",$js,$url) ? $url[0] : null;
    }
    
    $error = preg_match("/<\/div><\/div>(.+)<\/div>/",$data,$error) ? $error[1] : "获取失败";
    if(!$js) exit(response(-2,$error,null));
    $fileinfo = preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/',$data,$fileinfo) ? $fileinfo[1] : "";
    
    $info["name"] = $data2 && (preg_match('/<title>(.+)<\/title>/',$data2,$filename) 
                  || preg_match('/<div class="md">(.+) <span class="mtt">/',$data2,$filename)) 
                  || preg_match('/<div class="(?:md|appname)">(.*?) ?</',$data,$filename) 
                  ? htmlspecialchars_decode($filename[1]) : null; // 文件名
                  
    $info["size"] = preg_match('/(?:文件)?大小：(.*?)(?:\||$)/',$fileinfo,$filesize)
                 || preg_match('/>下载 *\( *(.+) \)<\/a>/', $data,  $filesize)
                 || ($data2 && preg_match('/mtt">\( (.+) \)/', $data2, $filesize))
                 ? $filesize[1] : null; // 文件大小
                 
    $info["user"] = preg_match('/(?<=分享者:<\/span>).+(?= )/U',$data,$username) 
                 || preg_match('/(?<=<div class="user-name">).+(?=<)/U',$data,$username) 
                 || $data2 && preg_match('/(?<=发布者:<\/span>).+(?= )/U',$data2,$username)
                 ? $username[0] : null; // 分享者
    
    $info["time"] = preg_match('/(?<=<span class="mt2"><\/span>).*?(?=<span class="mt2">)/', $data, $filetime) 
                 || preg_match('/(?<=<span class="appinfotime">).*?(?=<)/', $data, $filetime)
                 || $data2 && preg_match('/(?<=\<span class="mt2">时间:<\/span>).*?(?=\<span class="mt2">)/', $data2, $filetime)
                 ? trim($filetime[0]) : null; // 上传时间
    
    $info["desc"] = preg_match('/(?<=\|).*?(?=$)/',$fileinfo,$filedesc)
                  || preg_match('/<div class="appdes">([\s\S]+?)<\/div>/', $data, $filedesc)
                  || $data2 && preg_match('/<div class="mdo">([\s\S]+?)<\/div>/', $data2, $filedesc) && !strpos($filedesc[1], "<span>")
                  ? htmlspecialchars_decode(trim(strip_tags(str_replace("<br /> ","\n",$filedesc[1])))) : ""; // 文件描述
    
    $info["icon"] = preg_match('/https?:\/\/image\.woozooo\.com\/image\/ico\/.+?(?=\))/',$data,$fileicon) ? $fileicon[0] : null; // 文件图标 默认图标：https://assets.woozooo.com/assets/images/type/(ext)_max.gif
    $info["avatar"] = preg_match('/https?:\/\/image\.woozooo\.com\/image\/userimg\/.+?(?=\))/',$data,$fileavatar) ? $fileavatar[0] : null; // 分享者头像
    
    if($url) { // 无密码
        $fileid = preg_match('/(?<=\?f=)\d+/',$datar,$fileid) ? (int)$fileid[0] : null; // 获取文件ID
        $dom = preg_match("/(?<=')https?:\/\/.+(?=')/",$datar,$dom) ? $dom[0] : null; // 获取链接
        $info = array("fid" => $fileid) + $info; // 将文件ID放到最前
        $info["url"] = $dom.$url; // 拼接下载链接
	    e($info); // 获取文件直链
    } else { // 有密码
        geturl($js,$info,$error,$pw);
    }
}

//使用电脑UA获取
function pc() { 
    global $id,$pw,$ch,$desktopua;
    $data = preg_replace('/<!--.*?-->/s', '', request("https://www.lanzoui.com/$id","GET",null,$desktopua,"data",$ch));
    if(!$data) exit(response(-3,"获取失败",null));
    $js = preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $data, $js) ? trim(implode("\n", $js[1])) : "";
    $error = preg_match("/<\/div><\/div>(.+)<\/div>/",$data,$error) ? $error[1] : "获取失败";
    if(strpos($js,"/filemoreajax.php")) exit(folder($data,$js)); // 是否为文件夹
    if(preg_match('/<iframe\b[^>]* src="(.+?)"/',$data,$src)) { // 无密码
        $data2 = request("https://www.lanzoui.com".$src[1],"GET",null,$desktopua,"data",$ch);
        $js = preg_match('/https?:\/\/waf\.woozooo\.com\/pc\/.+\.js/',$data2,$jsurl) ? request($jsurl[0],"GET",null,$desktopua,"data") : $data2;
    }
    if(!$js) exit(response(-2,$error,null));
    $fileinfo = preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/',$data,$fileinfo) ? $fileinfo[1] : null;
    
    $info["name"] = preg_match('/<div class="n_box_3fn" [^>]+>(.*?)<\/div>/',$data,$filename) // 新版页面
                  || preg_match('/<div style="font[^>]+>(.*?)<\/div>/',$data,$filename) // 旧版页面
                  ? htmlspecialchars_decode($filename[1]) : null; // 获取文件名
                  
    $info["size"] = preg_match('/(?:文件)?大小：(.*?)(?:\||$)/',$fileinfo,$filesize) // 通用
                 || preg_match('/<div class="n_filesize">大小：(.+?)<\/div>/',$data,$filesize) // 新版页面
                 || preg_match('/文件大小：<\/span>(.+?)</',$data,$filesize) // 旧版页面
                 ? $filesize[1] : null;  // 获取文件大小 
                 
    $info["user"] = preg_match('/<span class="user-name">(.+?)<\/span>/',$data,$username) // 新版页面
                 || preg_match('/<font>(.+?)<\/font>/',$data,$username) // 旧版页面
                 ? $username[1] : null; // 获取分享者
    
    $info["time"] = preg_match('/<span class="n_file_infos">(.+?)<\/span> <span class="n_file_infos">/',$data,$filetime) // 新版页面
                 || preg_match('/<span class="p7">上传时间：<\/span>(.*?)<br>/',$data,$filetime) // 旧版页面
                 ? $filetime[1] : null; // 获取上传时间
    
    $info["desc"] = preg_match('/(?<=\|).*?(?=$)/',$fileinfo,$filedesc) // 通用
                  || preg_match('/(?<=<div class="n_box_des">).+?(?=<\/div>)/',$data,$filedesc) // 新版页面
                  || preg_match('/(?<=文件描述：<\/span><br>\n).+(?=\t)/',$data,$filedesc) // 旧版页面
                  ? htmlspecialchars_decode(strip_tags(str_replace("<br /> ","\n",$filedesc[0]))) : ""; // 获取文件描述
    
    $info["icon"] = preg_match('/https?:\/\/image\.woozooo\.com\/image\/ico\/.+?(?=")/',$data,$fileicon) ? $fileicon[0] : null; // 获取文件图标 默认图标：https://assets.woozooo.com/assets/images/type/(ext)_max.gif
    $info["avatar"] = preg_match('/https?:\/\/image\.woozooo\.com\/image\/userimg\/.+?(?=\))/',$data,$fileavatar) ? $fileavatar[0] : null; // 获取分享者头像

    geturl($js,$info,$error,$pw);
}

//获取文件夹
function folder($data,$js) {
    global $id,$pw,$page,$config,$fid;
        
    // 获取需要请求的参数
    if(!preg_match("/(?<=data : {)[\s\S]*?(?=},)/",$js,$arr)) { 
        exit(response(-2,"获取失败",null));
    }
    
    foreach(explode("\n",$arr[0]) as $v) {
        if(preg_match("/'(.+)':([\d]+),?$|'(.+)':'(.*)',?$/",$v,$kv) && ($kv[1] || $kv[3])) {
            if($kv[1]) {
                $parameter[$kv[1]] = $kv[2];
            } else {
                $parameter[$kv[3]] = $kv[4];
            }
        } else if(preg_match("/'(.*)':(.*?),/",$v,$kv) && $kv[1]) {
            preg_match("/".$kv[2]."\s*?=\s*?'(.*)'|".$kv[2]."\s*?=\s*?(\d+)/",$js,$value);
            $parameter[$kv[1]] = $value[1] ?? "";
        }
    }
    
    $info = array("fid" => (int)$parameter["fid"],"uid" => (int)$parameter["uid"]);

    //获取名称
    if(preg_match("/document\.title\s*=\s*(.*);/",$js,$var) && preg_match("/".$var[1]."\s*=\s*'(.*)'/",$js,$name)) {
        $info["name"] = htmlspecialchars_decode($name[1]); 
    } else if(preg_match("/class=\"b\">(.*?)</",$data,$name)) {
        $info["name"] = htmlspecialchars_decode(trim($name[1]));
    } else if(preg_match("/(?<=user-title\">).*(?=<)/",$data,$name)) {
        $info["name"] = htmlspecialchars_decode($name[0]);
    } else if(preg_match("/(?>=class=\"b\">).*?(?=<div)/",$data,$name)) {
        $info["name"] = htmlspecialchars_decode($name[0]);
    } else if($parameter["fid"] == 1 && preg_match("/<title>(.+) - 蓝奏云/",$data,$name)) {
        $info["name"] = htmlspecialchars_decode($name[1]);
    } else {
        $info["name"] = null;
    }
    
    //获取描述
    if(preg_match("/(?<=说<\/span>)[\s\S]*?(?=<\/div>)/",$data,$d) && $d[0]) {
        $info["desc"] = strip_tags(htmlspecialchars_decode($d[0]));
    } else if(preg_match("/(?<=<span id=\"filename\">)[\s\S]*?(?=<\/div>)/",$data,$d) && $d[0]) {
        $info["desc"] = strip_tags(htmlspecialchars_decode($d[0]));
    } else if(preg_match('/(?<=user-radio-0"><\/div>)[\s\S]*?(?=<\/div>)/',$data,$d) && $d[0]) {
        $info["desc"] = strip_tags(htmlspecialchars_decode($d[0])); 
    } else {
        $info["desc"] = '';
    }
    
    //获取分享者
    /*if(preg_match("/(?<=user-name\">).+?(?=<)/",$data,$u) && $u[0]){
        $info["user"] = $u[0];
    } else {
        $info["user"] = null;
    }*/
            
    //获取子文件夹
    $folderarr = preg_split('/<div class="pc-folderlink">|<div class="mbx mbxfolder">/', $data);
    $info["folder"] = [];
    if($folderarr) {
        unset($folderarr[0]);
        foreach ($folderarr as $f) {
            $info["folder"][] = array(
                "id"   => preg_match("/(?<=href=\"\/).*?(?=\")/",$f,$fi) ? $fi[0] : null, //ID
                "name" => preg_match("/(?<=filename\">|<a href=\"\/".$fi[0]."\">).+?(?=<)/",$f,$fn) ? htmlspecialchars_decode($fn[0]) : null, //名称
                "desc" => preg_match("/(?<=filesize\">)[\s\S]*?(?=<)/",$f,$fd) ? htmlspecialchars_decode($fd[0]) : null, //描述
            );
        }
    }
    
    $parameter["pg"] = $page;
    $parameter["pwd"] = $pw;
    $t_end = $parameter["t"] - time(); 
    if($config["foldercache"] && $t_end > 0) apcu_store("folder$fid",[$info,$parameter,$t_end],$t_end); // 缓存参数
    
    //密码文件检测
    if(strpos($js,"document.getElementById('pwd').value;") && !$pw) {
        $info["list"] = null;
        exit(response(2,"请输入密码",$info));
    }
    
    if($config["experimental"] && $page == 2) {
        $parameter["pg"] = 0;
    } else if($page != 1) {
        sleep($page);
    }
    
    f($info,$parameter);
    return "";
}

// 响应数据
function response($code,$msg,$data) {
    global $config,$type;
    
    //自动切换获取方式
    if($config["auto-switch"] && !in_array($code,array(-4,0,2)) && $msg!="密码不正确"){
        $config["auto-switch"] = 0;
        if($config["mode"] == "mobile") pc();
        else mobile();
        exit;
    }
    
    //响应数据
    $res=array("code"=>$code, "msg"=>$msg, "data"=>$data);
    switch ($type) {
        case 'xml':
            header('Content-Type: application/xml');
            echo arrayToXml($res);
            break;
        case 'down':
            if($data["url"]) header("Location: ".$data["url"]);
            break;
        default:
            header('Content-Type: application/json');
            echo json_encode($res,JSON_UNESCAPED_UNICODE);
            break;
    }
    
    return "";
}

//XML
function arrayToXml($arr,$dom=0,$item=0){
    if(!$dom){
        $dom = new DOMDocument("1.0"); 
    } 
    if(!$item){ 
        $item = $dom->createElement("root"); 
        $dom->appendChild($item); 
    } 
    foreach ($arr as $key=>$val){ 
        $itemx = $dom->createElement(is_string($key)?$key:"item"); 
        $item->appendChild($itemx); 
        if (!is_array($val)){ 
            if(is_bool($val)) $val = $val ? 1 : 0;
            $text = $dom->createTextNode((string)$val); 
            $itemx->appendChild($text); 
        } else { 
            arrayToXml($val,$dom,$itemx); 
        } 
    } 
    return $dom->saveXML(); 
}

// 获取直链
function e($info) {
	global $config,$fid,$desktopua,$mobileua;
	$ch = curl_init();
	$url = request($info["url"],"GET",null,$desktopua,"info",$ch)["redirect_url"];
	if(!$url) {
	    $request = request($info["url"],"GET",null,$mobileua,"all",$ch);
	    if($request["data"] && preg_match('/<a\s+href="(.+?)"/',$request["data"],$a)) {
	        $url = $a[1];
	    } else {
	        $url = $request["info"]["redirect_url"];
	    }
	}
	curl_close($ch);
   
	if(!$url) {
	    response(1,"获取链接失败",$info); 
	} else {
	    $info["url"] = $url;
	    if(!$info["time"] && preg_match("/(?!(0000))\d{4}\/(?:0[1-9]|1[0-2])\/(?:0[1-9]|[12]\d|3[01])/",$url,$time)) {
	        $info["time"] = str_ireplace("/","-",$time[0]); //截取上传时间
	    }
        if($config["cache"]) {
            if(preg_match("/&e=(.+?)&/",$url,$endtime) || preg_match("~^(?:[^/]*\/){4}([^/]*)~",$url,$endtime)) {
                if(!preg_match("/\d{10}/",$endtime[1]) && ctype_xdigit($endtime[1])) $endtime[1] = hexdec($endtime[1]);
                $t = $endtime[1] - time();
                if($t > 0) $config["cacheexpired"] = $t;
            }
            apcu_store("file$fid",$info,$config["cacheexpired"]); //写入缓存
            header("X-APCu-Cache: MISS");
        }
        response(0,"成功",$info);
	}
}

//获取文件夹文件
function f($info,$parameter) {
    global $config,$desktopua,$ch;
    $json = json_decode(request('https://www.lanzoui.com/filemoreajax.php',"post",$parameter,$desktopua,"data",$ch),true); // 获取文件列表 zt状态码： 1成功 2没有文件 3密码错误 4参数无效或过期
    curl_close($ch);
    if(is_array($json["text"])) {
        foreach ($json["text"] as $v) {
            if($v["id"] != "-1") {
                $info["list"][] = array(
                    "id"   => $v["id"],  // ID
                    "ad"   => (bool)$v["t"], // 推广文件
                    "name" => htmlspecialchars_decode($v["name_all"]), // 文件名 
                    "size" => $v["size"],  // 文件大小
                    "time" => $v["time"],  // 上传时间
                    "icon" => $v["p_ico"] ? "https://image.woozooo.com/image/ico/".$v["ico"]."?x-oss-process=image/auto-orient,1/resize,m_fill,w_100,h_100/format,png" : null, // 文件图标 默认图标："https://assets.woozooo.com/assets/images/(新: type,旧: filetype)/".$v["icon"].".gif"
                );
            }
        }
        $info["have_page"] = count($json["text"]) >= 50; // 是否有下一页
        response(0,"成功",$info);
    } else {
        $info["list"] = null;
        $info["have_page"] = false;
        $config["auto-switch"] = 0;
        response(-1,$json["info"],$info);
    }
}

//请求
function request($url, $method = 'GET', $postdata = array(), $headers = array(),$responsetype = "all",$curl = null) {
    
    $headers[]  =  "Referer: https://www.lanzoui.com/";
    $headers[]  =  "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9";
    $headers[]  =  "Accept-Encoding: gzip, deflate, br";
    $headers[]  =  "Accept-Language: zh-CN,zh;q=0.9,zh-HK;q=0.8,zh-TW;q=0.7";
    $headers[]  =  "Cache-Control: max-age=0";
    $headers[]  =  "Connection: keep-alive";
    $headers[]  =  'sec-ch-ua: "Not?A_Brand";v="8", "Chromium";v="108", "Google Chrome";v="108"';
    $headers[]  =  "X-Forwarded-For: 0.0.0.0";
    
    if(!$curl) {
        $curl = curl_init();
        $internalCurl = true;
    }
    
    curl_setopt($curl, CURLOPT_URL, $url); //设置请求 URL
    curl_setopt($curl, CURLOPT_ENCODING, 'gzip'); //自动解压缩
    // 设置请求方式
    if ( strtoupper($method) == 'POST') { 
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postdata));
    } else {
        curl_setopt($curl, CURLOPT_HTTPGET, true);
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); // 设置请求头信息
    curl_setopt($curl, CURLOPT_HEADER, false); //如果需返回头部信息，则设置此参数为 true
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //设置是否将响应结果以字符串形式返回
    // 开启 SSL 验证
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_NOBODY, ($responsetype == "info")); // 禁止下载响应体
    
    // 执行请求并获取响应结果
    $data = array('data' => curl_exec($curl),'info' => curl_getinfo($curl));
    if(isset($internalCurl)) curl_close($curl);  // 关闭 cURL 句柄
    return $data[$responsetype] ?? $data; // 返回
}

//获取链接
function geturl($data,$info,$error,$pw) {
    global $desktopua,$ch;
    $data = preg_replace("/\/\/.*|\/\*[\s\S]*\*\/|function woio[\s\S]*?}/","",$data);
    $fileid = preg_match("/(?<=file=)\d+/", $data,$fileid) ? (int)$fileid[0] : null;
    $info = array("fid" => $fileid) + $info;
    
    // 密码文件检测
    if(strpos($data,"document.getElementById('pwd').value;") && !$pw) {
        $info["url"] = null;
        exit(response(2,"请输入密码",$info)); 
    }
    
    // 获取 sign
    if(preg_match("/(?<='sign':')\w+?(?=')/",$data,$a)) {
        $sign = $a[0];
    } else if(preg_match("/(?<='sign':)[\w]+?(?=,)/",$data,$b) && preg_match_all("/(?<=".$b[0]." = ').*(?=')/",$data,$a)) {
        $lengths = array_map("strlen", $a[0]);
        $minIndex = array_search(min($lengths),$lengths);
        $sign = $a[0][$minIndex];
    } else if(preg_match_all("/(?<=').+?_c/", $data, $a)) {
        $lengths = array_map('strlen', $a[0]);
        $minIndex = array_search(min($lengths),$lengths);
        $sign = $a[0][$minIndex];
    } else if(preg_match_all("/(?<=')[\w]{50,}+(?=')/",$data,$a)) {
        $lengths = array_map("strlen", $a[0]);
        $maxIndex = array_search(max($lengths),$lengths);
        $sign = $a[0][$maxIndex];
    } else {
        exit(response(-2,$error,null)); //错误
    }
    
    // 获取链接
    $websign = preg_match("/(?<=')[0-9]{1}(?=')/", $data, $websign) ? $websign[0] : "";
    $websignkey = preg_match("/(?<=')(?!=|post|sign|json)[a-zA-Z0-9]{4}(?=')/", $data, $websignkey) ? $websignkey[0] : "";
    $json = json_decode(request("https://www.lanzoui.com/ajaxm.php?file=$fileid","post",array('action' => 'downprocess', 'sign' => $sign, 'p' => $pw, 'websign' => $websign, 'websignkey' => $websignkey),$desktopua,"data",$ch),true); // POST请求API获取下载地址
    if($json["zt"] == 1) {
        if(isset($json["inf"]) && $json["inf"]) { 
	        $info["name"] = $json["inf"]; //文件名
	    }
	    $info["url"] = $json["dom"].'/file/'.$json["url"];
	    curl_close($ch);
	    e($info);
    } else {
        $info["url"] = null;
        response(-1,$json['inf'] ?? "获取失败",$info); //蓝奏云返回的错误信息
    }
}

//将上传时间转为 yyyy-mm-dd 格式
function Text_conversion_time($str) {
    if(!$str) {
        return $str;
    } else if (preg_match("/^\d+(?=\s*秒)/",$str,$i)) {
        return date("Y-m-d", time() - $i[0]);
    } else if (preg_match("/^\d+(?=\s*分钟)/",$str,$i)) {
        return date("Y-m-d", time() - $i[0] * 60);
    } else if (preg_match("/^\d+(?=\s*小时)/",$str,$i)) {
        return date("Y-m-d", time() - $i[0] * 60 * 60);
    } else if (preg_match("/^\d+(?=\s*天)/", $str,$i)) {
        return date("Y-m-d", time() - $i[0] * 24 * 60 * 60);
    } else if (strpos($str,"昨天") !== false) {
        return date("Y-m-d", time() -  24 * 60 * 60);
    } else if (strpos($str,"前天") !== false) {
        return date("Y-m-d", time() - 2 * 24 * 60 * 60);
    } else {
        return $str;
    }
}
?>
