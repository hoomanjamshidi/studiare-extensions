<?php
/**
 * Login button for guests (Studiare's login popup or the account page) and
 * an avatar menu with the WooCommerce account links for members.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Core\Theme_Bridge;

defined( 'ABSPATH' ) || exit;

final class Account extends Base {

	/** Default icon keys; widgets saved before the icon controls existed use them too. */
	private const GUEST_ICON  = 'login';
	private const MEMBER_ICON = 'user';

	public function get_name(): string {
		return 'stx-account';
	}

	public function get_title(): string {
		return __( 'Account / login', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-lock-user';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Account', 'studiare-extensions' ) );

		$this->add_control(
			'guest_label',
			array(
				'label'   => __( 'Text for guests', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Log in', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'guest_icon',
			array(
				'label'       => __( 'Icon for guests', 'studiare-extensions' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => self::GUEST_ICON,
				'options'     => self::icon_options(),
				'description' => __( 'The "Icon only" style always shows an icon, so "None" keeps the default one there.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'guest_action',
			array(
				'label'   => __( 'Guests click to', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'modal',
				'options' => array(
					'modal' => __( 'Open Studiare\'s login popup (else the account page)', 'studiare-extensions' ),
					'page'  => __( 'Go to the account page', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'member_label',
			array(
				'label'   => __( 'Text for members', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'name',
				'options' => array(
					'name'   => __( 'Their name', 'studiare-extensions' ),
					'custom' => __( 'Custom text', 'studiare-extensions' ),
					'none'   => __( 'Avatar only', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'member_text',
			array(
				'label'     => __( 'Custom text', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'My account', 'studiare-extensions' ),
				'condition' => array( 'member_label' => 'custom' ),
			)
		);

		$this->add_control(
			'member_visual',
			array(
				'label'   => __( 'Picture for members', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'avatar',
				'options' => array(
					'avatar' => __( 'Their profile picture', 'studiare-extensions' ),
					'icon'   => __( 'Icon', 'studiare-extensions' ),
				),
			)
		);

		// No "None": members always get a picture. It is also the fallback
		// when a member has no avatar URL.
		$this->add_control(
			'member_icon',
			array(
				'label'     => __( 'Icon for members', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => self::MEMBER_ICON,
				'options'   => self::icon_options( false ),
				'condition' => array( 'member_visual' => 'icon' ),
			)
		);

		$this->add_control(
			'dropdown',
			array(
				'label'   => __( 'Account menu on click', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'variant',
			array(
				'label'   => __( 'Style', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'dark',
				'options' => array(
					'dark'    => __( 'Dark button', 'studiare-extensions' ),
					'accent'  => __( 'Accent button', 'studiare-extensions' ),
					'outline' => __( 'Outlined button', 'studiare-extensions' ),
					'icon'    => __( 'Icon only', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'editor_state',
			array(
				'label'       => __( 'Preview in the editor as', 'studiare-extensions' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'guest',
				'options'     => array(
					'guest'  => __( 'Guest', 'studiare-extensions' ),
					'member' => __( 'Logged-in member', 'studiare-extensions' ),
				),
				'separator'   => 'before',
				'description' => __( 'Only changes what you see while editing.', 'studiare-extensions' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Button', 'studiare-extensions' ) );
		$this->add_button_style( 'btn', '.stx-account__btn' );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s       = $this->get_settings_for_display();
		$has_woo = function_exists( 'wc_get_page_permalink' );
		$account = $has_woo ? wc_get_page_permalink( 'myaccount' ) : admin_url( 'profile.php' );
		$variant = 'stx-account__btn stx-account__btn--' . sanitize_html_class( $s['variant'] );

		$as_guest = ! is_user_logged_in() || ( $this->in_editor() && 'guest' === $s['editor_state'] );

		if ( $as_guest ) {
			$modal   = 'modal' === $s['guest_action'] && Theme_Bridge::is_active();
			$classes = $variant . ( $modal ? ' register-modal-opener' : '' );
			$href    = $has_woo ? $account : wp_login_url();
			$icon    = '' !== $s['guest_icon'] || 'icon' !== $s['variant'] ? $s['guest_icon'] : self::GUEST_ICON;

			printf(
				'<div class="stx-account"><a class="%1$s" href="%2$s"%3$s>%4$s<span class="%5$s">%6$s</span></a></div>',
				esc_attr( $classes ),
				esc_url( $href ),
				$modal ? ' data-stx-login="modal"' : '',
				'' !== $icon ? self::icon( $icon ) : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
				'icon' === $s['variant'] ? 'screen-reader-text' : 'stx-account__label',
				esc_html( $s['guest_label'] )
			);
			return;
		}

		$user   = wp_get_current_user();
		$avatar = 'avatar' === $s['member_visual'] ? get_avatar_url( $user->ID, array( 'size' => 64 ) ) : '';
		$label  = 'name' === $s['member_label'] ? $user->display_name : ( 'custom' === $s['member_label'] ? $s['member_text'] : '' );
		$menu   = 'yes' === $s['dropdown'] ? $this->menu_items( $has_woo ) : array();
		$id     = 'stx-account-' . $this->get_id();

		echo '<div class="stx-account' . ( $menu ? ' has-menu' : '' ) . '">';

		printf(
			'<%1$s class="%2$s is-member"%3$s>%4$s%5$s</%1$s>',
			$menu ? 'button' : 'a',
			esc_attr( $variant . ( $avatar ? ' has-avatar' : '' ) ),
			$menu ? ' type="button" aria-expanded="false" aria-controls="' . esc_attr( $id ) . '" data-stx-toggle="' . esc_attr( $id ) . '"' : ' href="' . esc_url( $account ) . '"',
			$avatar ? '<img class="stx-account__avatar" src="' . esc_url( $avatar ) . '" alt="" width="28" height="28" loading="lazy">' : self::icon( $s['member_icon'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped/bundled.
			'' !== $label && 'icon' !== $s['variant'] ? '<span class="stx-account__label">' . esc_html( $label ) . '</span>' : ''
		);

		if ( $menu ) {
			echo '<ul class="stx-account__menu" id="' . esc_attr( $id ) . '" hidden>';
			foreach ( $menu as $item ) {
				printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( $item['url'] ), esc_html( $item['label'] ) );
			}
			echo '</ul>';
		}

		echo '</div>';
	}

	/**
	 * @param bool $has_woo Whether WooCommerce is active.
	 * @return array<int, array{label:string, url:string}>
	 */
	private function menu_items( bool $has_woo ): array {
		if ( ! $has_woo || ! function_exists( 'wc_get_account_menu_items' ) ) {
			return array(
				array(
					'label' => __( 'Profile', 'studiare-extensions' ),
					'url'   => admin_url( 'profile.php' ),
				),
				array(
					'label' => __( 'Log out', 'studiare-extensions' ),
					'url'   => wp_logout_url( home_url( '/' ) ),
				),
			);
		}

		$items = array();
		foreach ( wc_get_account_menu_items() as $endpoint => $label ) {
			$items[] = array(
				'label' => $label,
				'url'   => 'customer-logout' === $endpoint ? wc_logout_url() : wc_get_account_endpoint_url( $endpoint ),
			);
		}

		return $items;
	}
}
