<?php
/**
 * Contact form: name, email and/or phone, a topic and the message. Messages
 * are saved in Studiare+ → Contact messages and emailed to the address set
 * here (Builder\Contact_Messages); a shortcode from a form plugin can replace
 * the built-in form.
 *
 * The form is a normal POST to admin-post.php, so it works without
 * JavaScript; pages.js sends it in the background instead, marks the fields
 * that need fixing and shows the answer in place. The handler reads this
 * widget's saved options by the document and element IDs in the form.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Assets;
use StudiareExt\Modules\Builder\Contact_Messages;

defined( 'ABSPATH' ) || exit;

final class Contact_Form extends Page_Base {

	public function get_name(): string {
		return Contact_Messages::WIDGET;
	}

	public function get_title(): string {
		return __( 'Contact form', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-form-horizontal';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'form', 'contact', 'message', 'email', 'فرم', 'پیام' ) );
	}

	public function get_script_depends(): array {
		return array( Assets::PAGES_HANDLE );
	}

	protected function register_controls(): void {
		$defaults = Contact_Messages::defaults();
		$modes    = array(
			'required' => __( 'Required', 'studiare-extensions' ),
			'optional' => __( 'Optional', 'studiare-extensions' ),
			'hidden'   => __( 'Hidden', 'studiare-extensions' ),
		);

		$this->start_content_section( 'section_form', __( 'Form', 'studiare-extensions' ) );

		$this->add_control(
			'provider',
			array(
				'label'   => __( 'Form', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'builtin',
				'options' => array(
					'builtin'   => __( 'Studiare+ form', 'studiare-extensions' ),
					'shortcode' => __( 'Another plugin\'s form (shortcode)', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'shortcode',
			array(
				'label'       => __( 'Shortcode', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => '[contact-form-7 id="123"]',
				'label_block' => true,
				'condition'   => array( 'provider' => 'shortcode' ),
			)
		);

		$this->add_control(
			'name_label',
			array(
				'label'     => __( 'Name field', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Your name', 'studiare-extensions' ),
				'separator' => 'before',
				'condition' => array( 'provider' => 'builtin' ),
			)
		);

		$this->add_control(
			'phone_field',
			array(
				'label'     => __( 'Phone field', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => $defaults['phone_field'],
				'options'   => $modes,
				'condition' => array( 'provider' => 'builtin' ),
			)
		);

		$this->add_control(
			'phone_label',
			array(
				'label'     => __( 'Phone field label', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Mobile number', 'studiare-extensions' ),
				'condition' => array(
					'provider'     => 'builtin',
					'phone_field!' => 'hidden',
				),
			)
		);

		$this->add_control(
			'email_field',
			array(
				'label'       => __( 'Email field', 'studiare-extensions' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => $defaults['email_field'],
				'options'     => $modes,
				'description' => __( 'When phone and email are both optional, visitors must fill in at least one, so you can answer.', 'studiare-extensions' ),
				'condition'   => array( 'provider' => 'builtin' ),
			)
		);

		$this->add_control(
			'email_label',
			array(
				'label'     => __( 'Email field label', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Email', 'studiare-extensions' ),
				'condition' => array(
					'provider'     => 'builtin',
					'email_field!' => 'hidden',
				),
			)
		);

		$this->add_control(
			'topic_field',
			array(
				'label'     => __( 'Topic field', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => $defaults['topic_field'],
				'options'   => array(
					'select' => __( 'Choose from a list', 'studiare-extensions' ),
					'text'   => __( 'Free text (optional)', 'studiare-extensions' ),
					'hidden' => __( 'Hidden', 'studiare-extensions' ),
				),
				'condition' => array( 'provider' => 'builtin' ),
			)
		);

		$this->add_control(
			'topic_label',
			array(
				'label'     => __( 'Topic field label', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Topic', 'studiare-extensions' ),
				'condition' => array(
					'provider'     => 'builtin',
					'topic_field!' => 'hidden',
				),
			)
		);

		$this->add_control(
			'topics',
			array(
				'label'       => __( 'Topics', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => $defaults['topics'],
				'rows'        => 5,
				'description' => __( 'One per line. The chosen topic is shown with the message and in the email subject.', 'studiare-extensions' ),
				'condition'   => array(
					'provider'    => 'builtin',
					'topic_field' => 'select',
				),
			)
		);

		$this->add_control(
			'message_label',
			array(
				'label'     => __( 'Message field label', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Your message', 'studiare-extensions' ),
				'condition' => array( 'provider' => 'builtin' ),
			)
		);

		$this->add_control(
			'message_placeholder',
			array(
				'label'     => __( 'Message placeholder', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'How can we help?', 'studiare-extensions' ),
				'condition' => array( 'provider' => 'builtin' ),
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'     => __( 'Button text', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Send message', 'studiare-extensions' ),
				'separator' => 'before',
				'condition' => array( 'provider' => 'builtin' ),
			)
		);

		$this->add_control(
			'note',
			array(
				'label'     => __( 'Text beside the button', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'We usually answer within one working day.', 'studiare-extensions' ),
				'condition' => array( 'provider' => 'builtin' ),
			)
		);

		$this->add_control(
			'success_text',
			array(
				'label'     => __( 'Thank-you message', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXTAREA,
				'rows'      => 2,
				'default'   => __( 'Thank you! Your message was sent. We will get back to you soon.', 'studiare-extensions' ),
				'condition' => array( 'provider' => 'builtin' ),
			)
		);

		$this->end_controls_section();

		$this->start_content_section(
			'section_delivery',
			__( 'Messages', 'studiare-extensions' ),
			array( 'condition' => array( 'provider' => 'builtin' ) )
		);

		$this->add_control(
			'delivery_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Every message is saved in Studiare+ → Contact messages, even when email does not work on your host.', 'studiare-extensions' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_control(
			'notify',
			array(
				'label'   => __( 'Also send each message by email', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => $defaults['notify'],
			)
		);

		$this->add_control(
			'email_to',
			array(
				'label'       => __( 'Send to', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => $defaults['email_to'],
				'placeholder' => (string) get_option( 'admin_email' ),
				'label_block' => true,
				'description' => __( 'Separate several addresses with commas. Empty: the site\'s admin email.', 'studiare-extensions' ),
				'condition'   => array( 'notify' => 'yes' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section(
			'section_style',
			__( 'Form', 'studiare-extensions' ),
			array( 'condition' => array( 'provider' => 'builtin' ) )
		);

		$this->add_control(
			'look',
			array(
				'label'   => __( 'Style', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'card',
				'options' => array(
					'card'  => __( 'Card', 'studiare-extensions' ),
					'plain' => __( 'Fields only (for a box you style)', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'fields',
			array(
				'label'   => __( 'Fields', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'outline',
				'options' => array(
					'outline' => __( 'With border', 'studiare-extensions' ),
					'soft'    => __( 'Soft background', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'two_columns',
			array(
				'label'       => __( 'Short fields side by side', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'Name, phone and email share a row on wide screens. Phones always show one field per row.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'full_button',
			array(
				'label'   => __( 'Full-width button', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => '',
			)
		);

		$this->add_box_style( 'form', '.stx-cform', array( 'shadow' => true ) );
		$this->add_box_style( 'field', '.stx-cform__input', array( 'label' => __( 'Fields', 'studiare-extensions' ) ) );
		$this->add_text_style( 'label', '.stx-cform__label', array( 'label' => __( 'Labels', 'studiare-extensions' ) ) );
		$this->add_control(
			'button_heading',
			array(
				'label'     => __( 'Button', 'studiare-extensions' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_button_style( 'btn', '.stx-cform .stx-btn' );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		if ( 'shortcode' === $s['provider'] ) {
			if ( '' === trim( (string) $s['shortcode'] ) ) {
				$this->editor_hint( __( 'Paste the shortcode of your form plugin\'s form.', 'studiare-extensions' ) );
				return;
			}
			echo '<div class="stx-cform stx-cform--shortcode">' . do_shortcode( shortcode_unautop( (string) $s['shortcode'] ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output.
			return;
		}

		$id      = 'stx-cform-' . $this->get_id();
		$status  = isset( $_GET['stx_contact'] ) ? sanitize_key( wp_unslash( $_GET['stx_contact'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only: the result of a plain form post.
		$notes   = array( 'ok' => (string) $s['success_text'] ) + Contact_Messages::error_messages();
		$reach   = 'optional' === $s['email_field'] && 'optional' === $s['phone_field'];
		$classes = array(
			'stx-cform',
			'stx-cform--' . $s['look'],
			'stx-cform--' . $s['fields'],
			'yes' === $s['two_columns'] ? 'stx-cform--cols' : '',
			'yes' === $s['full_button'] ? 'stx-cform--full-btn' : '',
		);
		?>
		<form class="<?php echo esc_attr( implode( ' ', array_filter( $classes ) ) ); ?>" id="<?php echo esc_attr( $id ); ?>" action="<?php echo esc_url( Contact_Messages::form_url() ); ?>" method="post" data-stx-contact data-success="<?php echo esc_attr( $s['success_text'] ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( Contact_Messages::ACTION ); ?>">
			<input type="hidden" name="document" value="<?php echo esc_attr( (string) self::document_id() ); ?>">
			<input type="hidden" name="form" value="<?php echo esc_attr( $this->get_id() ); ?>">
			<input type="hidden" name="source" value="<?php echo esc_attr( (string) get_queried_object_id() ); ?>">
			<input type="hidden" name="anchor" value="<?php echo esc_attr( $id ); ?>">
			<?php // Bots fill every field; people never see this one. ?>
			<span class="stx-cform__trap" aria-hidden="true"><input type="text" name="<?php echo esc_attr( Contact_Messages::HONEYPOT ); ?>" tabindex="-1" autocomplete="off"></span>

			<div class="stx-cform__grid">
				<?php
				$this->field( $id, 'name', (string) $s['name_label'], 'required', '<input class="stx-cform__input" type="text" maxlength="100" autocomplete="name"{attrs}>' );
				$this->field( $id, 'phone', (string) $s['phone_label'], (string) $s['phone_field'], '<input class="stx-cform__input" type="tel" dir="ltr" inputmode="tel" minlength="7" maxlength="20" autocomplete="tel"{attrs}>', $reach );
				$this->field( $id, 'email', (string) $s['email_label'], (string) $s['email_field'], '<input class="stx-cform__input" type="email" dir="ltr" inputmode="email" autocomplete="email"{attrs}>', $reach );
				$this->topic_field( $id, $s );
				$this->field( $id, 'message', (string) $s['message_label'], 'required', '<textarea class="stx-cform__input" rows="5" minlength="2" maxlength="5000" placeholder="' . esc_attr( $s['message_placeholder'] ) . '"{attrs}></textarea>' );
				?>
			</div>

			<?php if ( $reach ) : ?>
				<p class="stx-cform__hint" id="<?php echo esc_attr( $id ); ?>-reach"><?php esc_html_e( 'Leave a phone number or an email address so we can answer.', 'studiare-extensions' ); ?></p>
			<?php endif; ?>

			<div class="stx-cform__foot">
				<button type="submit" class="stx-btn stx-btn--accent stx-btn--lg"><span><?php echo esc_html( $s['button_text'] ); ?></span></button>
				<?php if ( '' !== trim( (string) $s['note'] ) ) : ?>
					<p class="stx-cform__note"><?php echo esc_html( $s['note'] ); ?></p>
				<?php endif; ?>
			</div>

			<p class="stx-cform__status<?php echo isset( $notes[ $status ] ) ? ( 'ok' === $status ? ' is-ok' : ' is-error' ) : ''; ?>" role="status" aria-live="polite"><?php echo isset( $notes[ $status ] ) ? esc_html( $notes[ $status ] ) : ''; ?></p>
		</form>
		<?php
	}

	/**
	 * One labelled field. Required fields are marked with an asterisk,
	 * optional ones say so in words.
	 *
	 * @param string $form_id Form ID (prefix of the field IDs).
	 * @param string $name    Field name.
	 * @param string $label   Label.
	 * @param string $mode    required|optional|hidden.
	 * @param string $control Control markup (escaped) with `{attrs}` where the id, name and required attributes go.
	 * @param bool   $reach   Whether the field is one of the "phone or email" pair.
	 */
	private function field( string $form_id, string $name, string $label, string $mode, string $control, bool $reach = false ): void {
		if ( 'hidden' === $mode ) {
			return;
		}

		$field_id = $form_id . '-' . $name;
		$attrs    = sprintf( ' id="%1$s" name="%2$s"', esc_attr( $field_id ), esc_attr( $name ) );
		$attrs   .= 'required' === $mode ? ' required' : '';
		$attrs   .= $reach ? sprintf( ' aria-describedby="%s-reach"', esc_attr( $form_id ) ) : '';
		$wide     = in_array( $name, array( 'message', 'topic' ), true );

		printf(
			'<p class="stx-cform__field%1$s"><label class="stx-cform__label" for="%2$s">%3$s%4$s</label>%5$s</p>',
			$wide ? ' stx-cform__field--wide' : '',
			esc_attr( $field_id ),
			esc_html( $label ),
			'required' === $mode
				? ' <span class="stx-cform__req" aria-hidden="true">*</span>'
				: ' <span class="stx-cform__optional">' . esc_html__( '(optional)', 'studiare-extensions' ) . '</span>',
			str_replace( '{attrs}', $attrs, $control ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped markup and attributes.
		);
	}

	/**
	 * Topic: a list to choose from, or free text.
	 *
	 * @param string $form_id Form ID.
	 * @param array  $s       Settings.
	 */
	private function topic_field( string $form_id, array $s ): void {
		if ( 'text' === $s['topic_field'] ) {
			$this->field( $form_id, 'topic', (string) $s['topic_label'], 'optional', '<input class="stx-cform__input" type="text" maxlength="150"{attrs}>' );
			return;
		}

		$topics = Contact_Messages::topic_list( (string) $s['topics'] );
		if ( 'select' !== $s['topic_field'] || ! $topics ) {
			return;
		}

		$options = '<option value="">' . esc_html__( 'Choose…', 'studiare-extensions' ) . '</option>';
		foreach ( $topics as $topic ) {
			$options .= sprintf( '<option value="%1$s">%1$s</option>', esc_attr( $topic ) );
		}

		$this->field( $form_id, 'topic', (string) $s['topic_label'], 'required', '<span class="stx-cform__select"><select class="stx-cform__input"{attrs}>' . $options . '</select></span>' );
	}

	/**
	 * The Elementor document this widget is saved in: the page, or the
	 * template (header, footer…) that holds the form.
	 */
	private static function document_id(): int {
		$document = \Elementor\Plugin::$instance->documents->get_current();

		return $document ? (int) $document->get_main_id() : (int) get_the_ID();
	}
}
