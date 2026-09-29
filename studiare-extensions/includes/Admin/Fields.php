<?php
/**
 * Declarative form controls for module panels.
 *
 * Every control carries `data-stx-bind="<dot.path>"`; admin.js reads the
 * module settings, fills the controls and writes changes back by path, so
 * module views only describe *which* settings exist — no per-field JS.
 *
 * Shared `$args`:
 *   - help    (string) helper text under the control
 *   - show_if (string) conditional display, e.g. `style=floating|pill`, `!behavior.haptic`
 *
 * @package StudiareExt
 */

namespace StudiareExt\Admin;

defined( 'ABSPATH' ) || exit;

final class Fields {

	/**
	 * On/off switch rendered as a full-width row.
	 *
	 * @param string $path  Settings path.
	 * @param string $label Field label.
	 * @param array  $args  Shared args.
	 */
	public static function toggle( string $path, string $label, array $args = array() ): void {
		$id = self::id( $path );
		self::open( 'toggle', $args );
		?>
		<label class="stx-switch-row" for="<?php echo esc_attr( $id ); ?>">
			<span class="stx-switch-row__text">
				<span class="stx-field__label"><?php echo esc_html( $label ); ?></span>
				<?php self::help( $args ); ?>
			</span>
			<span class="stx-switch">
				<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" data-stx-bind="<?php echo esc_attr( $path ); ?>">
				<span class="stx-switch__track" aria-hidden="true"></span>
			</span>
		</label>
		<?php
		self::close();
	}

	/**
	 * @param string                $path    Settings path.
	 * @param string                $label   Field label.
	 * @param array<string, string> $options Value => label.
	 * @param array                 $args    Shared args.
	 */
	public static function select( string $path, string $label, array $options, array $args = array() ): void {
		$id = self::id( $path );
		self::open( 'select', $args );
		self::label( $id, $label );
		?>
		<div class="stx-select">
			<select id="<?php echo esc_attr( $id ); ?>" data-stx-bind="<?php echo esc_attr( $path ); ?>">
				<?php foreach ( $options as $value => $option_label ) : ?>
					<option value="<?php echo esc_attr( (string) $value ); ?>"><?php echo esc_html( $option_label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
		self::help( $args );
		self::close();
	}

	/**
	 * Segmented control (radio group) for a handful of options.
	 *
	 * @param string                $path    Settings path.
	 * @param string                $label   Field label.
	 * @param array<string, string> $options Value => label.
	 * @param array                 $args    Shared args.
	 */
	public static function segmented( string $path, string $label, array $options, array $args = array() ): void {
		$name = self::id( $path );
		self::open( 'segmented', $args );
		?>
		<span class="stx-field__label" id="<?php echo esc_attr( $name ); ?>-label"><?php echo esc_html( $label ); ?></span>
		<div class="stx-segmented" role="radiogroup" aria-labelledby="<?php echo esc_attr( $name ); ?>-label">
			<?php foreach ( $options as $value => $option_label ) : ?>
				<label class="stx-segmented__option">
					<input type="radio" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" data-stx-bind="<?php echo esc_attr( $path ); ?>">
					<span><?php echo esc_html( $option_label ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
		<?php
		self::help( $args );
		self::close();
	}

	/**
	 * Slider with a live value read-out.
	 *
	 * @param string    $path  Settings path.
	 * @param string    $label Field label.
	 * @param int|float $min   Minimum.
	 * @param int|float $max   Maximum.
	 * @param int|float $step  Step.
	 * @param string    $unit  Unit shown after the value (e.g. `px`).
	 * @param array     $args  Shared args.
	 */
	public static function range( string $path, string $label, $min, $max, $step = 1, string $unit = 'px', array $args = array() ): void {
		$id = self::id( $path );
		self::open( 'range', $args );
		?>
		<div class="stx-range__head">
			<?php self::label( $id, $label ); ?>
			<output class="stx-range__value" for="<?php echo esc_attr( $id ); ?>"><span data-stx-output="<?php echo esc_attr( $path ); ?>"></span><?php echo esc_html( $unit ); ?></output>
		</div>
		<input type="range" class="stx-range" id="<?php echo esc_attr( $id ); ?>" min="<?php echo esc_attr( (string) $min ); ?>" max="<?php echo esc_attr( (string) $max ); ?>" step="<?php echo esc_attr( (string) $step ); ?>" data-stx-bind="<?php echo esc_attr( $path ); ?>" data-stx-type="number">
		<?php
		self::help( $args );
		self::close();
	}

	/**
	 * Text input.
	 *
	 * @param string $path  Settings path.
	 * @param string $label Field label.
	 * @param array  $args  Shared args plus `placeholder`, `type` (text|number|url) and `dir` (ltr for codes/URLs).
	 */
	public static function text( string $path, string $label, array $args = array() ): void {
		$id   = self::id( $path );
		$type = $args['type'] ?? 'text';
		self::open( 'text', $args );
		self::label( $id, $label );
		?>
		<input
			type="<?php echo esc_attr( $type ); ?>"
			class="stx-input"
			id="<?php echo esc_attr( $id ); ?>"
			data-stx-bind="<?php echo esc_attr( $path ); ?>"
			<?php echo 'number' === $type ? 'data-stx-type="number"' : ''; ?>
			<?php echo isset( $args['dir'] ) ? 'dir="' . esc_attr( $args['dir'] ) . '"' : ''; ?>
			placeholder="<?php echo esc_attr( $args['placeholder'] ?? '' ); ?>"
		>
		<?php
		self::help( $args );
		self::close();
	}

	/**
	 * Colour picker with a "use theme default" state. admin.js builds the
	 * widget inside the placeholder element.
	 *
	 * @param string $path     Settings path.
	 * @param string $label    Field label.
	 * @param string $fallback CSS colour shown when the value is empty (the theme default).
	 * @param array  $args     Shared args.
	 */
	public static function color( string $path, string $label, string $fallback, array $args = array() ): void {
		self::open( 'color', $args );
		?>
		<span class="stx-field__label"><?php echo esc_html( $label ); ?></span>
		<div class="stx-color" data-stx-color="<?php echo esc_attr( $path ); ?>" data-fallback="<?php echo esc_attr( $fallback ); ?>"></div>
		<?php
		self::help( $args );
		self::close();
	}

	/**
	 * @param string $type Field type modifier.
	 * @param array  $args Shared args.
	 */
	private static function open( string $type, array $args ): void {
		printf(
			'<div class="stx-field stx-field--%s"%s>',
			esc_attr( $type ),
			empty( $args['show_if'] ) ? '' : ' data-stx-show-if="' . esc_attr( $args['show_if'] ) . '"'
		);
	}

	private static function close(): void {
		echo '</div>';
	}

	/**
	 * @param string $id    Control id.
	 * @param string $label Label text.
	 */
	private static function label( string $id, string $label ): void {
		printf( '<label class="stx-field__label" for="%s">%s</label>', esc_attr( $id ), esc_html( $label ) );
	}

	/**
	 * @param array $args Shared args.
	 */
	private static function help( array $args ): void {
		if ( ! empty( $args['help'] ) ) {
			printf( '<p class="stx-field__help">%s</p>', esc_html( $args['help'] ) );
		}
	}

	/**
	 * @param string $path Settings path.
	 */
	private static function id( string $path ): string {
		return 'stx-f-' . str_replace( '.', '-', $path );
	}
}
