<?php
/**
 * Built-in list behind the Newsletter widget.
 *
 * Emails are stored as private `stx_subscriber` posts (title = email), so the
 * list works on any site without an email service. Admins download it as CSV
 * to import elsewhere, and WordPress's privacy tools can export and erase an
 * address. Form handling (no nonce, honeypot, rate limit) is shared with the
 * contact form through Public_Form.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

defined( 'ABSPATH' ) || exit;

final class Newsletter extends Public_Form {

	public const POST_TYPE = 'stx_subscriber';

	/** Form and AJAX action. */
	public const ACTION = 'stx_subscribe';

	private const EXPORT_ACTION = 'stx_newsletter_export';

	/** Sign-ups allowed per visitor in RATE_WINDOW seconds. */
	private const RATE_LIMIT = 5;

	private const RATE_WINDOW = 900;

	/** Page the address was sent from. */
	private const META_SOURCE = '_stx_source';

	public function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'label'           => __( 'Newsletter subscribers', 'studiare-extensions' ),
				'public'          => false,
				'show_ui'         => false,
				'show_in_rest'    => false,
				'rewrite'         => false,
				'query_var'       => false,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
			)
		);

		$this->add_handler( self::ACTION, array( $this, 'handle' ) );

		add_action( 'admin_post_' . self::EXPORT_ACTION, array( $this, 'export' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
	}

	/** Number of stored addresses. */
	public static function count(): int {
		$counts = wp_count_posts( self::POST_TYPE );

		return isset( $counts->private ) ? (int) $counts->private : 0;
	}

	/** Nonce-protected download link for the admin. */
	public static function export_url(): string {
		return self::admin_action_url( self::EXPORT_ACTION );
	}

	/** Sign-up from the widget's form. */
	public function handle(): void {
		$this->start();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- public form on cached pages; see Public_Form.
		$email  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$source = isset( $_POST['source'] ) ? absint( $_POST['source'] ) : 0;
		// phpcs:enable

		if ( ! is_email( $email ) ) {
			$this->respond( 'invalid' );
		}

		if ( ! $this->within_rate_limit( 'news', self::RATE_LIMIT, self::RATE_WINDOW ) ) {
			$this->respond( 'busy' );
		}

		if ( ! self::find( $email ) ) {
			$saved = wp_insert_post(
				array(
					'post_type'   => self::POST_TYPE,
					'post_status' => 'private',
					'post_title'  => strtolower( $email ),
					'meta_input'  => $source && get_post( $source ) ? array( self::META_SOURCE => $source ) : array(),
				),
				true
			);

			if ( is_wp_error( $saved ) ) {
				$this->respond( 'error' );
			}
		}

		// An address already on the list is a success too: the visitor gets the same answer either way.
		$this->respond( 'ok' );
	}

	/** Streams every address as a CSV file. */
	public function export(): void {
		check_admin_referer( self::EXPORT_ACTION );

		self::export_csv(
			self::POST_TYPE,
			'newsletter',
			array( 'email', 'date', 'page' ),
			static function ( \WP_Post $post ): array {
				$source = (int) get_post_meta( $post->ID, self::META_SOURCE, true );

				return array( $post->post_title, $post->post_date, $source ? get_permalink( $source ) : '' );
			}
		);
	}

	/**
	 * @param array $exporters Registered exporters.
	 */
	public function register_exporter( $exporters ) {
		$exporters['studiare-extensions-newsletter'] = array(
			'exporter_friendly_name' => __( 'Newsletter (Studiare+)', 'studiare-extensions' ),
			'callback'               => array( $this, 'export_personal_data' ),
		);

		return $exporters;
	}

	/**
	 * @param array $erasers Registered erasers.
	 */
	public function register_eraser( $erasers ) {
		$erasers['studiare-extensions-newsletter'] = array(
			'eraser_friendly_name' => __( 'Newsletter (Studiare+)', 'studiare-extensions' ),
			'callback'             => array( $this, 'erase_personal_data' ),
		);

		return $erasers;
	}

	/**
	 * @param string $email Address being exported.
	 */
	public function export_personal_data( $email ): array {
		$post = self::find( (string) $email );
		$data = array();

		if ( $post ) {
			$data[] = array(
				'group_id'    => 'studiare-extensions-newsletter',
				'group_label' => __( 'Newsletter', 'studiare-extensions' ),
				'item_id'     => 'subscriber-' . $post->ID,
				'data'        => array(
					array(
						'name'  => __( 'Email', 'studiare-extensions' ),
						'value' => $post->post_title,
					),
					array(
						'name'  => __( 'Subscribed on', 'studiare-extensions' ),
						'value' => $post->post_date,
					),
				),
			);
		}

		return array(
			'data' => $data,
			'done' => true,
		);
	}

	/**
	 * @param string $email Address being erased.
	 */
	public function erase_personal_data( $email ): array {
		$post    = self::find( (string) $email );
		$removed = $post && wp_delete_post( $post->ID, true );

		return array(
			'items_removed'  => (bool) $removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}

	/**
	 * @param string $email Address.
	 */
	private static function find( string $email ): ?\WP_Post {
		$email = strtolower( trim( $email ) );
		if ( '' === $email ) {
			return null;
		}

		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'private',
				'title'          => $email,
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			)
		);

		return $posts ? $posts[0] : null;
	}

	/** Query argument with the sign-up result. */
	protected function result_arg(): string {
		return 'stx_news';
	}
}
