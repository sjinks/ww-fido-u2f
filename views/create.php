<?php defined('ABSPATH') || die(); ?>
<div class="wrap">
	<h1><?=__('FIDO U2F', 'ww-u2f'); ?></h1>

	<h2 id="registered-keys"><?=__('Registered Keys', 'ww-u2f'); ?></h2>
<?php
$table = new WildWolf\U2F\KeyTable(['screen' => 'u2f', 'user_id' => get_current_user_id()]);
$table->prepare_items();
$table->display();
?>

	<h2 class="hide-if-no-js" id="new-key"><?=__('Register a New Key', 'ww-u2f'); ?></h2>
	<form class="hide-if-no-js" id="new-key-form">
		<p id="hint" hidden="hidden">
			<?=__('Please insert your security key. If the key has a blinking light, press the button or gold disk.', 'ww-u2f'); ?>
			<br/>
			<img src="<?=esc_attr(WildWolf\U2F\WPUtils::assetsUrl('key.png')); ?>" alt=""/>
		</p>
		<label for="key-name">
			<?=__('Key Name', 'ww-u2f'); ?>
			<input type="text" id="key-name" required="required" name="key-name"/>
		</label>
		<input type="submit" class="button button-primary" value="<?=__('Register', 'ww-u2f'); ?>" id="submit-button"/>
		<span class="spinner" style="float: none"></span>
	</form>
</div>

<script type="text/x-template" id="tpl-empty">
	<tr class="no-items"><td class="colspanchange" colspan="5"><?php ob_start(); $table->no_items(); echo ob_get_clean(); ?></td></tr>
</script>
