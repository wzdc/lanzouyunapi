# 请求
请求类型：`GET/POST`

请求示例：https://api.wzdc.tk/lanzouyunapi?data=wzdc
| 参数 | 必填 | 默认 | 说明 |
| -- | -- | -- | -- |
| mode | 否 | pc | 获取数据方式（可选值：pc、mobile） |
| data | 是 | | 蓝奏云链接或ID |
| pw | 否 | | 密码 |
| types | 否 | json | 返回的数据类型(可选值: text、json、xml) |
| redirect | 否 | 0 | 直接下载 |
| auto | 否 | 1 | 自动切换获取方式 |
| page | 否 | 1 | 页数 |

# 功能说明


| 参数 | 说明 |
| -- | -- |
| 1  | 开启 |
| 0  | 关闭 |

## 直接下载

开启直接下载(redirect)后，服务器会将获取到的链接进行重定向。如果获取链接失败则会返回错误信息，返回的数据类型由你（传入的`types`参数）决定。

直接下载：https://api.wzdc.tk/lanzouyunapi?data=wzdc&redirect=1

## 自动切换获取方式

例如当使用电脑UA获取链接失败就使用手机UA去获取（先用哪个由你传入的`mode`参数决定）。

自动切换获取方式默认开启，关闭：https://api.wzdc.tk/lanzouyunapi?data=wzdc&auto=0

# 返回

| 参数 | 说明 | 数据类型 |
| -- | -- | -- |
| code | 状态码 | int |
| msg | 信息 | String |
| data | 文件（夹）信息 |  |

## 文件信息
| 参数 | 说明 | 数据类型 |
| -- | -- | -- |
| name | 文件名 | String |
| size | 文件大小 | String |
| user | 分享者 | String |
| desc | 描述 | String |
| time | 上传时间 | String |
| url | 链接 | String |

## 文件夹信息
见[文件夹说明文档](Documentation_folder.md)

## 状态码说明
| code | msg | 说明 |
| -- | -- | -- |
| 1  | 获取直链失败 | 已经成功获取链接，但获取直链失败 |
| 0  | 成功 | 获取成功 |
| -1 | （显示蓝奏云返回的错误信息） | 请求接口时，蓝奏云接口返回的错误信息 |
| -2 | （显示获取到的错误信息，如果没有则显示获取失败） | 获取网页内容时发生错误，会尝试再次获取蓝奏云提示的错误信息 |
| -3 | 获取失败 | HTML解析错误 |
| -4 | 缺少参数 | 缺少参数 |

# 其他说明
1. 遇到无法解析请附带无法解析的文件分享链接[提交issues](https://github.com/wzdc/lanzouyunapi/issues)
