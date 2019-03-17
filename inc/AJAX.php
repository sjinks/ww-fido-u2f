<?php
namespace WildWolf\U2F;

final class AJAX
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
		$this->admin_init();
	}

	public function admin_init()
	{
		\add_action('wp_ajax_wwu2f_register',    [$this, 'wp_ajax_wwu2f_register']);
		\add_action('wp_ajax_wwu2f_sign',        [$this, 'wp_ajax_wwu2f_sign']);
		\add_action('wp_ajax_nopriv_wwu2f_sign', [$this, 'wp_ajax_wwu2f_sign']);
		\add_action('wp_ajax_wwu2f_revoke',      [$this, 'wp_ajax_wwu2f_revoke']);
	}

	public function wp_ajax_wwu2f_register()
	{
		\header('Content-Type: application/json; charset=' . \get_bloginfo('charset'));

		$data    = $_POST['data'] ?? null;
		$name    = $_POST['name'] ?? null;
		$user_id = \get_current_user_id();

		if ($data === null || $name === null) {
			\wp_die(\json_encode(['ok' => false, 'message' => \__('Required parameter missing', 'ww-u2f')]));
		}

		$response = \json_decode(\stripslashes($data));

		$manager = \WP_Session_Tokens::get_instance($user_id);
		$token   = \wp_get_session_token();
		$session = $manager->get($token);
		\assert(\is_array($session));
		$request = (object)($session['u2f_register_request'] ?? []);
		unset($session['u2f_register_request']);
		$manager->update($token, $session);

		try {
			list($key, $req, $sigs) = U2FUtils::register($request, $response, $user_id, $name);

			$table = new KeyTable(['screen' => 'u2f', 'user_id' => $user_id]);
			\ob_start();
			$table->single_row($key);
			$row = \ob_get_clean();

			$session['u2f_register_request'] = (array)$req;
			$manager->update($token, $session);

			\wp_die(\json_encode([
				'ok'      => true,
				'message' => \__('Your key has been successfully registered.', 'ww-u2f'),
				'request' => $req,
				'sigs'    => $sigs,
				'row'     => $row,
			]));
		}
		catch (\Throwable $e) {
			\wp_die(\json_encode(['ok' => false, 'message' => $e->getMessage()]));
		}
	}

	public function wp_ajax_wwu2f_sign()
	{
		\header('Content-Type: application/json; charset=' . \get_bloginfo('charset'));

		$data          = $_POST['data']    ?? null;
		$user_id       = $_POST['user_id'] ?? 0;
		$rememberme    = $_POST['rememberme'] ?? 0;
		$interim_login = $_POST['interim_login'] ?? 0;
		$redirect_to   = $_POST['redirect_to'] ?? '';

		if (empty($data) || empty($user_id)) {
			\wp_die(\json_encode(['ok' => false, 'message' => \__('Required parameter missing', 'ww-u2f')]));
		}

		$response = \json_decode(\stripslashes($data));
		try {
			U2FUtils::authenticate($user_id, $response);

			\wp_set_auth_cookie($user_id, (bool)$rememberme);
			if ($interim_login) {
				\wp_die(\json_encode(['ok' => true, 'message' => \__('You have logged in successfully.', 'ww-u2f')]));
			}

			$redirect_to = \apply_filters('login_redirect', $redirect_to, $redirect_to, \get_userdata($user_id));
			\wp_die(\json_encode(['ok' => true, 'redirect' => $redirect_to]));
		}
		catch (\Throwable $e) {
			$user = \get_userdata($user_id);
			\do_action('wp_login_failed', $user ? $user->user_login : '');
			\wp_die(\json_encode(['ok' => false, 'message' => $e->getMessage()]));
		}
	}

	public function wp_ajax_wwu2f_revoke()
	{
		\header('Content-Type: application/json; charset=' . \get_bloginfo('charset'));

		$handle  = $_POST['handle']   ?? null;
		$nonce   = $_POST['_wpnonce'] ?? null;
		$user_id = \get_current_user_id();

		if (false === \wp_verify_nonce($nonce, 'revoke-key_' . $handle)) {
			\wp_die(\json_encode(['ok' => false, 'message' => \__('CSRF token does not match. Please reload the page.', 'ww-u2f')]));
		}

		U2FUtils::deleteKey($user_id, $handle);
		\wp_die(\json_encode(['ok' => true, 'message' => \__('The key has been revoked.', 'ww-u2f'), 'sigs' => U2FUtils::getAuthDataFor($user_id)]));
	}
}
