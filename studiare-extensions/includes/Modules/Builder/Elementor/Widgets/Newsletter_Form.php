<?php
/**
 * Newsletter sign-up form. The built-in list (Builder\Newsletter) works
 * without any setup; a shortcode from a newsletter plugin can replace it.
 *
 * The form is a normal POST to admin-post.php, so it works without
 * JavaScript; home.js sends it in the background instead and shows the
 * answer in place.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Newsletter;

defined( 'ABSPATH' ) || exit;

final class Newsletter_Form extends Home_Base {

	public function get_name(): string {
		return 'stx-newsletter';
	}

	public function get_title(): string {
		return __( 'Newsletter form', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-mail';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'newsletter', 'email', 'subscribe', 'form' ) );
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Form', 'studiare-extensions' ) );

		$this->add_control(
			'provider',
			array(
				'label'       => __( 'Save emails in', 'studiare-extensions' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'builtin',
				'options'     => array(
					'builtin'   => __( 'Studiare+ list (download as CSV)', 'studiare-extensions' ),
					'shortcode' => __( 'Another plugin\'s form (shortcode)', 'studiare-extensions' ),
				),
				'description' => __( 'Download the list in Studiare+ → Page templates → Pages.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'shortcode',
			array(
				'label'       => __( 'Shortcode', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => '[mc4wp_form id="123"]',
				'label_block' => true,
				'condition'   => array( 'provider' => 'shortcode' ),
			)
		);

		$this->add_control(
			'placeholder',
			array(
				'label'     => __( 'Field placeholder', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Your email address', 'studiare-extensions' ),
				'condition' => array( 'provider' => 'builtin' ),
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'     => __( 'Button text', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Subscribe', 'studiare-extensions' ),
				'condition' => array( 'provider' => 'builtin' ),
			)
		);

		$this->add_control(
			'success_text',
			array(
				'label'     => __( 'Thank-you message', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Thank you! You are on the list.', 'studiare-extensions' ),
				'condition' => array( 'provider' => 'builtin' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section(
			'section_style',
			__( 'Form', 'studiare-extensions' ),
			array( 'condition' => array( 'provider' => 'builtin' ) )
		);
		$this->add_box_style( 'field', '.stx-news__input', array( 'label' => __( 'Field', 'studiare-extensions' ) ) );
		$this->add_control(
			'button_heading',
			array(
				'label'     => __( 'Button', 'studiare-extensions' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_button_style( 'btn', '.stx-news .stx-btn' );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		if ( 'shortcode' === $s['provider'] ) {
			if ( '' === trim( (string) $s['shortcode'] ) ) {
				$this->editor_hint( __( 'Paste the shortcode of your newsletter plugin\'s form.', 'studiare-extensions' ) );
				return;
			}
			echo '<div class="stx-news stx-news--shortcode">' . do_shortcode( shortcode_unautop( (string) $s['shortcode'] ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output.
			return;
		}

		$id     = 'stx-news-' . $this->get_id();
		$status = isset( $_GET['stx_news'] ) ? sanitize_key( wp_unslash( $_GET['stx_news'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only: the result of a plain form post.
		$notes  = array(
			'ok'      => (string) $s['success_text'],
			'invalid' => __( 'Please enter a valid email address.', 'studiare-extensions' ),
			'busy'    => __( 'Too many attempts. Please try again in a few minutes.', 'studiare-extensions' ),
			'error'   => __( 'That did not work. Please try again.', 'studiare-extensions' ),
		);
		?>
		<form class="stx-news" id="<?php echo esc_attr( $id ); ?>" action="<?php echo esc_url( Newsletter::form_url() ); ?>" method="post" data-stx-newsletter data-success="<?php echo esc_attr( $s['success_text'] ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( Newsletter::ACTION ); ?>">
			<input type="hidden" name="source" value="<?php echo esc_attr( (string) get_queried_object_id() ); ?>">
			<input type="hidden" name="anchor" value="<?php echo esc_attr( $id ); ?>">
			<?php // Bots fill every field; people never see this one. ?>
			<span class="stx-news__trap" aria-hidden="true"><input type="text" name="<?php echo esc_attr( Newsletter::HONEYPOT ); ?>" tabindex="-1" autocomplete="off"></span>
			<label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>-email"><?php esc_html_e( 'Email', 'studiare-extensions' ); ?></label>
			<input class="stx-news__input" id="<?php echo esc_attr( $id ); ?>-email" type="email" name="email" required autocomplete="email" inputmode="email" placeholder="<?php echo esc_attr( $s['placeholder'] ); ?>">
			<button type="submit" class="stx-btn stx-btn--accent"><span><?php echo esc_html( $s['button_text'] ); ?></span></button>
			<p class="stx-news__note<?php echo isset( $notes[ $status ] ) && 'ok' !== $status ? ' is-error' : ''; ?>" role="status" aria-live="polite"><?php echo isset( $notes[ $status ] ) ? esc_html( $notes[ $status ] ) : ''; ?></p>
		</form>
		<?php
	}
}
