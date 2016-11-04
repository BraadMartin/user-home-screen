<?php
/**
 * User Home Screen General Functions.
 */

/**
 * Build and return the array of data we'll pass to our JS.
 *
 * @todo    Add more specific ajax fail labels for non-Post List widget contexts.
 *
 * @return  array  The array of JS data.
 */
function user_home_screen_get_js_data() {

	$data = array();

	// Define labels.
	$data['labels'] = array(
		'add_widget'            => __( 'Add Widget', 'user-home-screen' ),
		'remove_widget'         => __( 'Remove Widget', 'user-home-screen' ),
		'remove_widget_confirm' => __( 'Are you sure you want to remove the selected widget?', 'user-home-screen' ),
		'edit_widget'           => __( 'Edit Widget', 'user-home-screen' ),
		'select_widget_type'    => __( 'Select widget type', 'user-home-screen' ),
		'select_default'        => __( 'Select', 'user-home-screen' ),
		'add_tab'               => __( 'Add Tab', 'user-home-screen' ),
		'remove_tab'            => __( 'Remove Tab', 'user-home-screen' ),
		'remove_tab_confirm'    => __( 'Are you sure you want to remove the selected tab? Widgets added to this tab will also be removed.', 'user-home-screen' ),
		'tab_name'              => __( 'Tab Name', 'user-home-screen' ),
		'no_tabs_notice'        => __( 'Please add a tab first, then you can add widgets', 'user-home-screen' ),
		'post_list_ajax_fail'   => __( 'Sorry, it appears the Ajax request to fetch posts has failed', 'user-home-screen' ),
	);

	// Add widget type data.
	$data['widget_types'] = user_home_screen_get_widget_type_data();

	// Add a nonce.
	$data['nonce'] = wp_create_nonce( 'user-home-screen' );

	/**
	 * Allow the JS data to be customized.
	 *
	 * @param  array  $data  The default JS data.
	 */
	return apply_filters( 'user_home_screen_js_data', $data );
}

/**
 * Return the array of widget type data.
 *
 * @return  array  The array of widget type data.
 */
function user_home_screen_get_widget_type_data() {

	$post_types       = user_home_screen_get_post_types();
	$categories       = user_home_screen_get_categories();
	$post_statuses    = user_home_screen_get_post_statuses();
	$authors          = user_home_screen_get_authors();
	$order_by_options = user_home_screen_get_order_by_options();
	$order_options    = user_home_screen_get_order_options();

	$widget_types = array(
		'post-list' => array(
			'label'  => __( 'Post List', 'user-home-screen' ),
			'fields' => array(
				array(
					'key'   => 'title',
					'label' => __( 'Widget Title', 'user-home-screen' ),
					'type'  => 'text',
				),
				array(
					'key'         => 'post_types',
					'label'       => __( 'Post Types', 'user-home-screen' ),
					'type'        => 'select-multiple',
					'placeholder' => __( 'Select a Post Type', 'user-home-screen' ),
					'values'      => $post_types,
				),
				array(
					'key'         => 'categories',
					'label'       => __( 'Categories', 'user-home-screen' ),
					'type'        => 'select-multiple',
					'placeholder' => __( 'Select a Category', 'user-home-screen' ),
					'values'      => $categories,
				),
				array(
					'key'         => 'post_statuses',
					'label'       => __( 'Post Statuses', 'user-home-screen' ),
					'type'        => 'select-multiple',
					'placeholder' => __( 'Select a Post Status', 'user-home-screen' ),
					'values'      => $post_statuses,
				),
				array(
					'key'         => 'authors',
					'label'       => __( 'Authors', 'user-home-screen' ),
					'type'        => 'select-multiple',
					'placeholder' => __( 'Select an Author', 'user-home-screen' ),
					'values'      => $authors,
				),
				array(
					'key'    => 'order_by',
					'label'  => __( 'Order By', 'user-home-screen' ),
					'type'   => 'select',
					'values' => $order_by_options,
				),
				array(
					'key'    => 'order',
					'label'  => __( 'Order', 'user-home-screen' ),
					'type'   => 'select',
					'values' => $order_options,
				),
			),
		),
		'rss-feed' => array(
			'label' => __( 'RSS Feed', 'user-home-screen' ),
			'fields' => array(
				array(
					'key'   => 'title',
					'label' => __( 'Widget Title', 'user-home-screen' ),
					'type'  => 'text',
				),
				array(
					'key'   => 'feed_url',
					'label' => __( 'Feed URL', 'user-home-screen' ),
					'type'  => 'text',
				),
			),
		),
	);

	/**
	 * Allow the widget types data to be customized.
	 *
	 * @param  array  $widget_types  The default array of widget types data.
	 */
	return apply_filters( 'user_home_screen_widget_types', $widget_types );
}

/**
 * Return an array of post types that should be selectable in widgets.
 *
 * @return  array  The array of post types.
 */
function user_home_screen_get_post_types() {

	$full_post_types = get_post_types( array( 'public' => true ), 'objects' );
	$post_types      = array( 'any' => __( 'Any', 'user-home-screen' ) );

	// Transform into a simple array of post_type => Display Name.
	foreach ( $full_post_types as $post_type => $config ) {
		$post_types[ $post_type ] = $config->labels->name;
	}

	/**
	 * Allow the selectable post types to be customized.
	 *
	 * @param  array  $post_types  The default array of selectable post types.
	 */
	return apply_filters( 'user_home_screen_selectable_post_types', $post_types );
}

/**
 * Return an array of categories that should be selectable in widgets.
 *
 * @return  array  The array of categories.
 */
function user_home_screen_get_categories() {

	$full_categories = get_terms( array(
		'taxonomy'               => 'category',
		'update_term_meta_cache' => false,
	) );

	$categories = array();

	// Transform into a simple array of user ID => Display name.
	foreach ( $full_categories as $category ) {
		$categories[ 'term_' . $category->term_id ] = $category->name;
	}

	/**
	 * Allow the selectable authors to be filtered.
	 *
	 * @param  array  $authors  The default array of selectable categories.
	 */
	return apply_filters( 'user_home_screen_selectable_categories', $categories );
}

/**
 * Return an array of post statuses that should be selectable in widgets.
 *
 * @return  array  The array of post statuses.
 */
function user_home_screen_get_post_statuses() {

	$full_post_statuses = get_post_stati( array( 'show_in_admin_status_list' => 1 ), 'objects' );
	$post_statuses      = array();

	// Transform into a simple array of post_status => Display name.
	foreach ( $full_post_statuses as $post_status => $config ) {
		$post_statuses[ $post_status ] = $config->label;
	}

	/**
	 * Allow the selectable post statuses to be filtered.
	 *
	 * @param  array  $post_statuses  The default array of selectable post statuses.
	 */
	return apply_filters( 'user_home_screen_selectable_post_statuses', $post_statuses );
}

/**
 * Return an array of authors that should be selectable in widgets.
 *
 * @return  array  The array of authors.
 */
function user_home_screen_get_authors() {

	$full_users = get_users( array(
		'orderby'     => 'display_name',
		'order'       => 'ASC',
		'count_total' => false,
	) );

	$authors = array();

	// Transform into a simple array of user ID => Display name.
	// We have to prefix the ID here to prevent the array for sorting by ID.
	foreach ( $full_users as $user ) {
		$authors[ 'user_' . $user->ID ] = $user->data->display_name;
	}

	/**
	 * Allow the selectable authors to be filtered.
	 *
	 * @param  array  $authors  The default array of selectable authors.
	 */
	return apply_filters( 'user_home_screen_selectable_authors', $authors );
}

/**
 * Return an array of order by options that should be selectable in widgets.
 *
 * @return  array  The array of order by options.
 */
function user_home_screen_get_order_by_options() {

	$order_by_options = array(
		'date'     => __( 'Publish Date', 'user-home-screen' ),
		'modified' => __( 'Last Modified Date', 'user-home-screen' ),
		'author'   => __( 'Author', 'user-home-screen' ),
		'title'    => __( 'Title', 'user-home-screen' ),
		'type'     => __( 'Post Type', 'user-home-screen' ),
	);

	/**
	 * Allow the selectable order by options to be filtered.
	 *
	 * @param  array  $order_by_options  The default array of selectable order by options.
	 */
	return apply_filters( 'user_home_screen_selectable_order_by_options', $order_by_options );
}

/**
 * Return an array of order options that should be selectable in widgets.
 *
 * @return  array  The array of order options.
 */
function user_home_screen_get_order_options() {

	$order_options = array(
		'DESC' => __( 'Descending', 'user-home-screen' ),
		'ASC'  => __( 'Ascending', 'user-home-screen' ),
	);

	/**
	 * Allow the selectable order options to be filtered.
	 *
	 * @param  array  $order_options  The default array of selectable order options.
	 */
	return apply_filters( 'user_home_screen_selectable_order_options', $order_options );
}

/**
 * Return an array of template parts for the Post List widget.
 *
 * @return  array  The array of template part options.
 */
function user_home_screen_get_post_list_template_parts() {

	$template_parts = array(
		'author'        => __( 'Author', 'user-home-screen' ),
		'post_type'     => __( 'Post Type', 'user-home-screen' ),
		'status'        => __( 'Post Status', 'user-home-screen' ),
		'publish_date'  => __( 'Publish Date', 'user-home-screen' ),
		'modified_date' => __( 'Modified Date', 'user-home-screen' ),
		'category'      => __( 'Categories', 'user-home-screen' ),
	);

	/**
	 * Allow the selectable template parts to be filtered.
	 *
	 * @param  array  $template_parts  The default array of template parts.
	 */
	return apply_filters( 'user_home_screen_selectable_post_list_template_parts', $template_parts );
}

/**
 * Build and return the HTML for a taxonomy term list.
 *
 * @param   int     $post_id    The post ID to use.
 * @param   string  $taxonomy   The taxonomy slug to output terms from.
 * @param   string  $label      The label to use.
 * @param   string  $separator  The separation string.
 * @param   bool    $link       Whether to link the terms.
 *
 * @return  string              The term list HTML.
 */
function user_home_screen_get_taxonomy_term_list( $post_id = 0, $taxonomy = '', $label = '', $separator = ', ', $link = true ) {

	// Taxonomy is required.
	if ( ! $taxonomy ) {
		return '';
	}

	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	$terms_args = array(
		'orderby' => 'name',
		'order'   => 'ASC',
		'fields'  => 'all',
	);

	$terms = wp_get_post_terms( $post_id, $taxonomy, $terms_args );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return '';
	}

	$output = sprintf(
		'<div class="%s %s">%s',
		'entry-tax-term-list',
		esc_attr( $taxonomy ) . '-tax-term-list',
		$label
	);

	$i = 0;

	foreach ( $terms as $term_slug => $term_obj ) {

		if ( $link ) {
			$output .= sprintf(
				'<a href="%s" rel="%s %s">%s</a>',
				get_term_link( $term_obj->term_id ),
				esc_attr( $term_obj->slug ),
				esc_attr( $term_obj->taxonomy ),
				esc_html( $term_obj->name )
			);
		} else {
			$output .= esc_html( $term_obj->name );
		}

		$i++;

		if ( count( $terms ) > $i ) {
			$output .= $separator;
		}
	}
	$output .= '</div>';

	return $output;
}
