<?php

return [
    'captcha_id' => [
        'title' => '极验 captcha_id',
        'type'  => 'text',
        'value' => '',
        'tip'   => '极验后台 - 无感验证 - 应用配置中的 captcha_id',
    ],
    'captcha_key' => [
        'title' => '极验 captcha_key',
        'type'  => 'text',
        'value' => '',
        'tip'   => '极验后台 - 无感验证 - 应用配置中的 captcha_key（请勿泄露）',
    ],
    'product' => [
        'title' => '展现形式',
        'type'  => 'select',
        'value' => 'float',
        'tip'   => 'float=浮动式 / popup=弹出式 / bind=隐藏式',
    ],
    'riskType' => [
        'title' => '验证类型',
        'type'  => 'select',
        'value' => 'slide',
    ],
    'replace_native_captcha' => [
        'title' => '替换原生验证码',
        'type'  => 'select',
        'value' => '1',
        'tip'   => '开启后前端用极验替换原生图形验证码；关闭则恢复显示原生验证码',
    ],
    'disable_native_captcha' => [
        'title' => '禁用原生验证码',
        'type'  => 'select',
        'value' => '0',
        'tip'   => '开启后，即使用户未完成极验也无法通过原生图形验证码校验，防止绕过极验',
    ],
];
