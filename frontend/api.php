<?php
/**
 * Renders an HTML select list that displays the available user interface languages.
 * Loads a script that allows the user to actually change the language.
 * You can customized what this function does by passing an option array.
 * These are the available options:
 * * $options['wrapper'] - Wether or not to add a div around the select tag. Defaults to true.
 * * $options['script'] - Wether or not to include the JS script that reloads the page when the select value change. Defaults to true.
 * * $options['raw'] - Set this to true so that supplang_switcher returns a raw array of available languages. Defaults to false.
 * * $options['template'] - Pass it a printf template string where %s will be replaced by the language name in each select option.
 * @param array $options An option array
 */
function supplang_switcher( array $options = array() ) {
  // Default values
  $options['wrapper'] = isset( $options['wrapper'] ) ? $options['wrapper'] : true;
  $options['script'] = isset( $options['script'] ) ? $options['script'] : true;
  $options['raw'] = isset( $options['raw'] ) ? $options['raw'] : false;
  $options['template'] = isset( $options['template'] ) ? $options['template'] : '%s';

  if ( $options['script']) {
    wp_enqueue_script(
      'supplang-language-switcher',
      plugin_dir_url( __FILE__ ) . 'js/language-switcher.js',
      array( 'jquery' ),
      filemtime( plugin_dir_path( __FILE__ ) . 'js/language-switcher.js' ),
      true
    );
  }
  if ( $options['raw'] ) {
    return supplang_languages();
  } else {
    include 'templates/language-selector.php';
  }
}

/**
 * Get an array of available languages.
 * The returned languages are the one that have been checked using the plugin setting in **Settings > Site Languages** page.
 * Each item is an array with the following properties:
 * * `name` - The name of the language, in the language itself
 * * `locale` - The name of the WordPress locale for this language
 * @return Array
 */
function supplang_languages() {
	$languages = array(
		'filtered'  => array(),
		// The option array uses the language locale as a key to indicate its availability
		'available' => get_option( SUPPLANG_AVAILABLE_UIL ),
	);

	foreach ( supplang_registered_languages() as $lang ) {
		if ( array_key_exists( $lang['locale'], $languages['available'] ) ) {
			// Add filtered language
			array_push(
				$languages['filtered'], array_filter(
					$lang, function( $key ) {
						// Do not send the description key
						return 'description' !== $key;
					}, ARRAY_FILTER_USE_KEY
				)
			);
		}
	}

	return $languages['filtered'];
}

/**
 * Append the supplang user interface language query param to the home url.
 * > This is a wrapper around the WordPress `home_url()` function.
 * @see https://developer.wordpress.org/reference/functions/home_url/
 * @param String path A path that will be appended to the home url
 * @return String the resulting home url
 */
function supplang_home_url( $path = '' ) {
	$home_url = home_url( $path );
	return $home_url . ( strpos( $home_url, '?' ) ? '&' : '?' ) . SUPPLANG_GET_PARAM . '=' . supplang_slug_from_locale();
}

/**
 * Get the supplang language slug corresponding to the currently set site locale.
 * **Note:** The locale will be searched for among the **available** languages as set in the **Settings > Site Languages** page.
 * @return String The language slug, composed of the first two characters of the defined locale.
 */
function supplang_slug_from_locale() {
	$slug_from_locale = array_column( supplang_languages(), 'slug', 'locale' );
	return array_key_exists( get_locale(), $slug_from_locale ) ? $slug_from_locale[ get_locale() ] : 'de';
}

/**
 * Get the supplang language locale corresponding to the given $slug.
 * **Note:** The slug will be searched for among the **available** languages as set in the **Settings > Site Languages** page.
 * @param String $slug The language slug
 * @return Mixed The language locale for the given slug, or null if no corresponding locale found.
 */
function supplang_locale_from_slug( $slug ) {
	$locale_from_slug = array_column( supplang_languages(), 'locale', 'slug' );
	return array_key_exists( $slug, $locale_from_slug ) ? $locale_from_slug[ $slug ] : null;
}

/**
 * Return the current requested URL (by default) with the `uil` param properly added (and retrieved by default from the currently defined locale).
 * One can pass specifics value for $url and $lang_slug.
 * @param String $lang_slug The value that will be set to the `uil` param in the returned url.
 * @param String $url The URL to which the `uil` param will be added
 * @return String The updated URL.
 */
function supplang_param_in_url( $lang_slug = null, $url = null ) {
	$param_name = SUPPLANG_GET_PARAM;
	$url        = isset( $url ) ? $url : "//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$lang_slug  = isset( $lang_slug ) ? $lang_slug : supplang_slug_from_locale();
	if ( preg_match( '/[\?|&]uil=[^&]*/', $url ) ) {
		return preg_replace( '/([\?|&]uil=)[^&]*/', '$1' . $lang_slug, $url );
	} elseif ( strstr( $url, '?' ) ) {
		return "$url&$param_name=$lang_slug";
	} else {
		return "$url?$param_name=$lang_slug";
	}
}
