<?php
/**
 * Copyright line with {year} (Solar Hijri), {gyear} (Gregorian) and {site}.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Core\Persian;

defined( 'ABSPATH' ) || exit;

final class Copyright extends Base {

	public function get_name(): string {
		return 'stx-copyright';
	}

	public function get_title(): string {
		return __( 'Copyright', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-footer';
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Copyright', 'studiare-extensions' ) );

		$this->add_control(
			'text',
			array(
				'label'       => __( 'Text', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				/* translators: keep {year} and {site}. */
				'default'     => __( '© {year} {site}. All rights reserved.', 'studiare-extensions' ),
				'label_block' => true,
				'description' => __( '{year} = current Solar Hijri year, {gyear} = Gregorian year, {site} = site name.', 'studiare-extensions' ),
			)
		);

		$this->add_align_control( 'align', '.stx-copyright' );
		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Text', 'studiare-extensions' ) );
		$this->add_text_style( 'text', '.stx-copyright' );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s    = $this->get_settings_for_display();
		$text = strtr(
			(string) $s['text'],
			array(
				'{year}'  => self::digits( (string) Persian::jalali_year() ),
				'{gyear}' => self::digits( wp_date( 'Y' ) ),
				'{site}'  => get_bloginfo( 'name' ),
			)
		);

		echo '<p class="stx-copyright">' . esc_html( $text ) . '</p>';
	}
}
