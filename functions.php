<?php
/**
 * User Home Screen General Functions.
 */

/**
 * Return the user capability that users must have before they can
 * access their User Home Screen.
 *
 * @return  string  The capability users must have to access their Home screen.
 */
function user_home_screen_user_capability() {

	$cap = 'read';
	/**
	 * Allow outside code to customize the user capability.
	 *
	 * @param  string  $cap  The default user capability.
	 */
	return apply_filters( 'user_home_screen_user_capability', $cap );
}

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
		'add_widget'             => __( 'Add Widget', 'user-home-screen' ),
		'add_widget_message'     => __( 'You just added a widget!', 'user-home-screen' ),
		'remove_widget'          => __( 'Remove Widget', 'user-home-screen' ),
		'remove_widget_confirm'  => __( 'Are you sure you want to remove the selected widget?', 'user-home-screen' ),
		'remove_widget_message'  => __( 'You just removed a widget.', 'user-home-screen' ),
		'edit_widget'            => __( 'Edit Widget', 'user-home-screen' ),
		'select_widget_type'     => __( 'Select widget type', 'user-home-screen' ),
		'select_default'         => __( 'Select', 'user-home-screen' ),
		'add_tab'                => __( 'Add Tab', 'user-home-screen' ),
		'add_tab_message'        => __( 'You just added a tab!', 'user-home-screen' ),
		'remove_tab'             => __( 'Remove Tab', 'user-home-screen' ),
		'remove_tab_confirm'     => __( 'Are you sure you want to remove the selected tab? Widgets added to this tab will also be removed.', 'user-home-screen' ),
		'remove_tab_message'     => __( 'You just removed a tab.', 'user-home-screen' ),
		'tab_name'               => __( 'Tab Name', 'user-home-screen' ),
		'no_tabs_notice'         => __( 'Please add a tab first, then you can add widgets', 'user-home-screen' ),
		'post_list_ajax_fail'    => __( 'Sorry, it appears the Ajax request to fetch posts has failed', 'user-home-screen' ),
		'refreshing_home_screen' => __( 'Refreshing your home screen...', 'user-home-screen' ),
	);

	// Add widget type data.
	$data['widget_types'] = user_home_screen_get_widget_types();

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
 * Register a widget type.
 *
 * @param  string  $widget_type_slug  The widget type slug.
 * @param  array   $widget_type_args  The array of widget type args.
 */
function user_home_screen_register_widget_type( $widget_type_slug, $widget_type_args ) {

	// Bail if we're not in the admin.
	if ( ! is_admin() ) {
		return;
	}

	global $user_home_screen;

	// Bail if our main plugin class is not yet set up.
	if ( empty( $user_home_screen ) ) {
		return;
	}

	$widget_types = (array) $user_home_screen->get_widget_types();

	// Add new or overwrite existing widget type args by slug.
	$widget_types[ $widget_type_slug ] = (array) $widget_type_args;

	$user_home_screen->update_widget_types( $widget_types );
}

/**
 * Return the array of widget type data.
 *
 * @return  array  The array of widget type data.
 */
function user_home_screen_get_widget_types() {

	// Bail if we're not in the admin.
	if ( ! is_admin() ) {
		return array();
	}

	global $user_home_screen;

	// Bail if our main plugin class is not yet set up.
	if ( empty( $user_home_screen ) ) {
		return array();
	}

	return $user_home_screen->get_widget_types();
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

		// Ensure the current user has the edit_* capability for each post type they can select.
		$post_type_object = get_post_type_object( $post_type );

		$cap = 'edit_posts';
		/**
		 * Allow the capability to be customized.
		 *
		 * @param  string        $cap               The default capability to check.
		 * @param  string        $post_type         The current post type.
		 * @param  WP_Post_Type  $post_type_object  The current post type object.
		 */
		$cap = apply_filters( 'user_home_screen_get_post_types_user_capability', $cap, $post_type, $post_type_object );

		if ( ! empty( $post_type_object->cap->{$cap} ) && current_user_can( $post_type_object->cap->{$cap} ) ) {
			$post_types[ $post_type ] = $config->labels->name;
		}
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

/**
 * Build and return the HTML for a widget.
 *
 * @param   string  $widget_id    The widget ID.
 * @param   array   $widget_args  The widget instance data.
 * @param   string  $tab_id       The tab ID.
 *
 * @return  string                The widget HTML.
 */
function user_home_screen_render_widget( $widget_id, $widget_args, $tab_id ) {

	$html = '';
	/**
	 * Allow outside code to short-circuit this whole function
	 * and render a custom widget.
	 *
	 * @param  string  $html    The empty string of HTML.
	 * @param  array   $widget  The widget instance args.
	 */
	if ( ! empty( apply_filters( 'user_home_screen_pre_render_widget', $html, $widget_args ) ) ) {
		return $html;
	}

	/**
	 * Allow outside code to customize the widget args before rendering.
	 *
	 * @param  array  $widget  The widget instance data.
	 */
	$widget_args = apply_filters( 'user_home_screen_widget_args', $widget_args );

	$type_class = 'type-' . $widget_args['type'];

	ob_start();

	?>
	<div class="uhs-widget postbox <?php echo esc_attr( $type_class ); ?>" data-widget-id="<?php echo esc_attr( $widget_id ); ?>" data-tab-id="<?php echo esc_attr( $tab_id ); ?>">
		<div class="uhs-widget-top-bar">
			<button type="button" class="uhs-toggle-widget-info"><span class="dashicons dashicons-arrow-down"></span></button>
			<button type="button" class="uhs-remove-widget"><span class="dashicons dashicons-no-alt"></span></button>
			<h2 class="uhs-widget-title hndle ui-sortable-handle">
				<span><?php echo esc_html( $widget_args['args']['title'] ); ?></span>
			</h2>
		</div>
		<div class="uhs-widget-info">
			<?php echo user_home_screen_render_widget_info( $widget_id, $widget_args ); ?>
		</div>
		<?php
			switch ( $widget_args['type'] ) {
				case 'post-list':
					echo user_home_screen_render_post_list_widget_placeholder( $widget_args['args'] );
					break;
				case 'rss-feed':
					echo user_home_screen_render_rss_feed_widget_placeholder( $widget_args['args']['feed_url'] );
					break;
			}
		?>
	</div>
	<?php

	$html = ob_get_clean();

	/**
	 * Allow the widget HTML to be customized.
	 *
	 * @param  string  $html         The default widget html.
	 * @param  array   $widget_args  The widget instance data.
	 */
	return apply_filters( 'user_home_screen_widget_html', $html, $widget_args );
}

/**
 * Build and return the HTML for a widget info panel.
 *
 * @param   string  $widget_id    The widget ID.
 * @param   array   $widget_args  The widget args.
 *
 * @return  string                The widget info HTML.
 */
function user_home_screen_render_widget_info( $widget_id, $widget_args ) {

	$widget_type_data = user_home_screen_get_widget_types();
	$widget_info      = '';

	// Add a standard Widget Type field if the widget type has a label.
	if (
		! empty( $widget_type_data[ $widget_args['type'] ] ) &&
		! empty( $widget_type_data[ $widget_args['type'] ]['label'] )
	) {
		$widget_info .= sprintf(
			'<div class="%s"><span class="%s">%s:</span> %s</div>',
			'uhs-widget-info-type',
			'uhs-widget-info-label',
			esc_html__( 'Widget Type', 'user-home-screen' ),
			esc_html( $widget_type_data[ $widget_args['type'] ]['label'] )
		);
	}

	// Add any widget info fields that have been saved in the widget args.
	if ( ! empty( $widget_args['args']['widget_info'] ) ) {
		foreach ( $widget_args['args']['widget_info'] as $arg_key => $arg_info ) {
			$widget_info .= sprintf(
				'<div class="%s">%s</div>',
				'uhs-widget-info-' . str_replace( '_', '-', esc_attr( $arg_key ) ),
				wp_kses_post( $arg_info )
			);
		}
	}

	/**
	 * Allow the widget info to be customized and custom widget types to be handled.
	 *
	 * This filter is used internally to add extra functionality for specific widget
	 * types into the widget info section.
	 *
	 * @param  string  $widget_info  The default widget info.
	 * @param  string  $widget_id    The current widget ID.
	 * @param  array   $widget_args  The current widget args.
	 */
	$widget_info = apply_filters( 'user_home_screen_render_widget_info', $widget_info, $widget_id, $widget_args );

	ob_start();

	?>
	<div class="uhs-widget-info-inner">
		<?php echo $widget_info; ?>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * Include the HTML for the extra content in the widget info section on certaein widget types.
 *
 * @param   string  $widget_info  The default widget info.
 * @param   string  $widget_id    The current widget ID.
 * @param   array   $widget_args  The current widget args.
 *
 * @return  string                The updated widget info.
 */
function user_home_screen_include_widget_info_extras( $widget_info, $widget_id, $widget_args ) {

	if ( 'post-list' === $widget_args['type'] ) {

		$template_parts          = user_home_screen_get_post_list_template_parts();
		$template_parts_selector = '<div class="uhs-post-list-template-part-selector">';
		$template_parts_selector .= '<h3 class="uhs-post-list-template-part-selector-title">' . esc_html__( 'Template Parts', 'user-home-screen' ) . '</h3>';

		foreach ( $template_parts as $part => $name ) {

			$part_slug  = str_replace( '_', '-', $part );
			$class      = 'uhs-post-list-template-part-selector-for-' . $part_slug;
			$show_class = 'uhs-post-list-show-' . $part_slug;
			$checked    = ( in_array( $part, $widget_args['args']['template_parts'] ) ) ? 'checked="true"' : '';

			$template_parts_selector .= sprintf(
				'<div class="%s"><label><input type="checkbox" data-show-class="%s" data-template-part="%s" %s /><span>%s</span></label></div>',
				esc_attr( $class ),
				esc_attr( $show_class ),
				esc_attr( $part ),
				$checked,
				esc_html( $name )
			);
		}

		$template_parts_selector .= sprintf(
			'<button type="button" class="%s">%s</button><span class="%s">%s</span><span class="%s"></span>',
			'uhs-post-list-template-part-selector-save button button-secondary',
			esc_html__( 'Save Template Parts', 'user-home-screen' ),
			'uhs-post-list-template-part-selector-save-confirm',
			esc_html__( 'Widget Saved', 'user-home-screen' ),
			'uhs-spinner spinner'
		);

		$template_parts_selector .= '</div>';

		$widget_info .= $template_parts_selector;
	}

	return $widget_info;
}

/**
 * Build and return the HTML placeholder for a Post List widget placeholder.
 *
 * @param   array  $args  The widget args.
 *
 * @return  string        The widget HTML.
 */
function user_home_screen_render_post_list_widget_placeholder( $args ) {

	$parts          = ( ! empty( $args['template_parts'] ) ) ? $args['template_parts'] : array();
	$classes        = array();
	$template_parts = user_home_screen_get_post_list_template_parts();

	foreach ( $template_parts as $template_part => $template_part_name ) {
		if ( in_array( $template_part, $parts ) ) {
			$classes[] = 'uhs-post-list-show-' . str_replace( '_', '-', $template_part );
		}
	}

	$classes = implode( ' ', $classes );

	ob_start();

	?>
	<div class="uhs-post-list-widget-posts-wrap <?php echo esc_attr( $classes ); ?>">
		<div class="uhs-post-list-widget-posts">
			<span class="uhs-spinner spinner"></span>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * Build and return the HTML for a post-list widget.
 *
 * @param   string  $widget_id           The widget ID.
 * @param   array   $args                The widget args.
 * @param   bool    $include_pagination  Whether to include the pagination HTML.
 *
 * @return  string                       The widget HTML.
 */
function user_home_screen_render_post_list_widget( $widget_id, $args, $include_pagination = true ) {

	$html = '';

	// Bail if we don't have query args.
	if ( empty( $args['query_args'] ) ) {
		return $html;
	}

	// Make the query.
	$query = new WP_Query( $args['query_args'] );

	ob_start();

	if ( $query->have_posts() ) {

		// Determine which "page" we're on in the pagination sense.
		$page = ( ! empty( $query->query_vars['paged'] ) ) ? (int) $query->query_vars['paged'] : 1;

		// Determine which set of posts we're on in the pagination sense.
		if ( $page < $query->max_num_pages ) {
			$current_posts_min = ( $query->post_count * ( $page - 1 ) ) + 1;
			$current_posts_max = $query->post_count * $page;
		} else {
			$current_posts_min = $query->found_posts - $query->post_count + 1;
			$current_posts_max = $query->found_posts;
		}

		printf(
			'<div class="%s" data-current-page="%s" data-total-pages="%s" data-current-post-min="%s" data-current-post-max="%s">',
			'uhs-post-list-widget-posts',
			esc_attr( $page ),
			esc_attr( $query->max_num_pages ),
			esc_attr( $current_posts_min ),
			esc_attr( $current_posts_max )
		);

		while ( $query->have_posts() ) {

			$query->the_post();

			$custom_html = '';
			/**
			 * Allow custom HTML to be used.
			 *
			 * @param  string    $custom_template  The HTML for a custom template.
			 * @param  WP_Post   $post             The current post object.
			 * @param  WP_Query  $query            The current query object.
			 * @param  array     $args             The array of widget args.
			 * @param  array     $parts            The array of template parts.
			 */
			$custom_html = apply_filters( 'user_home_screen_post_list_widget_post_html', $custom_html, $query->post, $query, $args, $parts );

			// Use custom HTML if provided, otherwise use the default HTML.
			if ( ! empty( $custom_html ) ) {

				echo $custom_html;

			} else {

				$post_type   = get_post_type_object( $query->post->post_type );
				$post_status = get_post_status_object( $query->post->post_status );
				$post_title  = ( ! empty( get_the_title( $query->post->ID ) ) ) ? get_the_title( $query->post->ID ) : __( 'Untitled', 'user-home-screen' );

				?>
				<div class="uhs-post-list-widget-post">
					<div class="uhs-post-list-widget-left">
						<h3 class="uhs-post-list-widget-post-title">
							<a href="<?php echo esc_url( get_edit_post_link( $query->post->ID, false ) ); ?>" target="_blank">
								<?php echo esc_html( $post_title ); ?>
							</a>
						</h3>
						<div class="uhs-post-list-widget-author">
							<?php echo esc_html__( 'By', 'user-home-screen' ) . ' ' . get_the_author(); ?>
						</div>
					</div>
					<div class="uhs-post-list-widget-right">
						<div class="uhs-post-list-widget-post-type">
							<?php echo esc_html( $post_type->labels->singular_name ); ?>
						</div>
						<div class="uhs-post-list-widget-status">
							<?php echo esc_html( $post_status->label ); ?>
						</div>
						<div class="uhs-post-list-widget-publish-date">
							<?php echo get_the_date(); ?>
						</div>
						<div class="uhs-post-list-widget-modified-date">
							<?php echo get_the_modified_date(); ?>
						</div>
						<div class="uhs-post-list-widget-category">
							<?php echo user_home_screen_get_taxonomy_term_list( $query->post->ID, 'category', '', ', ', false ); ?>
						</div>
					</div>
				</div>
				<?php
			}
		}

		echo '</div>';

		if ( $include_pagination ) {
			echo user_home_screen_render_post_list_widget_pagination( $current_posts_min, $current_posts_max, $query->found_posts );
		}

		wp_reset_postdata();

	} else {

		// @todo This should be more informative.
		?>
		<h3><?php esc_html_e( 'No Posts Found...', 'user-home-screen' ); ?></h3>
		<?php
	}

	return ob_get_clean();
}

/**
 * Build and return the HTML for the pagination section of the Post List widget.
 *
 * @param   int  $current_posts_min  The current first post in the list.
 * @param   int  $current_posts_max  The current last post in the list.
 * @param   int  $found_posts        The number of found posts.
 *
 * @return  string                   The pagination HTML.
 */
function user_home_screen_render_post_list_widget_pagination( $current_posts_min, $current_posts_max, $found_posts = 0 ) {

	// Determine whether to initially show next and previous links.
	if ( $current_posts_max < $found_posts ) {
		if ( $current_posts_min === 1 ) {

			// We're on the first page and only need to output next.
			$include_next = true;

		} elseif ( $current_posts_max === $found_posts ) {

			// We're on the last page and only need to output previous.
			$include_previous = true;

		} else {

			// We're on a page that is not the first or last and need to output the full pagination.
			$include_next     = true;
			$include_previous = true;
		}
	}

	$prev_class = ( ! empty( $include_previous ) ) ? 'uhs-visible' : '';
	$next_class = ( ! empty( $include_next ) ) ? 'uhs-visible' : '';

	ob_start();

	?>
	<div class="uhs-post-list-widget-pagination">
		<div class="uhs-post-list-widget-previous <?php echo esc_attr( $prev_class ); ?>">
			<?php esc_html_e( 'Previous', 'user-home-screen' ); ?>
		</div>
		<div class="uhs-post-list-widget-pagination-numbers">
			<?php
				printf(
					'<span class="%s">%s - %s</span> %s <span class="%s">%s</span>',
					'uhs-post-list-widget-post-x-x',
					esc_html( $current_posts_min ),
					esc_html( $current_posts_max ),
					__( 'of', 'user-home-screen' ),
					'uhs-post-list-widget-total-posts',
					esc_html( $found_posts )
				);
			?>
		</div>
		<div class="uhs-post-list-widget-next <?php echo esc_attr( $next_class ); ?>">
			<?php esc_html_e( 'Next', 'user-home-screen' ); ?>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * Build and return the HTML placeholder for the RSS Feed widget placeholder.
 *
 * @param   string  $feed_url  The feed URL.
 *
 * @return  string             The widget HTML.
 */
function user_home_screen_render_rss_feed_widget_placeholder( $feed_url ) {

	ob_start();

	?>
	<div class="uhs-rss-feed-widget-feed-content" data-feed-url="<?php echo esc_url( $feed_url ); ?>">
		<span class="uhs-spinner spinner"></span>
		<div class="uhs-feed-content-wrap"></div>
		<?php echo user_home_screen_render_rss_feed_widget_pagination(); ?>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * Build and return the HTML for the pagination section of the RSS Feed widget.
 *
 * @return  string  The pagination HTML.
 */
function user_home_screen_render_rss_feed_widget_pagination() {

	ob_start();

	?>
	<div class="uhs-rss-feed-widget-pagination">
		<div class="uhs-rss-feed-widget-previous">
			<?php esc_html_e( 'Previous', 'user-home-screen' ); ?>
		</div>
		<div class="uhs-rss-feed-widget-pagination-numbers">
			<?php
				printf(
					'%s <span class="%s"></span>',
					esc_html__( 'Page', 'user-home-screen' ),
					'uhs-rss-feed-widget-page-x'
				);
			?>
		</div>
		<div class="uhs-rss-feed-widget-next">
			<?php esc_html_e( 'Next', 'user-home-screen' ); ?>
		</div>
	</div>
	<?php

	return ob_get_clean();
}
