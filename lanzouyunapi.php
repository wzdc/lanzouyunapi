<?php
/**
 * @package lanzouyunapi
 * @author wzdc
 * @version 1.2.8
 * @Date 2024-12-19
 * @link https://github.com/wzdc/lanzouyunapi
 */
 
//error_reporting(0); // 不显示错误
require('simple_html_dom.php'); //HTML解析
include "lanzouyunapiconfig.php"; //配置文件
header('Access-Control-Allow-Origin:*'); //允许跨站请求
$id = preg_match("/\.com\/(?:tp\/)?(.*)/",$_REQUEST["data"] ?? "",$id) ? $id[1] : $_REQUEST["data"] ?? ""; //ID或链接
if(!$id) exit(response(-4,"缺少参数",null));
$pw = $_REQUEST["pw"] ?? ""; //密码
$types = $_REQUEST["types"] ?? ""; //需要响应的数据类型
$redirect = $_REQUEST["redirect"] ?? ""; //重定向
$page = $_REQUEST["page"] ?? 1; //文件夹页数
$fid = preg_match("/^[^?]+/",$id,$fid) ? $fid[0] : null; //用于存储缓存的ID

//读取缓存
if($config["cache"] && $data3=apcu_fetch("file".$fid)) {
    header("X-APCu-Cache: HIT");
    if($redirect) header("Location: ".$data3["url"]); //重定向
    else response(0,"成功",$data3);
    exit;
} else if($config["foldercache"] && $data3=apcu_fetch("folder".$fid)) { //读取缓存（文件夹）
    $info = $data3[0];
    $parameter = $data3[1];
    $parameter["pg"] = $page;
    $t=$parameter["t"] - $data3[2] - time() + $page;
    if($page!=1 && $t>=0) sleep($t);
    if($pw) $parameter["pwd"] = $pw;
    $json=json_decode(request('https://www.lanzoui.com/filemoreajax.php',"post",$parameter,null,"data"),true); //获取文件列表
    if(is_array($json["text"])){
        foreach ($json["text"] as $v){
            $info["list"][] = array(
                "id"   => $v["id"],  //ID
                "ad"   => (bool)$v["t"], //推广文件
                "name" => htmlspecialchars_decode($v["name_all"]), //文件名 
                "size" => $v["size"],  //文件大小
                "time" => Text_conversion_time($v["time"]),  //上传时间
                "icon" => $v["p_ico"] ? "https://image.woozooo.com/image/ico/".$v["ico"]."?x-oss-process=image/auto-orient,1/resize,m_fill,w_100,h_100/format,png" : "https://assets.woozooo.com/assets/images/type/".$v["icon"].".gif", //文件图标
            );
        }
        
        if(count($json["text"]) >= 50) { 
            $info["have_page"] = true;
        } else {
            $info["have_page"] = false;
        }
        
        response(0,"文件夹",$info);
    } else {
        $info["list"] = null;
        $info["have_page"] = false;
        $config["auto-switch"] = 0;
        response(-1,$json["info"],$info);
    }
    exit;
}

if($config["mode"]=="mobile") mobile(); 
else pc();

//使用手机UA获取
function mobile(){ 
    global $id,$pw;
    $headers[] = "User-Agent: Mozilla/5.0 (iPad; U; CPU OS 6_0 like Mac OS X; zh-CN; iPad2)";
    $data=request("https://www.lanzoui.com/$id","GET",null,$headers,"data");
    $html=str_get_html($data);
    if(!$html) exit(response(-3,"获取失败",null)); //HTML解析失败
    $js = implode("\n", $html->find('script'));
    if(strpos($js,"/filemoreajax.php")) exit(folder($data,$js)); //是否为文件夹
    
    if(!preg_match("/(?<=')\?.+(?=')/",$js,$vr)) { 
    	$id2 = preg_match('/(?<=\'tp\/).*?(?=\';)/',$data,$id2) ? $id2[0] : $id;
    	$data2 = request("https://www.lanzoui.com/tp/$id2","GET",null,$headers,"data");
        $html2 = str_get_html($data2);
        if($html2) {
            $html2_title = $html2->find('title',0);
            $js2 = implode("\n", $html2->find('script'));
            $vr = preg_match("/(?<=')\?.+(?=')/",$js2,$vr) ? $vr[0] : null;
        }
    } else {
        $data2 = null;
        $html2_title = null;
        $vr = $vr[0];
    }
    
    $fileinfo=$html->find('meta[name=description]',0)->content ?? "";
    $json["dom"]=preg_match("/(?<=')https?:\/\/.+(?=')/",$data2 ?? $data,$url) ? $url[0] : null; //获取链接
    $info["name"]=htmlspecialchars_decode(@$html2_title ? $html2_title->innertext : $html->find('.appname',0)->innertext ?? ""); //获取文件名
    // $info["name"] = preg_match('/(?<=\<div class="md">).*(?= \<span class="mtt">)/',$data2,$filename) ? $filename[0] : null; //获取文件名
    $info["size"]=preg_match('/(?<=\文件大小：).*?(?=\|)/',$fileinfo,$filesize) || $data2&&preg_match('/(?<=mtt">\( ).+(?= \))/',$data2,$filesize) ? $filesize[0] : null; //获取文件大小
    $info["user"]=preg_match('/(?<=分享者:<\/span>).+(?= )/U',$data,$username) || $data2&&preg_match('/(?<=发布者:<\/span>).+(?= )/U',$data2,$username) ? $username[0] : $html->find('.user-name',0)->innertext ?? null; //获取分享者
    $info["time"]=Text_conversion_time(preg_match('/(?<=\<span class="mt2"><\/span>).*?(?=\<span class="mt2">)/',$data,$filetime) || $data2&&preg_match('/(?<=\<span class="mt2">时间:<\/span>).*(?=\<span class="mt2">)/',$data2,$filetime) ? trim($filetime[0]) : $html->find('.appinfotime',0)->innertext?? null); //获取上传时间
    //获取文件描述
    if(preg_match('/(?<=\|).*?(?=$)/',$fileinfo,$filedesc)) {
        $info["desc"] = htmlspecialchars_decode(str_replace("<br /> ","\n",$filedesc[0]));
    } else if($data2) {
        $filedesc=$html2->find('.mdo',0)->innertext ?? "";
        $info["desc"] = strpos($filedesc, "<span>") ? "" : htmlspecialchars_decode(trim(str_replace("<br /> ","\n",$filedesc))); //获取文件描述
    } else {
        $info["desc"] = null;
    }
    //$info["avatar"]=preg_match('/(?<=background:url\().+(?=\))/',$data,$fileavatar) ? $fileavatar[0] : null; //获取用户头像
    
    //重新排序数组
    /*$orderMap = array_flip(['name', 'size', 'user', 'time', 'desc']);
    uksort($info, function($a, $b) use ($orderMap) {
        return $orderMap[$a] <=> $orderMap[$b];
    });*/
    
    $error=$html->find('.off',0)->plaintext ?? "获取失败";
    $html->clear();
    if($vr) {
        $json['url']=$vr; //无密码
    } else {  //有密码（或遇到其他错误）
        $sign=sign($js2 ?? $js);
        if(!$sign) exit(response(-2,$error,null)); //错误
        else if(strpos($js2 ?? $js,"document.getElementById('pwd').value;") && !$pw) {
            $info["url"] = null;
            exit(response(2,"请输入密码",$info)); //密码文件检测   
        }
	    $json=json_decode(request('https://www.lanzoui.com/ajaxm.php', 'POST', array('action'=>'downprocess', 'sign'=>$sign, 'p'=>$pw), $headers,"data"),true); //POST请求API获取下载地址
	    $json['dom'].='/file/';
    }
	e($json,$info);
}

//使用电脑UA获取
function pc(){ 
    global $id,$pw;
    $headers[] = "User-Agent:Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36";
    $data=request("https://www.lanzoui.com/$id","GET",null,$headers,"data");
    $html=str_get_html($data);
    if(!$html) exit(response(-3,"获取失败",null)); //HTML解析失败
    $js = implode("\n", $html->find('script'));
    if(strpos($js,"/filemoreajax.php")) exit(folder($data,$js)); //是否为文件夹
    
    $fileinfo=$html->find('meta[name=description]',0)->content ?? "";
    $info["name"]=htmlspecialchars_decode($html->find('.n_box_3fn',0)->innertext ?? $html->find('div[style=font-size: 30px;text-align: center;padding: 56px 0px 20px 0px;]',0)->innertext ?? $html->find('span',0)->innertext ?? ""); //获取文件名
    $info["size"]=preg_match('/(?<=\文件大小：).*?(?=(?:\||$))/',$fileinfo,$filesize) ? $filesize[0] : null;  //获取文件大小 
    $info["user"]=$html->find('.user-name',0)->innertext ?? $html->find('font',0)->innertext ?? null; //获取分享者
    //获取上传时间
    if(preg_match('/(?<=\<span class="p7">上传时间：<\/span>).*?(?=\<br>)/',$data,$filetime)){
        $info["time"] = Text_conversion_time($filetime[0]);
    } else if(isset($html->find('.n_file_infos',1)->innertext)) {
        $info["time"] = Text_conversion_time($html->find('.n_file_infos',0)->innertext);
    } else {
        $info["time"] = null;
    }
    //获取文件描述
    if(preg_match('/(?<=\|).*?(?=$)/',$fileinfo,$filedesc) || preg_match('/(?<=文件描述：<\/span><br>\n).+(?=\t)/',$data,$filedesc)) {
        $info["desc"] = htmlspecialchars_decode(str_replace("<br /> ","\n",$filedesc[0]));
    } else if($html->find('.n_box_des',0)) {
        $info["desc"] = htmlspecialchars_decode(str_replace("<br /> ","\n",$html->find('.n_box_des',0)->innertext));
    } else {
        $info["desc"] = null;
    }
    //$info["avatar"]=preg_match('/(?<=background:url\().+(?=\))/',$data,$fileavatar) ? $fileavatar[0] : null; //获取用户头像
    
    //密码文件检测
    if(strpos($js,"document.getElementById('pwd').value;") && !$pw) {
        $info["url"] = null;
        exit(response(2,"请输入密码",$info)); 
    }
    
    $error=$html->find('.off',0)->plaintext ?? "获取失败";
    $src=$html->find('iframe',0)->src ?? null;
    $html->clear();
    
    if($src) { //无密码
        $data2=request("https://www.lanzoui.com$src")["data"];
        preg_match("/(?<=')(?!=|post|sign|json)[a-zA-Z0-9]{4}(?=')/", $data2, $a);
        preg_match("/(?<=')[0-9]{1}(?=')/", $data2, $b);
        $sign=sign($data2);
    } else {
        $sign=sign($js);
    }
    
    if(!$sign) {
        exit(response(-2,$error,null)); //错误
    }
    
	$json=json_decode(request('https://www.lanzoui.com/ajaxm.php',"post",array('action' => 'downprocess', 'sign' => $sign, 'p' => $pw, 'websign' => $b[0]??"", 'websignkey' => $a[0]??""),$headers,"data"),true); //POST请求API获取下载地址
	$json["dom"].='/file/';
	e($json,$info);
}

//获取文件夹
function folder($data,$js) {
    global $id,$pw,$page,$config,$fid;
    
    //获取名称
    if(preg_match("/document\.title\s*=\s*(.*);/",$js,$var) && preg_match("/".$var[1]."\s*=\s*'(.*)'/",$js,$name)){
        $info["name"] = htmlspecialchars_decode($name[1]); 
    } else if(preg_match("/class=\"b\">(.*?)</",$data,$name)) {
        $info["name"] = htmlspecialchars_decode(trim($name[1]));
    } else if(preg_match("/(?<=user-title\">).*(?=<)/",$data,$name)){
        $info["name"] = htmlspecialchars_decode($name[0]);
    } else if(preg_match("/(?>=class=\"b\">).*?(?=<div)/",$data,$name)) {
        $info["name"] = htmlspecialchars_decode($name[0]);
    } else {
        $info["name"] = null;
    }
    
    //获取描述
    if(preg_match("/(?<=说<\/span>)[\s\S]*?(?=<\/div>)/",$data,$d) && $d[0]){
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
    
    if(!preg_match("/(?<=data : {)[\s\S]*?(?=},)/",$js,$arr)) { //获取需要请求的参数
        exit(response(-2,"获取失败",null));
    }
    
    foreach (explode("\n",$arr[0]) as $v){
        if(preg_match("/'(.+)':([\d]+),?$|'(.+)':'(.*)',?$/",$v,$kv) && ($kv[1] || $kv[3])){
            if($kv[1]){
                $parameter[$kv[1]] = $kv[2];
            } else {
                $parameter[$kv[3]] = $kv[4];
            }
        } else if(preg_match("/'(.*)':(.*?),/",$v,$kv) && $kv[1]){
            preg_match("/".$kv[2]."\s*?=\s*?'(.*)'|".$kv[2]."\s*?=\s*?(\d+)/",$js,$value);
            $parameter[$kv[1]] = $value[1]??"";
        }
    }
    
    //获取子文件夹
    $folderarr=preg_split('/<div class="pc-folderlink">|<div class="mbx mbxfolder">/', $data);
    $info["folder"] = [];
    if($folderarr){
        unset($folderarr[0]);
        foreach ($folderarr as $f){
            $info["folder"][] = array(
                "id"   => preg_match("/(?<=href=\"\/).*?(?=\")/",$f,$fi) ? $fi[0] : null, //ID
                "name" => preg_match("/(?<=filename\">|<a href=\"\/".$fi[0]."\">).+?(?=<)/",$f,$fn) ? htmlspecialchars_decode($fn[0]) : null, //名称
                "desc" => preg_match("/(?<=filesize\">)[\s\S]*?(?=<)/",$f,$fd) ? htmlspecialchars_decode($fd[0]) : null, //描述
            );
        }
    }
    
    $parameter["pg"] = $page;
    $parameter["pwd"] = $pw;
    $t_end=$parameter["t"] - time(); 
    if($config["foldercache"] && $t_end > 0) apcu_store("folder$fid",[$info,$parameter,$t_end],$t_end); //缓存参数
    if($page != 1) sleep($page);
    
    //密码文件检测
    if(strpos($js,"document.getElementById('pwd').value;") && !$pw) {
        $info["list"] = null;
        exit(response(2,"请输入密码",$info));
    }
    
    //获取文件列表
    $json=json_decode(request('https://www.lanzoui.com/filemoreajax.php',"post",$parameter,null,"data"),true); //zt: 1正常,2没有文件,3密码错误
    if(is_array($json["text"])){
        foreach ($json["text"] as $v){
            $info["list"][]=array(
                "id"   => $v["id"],  //ID
                "ad"   => (bool)$v["t"], //推广文件
                "name" => htmlspecialchars_decode($v["name_all"]), //文件名 
                "size" => $v["size"],  //文件大小
                "time" => Text_conversion_time($v["time"]),  //上传时间
                "icon" => $v["p_ico"] ? "https://image.woozooo.com/image/ico/".$v["ico"]."?x-oss-process=image/auto-orient,1/resize,m_fill,w_100,h_100/format,png" : "https://assets.woozooo.com/assets/images/type/".$v["icon"].".gif", //文件图标 
            );
        }
        
        if(count($json["text"]) >= 50) { 
            $info["have_page"] = true;
        } else {
            $info["have_page"] = false;
        }
        
        response(0,"文件夹",$info);
    } else {
        $info["list"]=null;
        $info["have_page"] = false;
        response(-1,$json["info"],$info);
    }
}

//将获取的数据做最后的处理
function response($code,$msg,$data){
    global $config,$types;
    
    //自动切换获取方式
    if($config["auto-switch"] && !in_array($code,array(-4,0,2)) && $msg!="密码不正确"){
        $config["auto-switch"] = 0;
        if($config["mode"] == "mobile") pc();
        else mobile();
        exit;
    }
    
    //响应数据
    //asort($data); //数组排序
    $res=array("code"=>$code, "msg"=>$msg, "data"=>$data);
    switch ($types) {
        case 'text':
            header('Content-Type: text/plain;charset=UTF-8');
            echo $data["url"] ?? $msg;
            break;
        case 'xml':
            header('Content-Type: application/xml');
            echo arrayToXml($res);
            break;
        default:
            header('Content-Type: application/json');
            echo json_encode($res,JSON_UNESCAPED_UNICODE);
            break;
    }
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
            $text = $dom->createTextNode($val); 
            $itemx->appendChild($text); 
        } else { 
            arrayToXml($val,$dom,$itemx); 
        } 
    } 
    return $dom->saveXML(); 
}

//处理蓝奏云数据
function e($json,$info){
	global $redirect,$config,$fid;
	
    if($json['url'] && $json["dom"]) {
	    $info["url"] = $json['dom'].$json['url']; //拼接链接
	    
	    if(isset($json["inf"]) && $json["inf"]) { 
	        $info["name"] = $json["inf"]; //文件名
	    }
	    
	    $url = request($info["url"])["info"]["redirect_url"]; //获取直链
		
		if(!$url) {
		    response(1,"获取直链失败",$info); //链接获取失败
		 } else {
		     $info["url"] = $url;
		     if(!$info["time"] && preg_match("/(?!(0000))\d{4}\/(?:0[1-9]|1[0-2])\/(?:0[1-9]|[12]\d|3[01])/",$url,$time)) {
		         $info["time"] = str_ireplace("/","-",$time[0]); //截取上传时间
		     }
		     
             if($config["cache"]){
                  apcu_store("file$fid",$info,preg_match("/(?<=&e=)\d*(?=&)/",$url,$endtime) ? $endtime[0] - time() : $config["cacheexpired"]); //写入缓存
                  header("X-APCu-Cache: MISS");
             }
             
             if($redirect) { 
                 header("Location: ".$url); //重定向
             } else { 
                 response(0,"成功",$info);
             }
		}
    } else {
        $info["url"] = null;
	    response(-1,$json['inf'] ?? "获取失败",$info); //蓝奏云返回的错误信息
	}
}

//请求
function request($url, $method = 'GET', $postdata = array(), $headers = array(),$responsetype = "all") {
    
    $headers[]  =  "Referer: https://www.lanzoui.com/";
    $headers[]  =  "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9";
    $headers[]  =  "Accept-Encoding: gzip, deflate, br";
    $headers[]  =  "Accept-Language: zh-CN,zh;q=0.9,zh-HK;q=0.8,zh-TW;q=0.7";
    $headers[]  =  "Cache-Control: max-age=0";
    $headers[]  =  "Connection: keep-alive";
    $headers[]  =  'sec-ch-ua: "Not?A_Brand";v="8", "Chromium";v="108", "Google Chrome";v="108"';
    
    $curl = curl_init();
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
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    if($responsetype=="info") {
        curl_setopt($curl, CURLOPT_NOBODY, true); // 禁止下载响应体
    }
    // 执行请求并获取响应结果
    $data = array('data' => curl_exec($curl),'info' => curl_getinfo($curl));
    curl_close($curl);  // 关闭 cURL 句柄
    return $data[$responsetype] ?? $data; // 返回
}

//获取sign
function sign($data) {
    $data=preg_replace("/\/\/.*?|\/\*(?s)\*\/|function woio[\s\S]*?}/","",$data);
    if(preg_match("/(?<='sign':')\w+?(?=')/",$data,$a)) {
        $sign = $a[0];
    }
    else if(preg_match("/(?<='sign':)[\w]+?(?=,)/",$data,$b)) {
        preg_match_all("/(?<=".$b[0]." = ').*(?=')/",$data,$a);
        $lengths = array_map("strlen", $a[0]);
        $minIndex = array_search(min($lengths),$lengths);
        $sign = $a[0][$minIndex];
    } else if(preg_match_all("/(?<=').+?_c/", $data, $a)){
        $lengths = array_map('strlen', $a[0]);
        $minIndex = array_search(min($lengths),$lengths);
        $sign = $a[0][$minIndex];
    } else if(preg_match_all("/(?<=')[\w]{50,}+(?=')/",$data,$a)) {
        $lengths = array_map("strlen", $a[0]);
        $maxIndex = array_search(max($lengths),$lengths);
        $sign = $a[0][$maxIndex];
    } else {
        $sign = false;
    }
    return $sign;
}

//将上传时间转为 yyyy-mm-dd 格式
function Text_conversion_time($str){
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
