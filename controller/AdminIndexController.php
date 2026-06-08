<?php
namespace addons\geetest\controller;

class AdminIndexController extends \app\admin\controller\PluginAdminBaseController
{
    public function setting()
    {
        $plugin = $this->getPlugin();
        $config = $plugin->getConfig();
        if (empty($config['product'])) $config['product'] = 'float';
        if (empty($config['captcha_id'])) $config['captcha_id'] = '';
        if (empty($config['riskType'])) $config['riskType'] = 'slide';

        if ($this->request->isPost()) {
            $param = $this->request->param();
            $data = [
                'captcha_id'  => $param['captcha_id'] ?? '',
                'captcha_key' => $param['captcha_key'] ?? '',
                'product'     => $param['product'] ?? 'float',
                'riskType'    => $param['riskType'] ?? 'slide',
                'replace_native_captcha' => $param['replace_native_captcha'] ?? '1',
                'disable_native_captcha' => $param['disable_native_captcha'] ?? '0',
            ];
            // captcha_key 为空时保留旧值，避免空值覆盖
            if (empty($data['captcha_key'])) {
                $data['captcha_key'] = $config['captcha_key'] ?? '';
            }
            \think\Db::name('plugin')->where('name', 'Geetest')->update(['config' => json_encode($data)]);
            $this->assign('SuccessMsg', '保存成功');
            $config = $data;
        }

        $this->assign('config', $config);

        $productOptions = [
            'float' => 'float - 浮动式（按钮悬浮在页面）',
            'popup' => 'popup - 弹出式（弹出验证窗口）',
            'bind'  => 'bind - 隐藏式（无按钮，调用 showCaptcha 触发）',
        ];
        $this->assign('productOptions', $productOptions);

        $riskTypeOptions = [
            'slide' => '滑动验证',
        ];
        $this->assign('riskTypeOptions', $riskTypeOptions);

        return $this->fetch('/setting');
    }

    public function guide()
    {
        $plugin = $this->getPlugin();
        $config = $plugin->getConfig();
        $this->assign('config', $config);
        return $this->fetch('/guide');
    }

    public function demo()
    {
        $plugin = $this->getPlugin();
        $config = $plugin->getConfig();
        if (empty($config['product'])) $config['product'] = 'float';
        if (empty($config['riskType'])) $config['riskType'] = 'slide';
        $this->assign('config', $config);
        return $this->fetch('/demo');
    }
}
