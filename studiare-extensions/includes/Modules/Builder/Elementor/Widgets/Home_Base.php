<?php
/**
 * Base for the home page widgets (slides, grids, sections). They add the
 * home stylesheet and script, so pages without them never load those files.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use StudiareExt\Modules\Builder\Assets;

defined( 'ABSPATH' ) || exit;

abstract class Home_Base extends Base {

	public function get_style_depends(): array {
		return array( Assets::HANDLE, Assets::HOME_HANDLE );
	}

	public function get_script_depends(): array {
		return array( Assets::HANDLE, Assets::HOME_HANDLE );
	}
}
