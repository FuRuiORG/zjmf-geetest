# zjmf-geetest

用极验v4替代魔方财务原生图形验证码的插件，通过hook拦截，不修改模板与控制器。

## 安装

1. 后台开启图形验证码。
2. 在极验控制台创建应用，获取`captcha_id`与`captcha_key`。
3. 把`geetest`目录放到：

```
public/plugins/addons/geetest/
```

4. 后台插件管理安装「极验验证码」。
5. 在插件配置页填写密钥，按需选择展现形式与验证类型。

## 配置

| 配置项 | 说明 |
| --- | --- |
| `captcha_id` | 极验应用ID |
| `captcha_key` | 极验密钥，留空不覆盖原值 |
| 展现形式 | `float`、`popup`、`bind` |
| 替换原生验证码 | 关闭后恢复原生图形验证码 |
| 禁用原生验证码 | 开启后强制要求极验参数 |

生产环境建议同时开启「替换原生验证码」与「禁用原生验证码」。

## 支持场景

登录、注册、找回密码、绑定手机或邮箱、修改密码、注销账号，以及获取短信或邮件验证码。

## Hook

| Hook | 作用 |
| --- | --- |
| `client_area_head_output` | 注入极验SDK |
| `client_area_footer_output` | 注入初始化脚本 |
| `custom_captcha_check` | 服务端二次校验 |

## 说明

`25YTheme`模式可能存在兼容问题。

## License

MIT，见`LICENSE`。
