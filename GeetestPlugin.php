<?php
namespace addons\geetest;

class GeetestPlugin extends \app\admin\lib\Plugin
{
    public $info = [
        'name'        => 'Geetest',
        'title'       => '极验验证码',
        'description' => '极验 v4 人机验证码，通过 custom_captcha_check hook 拦截系统验证码校验',
        'status'      => 1,
        'author'      => 'RuiNexus',
        'version'     => '1.2.1',
    ];

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }

    private function isAdminPath()
    {
        $adminApplication = trim(config('database.admin_application') ?: 'admin', '/');
        if ($adminApplication === '') return false;

        $path = parse_url(request()->url(), PHP_URL_PATH);
        $path = '/' . ltrim($path ?: '', '/');
        $adminPrefix = '/' . $adminApplication;

        return strcasecmp($path, $adminPrefix) === 0 || stripos($path, $adminPrefix . '/') === 0;
    }

    /**
     * client_area_head_output hook
     * 自动在前台页面 <head> 中注入极验 v4 SDK
     */
    public function clientAreaHeadOutput()
    {
        if ($this->isAdminPath()) return '';
        if (!configuration('is_captcha')) return '';
        $config = $this->getConfig();
        if (empty($config['captcha_id'])) return '';
        if (empty($config['replace_native_captcha'])) return '';

        return '<script src="https://static.geetest.com/v4/gt4.js"></script>';
    }

    /**
     * client_area_footer_output hook
     * 自动在前台页面底部注入极验初始化脚本
     * 自动创建 #geetest-box 容器并初始化，无需手动修改模板
     */
    public function clientAreaFooterOutput()
    {
        if ($this->isAdminPath()) return '';
        if (!configuration('is_captcha')) return '';
        $config = $this->getConfig();
        if (empty($config['captcha_id'])) return '';
        if (empty($config['replace_native_captcha'])) return '';

        $product  = $config['product'] ?: 'float';
        $riskType = $config['riskType'] ?: 'slide';
        $captchaId = $config['captcha_id'];

        $productJson  = json_encode($product);
        $captchaIdJson = json_encode($captchaId);
        $riskTypeJson = json_encode($riskType);

        $js = <<<JS
<script>
(function(){
  var product = {$productJson};
  var captchaId = {$captchaIdJson};
  var riskType = {$riskTypeJson};

  var box = document.getElementById('geetest-box');
  if (!box) {
    box = document.createElement('div');
    box.id = 'geetest-box';
    box.style.minWidth = '300px';
    box.style.minHeight = '40px';
  }

  /* 包装器：在模态框中让极验框与表单网格对齐 */
  var wrapper = document.getElementById('geetest-wrapper');
  if (!wrapper) {
    wrapper = document.createElement('div');
    wrapper.id = 'geetest-wrapper';
    wrapper.className = 'form-group row';
    var labelCol = document.createElement('label');
    labelCol.className = 'col-sm-3 col-form-label text-right';
    wrapper.appendChild(labelCol);
    var inputCol = document.createElement('div');
    inputCol.className = 'col-sm-8';
    inputCol.appendChild(box);
    wrapper.appendChild(inputCol);
  }

  var captchaObj = null;
  var activeForm = null;
  var lastValidateResult = null;
  /* popup/bind 模式：记录被拦截的操作，验证通过后重新触发 */
  var pendingAction = null;

  function isVisible(el) {
    if (!el) return false;
    var style = window.getComputedStyle(el);
    var rect = el.getBoundingClientRect();
    return style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0;
  }

  /* 获取所有含验证码的表单（登录 + 模态框中的表单） */
  function getCaptchaForms() {
    var forms = document.querySelectorAll('form');
    var list = [];
    for (var i = 0; i < forms.length; i++) {
      if (forms[i].querySelector('input[name="password"], input[type="password"]') ||
          forms[i].querySelector('input[name="code"]') ||
          forms[i].querySelector('input[name="captcha"]')) {
        list.push(forms[i]);
      }
    }
    return list;
  }

  /* 获取当前可见的表单（包括模态框中的） */
  function getActiveForm() {
    /* 优先找可见模态框中的表单 */
    var modals = document.querySelectorAll('.modal');
    for (var m = 0; m < modals.length; m++) {
      if (modals[m].classList.contains('show') || isVisible(modals[m])) {
        var form = modals[m].querySelector('form');
        if (form && (form.querySelector('input[name="captcha"]') || form.querySelector('input[name="code"]'))) {
          return form;
        }
      }
    }
    /* 其次找页面上的可见表单 */
    var forms = getCaptchaForms();
    for (var i = 0; i < forms.length; i++) {
      if (isVisible(forms[i])) return forms[i];
    }
    return forms[0] || null;
  }

  /* 隐藏所有原生图形验证码行 */
  function hideAllNativeCaptchas() {
    var captchaInputs = document.querySelectorAll('input[name="captcha"]');
    for (var i = 0; i < captchaInputs.length; i++) {
      var row = captchaInputs[i].closest('.form-group');
      if (row) row.style.display = 'none';
    }
  }

  /* 判断当前是否为手机验证码登录模式 */
  function isCodeLoginMode(form) {
    if (!form) return false;
    var pwd = form.querySelector('input[name="password"], input[type="password"]');
    if (pwd && pwd.disabled) return true;
    var action = form.getAttribute('action') || '';
    if (action.indexOf('action=phone_code') !== -1) return true;
    return false;
  }

  /* 将极验框定位到指定表单中 */
  function positionBox(form) {
    if (!form) return;
    activeForm = form;
    hideAllNativeCaptchas();

    /* bind 模式不需要在页面预留容器位置 */
    if (product === 'bind') {
      /* 确保容器不在页面中占用空间 */
      if (box.parentNode) {
        var placeholder = document.getElementById('geetest-placeholder');
        if (!placeholder) {
          placeholder = document.createElement('div');
          placeholder.id = 'geetest-placeholder';
          placeholder.style.display = 'none';
          document.body.appendChild(placeholder);
        }
        placeholder.appendChild(box);
      }
      if (wrapper.parentNode) {
        var wp = document.getElementById('geetest-wrapper-placeholder');
        if (!wp) {
          wp = document.createElement('div');
          wp.id = 'geetest-wrapper-placeholder';
          wp.style.display = 'none';
          document.body.appendChild(wp);
        }
        wp.appendChild(wrapper);
      }
      return;
    }

    /* 以下 float / popup 模式需要在页面显示容器 */

    /* 用户中心模板：优先复用 #jiyan-* 占位 div */
    var jiyanDiv = form.querySelector('[id^="jiyan-"]');
    if (jiyanDiv) {
      if (box.parentNode !== jiyanDiv) {
        jiyanDiv.innerHTML = '';
        jiyanDiv.appendChild(box);
      }
      return;
    }

    /* 判断表单是否使用 Bootstrap 网格布局（模态框中的表单） */
    var isGridForm = !!form.querySelector('.form-group.row .col-sm-3');

    /* 插入的元素：网格布局用 wrapper，简单布局用裸 box */
    var el = isGridForm ? wrapper : box;

    /* 确保 box 在正确的父元素中 */
    if (isGridForm) {
      var col = wrapper.querySelector('.col-sm-8');
      if (box.parentNode !== col) col.appendChild(box);
    } else {
      /* 简单布局：如果 box 还在 wrapper 里，先取出来 */
      if (box.parentNode && box.parentNode !== document.body) {
        box.parentNode.removeChild(box);
      }
    }

    /* 模态框中的表单：放在原生验证码行位置（已隐藏） */
    var captchaRow = form.querySelector('input[name="captcha"]');
    if (captchaRow) {
      var captchaGroup = captchaRow.closest('.form-group');
      if (captchaGroup && captchaGroup.parentNode) {
        captchaGroup.parentNode.insertBefore(el, captchaGroup.nextSibling);
        return;
      }
    }
    /* 页面表单：放在密码输入框后面 */
    var pwdInp = form.querySelector('input[type="password"]');
    if (pwdInp) {
      var pwdGroup = pwdInp.closest('.form-group') || pwdInp.closest('div');
      if (pwdGroup && pwdGroup.parentNode) {
        pwdGroup.parentNode.insertBefore(el, pwdGroup.nextSibling);
        return;
      }
    }
    /* 兜底：放在表单末尾 */
    form.appendChild(el);
  }

  function syncGeetestBox() {
    var form = getActiveForm();
    if (form) positionBox(form);
  }

  /* 覆盖 phoneCheck，切换密码/验证码登录模式 */
  var originalPhoneCheck = window.phoneCheck;
  window.phoneCheck = function(button, phone) {
    if (originalPhoneCheck) {
      originalPhoneCheck.apply(this, arguments);
    }
    hideAllNativeCaptchas();
    syncGeetestBox();
  };

  function bindEvents() {
    /* 监听 tab 切换 */
    if (typeof jQuery !== 'undefined') {
      jQuery(document).on('shown.bs.tab', function(){ syncGeetestBox(); });
      /* 监听模态框打开，将极验框移入模态框 */
      jQuery(document).on('show.bs.modal', function(e){
        var modal = e.target;
        var form = modal.querySelector('form');
        if (form && (form.querySelector('input[name="captcha"]') || form.querySelector('input[name="code"]'))) {
          setTimeout(function(){
            positionBox(form);
            hideAllNativeCaptchas();
          }, 50);
        }
      });
      /* 模态框关闭后，将极验框移回页面表单 */
      jQuery(document).on('hidden.bs.modal', function(){
        syncGeetestBox();
      });
    }
    var triggers = document.querySelectorAll('#tab-phone, #tab-email, [data-toggle="tab"], [data-bs-toggle="tab"], [role="tab"]');
    for (var i = 0; i < triggers.length; i++) {
      triggers[i].addEventListener('click', function(){ syncGeetestBox(); });
    }
  }

  hideAllNativeCaptchas();
  syncGeetestBox();
  bindEvents();

  initGeetest4({captchaId:captchaId,product:product,riskType:riskType},function(c){
    captchaObj = c;

    if (product === 'bind') {
      c.onReady(function(){
        syncGeetestBox();
      });
      c.onSuccess(function(){
        var v = c.getValidate();
        if (v) {
          lastValidateResult = v;
          addGeetestInputsToAllForms(v);
          /* 记录被拦截操作类型：按钮点击只执行 AJAX 不提交表单，表单提交才需提交 */
          var isPendingBtn = pendingAction && (pendingAction.tagName === 'BUTTON' || pendingAction.tagName === 'A');
          /* 触发被拦截的操作 */
          triggerPendingAction();
          if (!isPendingBtn && activeForm) {
            submitActiveForm();
          }
        }
      });
    } else {
      c.appendTo('#geetest-box');
      c.onReady(function(){
        syncGeetestBox();
      });
      c.onSuccess(function(){
        var v = c.getValidate();
        if (v) {
          lastValidateResult = v;
          addGeetestInputsToAllForms(v);
          /* 触发被拦截的操作（popup 模式下点击/提交会被拦截） */
          triggerPendingAction();
        }
      });
    }
    c.onError(function(err){});
  });

  function showError(msg) {
    if (window.toastr && typeof toastr.error === 'function') {
      toastr.error(msg);
      return;
    }
    var form = activeForm || getActiveForm();
    var target = form && form.querySelector('.invalid-feedback, .help-block, .text-danger');
    if (target) {
      target.innerHTML = msg;
      target.style.display = 'block';
      return;
    }
    alert(msg);
  }

  function addHiddenInput(form, name, value) {
    var old = form.querySelector('input[name="' + name + '"]');
    if (old) old.parentNode.removeChild(old);
    var inp = document.createElement('input');
    inp.type = 'hidden';
    inp.name = name;
    inp.value = value;
    form.appendChild(inp);
  }

  /* 极验通过后，将极验参数作为隐藏 input 添加到所有含验证码的表单 */
  function addGeetestInputsToAllForms(v) {
    var forms = getCaptchaForms();
    for (var i = 0; i < forms.length; i++) {
      addHiddenInput(forms[i], 'lot_number', v.lot_number);
      addHiddenInput(forms[i], 'captcha_output', v.captcha_output);
      addHiddenInput(forms[i], 'pass_token', v.pass_token);
      addHiddenInput(forms[i], 'gen_time', v.gen_time);
    }
  }

  /* 移除所有表单中的极验隐藏 input */
  function removeGeetestInputsFromAllForms() {
    var fields = ['lot_number', 'captcha_output', 'pass_token', 'gen_time'];
    var forms = getCaptchaForms();
    for (var i = 0; i < forms.length; i++) {
      for (var j = 0; j < fields.length; j++) {
        var old = forms[i].querySelector('input[name="' + fields[j] + '"]');
        if (old) old.parentNode.removeChild(old);
      }
    }
  }

  /* bind 模式下极验通过后自动提交当前活跃表单 */
  function submitActiveForm() {
    var form = activeForm || getActiveForm();
    if (!form) return;
    var pwd = form.querySelector('input[name="password"], input[type="password"]');
    if (pwd && !pwd.disabled && typeof window.encrypt === 'function') {
      pwd.value = window.encrypt(pwd.value);
    }
    form.onsubmit = null;
    form.submit();
  }

  /* popup/bind 模式：验证通过后重新触发被拦截的操作（按钮点击或表单提交） */
  function triggerPendingAction() {
    if (!pendingAction) return;
    var action = pendingAction;
    pendingAction = null;
    if (action.tagName === 'BUTTON' || action.tagName === 'A') {
      /* 按钮：直接执行 onclick 函数，避免 .click() 触发表单提交 */
      if (action.onclick) {
        action.onclick.call(action);
      }
    } else if (action.tagName === 'FORM' || action.submit) {
      /* 表单：直接提交 */
      action.onsubmit = null;
      action.submit();
    }
  }

  /* 拦截"获取验证码"按钮点击（覆盖 getCode 和 getCheckCode），要求先完成极验验证 */
  document.addEventListener('click', function(e) {
    var btn = e.target.closest('button[onclick*="getCode"], button[onclick*="getCheckCode"]');
    if (!btn || !captchaObj) return;

    if (!lastValidateResult) {
      e.preventDefault();
      e.stopImmediatePropagation();
      /* popup/bind 模式：记录按钮，验证通过后自动重新点击 */
      if (product === 'bind' || product === 'popup') {
        pendingAction = btn;
        captchaObj.showCaptcha();
        return;
      }
      showError('请先完成人机验证');
      return;
    }
    /* 极验已通过，放行点击，$.ajaxPrefilter 会自动附加极验参数 */
  }, true);

  /* 验证码发送相关的 AJAX URL 列表 */
  var captchaAjaxUrls = ['_send', 'bind_phone', 'bind_email', 'change_email', 'remind_send', 'bind_phone_code', 'second_verify_send'];

  function isCaptchaAjaxUrl(url) {
    if (!url) return false;
    for (var i = 0; i < captchaAjaxUrls.length; i++) {
      if (url.indexOf(captchaAjaxUrls[i]) !== -1) return true;
    }
    return false;
  }

  if (typeof jQuery !== 'undefined') {
    /* 给验证码发送 AJAX 请求附加极验参数 */
    jQuery.ajaxPrefilter(function(options, originalOptions) {
      if (isCaptchaAjaxUrl(options.url) && lastValidateResult) {
        if (typeof options.data === 'object' && options.data !== null) {
          options.data.lot_number = lastValidateResult.lot_number;
          options.data.captcha_output = lastValidateResult.captcha_output;
          options.data.pass_token = lastValidateResult.pass_token;
          options.data.gen_time = lastValidateResult.gen_time;
        } else if (typeof options.data === 'string') {
          options.data += '&lot_number=' + encodeURIComponent(lastValidateResult.lot_number);
          options.data += '&captcha_output=' + encodeURIComponent(lastValidateResult.captcha_output);
          options.data += '&pass_token=' + encodeURIComponent(lastValidateResult.pass_token);
          options.data += '&gen_time=' + encodeURIComponent(lastValidateResult.gen_time);
        }
      }
    });

    /* 获取验证码成功后重置极验，防止一次性验证被重复使用 */
    jQuery(document).ajaxSuccess(function(event, xhr, settings) {
      if (isCaptchaAjaxUrl(settings.url) && captchaObj && captchaObj.reset) {
        try {
          var res = typeof xhr.responseJSON !== 'undefined' ? xhr.responseJSON : JSON.parse(xhr.responseText);
          if (res.status === 200) {
            captchaObj.reset();
            lastValidateResult = null;
            removeGeetestInputsFromAllForms();
          }
        } catch(e) {}
      }
    });
  }

  /* 拦截表单提交：未完成极验则阻止，已完成则放行原生提交 */
  var forms = getCaptchaForms();
  for (var i = 0; i < forms.length; i++) {
    forms[i].addEventListener('submit', function(e) {
      var hasPasswordField = this.querySelector('input[name="password"], input[type="password"]');
      if (!hasPasswordField) return;

      activeForm = this;
      syncGeetestBox();

      /* 验证码登录模式：修改 action 为 phone_code，直接放行 */
      if (isCodeLoginMode(this)) {
        var action = this.getAttribute('action') || '';
        if (action.indexOf('phone_code') === -1) {
          this.setAttribute('action', action.replace('action=phone', 'action=phone_code'));
        }
        return;
      }

      /* 密码登录/找回密码模式：检查极验是否已完成 */
      if (!lastValidateResult) {
        e.preventDefault();
        e.stopImmediatePropagation();
        /* popup/bind 模式：记录表单，验证通过后自动重新提交 */
        if ((product === 'bind' || product === 'popup') && captchaObj) {
          pendingAction = this;
          captchaObj.showCaptcha();
          return;
        }
        showError('请先完成人机验证');
        return;
      }

      /* 极验已通过，隐藏 input 已在 onSuccess 中添加，放行原生提交 */
    }, true);
  }

})();
</script>
JS;
        return $js;
    }

    /**
     * custom_captcha_check hook
     * 系统 captcha_check() 函数在验证时会触发此 hook
     */
    public function customCaptchaCheck($param)
    {
        if ($this->isAdminPath()) return null;

        $request = request()->param();
        $config = $this->getConfig();

        // 禁用原生验证码：无论是否替换前端，后端都拒绝原生验证码通过
        if (!empty($config['disable_native_captcha'])) {
            // 有极验参数 → 走极验校验
            if (!empty($request['lot_number']) && !empty($request['captcha_output']) &&
                !empty($request['pass_token']) && !empty($request['gen_time'])) {
                if (empty($config['captcha_id']) || empty($config['captcha_key'])) {
                    throw new \think\exception\HttpResponseException(\think\Response::create([
                        'status' => 400,
                        'msg'    => '图形验证码已被禁用，请完成人机验证',
                    ], 'json'));
                }
                $result = $this->geetestValidate(
                    $config['captcha_id'],
                    $config['captcha_key'],
                    $request['lot_number'],
                    $request['captcha_output'],
                    $request['pass_token'],
                    $request['gen_time']
                );
                return $result['status'] === 'success' ? true : false;
            }
            // 无极验参数 → 直接拒绝并提示
            throw new \think\exception\HttpResponseException(\think\Response::create([
                'status' => 400,
                'msg'    => '图形验证码已被禁用，请完成人机验证',
            ], 'json'));
        }

        // 未开启替换原生验证码 → 不拦截，让系统走原生验证码
        if (empty($config['replace_native_captcha'])) {
            return null;
        }

        // 以下为正常替换模式（replace_native_captcha=1, disable_native_captcha=0）
        if (empty($request['lot_number']) || empty($request['captcha_output']) ||
            empty($request['pass_token']) || empty($request['gen_time'])) {
            return null;
        }

        if (empty($config['captcha_id']) || empty($config['captcha_key'])) {
            return null;
        }

        $result = $this->geetestValidate(
            $config['captcha_id'],
            $config['captcha_key'],
            $request['lot_number'],
            $request['captcha_output'],
            $request['pass_token'],
            $request['gen_time']
        );

        return $result['status'] === 'success' ? true : false;
    }

    public function geetestValidate($captchaId, $captchaKey, $lotNumber, $captchaOutput, $passToken, $genTime)
    {
        $signToken = hash_hmac('sha256', $lotNumber, $captchaKey);

        $postData = [
            'lot_number'     => $lotNumber,
            'captcha_output' => $captchaOutput,
            'pass_token'     => $passToken,
            'gen_time'       => $genTime,
            'captcha_id'     => $captchaId,
            'sign_token'     => $signToken,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://gcaptcha4.geetest.com/validate');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if (!empty($error)) {
            return ['status' => 'error', 'msg' => $error];
        }

        return json_decode($response, true) ?: ['status' => 'error', 'msg' => '极验接口返回异常'];
    }
}
