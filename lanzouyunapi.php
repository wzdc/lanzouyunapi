<?php
/**
 * @package lanzouyunapi
 * @author wzdc
 * @version 1.0.3
 * @Date 2023-3-20
 * @link https://github.com/xsbb666/lanzouyunapi
 */
header('Access-Control-Allow-Origin:*');
if(!$_REQUEST["data"])
exit(response(-4,"缺少参数",null));
include('simple_html_dom.php');
if($_REQUEST["type"]=="url"){
    if(explode('/',$_REQUEST['data'])[4]){
         $id=explode('/',$_REQUEST['data'])[4];
         $url='https://www.lanzoui.com/'.$id;
    } else if(explode('/',$_REQUEST['data'])[3]) {
         $id=explode('/',$_REQUEST['data'])[3];
         $url='https://www.lanzoui.com/'.$id;
    }
} else if($_REQUEST["type"]=="id"){
    $id=$_REQUEST["data"];
    $url='https://www.lanzoui.com/'.$id;
} else {
    if(explode('/',$_REQUEST['data'])[4]){
         $id=explode('/',$_REQUEST['data'])[4];
         $url='https://www.lanzoui.com/'.$id;
    } else if(explode('/',$_REQUEST['data'])[3]) {
         $id=explode('/',$_REQUEST['data'])[3];
         $url='https://www.lanzoui.com/'.$id;
    } else {
         $id=$_REQUEST['data'];
         $url="https://www.lanzoui.com/".$id;
    }
}

$headers[]  =  "Referer: https://www.lanzoui.com/";
if($_REQUEST["mode"]=="moblie"){ //使用手机UA获取
    $headers[]  =  "User-Agent:Mozilla/5.0 (iPad; U; CPU OS 6_0 like Mac OS X; zh-CN; iPad2)";
    $data=GET($url,$headers);
    $html=str_get_html($data);
    $vr = '?'.explode("'",explode("= '?",$data)[1])[0];
    if(!$data)
    exit(response(-3,"获取失败",null));
    else if($vr=='?') { 
    	preg_match('/(?<=\'tp\/).*?(?=\';)/',$data,$id2);
    	$data2=GET("https://www.lanzoui.com//tp/".$id2[0],$headers);
    	$vr = '?'.explode("'",explode("'?",$data2)[1])[0];
        $html2 = str_get_html($data2);
	    $json['dom']='http'.explode("'",explode("'http",$data2)[1])[0];
    } else  
	    $json['dom']='http'.explode("'",explode("'http",$data)[1])[0];
    
    $fileinfo=$html->find('meta[name=description]',0)->content;
    $fileinfo2=$html->find('.mf',0)->innertext;
    if($html->find('.appname',0)->innertext)//获取文件名
    $info["name"]=$html->find('.appname',0)->innertext;
    else
    $info["name"]=$html2->find('title',0)->innertext; 
    preg_match('/(?<=\文件大小：).*?(?=\|)/',$fileinfo,$filesize);//获取文件大小
    $info["size"]=$filesize[0]; 
    if($html->find('.user-name',0)->innertext)//获取分享者
    $info["user"]=$html->find('.user-name',0)->innertext;
    else{
    preg_match('/(?<=\<\/span>).*?(?=\<span class="mt2">)/',$fileinfo2,$username);
    $info["user"]=trim($username[0]); 
    }
    preg_match('/(?<=\<span class="mt2"><\/span>).*?(?=\<span class="mt2">)/',$fileinfo2,$filetime); //获取上传时间
    if($filetime[0])
    $info["time"]=trim($filetime[0]);
    else
    $info["time"]=$html->find('.appinfotime',0)->innertext;
    preg_match('/(?<=\|).*?(?=$)/',$fileinfo,$filedesc);//获取文件描述
    $info["desc"]=$filedesc[0];
    if($vr=='?') {//有密码
    preg_match_all("~['](.+_c)~", $data2, $c);
    $key=$c[1][0];
    if(!$key&&$html->find('.off',0)->innertext)
    exit(response(-2,$html->find('.off',0)->plaintext,null));
    else if(!$key)
    exit(response(-2,"获取失败",null));
	$json=json_decode(Post('https://www.lanzoui.com/ajaxm.php',array('action'=>'downprocess')+array('sign'=>$key)+array('p'=>$_REQUEST['pw']),$headers),true);//POST请求API获取下载地址
	$json['dom']=$json['dom'].'/file/';
    } else  //无密码
	$json['url']=$vr;
    
	if($json['url']) {
	    $shortUrl= $json['dom'].$json['url'];
	    if($_REQUEST['link']) {
		    $orinalUrl = restoreUrl($shortUrl);
		    if(!$orinalUrl) {
		        $info["url"]=$shortUrl;
		        response(1,"获取直链失败",$info);//链接还原失败
		    } else if($_REQUEST['redirect']) 
		    header("Location: ".$orinalUrl);
		    else {
		        $info["url"]=$orinalUrl;
		        response(0,"成功",$info);
		    }
	    } else {
		    if($_REQUEST['redirect']) 
		    header("Location: $shortUrl");
		    $info["url"]=$shortUrl;
		    response(0,"成功",$info);
	    }
    } else {
    $info["url"]=null;
	response(-1,$json['inf'],$info); //蓝奏云返回的错误信息
	}
} else { //使用电脑UA获取
    $headers[]  =  "User-Agent:Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36";
    $data=GET($url,$headers);
    $html=str_get_html($data);
    if(!$html)
    exit(response(-3,"获取失败",null));
    if($html->find('.n_box_3fn',0)->innertext)//获取文件名
    $info["name"]=$html->find('.n_box_3fn',0)->innertext;
    else
    $info["name"]=$html->find('div[style=font-size: 30px;text-align: center;padding: 56px 0px 20px 0px;]',0)->innertext;
    $fileinfo=$html->find('meta[name=description]',0)->content;
    preg_match('/(?<=\文件大小：).*?(?=\|)/',$fileinfo,$filesize);//获取文件大小
    $info["size"]=$filesize[0]; 
    if($html->find('.user-name',0)->innertext)//获取分享者
    $info["user"]=$html->find('.user-name',0)->innertext; 
    else
    $info["user"]=$html->find('font',0)->innertext; 
    preg_match('/(?<=\|).*?(?=$)/',$fileinfo,$filedesc);//获取文件描述
    $info["desc"]=$filedesc[0];
    preg_match('/(?<=\<span class="p7">上传时间：<\/span>).*?(?=\<br>)/',$data,$filetime);//获取上传时间
    if($filetime[0])
    $info["time"]=$filetime[0];
    else if($html->find('.n_file_infos',1)->innertext)
    $info["time"]=$html->find('.n_file_infos',0)->innertext;
    else
    $info["time"]=null;
    
    if($html->find('iframe',0)->src) { //无密码
        $data2=GET("https://www.lanzoui.com".$html->find('iframe',0)->src,null);
        preg_match_all("~sign':'(.*?)'~", $data2, $key);
        preg_match_all("~ws_sign = '(.*?)';~", $data2, $a);
        preg_match_all("~wsk_sign = '(.*?)';~", $data2, $b);
        $key=$key[1][0];
    } else { //有密码
        preg_match_all("~action=(.*?)&sign=(.*?)&p='\+(.*?),~", $data, $key);
        $key=$key[2][1];
    }
    
    if(!$key&&$html->find('.off',0)->innertext)
    exit(response(-2,$html->find('.off',0)->plaintext,null));
    else if(!$key)
    exit(response(-2,"获取失败",null));
	$json=json_decode(Post('https://www.lanzoui.com/ajaxm.php',array('action'=>'downprocess')+array('sign'=>$key)+array('p'=>$_REQUEST['pw'])+array('websign'=>$a[1][0])+array('websignkey'=>$b[1][0]),$headers),true);//POST请求API获取下载地址
	if($json['url']) {
	    $shortUrl= $json['dom'].'/file/'.$json['url'];
	    if($json["inf"])
	    $info["name"]=$json["inf"];
	    if($_REQUEST['link']) {
		    $orinalUrl = restoreUrl($shortUrl);
		    if(!$orinalUrl) {
		        $info["url"]=$shortUrl;
		        response(1,"获取直链失败",$info);//链接还原失败
		    } else if($_REQUEST['redirect']) 
		    header("Location: ".$orinalUrl);
		    else {
		        $info["url"]=$orinalUrl;
		        response(0,"成功",$info);
		    }
	    } else {
		    if($_REQUEST['redirect']) 
		    header("Location: $shortUrl");
		    $info["url"]=$shortUrl;
		    response(0,"成功",$info);
	    }
    } else {
    $info["url"]=null;
	response(-1,$json['inf'],$info); //蓝奏云返回的错误信息
	}
}

//GET获取跳转链接
function restoreUrl($shortUrl) {
$headers[]  =  "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9";
$headers[]  =  "Accept-Encoding: gzip, deflate, br";
$headers[]  =  "Accept-Language: zh-CN,zh;q=0.9,zh-HK;q=0.8,zh-TW;q=0.7";
$headers[]  =  "Cache-Control: max-age=0";
$headers[]  =  "Connection: keep-alive";
$headers[]  =  'sec-ch-ua: "Not?A_Brand";v="8", "Chromium";v="108", "Google Chrome";v="108"';

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $shortUrl);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:70.0) Gecko/20100101 Firefox/70.0');
curl_setopt($curl, CURLOPT_COOKIE, 'down_ip=1');
curl_setopt($curl, CURLOPT_HTTPHEADER,$headers);
curl_setopt($curl, CURLOPT_HEADER, true);
curl_setopt($curl, CURLOPT_NOBODY, false);
curl_setopt($curl, CURLOPT_TIMEOUT, 15);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
$data = curl_exec($curl);
$curlInfo = curl_getinfo($curl);
curl_close($curl);
if($curlInfo['http_code'] == 301 || $curlInfo['http_code'] == 302) {
return $curlInfo['redirect_url'];
}
return '';
}

//Post
function Post($url,$curlPost,$headers) {
        $curlPost=http_build_query($curlPost);
        $curl = curl_init ();
        curl_setopt($curl,CURLOPT_URL,$url);
        curl_setopt($curl,CURLOPT_HEADER,false);
        if($headers)
        curl_setopt($curl,CURLOPT_HTTPHEADER,$headers);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_NOBODY,true);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl,CURLOPT_POST,true);
        curl_setopt($curl,CURLOPT_POSTFIELDS,$curlPost);
        $return_str = curl_exec ($curl);
        curl_close ( $curl );
        return $return_str;
}

//返回数据 
function response($code,$msg,$data)
{
    $res=array("code"=>$code,"msg"=>$msg,"data"=>$data);
    if($_REQUEST["types"]=="text"){
        header('Content-Type:text/plain;charset=UTF-8');
        if($code=='0')
        echo $data["url"];
        else
        echo $msg;
    } else if($_REQUEST["types"]=="xml") {
        header('Content-Type: application/xml');
        echo arrayToXml(array("code"=>$code,"msg"=>$msg,$data));
    } else {
        header('Content-Type: application/json');
        echo json_encode($res,JSON_UNESCAPED_UNICODE);
    }
}

//GET 
function GET($url,$headers)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL,$url);
    curl_setopt($curl, CURLOPT_HEADER,0);
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    if($headers)
    curl_setopt($curl,CURLOPT_HTTPHEADER,$headers);
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($curl,CURLOPT_NOBODY,0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($curl);
    curl_close($curl);
    return $data;
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
?>
