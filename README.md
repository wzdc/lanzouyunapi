# 蓝奏云解析API
蓝奏云解析，支持获取：文件名、分享者、文件大小、描述、文件链接、文件直链

获取到的链接是临时的下载链接，请按需使用。

要求PHP版本>=7.0

# 功能
1. 支持获取详细的文件（夹）信息
2. 支持带密码文件
3. 支持获取大部分文件（夹）
4. 支持缓存链接
5. 支持多种数据格式响应

# 使用

[详细使用文档](Documentation.md)

## 请求示例

无密码：https://vercel-chi-kohl.vercel.app/lanzouyunapi.php?url=https://www.lanzoui.com/wzdc

有密码：https://vercel-chi-kohl.vercel.app/lanzouyunapi.php?url=https://wdsjhskj.lanzoum.com/iSzyf16gw8ti&pw=3ikz

直接下载：https://vercel-chi-kohl.vercel.app/lanzouyunapi.php?url=https://wdsjhskj.lanzoum.com/iSzyf16gw8ti&pw=3ikz&type=down

## 返回数据
```json
{
  "code": 200,
  "msg": "成功",
  "data": {
    "fid": 31176240,
    "name": "com.i0b47a46d08a1f3db.apk",
    "size": "2.4 M",
    "user": "无知的错",
    "time": "2020-10-18",
    "desc": "",
    "icon": null,
    "avatar": null,
    "url": "https://c1026.dmpdmp.com/6e491788d2d8ea01825ae92d419a5cd1/68ef6063/2020/10/18/954f63d15176aba369a518d38eeb2a0f.apk?fn=com.i0b47a46d08a1f3db.apk"
  }
}
```
