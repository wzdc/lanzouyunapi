<?php
/**
 * @package lanzouyunapi
 * @author wzdc
 * @version 1.0.5
 * @Date 2023-7-4
 * @link https://github.com/wzdc/lanzouyunapi
 */
 
//error_reporting(E_ALL & ~(E_NOTICE | E_WARNING)); // 不显示 Notice 和 WARNING 错误
require('simple_html_dom.php'); //HTML解析
include "lanzouyunapiconfig.php"; //配置文件
header('Access-Control-Allow-Origin:*'); //允许跨站请求
$id = preg_match("/\.com\/(?:tp\/)?(.*)/",$_REQUEST["data"] ?? "",$id) ? $id[1] : $_REQUEST["data"] ?? ""; //ID或链接
if(!$id) exit(response(-4,"缺少参数",null));
$pw = $_REQUEST["pw"] ?? ""; //密码
$mode = $_REQUEST["mode"] ?? ""; //获取方式
$link = $_REQUEST["link"] ?? ""; //直链
$auto = $_REQUEST["auto"] ?? ""; //自动切换获取方式
$types = $_REQUEST["types"] ?? ""; //需要响应的数据类型
$redirect = $_REQUEST["redirect"] ?? ""; //重定向

//读取缓存
if($cacheconfig["cache"]&&$data3=apcu_fetch($id)) {
    if(!isset($data3["time"])&&isset($data3["link"])&&preg_match("/(?!(0000))\d{4}\/(?:0[1-9]|1[0-2])\/(?:0[1-9]|[12]\d|3[01])/",$data3["link"],$time)) { //截取上传时间
	    $data3["time"]=str_ireplace("/","-",$time[0]);
    }
    
    if($link){ //获取缓存的直链
        if($cacheconfig["verify"]&&!preg_match('/200/',@get_headers($data3["link"])[0])) { //验证链接是否有效
            $data3["link"]=request($data3["url"])["info"]["redirect_url"]; //无效尝试重新获取
            apcu_store($id,$data3,$cacheconfig["time"]);
        }
        if(isset($data3["link"])) {
            exit(response(2,"来自缓存",$data3));
        }
    }
    else if($data3["url"]) { //获取缓存的链接
        if($cacheconfig["verify"]&&$link2=request($data3["url"])["info"]["redirect_url"]){
            if(!isset($data3["time"])&&preg_match("/(?!(0000))\d{4}\/(?:0[1-9]|1[0-2])\/(?:0[1-9]|[12]\d|3[01])/",$link2,$time)){ //截取上传时间
                $data3["time"]=str_ireplace("/","-",$time[0]);
            }
            $data3["link"]=$link2;
            apcu_store($id,$data3,$cacheconfig["time"]);
        }
        exit(response(2,"来自缓存",$data3));
    }
}

if($mode=="mobile") mobile();
else pc();

//使用手机UA获取
function mobile(){ 
    global $id,$pw;
    $headers[] = "User-Agent: Mozilla/5.0 (iPad; U; CPU OS 6_0 like Mac OS X; zh-CN; iPad2)";
    $data=request("https://www.lanzoui.com/$id","GET",null,$headers,"data");
    $html=str_get_html($data);
    
    if(!$html) exit(response(-3,"获取失败",null)); //HTML解析失败
    else if(!preg_match("/(?<=')\?.+(?=')/",$data,$vr)) { 
    	$id2 = preg_match('/(?<=\'tp\/).*?(?=\';)/',$data,$id2) ? $id2[0] : "";
    	$data2=request("https://www.lanzoui.com/tp/$id2","GET",null,$headers,"data");
        $vr = preg_match("/(?<=')\?.+(?=')/",$data2,$vr) ? $vr[0] : null;
        $html2 = str_get_html($data2);
    } else {
        $vr = $vr[0];
    }
    
    $fileinfo=$html->find('meta[name=description]',0)->content ?? "";
    $json["dom"]=preg_match("/(?<=')https?:\/\/.+(?=')/",$data2 ?? $data,$url) ? $url[0] : null; //获取链接
    $info["name"]=$html->find('.appname',0)->innertext ?? (isset($html2) ? $html2->find('title',0)->innertext : ""); //获取文件名
    $info["size"]=preg_match('/(?<=\文件大小：).*?(?=\|)/',$fileinfo,$filesize) ? $filesize[0] : null; //获取文件大小
    $info["user"]=$html->find('.user-name',0)->innertext ?? (preg_match('/(?<=分享者:<\/span>).+(?= )/U',$data,$username) ? $username[0] : null); //获取分享者
    $info["time"]=preg_match('/(?<=\<span class="mt2"><\/span>).*?(?=\<span class="mt2">)/',$data,$filetime) ? trim($filetime[0]) : $html->find('.appinfotime',0)->innertext ?? null; //获取上传时间
    $info["desc"]=preg_match('/(?<=\|).*?(?=$)/',$fileinfo,$filedesc) ? $filedesc[0] : null; //获取文件描述
    //$info["avatar"]=preg_match('/(?<=background:url\().+(?=\))/',$data,$fileavatar) ? $fileavatar[0] : null; //获取用户头像
    
    if($vr) {
        $json['url']=$vr; //无密码
    } else {  //有密码（或遇到其他错误）
    if(!preg_match("/(?<=sign = ').+'/", $data2, $key)) {
        exit(response(-2,$html->find('.off',0)->plaintext ?? "获取失败",null)); //错误
    }
	    $json=json_decode(request('https://www.lanzoui.com/ajaxm.php', 'POST', array('action'=>'downprocess', 'sign'=>$key[0], 'p'=>$pw), $headers,"data"),true); //POST请求API获取下载地址
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
    
    $fileinfo=$html->find('meta[name=description]',0)->content ?? "";
    $info["name"]=$html->find('.n_box_3fn',0)->innertext ?? $html->find('div[style=font-size: 30px;text-align: center;padding: 56px 0px 20px 0px;]',0)->innertext ?? null; //获取文件名
    $info["size"]=preg_match('/(?<=\文件大小：).*?(?=\|)/',$fileinfo,$filesize) ? $filesize[0] : null;  //获取文件大小 
    $info["user"]=$html->find('.user-name',0)->innertext ?? $html->find('font',0)->innertext ?? null; //获取分享者
    $info["time"]=preg_match('/(?<=\<span class="p7">上传时间：<\/span>).*?(?=\<br>)/',$data,$filetime) ? $filetime[0] : ($html->find('.n_file_infos',1)->innertext ?? null ? $html->find('.n_file_infos',0)->innertext : null); //获取上传时间
    $info["desc"]=preg_match('/(?<=\|).*?(?=$)/',$fileinfo,$filedesc) ? $filedesc[0] : null; //获取文件描述
    //$info["avatar"]=preg_match('/(?<=background:url\().+(?=\))/',$data,$fileavatar) ? $fileavatar[0] : null; //获取用户头像
    
    $src=$html->find('iframe',0)->src ?? null;
    if($src) { //无密码
        $data2=request("https://www.lanzoui.com$src")["data"];
        preg_match("/(?<=sign':').+?(?=')/", $data2, $key);
        preg_match("/(?<=ws_sign = ').*?(?=')/", $data2, $a);
        preg_match("/(?<=wsk_sign = ').*?(?=')/", $data2, $b);
    } else { //有密码
        preg_match("/(?<=&sign=).+&/", preg_replace("/\/\/.+|\/\*.+\s?.*\*\//","",$data), $key);
    }
    
    if(!isset($key[0])) {
        exit(response(-2,$html->find('.off',0)->plaintext ?? "获取失败",null)); //错误
    }
	$json=json_decode(request('https://www.lanzoui.com/ajaxm.php',"post",array('action' => 'downprocess', 'sign' => $key[0], 'p' => $pw, 'websign' => $a[0]??"", 'websignkey' => $b[0]??""),$headers,"data"),true); //POST请求API获取下载地址
	$json["dom"].='/file/';
	e($json,$info);
}

//将获取的数据做最后的处理
function response($code,$msg,$data){
    global $id,$cacheconfig,$auto,$link,$redirect,$types,$mode;
    
    //自动切换
    if($auto&&$code!=4&&!$data["url"]){
        $auto=null;
        if($mode=="mobile") mobile();
        else pc();
        exit;
    }
    
    //写入缓存
    if($cacheconfig["cache"]&&($code==0||$code==1))
    apcu_store($id,$data,$cacheconfig["time"]);
    
    //直链
    if($link&&($code==0||$code==2)) 
    $data["url"]=$data["link"];
    unset($data["link"]);
    
    //重定向
    if($redirect&&$code==0) 
	header("Location: ".$data["url"]);
    
    //响应数据
    //asort($data); //数组排序
    $res=array("code"=>$code,"msg"=>$msg,"data"=>$data);
    switch ($types) {
        case 'text':
            header('Content-Type:text/plain;charset=UTF-8');
            echo $code==0 ? $data["url"] : $msg;
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
	global $link;
	
	//将上传时间转为 yyyy-mm-dd 格式
	if (preg_match("/秒/", $info["time"] ?? "")) {
        $info["time"] = date("Y-m-d", time() - intval($info["time"]));
    } else if (preg_match("/分钟/", $info["time"] ?? "")) {
        $info["time"] = date("Y-m-d", time() - intval($info["time"]) * 60);
    } else if (preg_match("/小时/", $info["time"] ?? "")) {
        $info["time"] = date("Y-m-d", time() - intval($info["time"]) * 60* 60);
    } else if (preg_match("/天/", $info["time"] ?? "")) {
        $info["time"] = date("Y-m-d", time() - intval($info["time"]) * 24 * 60 * 60);
    }
    
    if($json['url']&&$json["dom"]) {
	    $info["url"]=$json['dom'].$json['url']; //拼接链接
	    if(isset($json["inf"])&&$json["inf"]) $info["name"]=$json["inf"]; //文件名
	    if($link) {
		    $info["link"]=request($info["url"])["info"]["redirect_url"]; //获取直链
		    if(!$info["link"])
		        response(1,"获取直链失败",$info); //链接获取失败
		     else {
		        if(!$info["time"]&&preg_match("/(?!(0000))\d{4}\/(?:0[1-9]|1[0-2])\/(?:0[1-9]|[12]\d|3[01])/",$info["link"],$time)) //截取上传时间
		            $info["time"]=str_ireplace("/","-",$time[0]);
		        response(0,"成功",$info);
		    }
	    } else response(0,"成功",$info);
    } else {
        $info["url"]=null;
	    response(-1,$json['inf']??"获取失败",$info); //蓝奏云返回的错误信息
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
?>
