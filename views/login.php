<?php defined('ABSPATH') || die(); ?>
<?php
$title       = WildWolf\U2F\WPUtils::xlate('Log In');
$login_title = get_bloginfo('name', 'display');
$login_title = sprintf(WildWolf\U2F\WPUtils::xlate('%1$s &lsaquo; %2$s &#8212; WordPress'), $title, $login_title);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?><?php if ($params['interim_login']) : ?> class="interim-login"<?php endif; ?>>
<head>
<meta charset=<?php bloginfo('charset'); ?>"/>
<title><?=$login_title;?></title>
<meta name="viewport" content="width=device-width"/>
<?php wp_print_styles(); ?>
<?php wp_print_head_scripts(); ?>
<?php wp_site_icon(); ?>
</head>
<body class="login wp-core-ui">
	<div id="login-container">
		<div id="login">
			<div id="login_error" hidden="hidden"></div>
			<noscript>
				<p><?=__('Please enable JavaScript.', 'ww-u2f'); ?></p>
			</noscript>
			<form id="u2f-form" hidden="hidden">
				<div id="progressbar">
					<p id="interact" hidden="hidden">
						<?=__('Press the button on your security key to finish signing in. If it does not have a button, just re-insert it.', 'ww-u2f'); ?>
					</p>
					<div class="progressbar-container" role="progressbar">
						<svg viewBox="22 22 44 44">
							<circle cx="44" cy="44" r="20.2" fill="none" stroke-width="3.6"/>
						</svg>
					</div>
				</div>
				<p class="submit" hidden="hidden">
					<input type="button" class="button button-primary button-large" value="Retry"/>
					<input type="hidden" id="rememberme" value="<?=esc_attr($params['rememberme']);?>"/>
					<input type="hidden" id="redirect_to" value="<?=esc_attr($params['redirect_to']);?>"/>
					<input type="hidden" id="user_id" value="<?=esc_attr($params['user_id']);?>"/>
					<input type="hidden" id="interim_login" value="<?=esc_attr($params['interim_login']);?>"/>
				</p>
			</form>
		</div>
	</div>
<?php wp_print_footer_scripts(); ?>
</body>
</html>
