<?php
/**
 * Border block support flag.
 *
 * @package gutenberg
 */

/**
 * Registers the style attribute used by the border feature if needed for block
 * types that support borders.
 *
 * @param WP_Block_Type $block_type Block Type.
 */
function gutenberg_register_border_support( $block_type ) {
	// Determine if any border related features are supported.
	$has_border_support       = block_has_support( $block_type, array( '__experimentalBorder' ) );
	$has_border_color_support = gutenberg_has_border_feature_support( $block_type, 'color' );

	// Setup attributes and styles within that if needed.
	if ( ! $block_type->attributes ) {
		$block_type->attributes = array();
	}

	if ( $has_border_support && ! array_key_exists( 'style', $block_type->attributes ) ) {
		$block_type->attributes['style'] = array(
			'type' => 'object',
		);
	}

	if ( $has_border_color_support && ! array_key_exists( 'borderColor', $block_type->attributes ) ) {
		$block_type->attributes['borderColor'] = array(
			'type' => 'string',
		);
	}
}

/**
 * Adds CSS classes and inline styles for border styles to the incoming
 * attributes array. This will be applied to the block markup in the front-end.
 *
 * @param WP_Block_Type $block_type       Block type.
 * @param array         $block_attributes Block attributes.
 *
 * @return array Border CSS classes and inline styles.
 */
function gutenberg_apply_border_support( $block_type, $block_attributes ) {
	if ( gutenberg_should_skip_block_supports_serialization( $block_type, 'border' ) ) {
		return array();
	}

	$sides               = array( 'top', 'right', 'bottom', 'left' );
	$border_block_styles = array();

	$has_border_color_support = gutenberg_has_border_feature_support( $block_type, 'color' );
	$has_border_width_support = gutenberg_has_border_feature_support( $block_type, 'width' );

	// Border radius.
	if (
		gutenberg_has_border_feature_support( $block_type, 'radius' ) &&
		isset( $block_attributes['style']['border']['radius'] ) &&
		! gutenberg_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'radius' )
	) {
		$border_radius = $block_attributes['style']['border']['radius'];

		if ( is_numeric( $border_radius ) ) {
			$border_radius .= 'px';
		}

		$border_block_styles['radius'] = $border_radius;
	}

	// Border style.
	if (
		gutenberg_has_border_feature_support( $block_type, 'style' ) &&
		isset( $block_attributes['style']['border']['style'] ) &&
		! gutenberg_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'style' )
	) {
		$border_block_styles['style'] = $block_attributes['style']['border']['style'];
	}

	// Border width.
	if (
		$has_border_width_support &&
		isset( $block_attributes['style']['border']['width'] ) &&
		! gutenberg_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'width' )
	) {
		$border_width = $block_attributes['style']['border']['width'];

		// This check handles original unitless implementation.
		if ( is_numeric( $border_width ) ) {
			$border_width .= 'px';
		}

		$border_block_styles['width'] = $border_width;
	}

	// Border color.
	if (
		$has_border_color_support &&
		! gutenberg_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'color' )
	) {
		$preset_border_color          = array_key_exists( 'borderColor', $block_attributes ) ? "var:preset|color|{$block_attributes['borderColor']}" : null;
		$custom_border_color          = _wp_array_get( $block_attributes, array( 'style', 'border', 'color' ), null );
		$border_block_styles['color'] = $preset_border_color ? $preset_border_color : $custom_border_color;
	}

	// Generate styles for individual border sides.
	if ( $has_border_color_support || $has_border_width_support ) {
		foreach ( $sides as $side ) {
			$border                       = _wp_array_get( $block_attributes, array( 'style', 'border', $side ), null );
			$border_side_values           = array(
				'width' => isset( $border['width'] ) && ! gutenberg_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'width' ) ? $border['width'] : null,
				'color' => isset( $border['color'] ) && ! gutenberg_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'color' ) ? $border['color'] : null,
				'style' => isset( $border['style'] ) && ! gutenberg_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'style' ) ? $border['style'] : null,
			);
			$border_block_styles[ $side ] = $border_side_values;
		}
	}

	// Collect classes and styles.
	$attributes = array();
	$styles     = gutenberg_style_engine_generate( array( 'border' => $border_block_styles ), array( 'css_vars' => true ) );

	if ( ! empty( $styles['classnames'] ) ) {
		$attributes['class'] = $styles['classnames'];
	}

	if ( ! empty( $styles['css'] ) ) {
		$attributes['style'] = $styles['css'];
	}

	return $attributes;
}

/**
 * Checks whether the current block type supports the border feature requested.
 *
 * If the `__experimentalBorder` support flag is a boolean `true` all border
 * support features are available. Otherwise, the specific feature's support
 * flag nested under `experimentalBorder` must be enabled for the feature
 * to be opted into.
 *
 * @param WP_Block_Type $block_type Block type to check for support.
 * @param string        $feature    Name of the feature to check support for.
 * @param mixed         $default    Fallback value for feature support, defaults to false.
 *
 * @return boolean                  Whether or not the feature is supported.
 */
function gutenberg_has_border_feature_support( $block_type, $feature, $default = false ) {
	// Check if all border support features have been opted into via `"__experimentalBorder": true`.
	if (
		property_exists( $block_type, 'supports' ) &&
		( true === _wp_array_get( $block_type->supports, array( '__experimentalBorder' ), $default ) )
	) {
		return true;
	}

	// Check if the specific feature has been opted into individually
	// via nested flag under `__experimentalBorder`.
	return block_has_support( $block_type, array( '__experimentalBorder', $feature ), $default );
}

// Register the block support.
WP_Block_Supports::get_instance()->register(
	'border',
	array(
		'register_attribute' => 'gutenberg_register_border_support',
		'apply'              => 'gutenberg_apply_border_support',
	)
);
