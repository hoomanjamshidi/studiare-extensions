<?php
/**
 * The contact messages screen: WordPress's own list table for the
 * `stx_message` posts (search, paging, trash for free), shown last in the
 * Studiare+ menu with a count of messages that arrived since an admin last
 * opened it. Messages are read in the list itself, so the edit screen is
 * never used.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

use StudiareExt\Admin\Admin;

defined( 'ABSPATH' ) || exit;

final class Contact_Inbox {

	/** Time an admin last opened the list (Unix time). */
	private const SEEN_OPTION = 'studiare_ext_messages_seen';

	/** Most messages counted for the menu badge. */
	private const BADGE_CAP = 99;

	/** @var int Last visit before this one: messages after it are new. */
	private $seen_before = 0;

	public function register(): void {
		$type = Contact_Messages::POST_TYPE;

		add_filter( 'manage_' . $type . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . $type . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'list_table_primary_column', array( $this, 'primary_column' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_filter( 'bulk_actions-edit-' . $type, array( $this, 'bulk_actions' ) );
		add_action( 'load-edit.php', array( $this, 'mark_seen' ) );
		add_action( 'load-post.php', array( $this, 'redirect_edit_screen' ) );
		// After Admin::register_menu(): a post type's own menu item would come before the dashboard.
		add_action( 'admin_menu', array( $this, 'add_menu' ), 20 );
		// admin_head runs after load-edit.php, so the badge already counts this visit.
		add_action( 'admin_head', array( $this, 'menu_badge' ) );
	}

	public function add_menu(): void {
		add_submenu_page(
			Admin::MENU_SLUG,
			__( 'Contact messages', 'studiare-extensions' ),
			__( 'Contact messages', 'studiare-extensions' ),
			Admin::CAPABILITY,
			'edit.php?post_type=' . Contact_Messages::POST_TYPE
		);
	}

	/**
	 * @param string[] $columns Default columns.
	 * @return string[]
	 */
	public function columns( $columns ): array {
		return array(
			'cb'       => $columns['cb'] ?? '<input type="checkbox">',
			'sender'   => __( 'From', 'studiare-extensions' ),
			'message'  => __( 'Message', 'studiare-extensions' ),
			'page'     => __( 'Page', 'studiare-extensions' ),
			'received' => __( 'Received', 'studiare-extensions' ),
		);
	}

	/**
	 * @param string $column  Column key.
	 * @param int    $post_id Message ID.
	 */
	public function render_column( $column, $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		switch ( $column ) {
			case 'sender':
				$this->render_sender( $post );
				break;

			case 'message':
				$topic = (string) get_post_meta( $post->ID, Contact_Messages::META_TOPIC, true );
				if ( '' !== $topic ) {
					printf( '<strong>%s</strong><br>', esc_html( $topic ) );
				}
				echo nl2br( esc_html( $post->post_content ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped before nl2br().
				break;

			case 'page':
				$source = (int) get_post_meta( $post->ID, Contact_Messages::META_SOURCE, true );
				if ( $source && get_post( $source ) ) {
					printf( '<a href="%1$s" target="_blank" rel="noopener">%2$s</a>', esc_url( (string) get_permalink( $source ) ), esc_html( get_the_title( $source ) ) );
				} else {
					echo '—';
				}
				break;

			case 'received':
				$time = (int) get_post_time( 'U', true, $post );
				printf(
					'<time datetime="%1$s" title="%2$s">%3$s</time>',
					esc_attr( (string) get_post_time( 'c', true, $post ) ),
					esc_attr( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $time ) ),
					/* translators: %s: time since the message arrived, e.g. "5 minutes". */
					esc_html( sprintf( __( '%s ago', 'studiare-extensions' ), human_time_diff( $time ) ) )
				);
				break;
		}
	}

	/**
	 * Name, "New" badge and the ways to answer.
	 *
	 * @param \WP_Post $post Message.
	 */
	private function render_sender( \WP_Post $post ): void {
		$email = (string) get_post_meta( $post->ID, Contact_Messages::META_EMAIL, true );
		$phone = (string) get_post_meta( $post->ID, Contact_Messages::META_PHONE, true );

		printf( '<strong>%s</strong>', esc_html( $post->post_title ) );
		if ( (int) get_post_time( 'U', true, $post ) > $this->seen_before ) {
			printf( ' <span class="stx-inbox-new">%s</span>', esc_html__( 'New', 'studiare-extensions' ) );
		}

		// Addresses and numbers are left-to-right even on Persian screens.
		if ( '' !== $email ) {
			printf( '<br><a href="mailto:%1$s" dir="ltr">%2$s</a>', esc_attr( $email ), esc_html( $email ) );
		}
		if ( '' !== $phone ) {
			printf( '<br><a href="tel:%1$s" dir="ltr">%2$s</a>', esc_attr( (string) preg_replace( '/[^\d+]/', '', $phone ) ), esc_html( $phone ) );
		}
	}

	/**
	 * @param string $primary Default primary column.
	 * @param string $screen  Screen ID.
	 */
	public function primary_column( $primary, $screen ) {
		return 'edit-' . Contact_Messages::POST_TYPE === $screen ? 'sender' : $primary;
	}

	/**
	 * Reply and call instead of edit, quick edit and view.
	 *
	 * @param string[] $actions Row actions.
	 * @param \WP_Post $post    Post.
	 * @return string[]
	 */
	public function row_actions( $actions, $post ) {
		if ( ! $post instanceof \WP_Post || Contact_Messages::POST_TYPE !== $post->post_type ) {
			return $actions;
		}

		$keep  = array_intersect_key( $actions, array_flip( array( 'trash', 'untrash', 'delete' ) ) );
		$email = (string) get_post_meta( $post->ID, Contact_Messages::META_EMAIL, true );
		$topic = (string) get_post_meta( $post->ID, Contact_Messages::META_TOPIC, true );
		$more  = array();

		if ( '' !== $email && 'trash' !== $post->post_status ) {
			/* translators: %s: topic of the message being answered. */
			$subject       = '' !== $topic ? sprintf( __( 'Re: %s', 'studiare-extensions' ), $topic ) : get_bloginfo( 'name' );
			$more['reply'] = sprintf( '<a href="%1$s">%2$s</a>', esc_url( 'mailto:' . $email . '?subject=' . rawurlencode( $subject ) ), esc_html__( 'Reply by email', 'studiare-extensions' ) );
		}

		return $more + $keep;
	}

	/**
	 * @param string[] $actions Bulk actions.
	 * @return string[]
	 */
	public function bulk_actions( $actions ): array {
		unset( $actions['edit'] );

		return $actions;
	}

	/** Remembers the previous visit (for the "New" badges) and records this one. */
	public function mark_seen(): void {
		if ( ! $this->is_inbox() ) {
			return;
		}

		$this->seen_before = (int) get_option( self::SEEN_OPTION, 0 );
		update_option( self::SEEN_OPTION, time(), false );

		add_action( 'admin_head', array( $this, 'print_styles' ) );
	}

	/** Messages are read in the list; opening one goes back there. */
	public function redirect_edit_screen(): void {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- routing only.

		if ( Contact_Messages::POST_TYPE === get_post_type( $post_id ) ) {
			wp_safe_redirect( Contact_Messages::inbox_url() );
			exit;
		}
	}

	/** Adds the number of new messages to the menu item, like WordPress's comment count. */
	public function menu_badge(): void {
		global $submenu;

		if ( ! current_user_can( Admin::CAPABILITY ) || empty( $submenu[ Admin::MENU_SLUG ] ) ) {
			return;
		}

		$new = count(
			get_posts(
				array(
					'post_type'      => Contact_Messages::POST_TYPE,
					'post_status'    => 'private',
					'posts_per_page' => self::BADGE_CAP,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'date_query'     => array(
						array(
							'column' => 'post_date_gmt',
							'after'  => gmdate( 'Y-m-d H:i:s', (int) get_option( self::SEEN_OPTION, 0 ) ),
						),
					),
				)
			)
		);

		if ( ! $new ) {
			return;
		}

		$slug = 'edit.php?post_type=' . Contact_Messages::POST_TYPE;
		foreach ( $submenu[ Admin::MENU_SLUG ] as $index => $item ) {
			if ( $slug === $item[2] ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- adding a count badge is the documented way.
				$submenu[ Admin::MENU_SLUG ][ $index ][0] .= sprintf( ' <span class="awaiting-mod"><span class="pending-count">%s</span></span>', esc_html( number_format_i18n( $new ) ) );
			}
		}
	}

	/** Tiny styles for the list: the "New" badge and readable message text. */
	public function print_styles(): void {
		echo '<style>.stx-inbox-new{display:inline-block;padding:0 7px;border-radius:9px;background:#d63638;color:#fff;font-size:11px;line-height:18px;vertical-align:middle}.post-type-stx_message .column-message{width:45%;white-space:normal}.post-type-stx_message .column-page,.post-type-stx_message .column-received{width:14%}</style>';
	}

	private function is_inbox(): bool {
		$screen = get_current_screen();

		return $screen && 'edit-' . Contact_Messages::POST_TYPE === $screen->id;
	}
}
