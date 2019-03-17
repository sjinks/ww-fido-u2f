<?php
namespace WildWolf\U2F;

use u2flib_server\Registration;
use u2flib_server\U2F;

abstract class U2FUtils
{
	const USERMETA_OPTIONS_KEY = '_wwu2f_options';

	private static function appId() : string
	{
		$parts = \wp_parse_url(\home_url());
		if (!empty($parts['port'])) {
			return \sprintf('https://%s:%d', $parts['host'], $parts['port']);
		}

		return 'https://' . $parts['host'];
	}

	public static function enabledFor(\WP_User $user) : bool
	{
		$meta = \get_user_meta($user->ID, self::USERMETA_OPTIONS_KEY, true);
		return \is_array($meta) && !empty($meta['keys']) && \is_array($meta['keys']);
	}

	private static function dataFor(int $user_id) : array
	{
		$meta = \get_user_meta($user_id, self::USERMETA_OPTIONS_KEY, true);
		if (!\is_array($meta) || !isset($meta['keys'])) {
			return ['keys' => []];
		}

		if (!\is_array($meta['keys'])) {
			$meta['keys'] = [];
		}

		if (isset($meta['request']) && !\is_array($meta['request'])) {
			unset($meta['request']);
		}

		return $meta;
	}

	public static function keysFor(int $user_id) : array
	{
		$meta = self::dataFor($user_id);
		return $meta['keys'];
	}

	public static function getAuthDataFor(int $user_id) : array
	{
		$server = new U2F(self::appId());
		$meta   = self::dataFor($user_id);
		$data   = $server->getAuthenticateData($meta['keys']);

		$meta['request'] = $data;
		\update_user_meta($user_id, self::USERMETA_OPTIONS_KEY, $meta);
		return $data;
	}

	public static function getRegisterDataFor(int $user_id) : array
	{
		$server  = new U2F(self::appId());
		$keys    = self::keysFor($user_id);
		return $server->getRegisterData($keys);
	}

	public static function deleteKey(int $user_id, string $handle) : bool
	{
		$meta = self::dataFor($user_id);
		foreach ($meta['keys'] as $key => $val) {
			if (!\strcmp($val->keyHandle, $handle)) {
				unset($meta['keys'][$key]);
				break;
			}
		}

		$meta['keys'] = \array_values($meta['keys']);
		return \update_user_meta($user_id, self::USERMETA_OPTIONS_KEY, $meta) !== false;
	}

	private static function updateKey(int $user_id, array $meta, Registration $reg)
	{
		foreach ($meta['keys'] as $key => $val) {
			if (!\strcmp($val->keyHandle, $reg->keyHandle)) {
				$val->counter   = $reg->counter;
				$val->last_used = \time();
				$meta['keys'][$key] = $val;
				break;
			}
		}

		unset($meta['request']);
		\update_user_meta($user_id, self::USERMETA_OPTIONS_KEY, $meta);
	}

	private static function addKey(int $user_id, string $name, Registration $reg) : array
	{
		$key = (object)[
			'name'      => $name,
			'created'   => \time(),
			'last_used' => 0,
			'keyHandle' => $reg->keyHandle,
			'publicKey' => $reg->publicKey,
			'counter'   => $reg->counter,
		];

		$meta = self::dataFor($user_id);
		$meta['keys'][] = $key;
		\update_user_meta($user_id, self::USERMETA_OPTIONS_KEY, $meta);
		return [$key, $meta['keys']];
	}

	public static function authenticate(int $user_id, $response)
	{
		$meta = self::dataFor($user_id);
		$u2f  = new U2F(self::appId());
		$reg  = $u2f->doAuthenticate($meta['request'] ?? [], $meta['keys'], $response);
		self::updateKey($user_id, $meta, $reg);
	}

	public static function register($request, $response, int $user_id, string $name) : array
	{
		$u2f  = new U2F(self::appId());
		$reg  = $u2f->doRegister($request, $response, false);
		list($key, $keys) = self::addKey($user_id, $name, $reg);
		list($req, $sigs) = $u2f->getRegisterData($keys);
		return [$key, $req, $sigs];
	}
}
