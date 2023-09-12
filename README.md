# 蓝奏云解析API
蓝奏云解析，支持获取：文件名、分享者、文件大小、描述、文件链接、文件直链

获取到的链接是临时的下载链接，请按需使用。

要求PHP版本>=7.0

# 功能
1. 获取文件名、分享者、文件大小、文件描述、文件直链、上传时间
2. 支持带密码文件和带`webpage`参数的文件
3. 支持手机UA和电脑UA获取文件
4. 支持缓存链接
5. 支持多种数据格式响应

# 使用

[详细使用文档](Documentation.md)

## 请求示例

无密码：https://api.wzdc.cc/lzy/lanzouyunapi.php?data=https://www.lanzoui.com/wzdc

有密码：https://api.wzdc.cc/lzy/lanzouyunapi.php?data=https://wdsjhskj.lanzoum.com/iSzyf16gw8ti&pw=3ikz

直接下载：https://api.wzdc.cc/lzy/lanzouyunapi.php?data=https://wdsjhskj.lanzoum.com/iSzyf16gw8ti&pw=3ikz&redirect=1

## 返回数据
```json
{
    "code": 0,
    "msg": "成功",
    "data": {
        "name": "com.i0b47a46d08a1f3db.apk",
        "size": "2.4 M",
        "user": "无知的错",
        "time": "2020-10-18",
        "desc": "",
        "url": "https://store6.lanzouk.com/0826110031176240bb/2020/10/18/954f63d15176aba369a518d38eeb2a0f.apk?st=h2A7E-4A1OwWCkkDRNZT3A&e=1693023087&b=AjMNYgBtUHtUaFUzVmQAMARmWWRRZAAwAjUBPFFuVjUIaw5oCTpTMlJlBX0LOgRwVGo_c&fi=31176240&pid=44-200-205-205&up=2&mp=0&co=0"
    }
}
```
