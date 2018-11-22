<?php defined('IN_ECJIA') or exit('No permission resources.');?>
<!-- {extends file="admin_shop_config.dwt.php"} -->

<!-- {block name="footer"} -->
<script type="text/javascript">
	ecjia.admin.printer.init();
</script>
<!-- {/block} -->

<!-- {block name="admin_shop_config_nav"} -->
<!-- {ecjia:hook id=admin_theme_option_nav arg=$current_code} -->
<!-- {/block} -->

<!-- {block name="admin_config_form"} -->
<div class="row-fluid">
	<form method="post" class="form-horizontal" action="{$form_action}" name="theForm" >
		<fieldset>
			<div>
				<h3 class="heading">
					<!-- {if $ur_here}{$ur_here}{/if} -->
				</h3>
			</div>
			
			<div class="control-group formSep">
				<label class="control-label">App Key：</label>
				<div class="controls">
					<input type="text" class="span7" name="app_key" value="{$printer_key}"/>
					<span class="input-must"><span class="require-field">*</span></span>
				</div>
			</div>
			
			<div class="control-group formSep">
				<label class="control-label">App Secret：</label>
				<div class="controls">
					<input type="text" class="span7" name="app_secret" value="{$printer_secret}"/>
					<span class="input-must"><span class="require-field">*</span></span>
				</div>
			</div>
			
			<div class="control-group formSep">
				<label class="control-label">是否打印平台名称：</label>
				<div class="controls">
					<div class="toggle-printer-button">
		                <input class="nouniform" name="printer_display_platform" type="checkbox" {if $printer_display_platform eq 1}checked{/if} value="1"/>
		            </div>
		            <span class="help-block">此按钮开启之后，在小票打印时，尾部会打印出平台的名称。</span>
				</div>
			</div>
			
			{if $printer_key && $printer_secret}
			<div class="control-group formSep">
				<label class="control-label">打印完成状态推送地址：</label>
				<div class="controls">
					<input type="text" class="span7" name="printer_print_push" value="{$printer_print_push}"/>
				</div>
			</div>
			
			<div class="control-group formSep">
				<label class="control-label">终端状态推送地址：</label>
				<div class="controls">
					<input type="text" class="span7" name="printer_status_push" value="{$printer_status_push}"/>
				</div>
			</div>
			
			<div class="control-group formSep">
				<label class="control-label">接单拒单推送地址：</label>
				<div class="controls">
					<input type="text" class="span7" name="printer_order_push" value="{$printer_order_push}"/>
				</div>
			</div>
			{/if}
			
			<div class="control-group">
				<div class="controls">
					<input type="submit" value="确定" class="btn btn-gebo" />
				</div>
			</div>
		</fieldset>
	</form>
</div>
<!-- {/block} -->