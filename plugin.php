<?php
/*
 * Plugin Name: WW FIDO U2F Authentication
 * Plugin URI:
 * Description: Provides support for the Universal Second Factor (FIDO U2F) authentication
 * Version: 1.0.0
 * Author: Volodymyr Kolesnykov
 * License: MIT
 * Text Domain: ww-u2f
 * Domain Path: /lang
 * Network:
 */

defined('ABSPATH') || die();

if (defined('VENDOR_PATH')) {
	require VENDOR_PATH . '/vendor/autoload.php';
}
elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require __DIR__ . '/vendor/autoload.php';
}
elseif (file_exists(ABSPATH . 'vendor/autoload.php')) {
	require ABSPATH . 'vendor/autoload.php';
}

WildWolf\WordPress\Autoloader::register();
WildWolf\U2F\Plugin::instance();
