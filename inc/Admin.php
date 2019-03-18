<?php
namespace WildWolf\U2F;

final class Admin
{
	private $user_settings_hook;

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
		\add_action('admin_menu', [$this, 'admin_menu']);
		\add_action('admin_init', [$this, 'admin_init']);
	}

	public function admin_menu()
	{
		$this->user_settings_hook = \add_users_page(\__('FIDO U2F', 'ww-u2f'), \__('FIDO U2F', 'ww-u2f'), 'read', 'ww-u2f', [$this, 'user_page']);
	}

	public function admin_init()
	{
		\add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
		if (WPUtils::doingAJAX()) {
			AJAX::instance();
		}
	}

	public function admin_enqueue_scripts($hook)
	{
		if ($this->user_settings_hook === $hook) {
			$user_id = \get_current_user_id();
			list($req, $sigs) = U2FUtils::getRegisterDataFor($user_id);

			$suffix = \wp_scripts_get_suffix();
			\wp_enqueue_script('u2fcreate', WPUtils::assetsUrl("u2fcreate{$suffix}.js"), [], '2019031800', true);
			\wp_localize_script('u2fcreate', 'wwU2F', [
				'serverError' => \__('There was an error communicating with the server.', 'ww-u2f'),
				'errors'      => [
					0 => \__('Success.', 'ww-u2f'),
					1 => \__('Unknown error.', 'ww-u2f'),
					2 => \__('The request cannot be processed.', 'ww-u2f'),
					3 => \__('Client configuration is not supported.', 'ww-u2f'),
					4 => \__('The presented device is not eligible.', 'ww-u2f'),
					5 => \__('Timeout reached before request could be satisfied.', 'ww-u2f'),
				],
				'u2f_api'    => WPUtils::assetsUrl("u2f-api{$suffix}.js?v=2019031600"),
				'noSupport'  => \__('Your browser does not support FIDO U2F. Please try another one.', 'ww-u2f'),
				'request'    => $req,
				'sigs'       => $sigs,
				'revconfirm' => \__('Are you sure you want to revoke this key?', 'ww-u2f'),
			]);

			self::setRegisterRequest($user_id, (array)$req);
		}
	}

	private static function setRegisterRequest(int $user_id, array $req)
	{
		$manager = \WP_Session_Tokens::get_instance($user_id);
		$token   = \wp_get_session_token();
		$session = $manager->get($token);

		\assert(\is_array($session));

		$session['u2f_register_request'] = $req;
		$manager->update($token, $session);
	}

	public function user_page()
	{
		WPUtils::render('create');
	}
}
