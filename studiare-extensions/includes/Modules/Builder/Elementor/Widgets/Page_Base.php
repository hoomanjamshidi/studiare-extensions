<?php
/**
 * Base for the widgets of the about us and contact us pages (contact form,
 * map, contact details, timeline). They add the pages stylesheet and
 * script, so other pages never load those files.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use StudiareExt\Modules\Builder\Assets;

defined( 'ABSPATH' ) || exit;

abstract class Page_Base extends Base {

	public function get_style_depends(): array {
		return array( Assets::HANDLE, Assets::PAGES_HANDLE );
	}

	/** Only the contact form and the map need the script; they override this. */
	public function get_script_depends(): array {
		return array();
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'about', 'contact', 'درباره', 'تماس' ) );
	}
}
