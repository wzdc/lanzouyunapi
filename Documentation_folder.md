# 文件夹
| 参数 | 说明 | 数据类型 |
| -- | -- | -- |
| name | 文件夹名称 | string |
| desc | 文件夹描述 | string |
| folder | 子文件夹列表 |  |
| list | 文件列表 | |
| have_page | 有无下一页 | Boolean |

# 子文件夹列表
| 参数 | 说明 | 数据类型 |
| -- | -- | -- |
| id | 子文件夹ID | String |
| name | 子文件夹名称 | String |
| desc | 子文件夹描述 | String |

# 文件列表
| 参数 | 说明 | 数据类型 |
| -- | -- | -- |
| id | id | String |
| name | 文件名 | String |
| size | 文件大小 | String |
| time | 上传时间 | String |

# 其他说明
1. 文件夹查看多页内容需要等待一定时间才能查看更多页内容（官方限制）。对于页数较多的文件夹，缓存可以解决等待时间长的问题。
2. 文件夹不支持`types=text`
