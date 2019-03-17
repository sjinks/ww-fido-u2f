<?php
namespace WildWolf\U2F;

abstract class WPUtils
{
	public static function render(string $view, array $params = [])
	{
		require __DIR__ . '/../views/' . $view . '.php';
	}

	public static function assetsUrl(string $file) : string
	{
		return \plugins_url('assets/' . $file, \dirname(__DIR__) . '/plugin.php');
	}

	public static function doingAJAX() : bool
	{
		return \defined('\\DOING_AJAX') && \DOING_AJAX;
	}

	public static function xlate(string $s) : string
	{
		return \call_user_func('\\_', $s);
	}
}
