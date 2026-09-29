<?php
/**
 * People: portrait cards with name and role over the picture. Shows
 * Studiare's teachers (the `teacher` post type with its job title), or a
 * hand-made list. With no teachers yet, the hand-made list is shown, so the
 * section never disappears from a new site.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use StudiareExt\Modules\Builder\Elementor\Cards;

defined( 'ABSPATH' ) || exit;

final class People extends Home_Base {

	/** Studiare's teacher post type and job title field. */
	private const TEACHER_TYPE = 'teacher';

	private const TEACHER_ROLE = '_studiare_teacher_job_title';

	public function get_name(): string {
		return 'stx-people';
	}

	public function get_title(): string {
		return __( 'Teachers & team', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-person';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'People', 'studiare-extensions' ) );

		$this->add_control(
			'source',
			array(
				'label'   => __( 'Who', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'teachers',
				'options' => array(
					'teachers' => __( 'Studiare teachers', 'studiare-extensions' ),
					'manual'   => __( 'My own list', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'count',
			array(
				'label'     => __( 'Number of teachers', 'studiare-extensions' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 4,
				'min'       => 1,
				'max'       => 12,
				'condition' => array( 'source' => 'teachers' ),
			)
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'photo',
			array(
				'label' => __( 'Photo', 'studiare-extensions' ),
				'type'  => Controls_Manager::MEDIA,
			)
		);
		$repeater->add_control(
			'name',
			array(
				'label'   => __( 'Name', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Name', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'role',
			array(
				'label'   => __( 'Role', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'   => __( 'Link', 'studiare-extensions' ),
				'type'    => Controls_Manager::URL,
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => __( 'People', 'studiare-extensions' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array( 'name' => __( 'Name', 'studiare-extensions' ) ),
					array( 'name' => __( 'Name', 'studiare-extensions' ) ),
					array( 'name' => __( 'Name', 'studiare-extensions' ) ),
					array( 'name' => __( 'Name', 'studiare-extensions' ) ),
				),
				'title_field' => '{{{ name }}}',
				'description' => __( 'With "Studiare teachers", this list is shown until you add teachers.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'ratio',
			array(
				'label'   => __( 'Picture shape', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '3/4',
				'options' => Product_Grid::ratio_options(),
			)
		);

		$this->add_columns_control( '.stx-people', array( 4, 2, 2 ) );

		$this->add_control(
			'mobile_scroll',
			array(
				'label'       => __( 'Swipe row on phones', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'Cards sit side by side and scroll sideways instead of stacking.', 'studiare-extensions' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Cards', 'studiare-extensions' ) );
		$this->add_gap_control( '.stx-people' );
		$this->add_text_style( 'name', '.stx-person__name', array( 'label' => __( 'Name', 'studiare-extensions' ) ) );
		$this->add_text_style( 'role', '.stx-person__role', array( 'label' => __( 'Role', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s      = $this->get_settings_for_display();
		$people = 'teachers' === $s['source'] ? $this->teachers( max( 1, min( 12, (int) $s['count'] ) ) ) : array();

		if ( ! $people ) {
			$people = $this->manual( (array) $s['items'] );
		}

		if ( ! $people ) {
			return;
		}

		printf( '<div class="stx-people%1$s" style="--stx-ratio:%2$s">', 'yes' === $s['mobile_scroll'] ? ' stx-swipe' : '', esc_attr( $s['ratio'] ) );

		foreach ( $people as $person ) {
			$tag   = '' !== $person['url'] ? 'a' : 'div';
			$href  = '' !== $person['url'] ? ' href="' . esc_url( $person['url'] ) . '"' : '';
			$photo = '' !== $person['photo'] ? $person['photo'] : Cards::placeholder( '', 'user' );

			printf(
				'<%1$s class="stx-person"%2$s>%3$s<span class="stx-person__text"><span class="stx-person__name">%4$s</span>%5$s</span></%1$s>',
				esc_html( $tag ),
				$href, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				$photo, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup or placeholder.
				esc_html( $person['name'] ),
				'' !== $person['role'] ? '<span class="stx-person__role">' . esc_html( $person['role'] ) . '</span>' : ''
			);
		}

		echo '</div>';
	}

	/**
	 * @param int $count Number of teachers.
	 * @return array<int, array{name:string, role:string, url:string, photo:string}>
	 */
	private function teachers( int $count ): array {
		if ( ! post_type_exists( self::TEACHER_TYPE ) ) {
			return array();
		}

		$people = array();
		foreach ( get_posts(
			array(
				'post_type'      => self::TEACHER_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => $count,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'date'       => 'DESC',
				),
				'no_found_rows'  => true,
			)
		) as $teacher ) {
			$people[] = array(
				'name'  => get_the_title( $teacher ),
				'role'  => (string) get_post_meta( $teacher->ID, self::TEACHER_ROLE, true ),
				'url'   => (string) get_permalink( $teacher ),
				'photo' => has_post_thumbnail( $teacher ) ? get_the_post_thumbnail(
					$teacher,
					'medium_large',
					array(
						'class'   => 'stx-person__img',
						'loading' => 'lazy',
						'alt'     => '',
					)
				) : '',
			);
		}

		return $people;
	}

	/**
	 * @param array $items Repeater rows.
	 * @return array<int, array{name:string, role:string, url:string, photo:string}>
	 */
	private function manual( array $items ): array {
		$people = array();

		foreach ( $items as $item ) {
			$people[] = array(
				'name'  => (string) $item['name'],
				'role'  => (string) $item['role'],
				'url'   => (string) ( $item['link']['url'] ?? '' ),
				'photo' => ! empty( $item['photo']['id'] ) ? wp_get_attachment_image(
					(int) $item['photo']['id'],
					'medium_large',
					false,
					array(
						'class'   => 'stx-person__img',
						'loading' => 'lazy',
						'alt'     => '',
					)
				) : '',
			);
		}

		return $people;
	}
}
