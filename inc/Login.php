<?php
namespace WildWolf\U2F;

final class Login
{
	private $last_token;

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
		$this->login_init();
	}

	public function login_init()
	{
		\add_action('wp_login',        [$this, 'wp_login'], 50, 2);
		\add_action('set_auth_cookie', [$this, 'set_auth_cookie'], 10, 6);
	}

	/**
	 * Fires immediately before the authentication cookie is set.
	 *
	 * @param string $auth_cookie Authentication cookie value.
	 * @param int    $expire      The time the login grace period expires as a UNIX timestamp.
	 *                            Default is 12 hours past the cookie's expiration time.
	 * @param int    $expiration  The time when the authentication cookie expires as a UNIX timestamp.
	 *                            Default is 14 days from now.
	 * @param int    $user_id     User ID.
	 * @param string $scheme      Authentication scheme. Values include 'auth' or 'secure_auth'.
	 * @param string $token       User's session token to use for this cookie.
	 */
	public function set_auth_cookie(/** @scrutinizer ignore-unused */ $auth_cookie, /** @scrutinizer ignore-unused */ $expire, /** @scrutinizer ignore-unused */ $expiration, /** @scrutinizer ignore-unused */ $user_id, /** @scrutinizer ignore-unused */ $scheme, $token)
	{
		$this->last_token = $token;
	}

	public function wp_login($user_login, \WP_User $user)
	{
		if (!U2FUtils::enabledFor($user) || !\is_ssl()) {
			unset($this->last_token);
			return;
		}

		if ($this->last_token) {
			$manager = \WP_Session_Tokens::get_instance($user->ID);
			$manager->destroy($this->last_token);
		}

		\wp_clear_auth_cookie();

		$scripts = \wp_scripts();
		$styles  = \wp_styles();
		$scripts->queue = [];
		$scripts->to_do = [];
		$styles->queue  = [];
		$styles->to_do  = [];

		$data = U2FUtils::getAuthDataFor($user->ID);

		$suffix = \wp_scripts_get_suffix();
		\wp_enqueue_style('u2flogin', WPUtils::assetsUrl("u2flogin{$suffix}.css"), ['login'], '2019031900');
		\wp_enqueue_script('u2flogin', WPUtils::assetsUrl("u2flogin{$suffix}.js"), [], '2019041601', true);
		\wp_localize_script('u2flogin', 'wwU2F', [
			'serverError' => \__('There was an error communicating with the server.', 'ww-u2f'),
			'errors'      => [
				0 => \__('Success.', 'ww-u2f'),
				1 => \__('Unknown error.', 'ww-u2f'),
				2 => \__('The request cannot be processed.', 'ww-u2f'),
				3 => \__('Client configuration is not supported.', 'ww-u2f'),
				4 => \__('The presented device is not eligible.', 'ww-u2f'),
				5 => \__('Timeout reached before request could be satisfied.', 'ww-u2f'),
			],
			'ajax_url'  => \admin_url('admin-ajax.php'),
			'u2f_api'   => \plugins_url("assets/u2f-api{$suffix}.js?v=2019031900", \dirname(__DIR__) . '/plugin.php'),
			'noSupport' => \__('Your browser does not support FIDO U2F. Please try another one.', 'ww-u2f'),
			'request'   => $data,
		]);

		$params = [
			'rememberme'    => $_POST['rememberme']  ?? 0,
			'redirect_to'   => $_POST['redirect_to'] ?? \home_url(),
			'interim_login' => $_POST['interim-login'] ?? 0,
			'user_id'       => $user->ID,
		];

		WPUtils::render('login', $params);
		exit;
	}
}
