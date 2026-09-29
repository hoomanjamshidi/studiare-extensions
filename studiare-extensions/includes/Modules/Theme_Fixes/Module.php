<?php
/**
 * Theme fixes module: repairs known problems of the Studiare theme, each
 * with its own switch.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Theme_Fixes;

use StudiareExt\Core\Module as Base_Module;
use StudiareExt\Core\Sanitizer;

defined( 'ABSPATH' ) || exit;

final class Module extends Base_Module {

	public function id(): string {
		return 'theme_fixes';
	}

	public function title(): string {
		return __( 'Theme fixes', 'studiare-extensions' );
	}

	public function description(): string {
		return __( 'Repairs known problems of the Studiare theme without editing its files. Each fix can be switched off on its own.', 'studiare-extensions' );
	}

	public function icon(): string {
		return 'wrench';
	}

	public function defaults(): array {
		return Schema::defaults();
	}

	public function sanitize( array $input ): array {
		return Sanitizer::apply( Schema::fields(), $input, Schema::defaults() );
	}

	protected function boot(): void {
		$switches = $this->settings()['fixes'];

		foreach ( Fixes::all() as $id => $fix ) {
			if ( ! empty( $switches[ $id ] ) ) {
				$fix->boot();
			}
		}
	}

	public function render_admin(): void {
		$module = $this;
		require __DIR__ . '/views/admin.php';
	}
}
