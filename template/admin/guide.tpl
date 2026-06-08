<section class="admin-main">
  <div class="container-fluid">
    <div class="page-container">
      <div class="card">
        <div class="card-body">
          <h3 class="font-weight-bold">接入说明</h3>
          <h6 class="text-black-50">插件已通过 hook 全自动注入，无需修改任何模板文件</h6>

          <h5 class="mt-4">1. 在极验官网创建应用</h5>
          <p>在 <a href="https://www.geetest.com/" target="_blank">极验官网</a> 注册 → 业务管理 中创建「无感验证」应用，获取 <code>captcha_id</code> 和 <code>captcha_key</code>。</p>

          <h5 class="mt-4">2. 填写插件配置</h5>
          <p>在 <a href="{:shd_addon_url('Geetest://AdminIndex/setting')}">验证配置</a> 页面填入 captcha_id、captcha_key，选择展现形式，保存即可。</p>

          <h5 class="mt-4">3. 自动生效，无需改模板</h5>
          <p>插件通过 3 个 hook 全自动运行：</p>
          <ul>
            <li><code>client_area_head_output</code> → 自动注入极验 GT4 SDK</li>
            <li><code>client_area_footer_output</code> → 自动创建 <code>#geetest-box</code> 容器（插入到登录按钮前）、初始化验证码、自动填充表单隐藏字段</li>
            <li><code>custom_captcha_check</code> → 后端调用极验 API 做二次校验</li>
          </ul>

          <h5 class="mt-4">验证流程</h5>
          <ol>
            <li>用户打开登录页 → 自动加载极验 SDK → 自动渲染验证按钮</li>
            <li>用户完成验证 → JavaScript 自动把 <code>lot_number</code> <code>captcha_output</code> <code>pass_token</code> <code>gen_time</code> 填入表单</li>
            <li>用户提交登录 → <code>captcha_check()</code> → <code>customCaptchaCheck()</code> → 调用极验 /validate 二次校验</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
</section>
