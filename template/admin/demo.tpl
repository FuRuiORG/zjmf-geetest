{if $config.captcha_id eq ''}
<section class="admin-main">
  <div class="container-fluid">
    <div class="page-container">
      <div class="card">
        <div class="card-body" style="text-align:center;padding:60px;">
          <h5 class="text-black-50">请先在 <a href="{:shd_addon_url('Geetest://AdminIndex/setting')}">验证配置</a> 中填写 captcha_id 后，再进行 Demo 测试</h5>
        </div>
      </div>
    </div>
  </div>
</section>
{else}
<script src="https://static.geetest.com/v4/gt4.js"></script>
<section class="admin-main">
  <div class="container-fluid">
    <div class="page-container">
      <div class="card">
        <div class="card-body">
          <h3 class="font-weight-bold">Demo 测试</h3>
          <h6 class="text-black-50">当前: 展现形式={$config.product} / 验证类型={$config.riskType}，下方可实时测试验证码是否正常</h6>

          <div class="mt-3 p-3" style="background:#fafafa;border-radius:6px;">
            <div id="geetest-demo-box" style="margin-bottom:15px;"></div>

            <div id="geetest-demo-result" style="margin-top:10px;display:none;">
              <div class="alert alert-success">
                <strong>验证通过!</strong> getValidate() 返回值:
                <pre id="geetest-demo-data" style="margin-top:8px;background:#fff;padding:8px;"></pre>
              </div>
            </div>

            <div id="geetest-demo-error" style="margin-top:10px;display:none;">
              <div class="alert alert-danger" id="geetest-demo-error-msg"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
(function() {
  var product = '{$config.product}';
  var captchaId = '{$config.captcha_id}';
  var riskType = '{$config.riskType}';

  console.log('[极验 Demo] 开始初始化, captcha_id=' + captchaId + ', product=' + product + ', riskType=' + riskType);

  initGeetest4({
    captchaId: captchaId,
    product: product,
    riskType: riskType
  }, function(captchaObj) {

    if (product === 'bind') {
      console.log('[极验 Demo] bind 隐藏模式已就绪，点击下方按钮触发');

      var btn = document.createElement('button');
      btn.className = 'btn btn-primary';
      btn.textContent = '点击触发验证';
      btn.onclick = function() {
        console.log('[极验 Demo] bind showCaptcha() 调用');
        captchaObj.showCaptcha();
      };
      document.getElementById('geetest-demo-box').appendChild(btn);

      captchaObj.onReady(function() {
        console.log('[极验 Demo] bind 验证码 ready');
      });
      captchaObj.onSuccess(function() {
        console.log('[极验 Demo] bind 验证通过');
        showResult(captchaObj);
      });
      captchaObj.onError(function(err) {
        console.error('[极验 Demo] bind 错误:', err);
        showError(err);
      });
      captchaObj.onClose(function() {
        console.log('[极验 Demo] bind 用户关闭了验证窗口');
      });
      captchaObj.onFail(function(failObj) {
        console.log('[极验 Demo] bind 验证失败:', failObj);
      });
    } else {
      captchaObj.appendTo('#geetest-demo-box');
      console.log('[极验 Demo] ' + product + ' 模式按钮已渲染到页面');

      captchaObj.onSuccess(function() {
        console.log('[极验 Demo] ' + product + ' 验证通过');
        showResult(captchaObj);
      });
      captchaObj.onError(function(err) {
        console.error('[极验 Demo] ' + product + ' 错误:', err);
        showError(err);
      });
      captchaObj.onFail(function(failObj) {
        console.log('[极验 Demo] ' + product + ' 验证失败:', failObj);
      });
    }

    captchaObj.onReady(function() {
      console.log('[极验 Demo] ' + product + ' 验证码 ready');
    });
  });

  function showResult(captchaObj) {
    var v = captchaObj.getValidate();
    if (v) {
      document.getElementById('geetest-demo-data').textContent = JSON.stringify(v, null, 2);
      document.getElementById('geetest-demo-result').style.display = 'block';
      document.getElementById('geetest-demo-error').style.display = 'none';
      console.log('[极验 Demo] getValidate() 返回数据:', v);
    }
  }

  function showError(err) {
    var msg = err.msg || err;
    document.getElementById('geetest-demo-error-msg').textContent = '错误: ' + msg;
    document.getElementById('geetest-demo-error').style.display = 'block';
    document.getElementById('geetest-demo-result').style.display = 'none';
  }
})();
</script>
{/if}
