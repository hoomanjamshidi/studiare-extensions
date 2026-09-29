<?php
/**
 * Countdown to the end of an offer: a fixed date, or the end of every day or
 * week (a "weekly offer" that never needs updating).
 *
 * The end time is worked out in the site's time zone and printed as a
 * timestamp; home.js ticks it down, and for repeating offers moves on to the
 * next period when a cached page outlives the current one.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Countdown extends Home_Base {

	public function get_name(): string {
		return 'stx-countdown';
	}

	public function get_title(): string {
		return __( 'Countdown', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-countdown';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Countdown', 'studiare-extensions' ) );

		$this->add_control(
			'mode',
			array(
				'label'   => __( 'Counts down to', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'weekly',
				'options' => array(
					'weekly' => __( 'The end of every week', 'studiare-extensions' ),
					'daily'  => __( 'The end of every day', 'studiare-extensions' ),
					'date'   => __( 'A date', 'studiare-extensions' ),
				),
			)
		);

		$days = array( 'auto' => __( 'Last day of the week (Settings → General)', 'studiare-extensions' ) );
		foreach ( array( 6, 0, 1, 2, 3, 4, 5 ) as $day ) {
			$days[ (string) $day ] = $GLOBALS['wp_locale']->get_weekday( $day );
		}

		$this->add_control(
			'weekday',
			array(
				'label'     => __( 'Week ends on', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'auto',
				'options'   => $days,
				'condition' => array( 'mode' => 'weekly' ),
			)
		);

		$this->add_control(
			'end_date',
			array(
				'label'       => __( 'Ends at', 'studiare-extensions' ),
				'type'        => Controls_Manager::DATE_TIME,
				'default'     => '',
				'description' => __( 'In the site\'s time zone (Settings → General).', 'studiare-extensions' ),
				'condition'   => array( 'mode' => 'date' ),
			)
		);

		$this->add_control(
			'expired',
			array(
				'label'     => __( 'After the date', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'hide',
				'options'   => array(
					'hide' => __( 'Hide the countdown', 'studiare-extensions' ),
					'zero' => __( 'Show zeros', 'studiare-extensions' ),
					'text' => __( 'Show a message', 'studiare-extensions' ),
				),
				'condition' => array( 'mode' => 'date' ),
			)
		);

		$this->add_control(
			'expired_text',
			array(
				'label'     => __( 'Message', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'This offer has ended.', 'studiare-extensions' ),
				'condition' => array(
					'mode'    => 'date',
					'expired' => 'text',
				),
			)
		);

		$this->add_control(
			'labels_heading',
			array(
				'label'     => __( 'Labels', 'studiare-extensions' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		foreach ( self::units() as $unit => $label ) {
			$this->add_control(
				'label_' . $unit,
				array(
					'label'   => $label,
					'type'    => Controls_Manager::TEXT,
					'default' => $label,
				)
			);
		}

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Boxes', 'studiare-extensions' ) );
		$this->add_gap_control( '.stx-countdown' );
		$this->add_box_style( 'unit', '.stx-countdown__unit' );
		$this->add_text_style( 'number', '.stx-countdown__num', array( 'label' => __( 'Numbers', 'studiare-extensions' ) ) );
		$this->add_text_style( 'label', '.stx-countdown__label', array( 'label' => __( 'Labels', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s   = $this->get_settings_for_display();
		$now = current_datetime();
		$end = $this->end_time( $s, $now );

		if ( ! $end ) {
			$this->editor_hint( __( 'Pick the end date of the offer.', 'studiare-extensions' ) );
			return;
		}

		$left = max( 0, $end->getTimestamp() - $now->getTimestamp() );

		if ( ! $left && 'date' === $s['mode'] ) {
			if ( 'text' === $s['expired'] ) {
				echo '<p class="stx-countdown__ended">' . esc_html( $s['expired_text'] ) . '</p>';
				return;
			}
			if ( 'hide' === $s['expired'] ) {
				$this->editor_hint( __( 'The offer has ended, so the countdown is hidden on the site.', 'studiare-extensions' ) );
				return;
			}
		}

		$periods = array(
			'weekly' => WEEK_IN_SECONDS,
			'daily'  => DAY_IN_SECONDS,
		);

		$labels = array();
		foreach ( array_keys( self::units() ) as $unit ) {
			$labels[ $unit ] = (string) $s[ 'label_' . $unit ];
		}

		echo self::markup( $end->getTimestamp(), $left, $periods[ $s['mode'] ] ?? 0, $labels ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in markup().
	}

	/**
	 * Countdown markup that home.js ticks down (also used by the product spotlight).
	 *
	 * @param int   $end    End time (Unix timestamp).
	 * @param int   $left   Seconds left now.
	 * @param int   $period Repeat period in seconds (0 for a fixed date).
	 * @param array $labels Unit (days, hours, minutes, seconds) → label.
	 */
	public static function markup( int $end, int $left, int $period, array $labels ): string {
		$values = array(
			'days'    => intdiv( $left, DAY_IN_SECONDS ),
			'hours'   => intdiv( $left % DAY_IN_SECONDS, HOUR_IN_SECONDS ),
			'minutes' => intdiv( $left % HOUR_IN_SECONDS, MINUTE_IN_SECONDS ),
			'seconds' => $left % MINUTE_IN_SECONDS,
		);

		$html = sprintf(
			'<div class="stx-countdown" data-stx-countdown data-end="%1$s" data-period="%2$s" role="timer" aria-label="%3$s">',
			esc_attr( (string) ( $end * 1000 ) ),
			esc_attr( (string) ( $period * 1000 ) ),
			esc_attr__( 'Time left', 'studiare-extensions' )
		);

		foreach ( $values as $unit => $value ) {
			$html .= sprintf(
				'<span class="stx-countdown__unit"><b class="stx-countdown__num" data-unit="%1$s">%2$s</b><span class="stx-countdown__label">%3$s</span></span>',
				esc_attr( $unit ),
				esc_html( self::digits( 'days' === $unit ? (string) $value : str_pad( (string) $value, 2, '0', STR_PAD_LEFT ) ) ),
				esc_html( (string) ( $labels[ $unit ] ?? '' ) )
			);
		}

		return $html . '</div>';
	}


	/**
	 * End of the current period, or the chosen date.
	 *
	 * @param array              $s   Settings.
	 * @param \DateTimeImmutable $now Now, in the site's time zone.
	 */
	private function end_time( array $s, \DateTimeImmutable $now ): ?\DateTimeImmutable {
		if ( 'date' === $s['mode'] ) {
			if ( '' === trim( (string) $s['end_date'] ) ) {
				return null;
			}
			try {
				return new \DateTimeImmutable( (string) $s['end_date'], wp_timezone() );
			} catch ( \Exception $e ) {
				return null;
			}
		}

		$end = $now->setTime( 23, 59, 59 );

		if ( 'weekly' === $s['mode'] ) {
			// The week ends the day before it starts again.
			$last = 'auto' === $s['weekday'] ? ( (int) get_option( 'start_of_week', 0 ) + 6 ) % 7 : (int) $s['weekday'];
			$end  = $end->modify( '+' . ( ( $last - (int) $now->format( 'w' ) + 7 ) % 7 ) . ' days' );
		}

		return $end;
	}

	/** @return array<string, string> Unit → default label. */
	public static function units(): array {
		return array(
			'days'    => __( 'Days', 'studiare-extensions' ),
			'hours'   => __( 'Hours', 'studiare-extensions' ),
			'minutes' => __( 'Minutes', 'studiare-extensions' ),
			'seconds' => __( 'Seconds', 'studiare-extensions' ),
		);
	}
}
