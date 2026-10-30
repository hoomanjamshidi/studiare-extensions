<?php
/**
 * View model of the Studiare+ thank-you page (views/thankyou.php): how the
 * order stands for the buyer, what is ready to open, the next steps and the
 * buttons that lead there.
 *
 * Each item that is ready gets its own buttons: the course, its download
 * files, and whatever other modules add through the
 * `studiare_ext_thankyou_item_links` filter (the audio module's "Listen").
 *
 * The state follows WooCommerce's statuses: `failed`, `paid` (a paid status,
 * see wc_get_is_paid_statuses()), `confirmed` (cash on delivery, which
 * WooCommerce marks "processing" before any money arrives) and `waiting`
 * (on hold or pending, e.g. a bank transfer). Account links and the
 * buyer's email show only to the logged-in owner of the order, as in
 * WooCommerce's own template; anyone else with the order link sees the
 * order without them.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Checkout;

use StudiareExt\Core\Persian;
use StudiareExt\Core\Theme_Bridge;

defined( 'ABSPATH' ) || exit;

final class Thank_You {

	/** @var \WC_Order */
	private $order;

	/** @var array Thank-you page settings. */
	private $settings;

	/** @var bool Whether the viewer is the logged-in customer of the order. */
	private $owner;

	/** Most download buttons on one item before "All downloads" takes over. */
	private const MAX_DOWNLOADS = 2;

	/** @var array|null Items with their buttons, see ready_items(). */
	private $ready = null;

	/**
	 * @param \WC_Order $order    Order.
	 * @param array     $settings Thank-you page settings.
	 */
	public function __construct( \WC_Order $order, array $settings ) {
		$this->order    = $order;
		$this->settings = $settings;
		$this->owner    = is_user_logged_in() && $order->get_user_id() === get_current_user_id();
	}

	/**
	 * Everything views/thankyou.php prints. Texts are plain unless the key
	 * says `html` (WooCommerce's price and payment method markup, or a lead
	 * changed by a gateway through WooCommerce's filter).
	 */
	public function view(): array {
		$state   = $this->state();
		$digital = self::is_digital( $this->order );
		$done    = in_array( $state, array( 'paid', 'confirmed' ), true );

		return array(
			'state'     => $state,
			'chip'      => $this->chip( $state ),
			'title'     => $this->title( $state ),
			'lead_html' => $this->lead( $state, $digital ),
			'email'     => $this->owner && 'failed' !== $state ? $this->order->get_billing_email() : '',
			'note'      => 'failed' !== $state ? (string) $this->settings['note'] : '',
			'facts'     => $this->facts(),
			'actions'   => $this->actions( $state, $digital ),
			'ready'     => $done ? $this->ready_items() : array(),
			'next'      => 'failed' !== $state && ! empty( $this->settings['next'] ) ? $this->next_steps( $state, $digital ) : array(),
			'celebrate' => $done && ! empty( $this->settings['celebrate'] ),
		);
	}

	/**
	 * Whether nothing in the order is sent by post, which decides the wording
	 * and the steps: no shipping method was chosen (stores that sell only
	 * courses often switch shipping off, so their courses need not be marked
	 * virtual), or every item is digital (is_digital_product()).
	 *
	 * @param \WC_Order $order Order.
	 */
	public static function is_digital( \WC_Order $order ): bool {
		if ( ! $order->get_shipping_methods() ) {
			return true;
		}

		foreach ( $order->get_items() as $item ) {
			$product = $item instanceof \WC_Order_Item_Product ? $item->get_product() : null;
			if ( $product && ! self::is_digital_product( $product ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * A product that is opened, not shipped: virtual or downloadable, a
	 * Studiare course (its switch, or lessons: the switch is often left off),
	 * or whatever another module says through `studiare_ext_is_digital_product`
	 * (the audio module's products).
	 *
	 * @param \WC_Product $product Product or variation.
	 */
	private static function is_digital_product( \WC_Product $product ): bool {
		$id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		if ( $product->is_virtual() || $product->is_downloadable() || Theme_Bridge::is_course( $id ) || Theme_Bridge::has_lessons( $id ) ) {
			return true;
		}

		/**
		 * Filters whether a product is opened on the site rather than shipped (thank-you page wording and steps).
		 *
		 * @param bool        $digital Detected value.
		 * @param \WC_Product $product Product or variation.
		 */
		return (bool) apply_filters( 'studiare_ext_is_digital_product', false, $product );
	}

	private function state(): string {
		if ( $this->order->has_status( 'failed' ) ) {
			return 'failed';
		}
		if ( ! $this->order->is_paid() ) {
			return 'waiting';
		}

		return 'cod' === $this->order->get_payment_method() ? 'confirmed' : 'paid';
	}

	/**
	 * @param string $state Order state.
	 */
	private function chip( string $state ): string {
		$chips = array(
			'paid'      => __( 'Payment successful', 'studiare-extensions' ),
			'confirmed' => __( 'Order confirmed', 'studiare-extensions' ),
			'waiting'   => __( 'Awaiting payment', 'studiare-extensions' ),
			'failed'    => __( 'Payment failed', 'studiare-extensions' ),
		);

		return $chips[ $state ];
	}

	/**
	 * @param string $state Order state.
	 */
	private function title( string $state ): string {
		if ( 'failed' === $state ) {
			return __( 'Your payment did not go through', 'studiare-extensions' );
		}

		$name = trim( $this->order->get_billing_first_name() );

		/* translators: %s: the customer's first name. */
		return '' !== $name ? sprintf( __( 'Thank you, %s!', 'studiare-extensions' ), $name ) : __( 'Thank you for your order!', 'studiare-extensions' );
	}

	/**
	 * The sentence under the title. It passes through WooCommerce's own
	 * filters, so gateways that add a tracking code or replace the message
	 * keep doing so.
	 *
	 * @param string $state   Order state.
	 * @param bool   $digital Whether the order holds only courses and downloads.
	 */
	private function lead( string $state, bool $digital ): string {
		if ( 'failed' === $state ) {
			$text = esc_html__( 'The payment was not completed. If any amount was taken from your account, the bank returns it within 72 hours. Your order is kept, so you can try again.', 'studiare-extensions' );

			return (string) apply_filters( 'woocommerce_thankyou_order_failed_text', $text, $this->order ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's hook.
		}

		$leads = array(
			'paid'      => $digital
				? __( 'Your purchase is ready in your account. You can start right now.', 'studiare-extensions' )
				: __( 'We have received your payment and are getting your order ready.', 'studiare-extensions' ),
			'confirmed' => $digital
				? __( 'Your order is confirmed and ready in your account. You pay later, in the way you chose.', 'studiare-extensions' )
				: __( 'Your order is confirmed. You pay when it is delivered.', 'studiare-extensions' ),
			'waiting'   => $digital
				? __( 'Your order is saved. Your courses open as soon as the payment is confirmed.', 'studiare-extensions' )
				: __( 'Your order is saved. We send it as soon as the payment is confirmed.', 'studiare-extensions' ),
		);

		return (string) apply_filters( 'woocommerce_thankyou_order_received_text', esc_html( $leads[ $state ] ), $this->order ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's hook.
	}

	/**
	 * Order number, date, total and payment method; `html` values are
	 * WooCommerce's markup, the others plain text.
	 *
	 * @return array<int, array{label: string, html: string}>
	 */
	private function facts(): array {
		$persian = Persian::is_site_persian();
		$number  = (string) $this->order->get_order_number();
		$date    = $this->order->get_date_created() ? wc_format_datetime( $this->order->get_date_created() ) : '';
		$facts   = array(
			array(
				'label' => __( 'Order number', 'studiare-extensions' ),
				'html'  => esc_html( $persian ? Persian::digits( $number ) : $number ),
			),
			array(
				'label' => __( 'Date', 'studiare-extensions' ),
				'html'  => esc_html( $persian ? Persian::digits( $date ) : $date ),
			),
			array(
				'label' => __( 'Total', 'studiare-extensions' ),
				'html'  => $this->order->get_formatted_order_total(),
			),
		);

		if ( '' !== $this->order->get_payment_method_title() ) {
			$facts[] = array(
				'label' => __( 'Payment method', 'studiare-extensions' ),
				'html'  => wp_kses_post( $this->order->get_payment_method_title() ),
			);
		}

		return array_values(
			array_filter(
				$facts,
				static function ( array $fact ): bool {
					return '' !== $fact['html'];
				}
			)
		);
	}

	/**
	 * One or two buttons, the first one the main way forward: pay again,
	 * the buyer's courses, or the order in their account.
	 *
	 * @param string $state   Order state.
	 * @param bool   $digital Whether the order holds only courses and downloads.
	 * @return array<int, array{label: string, url: string}>
	 */
	private function actions( string $state, bool $digital ): array {
		$shop = self::action( __( 'Continue shopping', 'studiare-extensions' ), (string) apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's hook.

		if ( 'failed' === $state ) {
			$actions = array( self::action( __( 'Try paying again', 'studiare-extensions' ), $this->order->get_checkout_payment_url() ) );
			if ( is_user_logged_in() ) {
				$actions[] = self::action( __( 'My account', 'studiare-extensions' ), wc_get_page_permalink( 'myaccount' ) );
			}

			return $actions;
		}

		if ( ! $this->owner ) {
			return array( $shop );
		}

		$order = self::action( __( 'View order', 'studiare-extensions' ), $this->order->get_view_order_url() );
		if ( 'waiting' !== $state && $digital ) {
			return array( $this->items_action() ?? $this->courses_action(), $order );
		}

		return array( $order, $shop );
	}

	/**
	 * The main button from the ready items: with one item, its course or
	 * player ("Start the course", "Listen"; a download is no main button, its
	 * file name is no label for it); with several that all open in the same
	 * place, that place (a module's `collection`, such as "My audio").
	 */
	private function items_action(): ?array {
		$firsts = array_column( array_column( $this->ready_items(), 'links' ), 0 );
		if ( ! $firsts ) {
			return null;
		}
		if ( 1 === count( $firsts ) && in_array( $firsts[0]['kind'], array( 'course', 'audio' ), true ) ) {
			return self::action( $firsts[0]['label'], $firsts[0]['url'] );
		}

		$collection = $firsts[0]['collection'] ?? null;
		foreach ( $firsts as $link ) {
			if ( ( $link['collection'] ?? null ) !== $collection ) {
				return null;
			}
		}

		return is_array( $collection ) ? self::action( (string) $collection['label'], (string) $collection['url'] ) : null;
	}

	/**
	 * Studiare's "My courses" account tab when the theme has it, otherwise
	 * the account itself.
	 */
	private function courses_action(): array {
		if ( isset( wc_get_account_menu_items()['my-courses'] ) ) {
			return self::action( __( 'Go to my courses', 'studiare-extensions' ), wc_get_account_endpoint_url( 'my-courses' ) );
		}

		return self::action( __( 'Go to my account', 'studiare-extensions' ), wc_get_page_permalink( 'myaccount' ) );
	}

	/**
	 * @param string $label Button text.
	 * @param string $url   Link.
	 */
	private static function action( string $label, string $url ): array {
		return array(
			'label' => $label,
			'url'   => $url,
		);
	}

	/**
	 * The order's products that have something to open, each once
	 * (variations as their product), with their buttons. Download files
	 * come from WooCommerce (only once it permits downloads) and show to
	 * anyone who may see this page, as WooCommerce's own downloads table;
	 * the course and the other modules' links need the buyer's account.
	 *
	 * @return array<int, array{name: string, url: string, image: string, links: array}>
	 */
	private function ready_items(): array {
		if ( null !== $this->ready ) {
			return $this->ready;
		}

		$downloads   = $this->downloads();
		$this->ready = array();

		foreach ( $this->order->get_items() as $item ) {
			$product = $item instanceof \WC_Order_Item_Product ? $item->get_product() : null;
			$parent  = $product && $product->get_parent_id() ? wc_get_product( $product->get_parent_id() ) : $product;
			if ( ! $parent instanceof \WC_Product || isset( $this->ready[ $parent->get_id() ] ) ) {
				continue;
			}

			$links = $this->item_links( $parent, self::is_digital_product( $product ), $downloads[ $parent->get_id() ] ?? array() );
			if ( $links ) {
				$this->ready[ $parent->get_id() ] = array(
					'name'  => $parent->get_name(),
					'url'   => $parent->is_visible() ? $parent->get_permalink() : '',
					'image' => $parent->get_image( 'woocommerce_thumbnail' ),
					'links' => $links,
				);
			}
		}

		$this->ready = array_values( $this->ready );

		return $this->ready;
	}

	/**
	 * Buttons of one product, in this order: the course, other modules'
	 * links (the audio player), its files, and the product page when a
	 * digital product has nothing else.
	 *
	 * The course button needs lessons. A product flagged as a course without
	 * any gets it only when no module opens it elsewhere: an audio product's
	 * lessons are empty, its content is in "My audio".
	 *
	 * @param \WC_Product $product   Product (the parent of a variation).
	 * @param bool        $digital   Whether the bought item is opened rather than shipped.
	 * @param array       $downloads Its download files.
	 * @return array<int, array{kind: string, label: string, url: string}>
	 */
	private function item_links( \WC_Product $product, bool $digital, array $downloads ): array {
		$own = array();
		if ( $this->owner ) {
			/**
			 * Filters the buttons another module adds to a bought product on the Studiare+ thank-you page.
			 *
			 * @param array       $links   Buttons: `kind`, `label`, `url`, and optionally `collection`
			 *                             (`label`, `url`: where all items of this kind are, for the main button).
			 * @param \WC_Product $product Product (the parent of a variation).
			 * @param \WC_Order   $order   Order.
			 */
			$own = (array) apply_filters( 'studiare_ext_thankyou_item_links', array(), $product, $this->order );
		}

		$links  = array();
		$course = Theme_Bridge::has_lessons( $product->get_id() ) || ( ! $own && Theme_Bridge::is_course( $product->get_id() ) );
		if ( $this->owner && $product->is_visible() && $course ) {
			$links[] = self::link( 'course', __( 'Start the course', 'studiare-extensions' ), $product->get_permalink() );
		}

		$links  = array_merge( $links, $own );
		$capped = $this->owner && count( $downloads ) > self::MAX_DOWNLOADS;
		foreach ( $capped ? array_slice( $downloads, 0, self::MAX_DOWNLOADS ) : $downloads as $download ) {
			$links[] = self::link( 'download', $download['download_name'], $download['download_url'] );
		}
		if ( $capped ) {
			/* translators: %s: number of download files. */
			$links[] = self::link( 'downloads', sprintf( __( 'All downloads (%s)', 'studiare-extensions' ), Persian::is_site_persian() ? Persian::number( count( $downloads ) ) : count( $downloads ) ), wc_get_account_endpoint_url( 'downloads' ) );
		}

		if ( ! $links && $this->owner && $digital && $product->is_visible() ) {
			$links[] = self::link( 'view', __( 'View', 'studiare-extensions' ), $product->get_permalink() );
		}

		return array_values(
			array_filter(
				$links,
				static function ( $link ): bool {
					return is_array( $link ) && ! empty( $link['label'] ) && ! empty( $link['url'] );
				}
			)
		);
	}

	/**
	 * The order's download files by product (a variation's under its
	 * product), in WooCommerce's order.
	 *
	 * @return array<int, array<int, array>>
	 */
	private function downloads(): array {
		$files = array();

		foreach ( $this->order->is_download_permitted() ? $this->order->get_downloadable_items() : array() as $download ) {
			$product = wc_get_product( (int) $download['product_id'] );
			$key     = $product && $product->get_parent_id() ? $product->get_parent_id() : (int) $download['product_id'];

			$files[ $key ][] = $download;
		}

		return $files;
	}

	/**
	 * @param string $kind  `course`, `download`, `downloads`, `view`, or a module's own (`audio`).
	 * @param string $label Button text.
	 * @param string $url   Link.
	 */
	private static function link( string $kind, string $label, string $url ): array {
		return array(
			'kind'  => $kind,
			'label' => $label,
			'url'   => $url,
		);
	}

	/**
	 * Three steps from here on: done, the current one, and the one after.
	 *
	 * @param string $state   Order state.
	 * @param bool   $digital Whether the order holds only courses and downloads.
	 * @return array<int, array{title: string, text: string, status: string}>
	 */
	private function next_steps( string $state, bool $digital ): array {
		if ( 'waiting' === $state ) {
			return array(
				self::step( __( 'Order saved', 'studiare-extensions' ), __( 'We have your order and its details.', 'studiare-extensions' ), 'done' ),
				self::step( __( 'Payment confirmation', 'studiare-extensions' ), __( 'We confirm the order once the payment arrives.', 'studiare-extensions' ), 'current' ),
				$digital
					? self::step( __( 'Access to your courses', 'studiare-extensions' ), __( 'Your courses open in your account right after.', 'studiare-extensions' ), 'todo' )
					: self::step( __( 'Packing and sending', 'studiare-extensions' ), __( 'We send your order and let you know.', 'studiare-extensions' ), 'todo' ),
			);
		}

		$first = 'paid' === $state
			? self::step( __( 'Payment confirmed', 'studiare-extensions' ), __( 'Your payment went through.', 'studiare-extensions' ), 'done' )
			: self::step( __( 'Order confirmed', 'studiare-extensions' ), $digital ? __( 'You pay later, in the way you chose.', 'studiare-extensions' ) : __( 'You pay when the order is delivered.', 'studiare-extensions' ), 'done' );

		if ( $digital ) {
			// A guest has no account to find the purchase in, only the order email.
			$second = $this->order->get_user_id()
				? self::step( __( 'Added to your account', 'studiare-extensions' ), __( 'Your purchases are waiting for you in your account.', 'studiare-extensions' ), 'done' )
				: self::step( __( 'Sent to your email', 'studiare-extensions' ), __( 'Your order and its links are in your email.', 'studiare-extensions' ), 'done' );

			return array(
				$first,
				$second,
				self::step( __( 'Start learning', 'studiare-extensions' ), __( 'Watch, listen or download whenever you like, on any device.', 'studiare-extensions' ), 'current' ),
			);
		}

		return array(
			$first,
			self::step( __( 'Packing your order', 'studiare-extensions' ), __( 'We are getting your order ready to send.', 'studiare-extensions' ), 'current' ),
			self::step( __( 'On its way', 'studiare-extensions' ), __( 'You can follow your order in your account.', 'studiare-extensions' ), 'todo' ),
		);
	}

	/**
	 * @param string $title  Step title.
	 * @param string $text   One line about it.
	 * @param string $status `done`, `current` or `todo`.
	 */
	private static function step( string $title, string $text, string $status ): array {
		return array(
			'title'  => $title,
			'text'   => $text,
			'status' => $status,
		);
	}
}
