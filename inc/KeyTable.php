<?php
namespace WildWolf\U2F;

final class KeyTable extends \WP_List_Table
{
	/**
	 * @var int
	 */
	private $user_id;

	public function __construct($args = [])
	{
		$this->user_id = $args['user_id'] ?? 0;
		unset($args['user_id']);
		parent::__construct($args);
	}

	public function prepare_items()
	{
		$this->items = U2FUtils::keysFor($this->user_id);
	}

	public function get_columns()
	{
		return [
			'name'      => \__('Key Name', 'ww-u2f'),
			'counter'   => \__('Counter Value', 'ww-u2f'),
			'publicKey' => \__('Public Key', 'ww-u2f'),
			'created'   => \__('Created', 'ww-u2f'),
			'last_used' => \__('Last Used', 'ww-u2f'),
		];
	}

	protected function column_name($item) : string
	{
		$actions = [
			'revoke' => \sprintf(
				'<button class="button-link hide-if-no-js revoke-button" data-handle="%1$s" data-nonce="%2$s">%3$s <span class="spinner"></span></button>',
				$item->keyHandle,
				\wp_create_nonce('revoke-key_' . $item->keyHandle),
				\__('Revoke', 'ww-u2f')
			),
		];

		return
			  \esc_html($item->name)
			. $this->row_actions($actions, false)
		;
	}

	protected function column_counter($item) : string
	{
		return \esc_html($item->counter);
	}

	protected function column_publicKey($item) : string
	{
		return \nl2br(\esc_html($item->publicKey), true);
	}

	protected function column_created($item) : string
	{
		return self::handleDateColumn($item, 'created');
	}

	protected function column_last_used($item) : string
	{
		return self::handleDateColumn($item, 'last_used');
	}

	private static function handleDateColumn($item, string $idx) : string
	{
		$date_format = (string)\get_option('date_format', 'r');
		$time_format = (string)\get_option('time_format', 'r');
		return \date_i18n($date_format . ' ' . $time_format, $item->$idx);
	}

	protected function display_tablenav($which)
	{
	}
}
