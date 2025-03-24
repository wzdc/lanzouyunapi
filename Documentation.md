# 请求
请求类型：`GET/POST`

请求示例：https://vercel-chi-kohl.vercel.app/lanzouyunapi.php?url=wzdc
| 参数 | 必填 | 默认 | 说明 |
| -- | -- | -- | -- |
| url | 是 | | 蓝奏云链接或ID |
| pw | 否 | | 密码 |
| type | 否 | json | 返回的数据类型(可选值: text、json、xml、down) |
| page | 否 | 1 | 页数 |

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
| 参数 | 说明 | 数据类型 |
| -- | -- | -- |
| name | 文件夹名称 | string |
| desc | 文件夹描述 | string |
| folder | 子文件夹列表 |  |
| list | 文件列表 | |
| have_page | 有无下一页 | Boolean |

### 子文件夹列表
| 参数 | 说明 | 数据类型 |
| -- | -- | -- |
| id | 子文件夹ID | String |
| name | 子文件夹名称 | String |
| desc | 子文件夹描述 | String |

### 文件列表
| 参数 | 说明 | 数据类型 |
| -- | -- | -- |
| id | id | String |
| ad | 推广文件 | Boolean |
| name | 文件名 | String |
| size | 文件大小 | String |
| time | 上传时间 | String |
| icon | 文件图标 | String |

## 状态码说明
| code | msg | 说明 |
| -- | -- | -- |
| 2  | 请输入密码 | 检测到密码文件未输入密码 |
| 1  | 获取直链失败 | 已经成功获取链接，但获取直链失败 |
| 0  | 成功 | 获取成功 |
| -1 | （显示蓝奏云返回的错误信息） | 请求接口时，蓝奏云接口返回的错误信息 |
| -2 | （显示获取到的错误信息，如果没有则显示获取失败） | 获取网页内容时发生错误，会尝试再次获取蓝奏云提示的错误信息 |
| -3 | 获取失败 | HTML解析错误 |
| -4 | 缺少参数 | 缺少参数 |

# 其他说明
1. 遇到无法解析请附带无法解析的文件分享链接[提交issues](https://github.com/wzdc/lanzouyunapi/issues)
2. 文件夹查看多页内容需要等待一定时间才能查看更多页内容（官方限制）。对于页数较多的文件夹，缓存可以解决等待时间长的问题。
3. 文件夹不支持`types=text`
