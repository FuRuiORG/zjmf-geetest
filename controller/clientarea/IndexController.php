<?php
namespace addons\geetest\controller\clientarea;

class IndexController extends \app\home\controller\PluginHomeBaseController
{
    /**
     * 返回极验前端配置
     * GET /addons?_plugin=geetest&_controller=index&_action=config
     */
    public function config()
    {
        $plugin = new \addons\geetest\GeetestPlugin();
        $config = $plugin->getConfig();

        if (empty($config['captcha_id'])) {
            return json(['status' => 400, 'msg' => '极验验证码未配置']);
        }

        return json([
            'status' => 200,
            'msg'    => 'success',
            'data'   => [
                'captcha_id' => $config['captcha_id'],
                'product'    => $config['product'] ?: 'float',
                'riskType'   => $config['riskType'] ?: 'slide',
            ],
        ]);
    }
}
