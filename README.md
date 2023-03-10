## 蓝奏云解析API
蓝奏云解析，支持获取：文件名、分享者、文件大小、描述、文件链接、文件直链

获取到的链接是临时的下载链接，请按需使用。


## 请求
请求类型：`GET/POST`

请求示例：`lanzouyunapi.php?data=wzdc`
| 参数 | 必填 | 默认 | 说明 |
| -- | -- | -- | -- |
| mode | 否 | pc | 获取数据方式 |
| data | 是 | | 蓝奏云链接或ID |
| pw | 否 | | 密码 |
| type | 否 | auto | 请求类型 |
| types | 否 | json | 返回的数据类型 |
| redirect | 否 | false | 重定向 |
| link | 否 | false | 获取文件直链 |


### mode参数说明
> 当电脑UA获取不了文件后可以尝试使用手机UA获取

| 参数 | 说明 |
| -- | -- |
| pc | 使用电脑UA获取数据 |
| moblie | 使用手机UA获取数据 |


### type参数说明
可选值`auto(自动)、url、id`。

### types参数说明
可选值 `text(文字)、json、xml`。

### redirect和link说明
> 开启重定向(redirect)后，服务器会将获取到的链接进行重定向。如果获取链接失败则会返回错误信息，返回的数据类型由你（传入的types参数）决定。

请求示例（开启重定向和获取文件直链）：`lanzouyunapi.php?data=wzdc&redirect=true&link=true`

| 参数 | 说明 |
| -- | -- |
| true | 开启 |
| false | 关闭 |

## 返回

| 参数 | 说明 | 数据类型 |
| -- | -- | -- |
| code | 状态码 | int |
| msg | 信息 | String |
| data | 文件信息 | object |

### 文件信息
| 参数 | 说明 | 数据类型 |
| -- | -- | -- |
| name | 文件名 | String |
| size | 文件大小 | String |
| user | 分享者 | String |
| desc | 描述 | String |
| time | 上传时间 | String |
| url | 链接 | String |

### 状态码说明
| code | 说明 | msg |
| -- | -- | -- |
| 1 | 已经成功获取链接，但获取直链失败 | 获取直链失败 |
| 0 | 获取成功 | 成功 |
| -1 | 请求接口时，蓝奏云接口返回的错误信息 | （显示蓝奏云返回的错误信息） |
| -2 | 获取网页内容时发生错误，会尝试再次获取蓝奏云提示的错误信息 | （显示获取到的错误信息，如果没有则显示获取失败） |
| -3 | HTML解析错误 | 获取失败 |
| -4 | 缺少参数 | 缺少参数 |
