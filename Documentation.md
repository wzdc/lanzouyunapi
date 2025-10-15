# 请求
请求类型：`GET/POST`

请求示例：https://vercel-chi-kohl.vercel.app/lanzouyunapi.php?url=wzdc
| 参数 | 必填 | 默认 | 说明 |
| -- | -- | -- | -- |
| url | 是 | | 蓝奏云链接或路径 |
| pw | 否 | | 密码 |
| type | 否 | json | 返回的数据类型(可选值: json、xml、down) |
| page | 否 | 1 | 页数 |

# 返回

| 参数 | 说明 | 数据类型 |
| -- | -- | -- |
| code | 状态码 | int |
| msg | 信息 | String |
| data | 文件（夹）信息 | Object |

## 文件信息
| 参数 | 说明 | 数据类型 |
| -- | -- | -- |
| fid | 文件ID | int |
| name | 文件名 | String |
| size | 文件大小 | String |
| user | 分享者 | String |
| time | 上传时间 | String |
| desc | 描述 | String |
| icon | 文件图标 | String |
| avatar | 分享者头像 | String |
| url | 链接 | String |

## 文件夹信息
| 参数 | 说明 | 数据类型 |
| -- | -- | -- |
| fid | 文件夹ID | int |
| uid | 分享者用户ID | int |
| name | 文件夹名称 | String |
| desc | 文件夹描述 | String |
| folder | 子文件夹列表 | Array |
| list | 文件列表 | Array |
| have_page | 有无下一页 | Boolean |

### 子文件夹列表
| 参数 | 说明 | 数据类型 |
| -- | -- | -- |
| id | 分享路径 | String |
| name | 名称 | String |
| desc | 描述 | String |

### 文件列表
| 参数 | 说明 | 数据类型 |
| -- | -- | -- |
| id | 分享路径 | String |
| ad | 推广文件 | Boolean |
| name | 文件名 | String |
| size | 文件大小 | String |
| time | 上传时间 | String |
| icon | 文件图标 | String |

## 状态码说明
| 状态码 | 含义                 | 可能原因                               |
| --- | ------------------ | ---------------------------------- |
| 500 | 数据获取失败             | 网络问题或蓝奏云返回空响应                               |
| 501 | 数据无法解析             | 蓝奏云网页结构更新导致解析失败或网页返回错误信息（可能是文件被删除、文件违规被屏蔽、链接失效等原因）  |
| 502 | 获取链接失败             | 密码错误或脚本获取的参数错误                     |
| 200 | 成功                      |                                   |
| 201 | 获取文件链接失败，但获取下载链接成功 | 蓝奏云识别到爬虫返回假链接或者要求人机验证               |
| 400 | 缺少参数                 |                                   |
| 401 | 文件需要密码但客户端未提供     | 文件确实需要密码或脚本误认为文件需要密码（传入任意密码即可） |


# 其他说明
1. 遇到无法解析请附带无法解析的文件分享链接[提交issues](https://github.com/wzdc/lanzouyunapi/issues)
2. 文件夹查看多页内容需要等待一定时间才能查看更多页内容（官方限制）。对于页数较多的文件夹，缓存可以解决等待时间长的问题。

