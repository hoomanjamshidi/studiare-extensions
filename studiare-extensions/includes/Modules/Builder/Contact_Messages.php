<?php
/**
 * Messages sent through the Contact form widget.
 *
 * Each message is stored as a private `stx_message` post (title = the
 * sender's name, content = the message), so nothing is lost on hosts where
 * email does not work; a copy goes by email to the address set on the
 * widget. The form's options (which fields it asks for, the topics, where to
 * send the email) are read from the saved widget, never from the request, so
 * a crafted post cannot turn the form into a mail relay. Contact_Inbox shows
 * the messages in the admin.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

use StudiareExt\Admin\Admin;
use StudiareExt\Core\Persian;

defined( 'ABSPATH' ) || exit;

final class Contact_Messages extends Public_Form {

	public const POST_TYPE = 'stx_message';

	/** Form and AJAX action. */
	public const ACTION = 'stx_contact';

	/** Elementor widget name of the contact form. */
	public const WIDGET = 'stx-contact-form';

	public const META_EMAIL = '_stx_email';

	public const META_PHONE = '_stx_phone';

	public const META_TOPIC = '_stx_topic';

	/** Page the message was sent from. */
	public const META_SOURCE = '_stx_source';

	private const EXPORT_ACTION = 'stx_messages_export';

	/** Messages allowed per visitor in RATE_WINDOW seconds. */
	private const RATE_LIMIT = 3;

	private const RATE_WINDOW = 600;

	private const MAX_NAME = 100;

	private const MAX_TOPIC = 150;

	private const MAX_MESSAGE = 5000;

	public function register(): void {
		$cap = Admin::CAPABILITY;

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Contact messages', 'studiare-extensions' ),
					'singular_name'      => __( 'Contact message', 'studiare-extensions' ),
					'menu_name'          => __( 'Contact messages', 'studiare-extensions' ),
					'all_items'          => __( 'Contact messages', 'studiare-extensions' ),
					'search_items'       => __( 'Search messages', 'studiare-extensions' ),
					'not_found'          => __( 'No messages yet. Messages sent through the Contact form widget appear here.', 'studiare-extensions' ),
					'not_found_in_trash' => __( 'No messages in the trash.', 'studiare-extensions' ),
				),
				'public'              => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				// Contact_Inbox adds the menu item itself, after the modules.
				'show_in_menu'        => false,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title' ),
				// Only administrators read messages, and nobody writes one in the admin.
				'capability_type'     => array( 'stx_message', 'stx_messages' ),
				'map_meta_cap'        => true,
				'capabilities'        => array(
					'create_posts'           => 'do_not_allow',
					'edit_posts'             => $cap,
					'edit_others_posts'      => $cap,
					'edit_private_posts'     => $cap,
					'edit_published_posts'   => $cap,
					'delete_posts'           => $cap,
					'delete_others_posts'    => $cap,
					'delete_private_posts'   => $cap,
					'delete_published_posts' => $cap,
					'read_private_posts'     => $cap,
					'publish_posts'          => $cap,
				),
			)
		);

		$this->add_handler( self::ACTION, array( $this, 'handle' ) );

		add_action( 'admin_post_' . self::EXPORT_ACTION, array( $this, 'export' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
	}

	/**
	 * Form options the handler reads from the saved widget. The widget's
	 * controls use these as their defaults.
	 *
	 * @return array{email_field:string, phone_field:string, topic_field:string, topics:string, email_to:string, notify:string}
	 */
	public static function defaults(): array {
		return array(
			// required | optional | hidden.
			'email_field' => 'optional',
			'phone_field' => 'optional',
			// select | text | hidden.
			'topic_field' => 'select',
			'topics'      => implode(
				"\n",
				array(
					__( 'Choosing a course', 'studiare-extensions' ),
					__( 'Technical support', 'studiare-extensions' ),
					__( 'Orders and payment', 'studiare-extensions' ),
					__( 'Working with us', 'studiare-extensions' ),
				)
			),
			'email_to'    => '',
			'notify'      => 'yes',
		);
	}

	/**
	 * Topics as a list (one per line in the widget).
	 *
	 * @param string $topics Topics setting.
	 * @return string[]
	 */
	public static function topic_list( string $topics ): array {
		return array_values( array_filter( array_map( 'trim', explode( "\n", $topics ) ), 'strlen' ) );
	}

	/**
	 * What the form says when a message is not sent, by status. The widget
	 * shows them after a plain (no-JS) submit and pages.js after a
	 * background one.
	 *
	 * @return array<string, string>
	 */
	public static function error_messages(): array {
		return array(
			'invalid' => __( 'Please check the highlighted fields and try again.', 'studiare-extensions' ),
			'busy'    => __( 'Too many messages in a short time. Please try again in a few minutes.', 'studiare-extensions' ),
			'error'   => __( 'Your message could not be sent. Please try again.', 'studiare-extensions' ),
		);
	}

	/** Number of stored messages. */
	public static function count(): int {
		$counts = wp_count_posts( self::POST_TYPE );

		return isset( $counts->private ) ? (int) $counts->private : 0;
	}

	/** Admin list of the messages. */
	public static function inbox_url(): string {
		return admin_url( 'edit.php?post_type=' . self::POST_TYPE );
	}

	/** Nonce-protected download link for the admin. */
	public static function export_url(): string {
		return self::admin_action_url( self::EXPORT_ACTION );
	}

	/** A message from the widget's form. */
	public function handle(): void {
		$this->start();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- public form on cached pages; see Public_Form.
		$document = isset( $_POST['document'] ) ? absint( $_POST['document'] ) : 0;
		$form     = isset( $_POST['form'] ) ? sanitize_key( wp_unslash( $_POST['form'] ) ) : '';
		$source   = isset( $_POST['source'] ) ? absint( $_POST['source'] ) : 0;
		$input    = array(
			'name'    => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'email'   => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			'phone'   => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
			'topic'   => isset( $_POST['topic'] ) ? sanitize_text_field( wp_unslash( $_POST['topic'] ) ) : '',
			'message' => isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '',
		);
		// phpcs:enable

		$settings = self::form_settings( $document, $form );
		if ( null === $settings ) {
			$this->respond( 'error' );
		}

		$message = self::clean( $input, $settings );
		$invalid = self::invalid_fields( $message, $settings );
		if ( $invalid ) {
			$this->respond( 'invalid', array( 'fields' => $invalid ) );
		}

		if ( ! $this->within_rate_limit( 'contact', self::RATE_LIMIT, self::RATE_WINDOW ) ) {
			$this->respond( 'busy' );
		}

		$source  = $source && get_post( $source ) ? $source : 0;
		$post_id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_status'  => 'private',
				'post_title'   => $message['name'],
				'post_content' => $message['message'],
				'meta_input'   => array(
					self::META_EMAIL  => $message['email'],
					self::META_PHONE  => $message['phone'],
					self::META_TOPIC  => $message['topic'],
					self::META_SOURCE => $source,
				),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			$this->respond( 'error' );
		}

		if ( 'yes' === $settings['notify'] ) {
			self::notify( $message, $source, $settings['email_to'] );
		}

		$this->respond( 'ok' );
	}

	/**
	 * Options of a saved contact form widget, or null when the request does
	 * not point at one.
	 *
	 * @param int    $document_id Elementor document holding the widget (page or template).
	 * @param string $element_id  Widget element ID.
	 */
	public static function form_settings( int $document_id, string $element_id ): ?array {
		if ( ! $document_id || '' === $element_id || ! Library::elementor_active() || 'trash' === get_post_status( $document_id ) ) {
			return null;
		}

		$document = \Elementor\Plugin::$instance->documents->get( $document_id );
		$element  = $document ? self::find_widget( (array) $document->get_elements_data(), $element_id ) : null;

		if ( null === $element ) {
			return null;
		}

		$saved = is_array( $element['settings'] ?? null ) ? $element['settings'] : array();

		return array_merge( self::defaults(), array_intersect_key( $saved, self::defaults() ) );
	}

	/**
	 * Depth-first search for the contact form widget with an ID.
	 *
	 * @param array  $elements   Elementor elements.
	 * @param string $element_id Element ID.
	 */
	private static function find_widget( array $elements, string $element_id ): ?array {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}
			if ( ( $element['id'] ?? '' ) === $element_id ) {
				return self::WIDGET === ( $element['widgetType'] ?? '' ) ? $element : null;
			}
			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$found = self::find_widget( $element['elements'], $element_id );
				if ( null !== $found ) {
					return $found;
				}
			}
		}

		return null;
	}

	/**
	 * Keeps only the fields the form asks for, trimmed to sane lengths.
	 *
	 * @param array $input    Sanitized request fields.
	 * @param array $settings Form options.
	 */
	private static function clean( array $input, array $settings ): array {
		$topic = mb_substr( $input['topic'], 0, self::MAX_TOPIC );
		if ( 'select' === $settings['topic_field'] && ! in_array( $topic, self::topic_list( (string) $settings['topics'] ), true ) ) {
			$topic = '';
		}

		// Visitors type numbers with Persian digits; keep digits, + and the usual separators.
		$phone = trim( (string) preg_replace( '/[^\d+()\s-]/', '', Persian::latin_digits( $input['phone'] ) ) );

		return array(
			'name'    => mb_substr( trim( $input['name'] ), 0, self::MAX_NAME ),
			'email'   => 'hidden' === $settings['email_field'] ? '' : $input['email'],
			'phone'   => 'hidden' === $settings['phone_field'] ? '' : $phone,
			'topic'   => 'hidden' === $settings['topic_field'] ? '' : $topic,
			'message' => mb_substr( trim( $input['message'] ), 0, self::MAX_MESSAGE ),
		);
	}

	/**
	 * Names of the fields that need fixing.
	 *
	 * @param array $message  Cleaned message.
	 * @param array $settings Form options.
	 * @return string[]
	 */
	private static function invalid_fields( array $message, array $settings ): array {
		$invalid     = array();
		$phone_ok    = strlen( (string) preg_replace( '/\D/', '', $message['phone'] ) ) >= 7;
		$email_given = '' !== $message['email'];
		$phone_given = '' !== $message['phone'];

		if ( '' === $message['name'] ) {
			$invalid[] = 'name';
		}
		if ( ( 'required' === $settings['email_field'] || $email_given ) && ! is_email( $message['email'] ) ) {
			$invalid[] = 'email';
		}
		if ( ( 'required' === $settings['phone_field'] || $phone_given ) && ! $phone_ok ) {
			$invalid[] = 'phone';
		}

		// Two optional fields: at least one of them, or there is no way to answer.
		if ( 'optional' === $settings['email_field'] && 'optional' === $settings['phone_field'] && ! $email_given && ! $phone_given ) {
			$invalid[] = 'email';
			$invalid[] = 'phone';
		}

		if ( 'select' === $settings['topic_field'] && self::topic_list( (string) $settings['topics'] ) && '' === $message['topic'] ) {
			$invalid[] = 'topic';
		}
		if ( mb_strlen( $message['message'] ) < 2 ) {
			$invalid[] = 'message';
		}

		return array_values( array_unique( $invalid ) );
	}

	/**
	 * Emails a copy of the message; the sender's address is the Reply-To, so
	 * answering is one click.
	 *
	 * @param array  $message  Cleaned message.
	 * @param int    $source   Page the form was on.
	 * @param string $email_to Comma-separated addresses ('' = the site's admin email).
	 */
	private static function notify( array $message, int $source, string $email_to ): void {
		$to = array_values( array_filter( array_map( 'trim', explode( ',', $email_to ) ), 'is_email' ) );
		if ( ! $to ) {
			$to = array( (string) get_option( 'admin_email' ) );
		}

		$site  = wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES );
		$about = '' !== $message['topic'] ? $message['topic'] : $message['name'];

		$lines = array(
			/* translators: %s: site name. */
			sprintf( __( 'New message from the contact form on %s.', 'studiare-extensions' ), $site ),
			'',
			__( 'Name', 'studiare-extensions' ) . ': ' . $message['name'],
		);
		foreach ( array(
			'email' => __( 'Email', 'studiare-extensions' ),
			'phone' => __( 'Phone', 'studiare-extensions' ),
			'topic' => __( 'Topic', 'studiare-extensions' ),
		) as $field => $label ) {
			if ( '' !== $message[ $field ] ) {
				$lines[] = $label . ': ' . $message[ $field ];
			}
		}
		if ( $source ) {
			$lines[] = __( 'Page', 'studiare-extensions' ) . ': ' . get_permalink( $source );
		}
		$lines[] = '';
		$lines[] = $message['message'];
		$lines[] = '';
		$lines[] = '—';
		/* translators: %s: link to the messages in the admin. */
		$lines[] = sprintf( __( 'All messages: %s', 'studiare-extensions' ), self::inbox_url() );

		$headers = array();
		if ( '' !== $message['email'] ) {
			// Names can hold characters that break a header; the address alone is enough.
			$headers[] = 'Reply-To: ' . $message['email'];
		}

		wp_mail(
			$to,
			/* translators: 1: site name, 2: message topic or the sender's name. */
			sprintf( __( '[%1$s] New message: %2$s', 'studiare-extensions' ), $site, $about ),
			implode( "\n", $lines ),
			$headers
		);
	}

	/** Streams every message as a CSV file. */
	public function export(): void {
		check_admin_referer( self::EXPORT_ACTION );

		self::export_csv(
			self::POST_TYPE,
			'contact-messages',
			array( 'date', 'name', 'email', 'phone', 'topic', 'message', 'page' ),
			static function ( \WP_Post $post ): array {
				$source = (int) get_post_meta( $post->ID, self::META_SOURCE, true );

				return array(
					$post->post_date,
					$post->post_title,
					(string) get_post_meta( $post->ID, self::META_EMAIL, true ),
					(string) get_post_meta( $post->ID, self::META_PHONE, true ),
					(string) get_post_meta( $post->ID, self::META_TOPIC, true ),
					$post->post_content,
					$source ? get_permalink( $source ) : '',
				);
			}
		);
	}

	/**
	 * @param array $exporters Registered exporters.
	 */
	public function register_exporter( $exporters ) {
		$exporters['studiare-extensions-contact'] = array(
			'exporter_friendly_name' => __( 'Contact messages (Studiare+)', 'studiare-extensions' ),
			'callback'               => array( $this, 'export_personal_data' ),
		);

		return $exporters;
	}

	/**
	 * @param array $erasers Registered erasers.
	 */
	public function register_eraser( $erasers ) {
		$erasers['studiare-extensions-contact'] = array(
			'eraser_friendly_name' => __( 'Contact messages (Studiare+)', 'studiare-extensions' ),
			'callback'             => array( $this, 'erase_personal_data' ),
		);

		return $erasers;
	}

	/**
	 * @param string $email Address being exported.
	 */
	public function export_personal_data( $email ): array {
		$data = array();

		foreach ( self::find_by_email( (string) $email ) as $post ) {
			$data[] = array(
				'group_id'    => 'studiare-extensions-contact',
				'group_label' => __( 'Contact messages', 'studiare-extensions' ),
				'item_id'     => 'message-' . $post->ID,
				'data'        => array(
					array(
						'name'  => __( 'Name', 'studiare-extensions' ),
						'value' => $post->post_title,
					),
					array(
						'name'  => __( 'Phone', 'studiare-extensions' ),
						'value' => (string) get_post_meta( $post->ID, self::META_PHONE, true ),
					),
					array(
						'name'  => __( 'Message', 'studiare-extensions' ),
						'value' => $post->post_content,
					),
					array(
						'name'  => __( 'Sent on', 'studiare-extensions' ),
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
		$removed = false;

		foreach ( self::find_by_email( (string) $email ) as $post ) {
			$removed = (bool) wp_delete_post( $post->ID, true ) || $removed;
		}

		return array(
			'items_removed'  => $removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}

	/**
	 * Messages sent with an email address.
	 *
	 * @param string $email Address.
	 * @return \WP_Post[]
	 */
	private static function find_by_email( string $email ): array {
		$email = trim( $email );
		if ( ! is_email( $email ) ) {
			return array();
		}

		return get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array( 'private', 'trash' ),
				'posts_per_page' => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- one person's messages; a safety cap.
				'no_found_rows'  => true,
				'meta_key'       => self::META_EMAIL, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- privacy request, runs rarely.
				'meta_value'     => $email, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
	}

	/** Query argument with the result of a plain (no-JS) submit. */
	protected function result_arg(): string {
		return 'stx_contact';
	}
}
