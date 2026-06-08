
{if $ErrorMsg}
    {include file="error/alert" value="$ErrorMsg"}
{/if}

{if $SuccessMsg}
    {include file="error/notifications" value="$SuccessMsg" url=""}
{/if}

<style type="text/css">
.biaoti {
  display: inline-block;
  width: 120px;
  text-align: right;
  margin-right: 15px;
}
</style>

<form method="post" action="{:shd_addon_url('Geetest://AdminIndex/setting')}" class="needs-validation" novalidate>
  <section class="admin-main">
    <div class="container-fluid">
      <div class="page-container">
        <div class="card">
          <div class="card-body">
            <h3 class="font-weight-bold">极验验证码配置</h3>
            <h6 class="text-black-50">在这里可以配置极验 v4 验证码</h6>

            <ul class="rs mt-2">

              <li class="row my-3">
                <span class="col-md-8 col-xs-12">
                  <span class="biaoti">展现形式</span>
                  <select class="form-control" style="display:inline-block;width:60%;" name="product">
                    {foreach $productOptions as $k => $v}
                    <option value="{$k}" {if $config.product eq $k}selected{/if}>{$v}</option>
                    {/foreach}
                  </select>
                  <br><small class="text-black-50" style="margin-left:138px;">float=浮动式 / popup=弹出式 / bind=隐藏式</small>
                </span>
              </li>

              <li class="row my-3">
                <span class="col-md-8 col-xs-12">
                  <span class="biaoti">验证类型</span>
                  <select class="form-control" style="display:inline-block;width:60%;" name="riskType">
                    {foreach $riskTypeOptions as $k => $v}
                    <option value="{$k}" {if $config.riskType eq $k}selected{/if}>{$v}</option>
                    {/foreach}
                  </select>
                  <br><small class="text-black-50" style="margin-left:138px;">选择验证挑战类型，比如滑动验证、九宫格、一键通过等</small>
                </span>
              </li>

              <li class="row my-3">
                <span class="col-md-8 col-xs-12">
                  <span class="biaoti">替换原生验证码</span>
                  <select class="form-control" style="display:inline-block;width:60%;" name="replace_native_captcha">
                    <option value="1" {if $config.replace_native_captcha eq '1' || !$config.replace_native_captcha}selected{/if}>开启 - 用极验替换原生图形验证码</option>
                    <option value="0" {if $config.replace_native_captcha eq '0'}selected{/if}>关闭 - 恢复显示原生图形验证码</option>
                  </select>
                  <br><small class="text-black-50" style="margin-left:138px;">关闭后前端恢复原生验证码，极验不注入；可临时关闭用于测试</small>
                </span>
              </li>

              <li class="row my-3">
                <span class="col-md-8 col-xs-12">
                  <span class="biaoti">禁用原生验证码</span>
                  <select class="form-control" style="display:inline-block;width:60%;" name="disable_native_captcha">
                    <option value="0" {if $config.disable_native_captcha eq '0' || !$config.disable_native_captcha}selected{/if}>关闭 - 未完成极验时可使用原生图形验证码</option>
                    <option value="1" {if $config.disable_native_captcha eq '1'}selected{/if}>开启 - 必须完成极验，禁用原生图形验证码</option>
                  </select>
                  <br><small class="text-black-50" style="margin-left:138px;">开启后即使用户绕过前端直接提交也无法通过原生验证码校验</small>
                </span>
              </li>

              <li class="row my-3">
                <span class="col-md-8 col-xs-12">
                  <span class="biaoti">25YTheme 兼容</span>
                  <select class="form-control" style="display:inline-block;width:60%;" name="enable_25y_theme">
                    <option value="0" {if $config.enable_25y_theme eq '0' || !$config.enable_25y_theme}selected{/if}>关闭 - 使用插件前端接管验证码</option>
                    <option value="1" {if $config.enable_25y_theme eq '1'}selected{/if}>开启 - 兼容模板自带极验字段</option>
                  </select>
                  <br><small class="text-black-50" style="margin-left:138px;">开启后请先在系统设置中关闭图形验证码，插件不接管模板前端，仅在后端识别 geetest_* 字段并二次校验；极验配置需在模板独立后台中设置，并确保插件后台的 captcha_id 和 captcha_key 与模板一致</small>
                </span>
              </li>

              <li class="row my-3">
                <span class="col-md-8 col-xs-12">
                  <span class="biaoti">captcha_id</span>
                  <input class="form-control" style="display:inline-block;width:60%;"
                         type="text" name="captcha_id"
                         value="{$config.captcha_id}"
                         placeholder="极验后台 - 业务管理 中的 captcha_id" />
                  <br><small class="text-black-50" style="margin-left:138px;">极验后台 &gt; 业务管理 中获取</small>
                </span>
              </li>
              <li class="row my-3">
                <span class="col-md-8 col-xs-12">
                  <span class="biaoti">captcha_key</span>
                  <input class="form-control" style="display:inline-block;width:60%;"
                         type="password" name="captcha_key" value=""
                         placeholder="不修改请留空，将保留原有密钥" />
                  <br><small class="text-black-50" style="margin-left:138px;">密钥，请勿泄露；不修改时留空即可</small>
                </span>
              </li>
            </ul>

            <div class="form-group row">
              <div class="col-sm-10">
                <button type="submit" class="btn btn-primary w-md">保存更改</button>
                <button type="button" class="btn btn-outline-secondary w-md" onclick="javascript:location.reload();">取消更改</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</form>
