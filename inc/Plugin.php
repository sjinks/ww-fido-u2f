<?php
namespace WildWolf\U2F;

final class Plugin
{
	public static function instance()
	{
		static $self = null;

		if (!$self) {
			$self = new self();
		}

		return $self;
	}

	private function __construct()
	{
		\load_plugin_textdomain('ww-u2f', /** @scrutinizer ignore-type */ false, \plugin_basename(\dirname(__DIR__)) . '/lang/');

		\add_action('init', [$this, 'init']);
	}

	public function init()
	{
		\add_action('login_init', [Login::class, 'instance']);

		if (\is_admin()) {
			Admin::instance();
		}
	}
}
