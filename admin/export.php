<?php

$wordpress_root_path = preg_replace( '/wp-content(?!.*wp-content).*/', '', __DIR__ );
require_once( $wordpress_root_path . 'wp-admin/admin.php' );
if ( !( current_user_can( 'manage_dt' ) || current_user_can( 'edit_landings' ) ) ) { // manage dt is a permission that is specific to Disciple.Tools and allows admins, strategists and dispatchers into the wp-admin
    wp_die( 'You do not have sufficient permissions to access this page.' );
}

if ( !defined( 'WXR_VERSION' ) ){
    define( 'WXR_VERSION', '1.2' );
}

function export_fuel( $language = null, $linked_campaign = null ){


    global $wpdb, $post;
    $join = '';

    $where = $wpdb->prepare( 'post_type = %s AND post_status = %s', 'landing', 'publish' );

    if ( $language ) {
        $join .= " INNER JOIN $wpdb->postmeta pm ON ({$wpdb->posts}.ID = pm.post_id) ";
        $where .= $wpdb->prepare( " AND pm.meta_key = 'post_language' AND pm.meta_value = %s ", esc_sql( $language ) );
    }
    if ( $linked_campaign ) {
        $join .= " INNER JOIN $wpdb->postmeta pm2 ON ({$wpdb->posts}.ID = pm2.post_id) ";
        $where .= $wpdb->prepare( " AND pm2.meta_key = 'linked_campaign' AND pm2.meta_value = %s ", esc_sql( $linked_campaign ) );
    }

    $post_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} $join WHERE $where" );

    /**
     * Returns the URL of the site.
     *
     * @since 2.5.0
     *
     * @return string Site URL.
     */
    function wxr_site_url() {
        if ( is_multisite() ) {
            // Multisite: the base URL.
            return network_home_url();
        } else {
            // WordPress (single site): the blog URL.
            return get_bloginfo_rss( 'url' );
        }
    }
    /**
     * Wraps given string in XML CDATA tag.
     *
     * @since 2.1.0
     *
     * @param string $str String to wrap in XML CDATA tag.
     * @return string
     */
    function wxr_cdata( $str ) {
        if ( ! seems_utf8( $str ) ) {
            $str = utf8_encode( $str );
        }
        // $str = ent2ncr(esc_html($str));
        $str = '<![CDATA[' . str_replace( ']]>', ']]]]><![CDATA[>', $str ) . ']]>';

        return $str;
    }
    /**
     * Outputs list of taxonomy terms, in XML tag format, associated with a post.
     *
     * @since 2.3.0
     */
    function wxr_post_taxonomy() {
        $post = get_post();

        $taxonomies = get_object_taxonomies( $post->post_type );
        if ( empty( $taxonomies ) ) {
            return;
        }
        $terms = wp_get_object_terms( $post->ID, $taxonomies );

        foreach ( (array) $terms as $term ) {
            echo "\t\t<category domain=\"{$term->taxonomy}\" nicename=\"{$term->slug}\">" . wxr_cdata( $term->name ) . "</category>\n";
        }
    }
    /**
     * Determines whether to selectively skip post meta used for WXR exports.
     *
     * @since 3.3.0
     *
     * @param bool   $return_me Whether to skip the current post meta. Default false.
     * @param string $meta_key  Meta key.
     * @return bool
     */
    function wxr_filter_postmeta( $return_me, $meta_key ) {
        if ( '_edit_lock' === $meta_key ) {
            $return_me = true;
        }
        return $return_me;
    }
    add_filter( 'wxr_export_skip_postmeta', 'wxr_filter_postmeta', 10, 2 );
    /**
     * Outputs list of authors with posts.
     *
     * @since 3.1.0
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     *
     * @param int[] $post_ids Optional. Array of post IDs to filter the query by.
     */
    function wxr_authors_list( array $post_ids = null ) {
        global $wpdb;

        if ( ! empty( $post_ids ) ) {
            $post_ids = array_map( 'absint', $post_ids );
            $and      = 'AND ID IN ( ' . implode( ', ', $post_ids ) . ')';
        } else {
            $and = '';
        }

        $authors = array();
        $results = $wpdb->get_results( "SELECT DISTINCT post_author FROM $wpdb->posts WHERE post_status != 'auto-draft' $and" );
        foreach ( (array) $results as $result ) {
            $authors[] = get_userdata( $result->post_author );
        }

        $authors = array_filter( $authors );

        foreach ( $authors as $author ) {
            echo "\t<wp:author>";
            echo '<wp:author_id>' . (int) $author->ID . '</wp:author_id>';
            echo '<wp:author_login>' . wxr_cdata( $author->user_login ) . '</wp:author_login>';
            echo '<wp:author_email>' . wxr_cdata( $author->user_email ) . '</wp:author_email>';
            echo '<wp:author_display_name>' . wxr_cdata( $author->display_name ) . '</wp:author_display_name>';
            echo '<wp:author_first_name>' . wxr_cdata( $author->first_name ) . '</wp:author_first_name>';
            echo '<wp:author_last_name>' . wxr_cdata( $author->last_name ) . '</wp:author_last_name>';
            echo "</wp:author>\n";
        }
    }


    $sitename = sanitize_key( get_bloginfo( 'name' ) );
    if ( ! empty( $sitename ) ) {
        $sitename .= '.';
    }
    $date        = gmdate( 'Y-m-d' );
    $wp_filename = $sitename . 'WordPress.' . $date . '.xml';
    /**
     * Filters the export filename.
     *
     * @since 4.4.0
     *
     * @param string $wp_filename The name of the file for download.
     * @param string $sitename    The site name.
     * @param string $date        Today's date, formatted.
     */
    $filename = apply_filters( 'export_wp_filename', $wp_filename, $sitename, $date );

    header( 'Content-Description: File Transfer' );
    header( 'Content-Disposition: attachment; filename=' . $filename );
    header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );



    add_filter( 'wxr_export_skip_postmeta', 'wxr_filter_postmeta', 10, 2 );

    echo '<?xml version="1.0" encoding="' . get_bloginfo( 'charset' ) . "\" ?>\n";

    ?>
    <!-- This is a WordPress eXtended RSS file generated by WordPress as an export of your site. -->
    <!-- It contains information about your site's posts, pages, comments, categories, and other content. -->
    <!-- You may use this file to transfer that content from one site to another. -->
    <!-- This file is not intended to serve as a complete backup of your site. -->

    <!-- To import this information into a WordPress site follow these steps: -->
    <!-- 1. Log in to that site as an administrator. -->
    <!-- 2. Go to Tools: Import in the WordPress admin panel. -->
    <!-- 3. Install the "WordPress" importer from the list. -->
    <!-- 4. Activate & Run Importer. -->
    <!-- 5. Upload this file using the form provided on that page. -->
    <!-- 6. You will first be asked to map the authors in this export file to users -->
    <!--    on the site. For each author, you may choose to map to an -->
    <!--    existing user on the site or to create a new user. -->
    <!-- 7. WordPress will then import each of the posts, pages, comments, categories, etc. -->
    <!--    contained in this file into your site. -->

    <?php the_generator( 'export' ); ?>
    <rss version="2.0"
         xmlns:excerpt="http://wordpress.org/export/<?php echo WXR_VERSION; ?>/excerpt/"
         xmlns:content="http://purl.org/rss/1.0/modules/content/"
         xmlns:wfw="http://wellformedweb.org/CommentAPI/"
         xmlns:dc="http://purl.org/dc/elements/1.1/"
         xmlns:wp="http://wordpress.org/export/<?php echo WXR_VERSION; ?>/"
    >

        <channel>
            <title><?php bloginfo_rss( 'name' ); ?></title>
            <link><?php bloginfo_rss( 'url' ); ?></link>
            <description><?php bloginfo_rss( 'description' ); ?></description>
            <pubDate><?php echo gmdate( 'D, d M Y H:i:s +0000' ); ?></pubDate>
            <language><?php bloginfo_rss( 'language' ); ?></language>
            <wp:wxr_version><?php echo WXR_VERSION; ?></wp:wxr_version>
            <wp:base_site_url><?php echo wxr_site_url(); ?></wp:base_site_url>
            <wp:base_blog_url><?php bloginfo_rss( 'url' ); ?></wp:base_blog_url>

<!--            --><?php //wxr_authors_list( $post_ids ); ?>


            <?php
            /** This action is documented in wp-includes/feed-rss2.php */
            do_action( 'rss2_head' );
            ?>

            <?php
            if ( $post_ids ){
                /**
                 * @global WP_Query $wp_query WordPress Query object.
                 */
                global $wp_query;

                // Fake being in the loop.
                $wp_query->in_the_loop = true;

                // Fetch 20 posts at a time rather than loading the entire table into memory.
                while ( $next_posts = array_splice( $post_ids, 0, 20 ) ){
                    $where = 'WHERE ID IN (' . implode( ',', $next_posts ) . ')';
                    $posts = $wpdb->get_results( "SELECT * FROM {$wpdb->posts} $where" );

                    // Begin Loop.
                    foreach ( $posts as $post ){
                        setup_postdata( $post );

                        /**
                         * Filters the post title used for WXR exports.
                         *
                         * @param string $post_title Title of the current post.
                         * @since 5.7.0
                         *
                         */
                        $title = wxr_cdata( apply_filters( 'the_title_export', $post->post_title ) );

                        /**
                         * Filters the post content used for WXR exports.
                         *
                         * @param string $post_content Content of the current post.
                         * @since 2.5.0
                         *
                         */
                        $content = wxr_cdata( apply_filters( 'the_content_export', $post->post_content ) );

                        /**
                         * Filters the post excerpt used for WXR exports.
                         *
                         * @param string $post_excerpt Excerpt for the current post.
                         * @since 2.6.0
                         *
                         */
                        $excerpt = wxr_cdata( apply_filters( 'the_excerpt_export', $post->post_excerpt ) );

                        $is_sticky = is_sticky( $post->ID ) ? 1 : 0;
                        ?>
                        <item>
                            <title><?php echo $title; ?></title>
                            <link><?php the_permalink_rss(); ?></link>
                            <pubDate><?php echo mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false ); ?></pubDate>
                            <guid isPermaLink="false"><?php the_guid(); ?></guid>
                            <description></description>
                            <content:encoded><?php echo $content; ?></content:encoded>
                            <excerpt:encoded><?php echo $excerpt; ?></excerpt:encoded>
                            <wp:post_id><?php echo (int)$post->ID; ?></wp:post_id>
                            <wp:post_date><?php echo wxr_cdata( $post->post_date ); ?></wp:post_date>
                            <wp:post_date_gmt><?php echo wxr_cdata( $post->post_date_gmt ); ?></wp:post_date_gmt>
                            <wp:post_modified><?php echo wxr_cdata( $post->post_modified ); ?></wp:post_modified>
                            <wp:post_modified_gmt><?php echo wxr_cdata( $post->post_modified_gmt ); ?></wp:post_modified_gmt>
                            <wp:comment_status><?php echo wxr_cdata( $post->comment_status ); ?></wp:comment_status>
                            <wp:ping_status><?php echo wxr_cdata( $post->ping_status ); ?></wp:ping_status>
                            <wp:post_name><?php echo wxr_cdata( $post->post_name ); ?></wp:post_name>
                            <wp:status><?php echo wxr_cdata( $post->post_status ); ?></wp:status>
                            <wp:post_parent><?php echo (int)$post->post_parent; ?></wp:post_parent>
                            <wp:menu_order><?php echo (int)$post->menu_order; ?></wp:menu_order>
                            <wp:post_type><?php echo wxr_cdata( $post->post_type ); ?></wp:post_type>
                            <wp:post_password><?php echo wxr_cdata( $post->post_password ); ?></wp:post_password>
                            <wp:is_sticky><?php echo (int)$is_sticky; ?></wp:is_sticky>
                            <?php if ( 'attachment' === $post->post_type ) : ?>
                                <wp:attachment_url><?php echo wxr_cdata( wp_get_attachment_url( $post->ID ) ); ?></wp:attachment_url>
                            <?php endif; ?>
                            <?php wxr_post_taxonomy(); ?>
                            <?php
                            $postmeta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE post_id = %d", $post->ID ) );
                            foreach ( $postmeta as $meta ) :
                                /**
                                 * Filters whether to selectively skip post meta used for WXR exports.
                                 *
                                 * Returning a truthy value from the filter will skip the current meta
                                 * object from being exported.
                                 *
                                 * @param bool $skip Whether to skip the current post meta. Default false.
                                 * @param string $meta_key Current meta key.
                                 * @param object $meta Current meta object.
                                 * @since 3.3.0
                                 *
                                 */
                                if ( apply_filters( 'wxr_export_skip_postmeta', false, $meta->meta_key, $meta ) ){
                                    continue;
                                }
                                ?>
                                <wp:postmeta>
                                    <wp:meta_key><?php echo wxr_cdata( $meta->meta_key ); ?></wp:meta_key>
                                    <wp:meta_value><?php echo wxr_cdata( $meta->meta_value ); ?></wp:meta_value>
                                </wp:postmeta>
                            <?php endforeach; ?>
                        </item>
                        <?php
                    }
                }
            }
            ?>
        </channel>
    </rss>
    <?php
}

if ( isset( $_POST['export_from_file_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['export_from_file_nonce'] ) ), 'export_from_file' ) ){
    $lang = null;
    if ( isset( $_POST['language'] ) ){
        $lang = sanitize_text_field( wp_unslash( $_POST['language'] ) );
    }
    $linked_campaign = null;
    if ( isset( $_POST['linked_campaign'])){
        $linked_campaign = sanitize_text_field( wp_unslash( $_POST['linked_campaign'] ) );
    }

    export_fuel( $lang, $linked_campaign );
    die();
}
