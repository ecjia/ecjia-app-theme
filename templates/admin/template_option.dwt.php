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
			

			
			<div class="control-group">
				<div class="controls">
					<input type="submit" value="确定" class="btn btn-gebo" />
				</div>
			</div>
		</fieldset>
	</form>
</div>
<!-- {/block} -->