<?php

if ( !defined( 'WP_CLI' ) ) return;

/**
 * Migrate meta values to terms
 *
 * @package wp-cli
 */
class Meta_To_Term_Migration extends WP_CLI_Command {

	/**
	 * Migrate meta values to taxonomy terms. Delete meta after import
	 *
	 * ## OPTIONS
	 *
	 * <meta-key>
	 * : Meta key to convert
	 *
	 * <taxonomy-slug>
	 * : Taxonomy to move values into
	 *
 	 * [--<field>=<value>]
	 * : One or more args to pass to WP_Query.
	 *
	 * ## EXAMPLES
	 *
	 *     wp mtt migrate meta_key taxonomy_slug
	 *     wp mtt migrate meta_key taxonomy_slug --posts_per_page=200 --paged=2
	 *
	 */
	function migrate( $args, $assoc_args ) {
		list( $meta_key, $taxonomy ) = $args;

		if ( ! ( $taxonomy_object = get_taxonomy( $taxonomy ) ) ) {
			WP_CLI::error( sprintf( "The taxonomy '%s' doesn't exist", $taxonomy ) );
		}

 		$defaults = array(
			'post_type'      => $taxonomy_object->object_type,
 			'posts_per_page' => -1,
 			'post_status'    => 'any',
		);
 		$query_args = array_merge( $defaults, $assoc_args );

		$query = new WP_Query( $query_args );

		// summary
		WP_CLI::log( sprintf(
			"---\nPer page: %d \nPage: %d \nTotal pages: %d\n---",
			$query_args['posts_per_page'],
			isset( $query_args['paged'] ) ? $query_args['paged'] : 1,
			$query->max_num_pages
		) );

		while ( $query->have_posts() ) {
			$query->the_post();

			$id = get_the_id();

			// get meta
			$metas = get_post_meta( $id, $meta_key );
			// create term
			if ( ! $metas ) {
				WP_CLI::log( WP_CLI::colorize("%c[$id]%n No meta, skipped" ) );
			} else if ( ! is_wp_error( wp_set_object_terms( $id, $metas, $taxonomy, true ) ) ) {
				WP_CLI::log( WP_CLI::colorize("%g[$id]%n Migrated: " . implode( ', ',  $metas ) ) );
				// clean meta
				delete_post_meta( $id, $meta_key );
			} else {
				WP_CLI::log( WP_CLI::colorize("%r[$id]%n Error: Could not set terms for post") );
			}
		}

	}

}

WP_CLI::add_command( 'mtt', 'Meta_To_Term_Migration' );
