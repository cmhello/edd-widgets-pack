<?php
/** 
 * EDD Most Commented Widget
 *
 * @package      EDD Widgets Pack
 * @author       Matt Varone <contact@mattvarone.com>
 * @copyright    Copyright (c) 2012, Matt Varone
 * @license      http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        1.0
*/


/**
 * EDD Most Commented Widget Class
 *
 * A list of EDD most commented downloads.
 *  
 * @access   private
 * @return   void
 * @since    1.0
*/

if ( ! class_exists( 'EDD_Most_Commented' ) ) {
    class EDD_Most_Commented extends WP_Widget {

        /**
         * Construct
         *
         * @return   void
         * @since    1.0
        */

        function __construct()
        {   
            // hook updates
            add_action( 'save_post', array( &$this, 'delete_cache' ) );
            add_action( 'delete_post', array( &$this, 'delete_cache' ) );
            add_action( 'update_option_start_of_week', array( &$this, 'delete_cache' ) );
            add_action( 'update_option_gmt_offset', array( &$this, 'delete_cache' ) );

            // contruct widget
            parent::__construct( false, __( 'EDD Most Commented', 'edd-widgets-pack' ), array( 'description' => sprintf( __( 'A list of EDD most commented %s.', 'edd-widgets-pack' ), edd_get_label_plural( true ) ) ) );
        }


        /**
         * Widget
         *
         * @return   void
         * @since    1.0
        */

        function widget( $args, $instance )
        {
            
            // check if comments have been activated for EDD Downloads
            if ( ! post_type_supports( 'download', 'comments' ) )
            return;

            if ( false == $cache = get_transient( 'edd_widgets_most_commented' ) ) {

                // get the title and apply filters
                $title = apply_filters( 'widget_title', $instance['title'] ? $instance['title'] : '' );

                // set the limit 
                $limit = isset( $instance['limit'] ) ? $instance['limit'] : 4; 
                
                // get show price boolean
                $show_price = isset( $instance['show_price'] ) && $instance['show_price'] === 1 ? 1 : 0;

                // get the thumbnail boolean
                $thumbnail = isset( $instance['thumbnail'] ) && $instance['thumbnail'] === 1 ? 1 : 0;

                // set the thumbnail size
                $thumbnail_size = isset( $instance['thumbnail_size'] ) ? $instance['thumbnail_size'] : 80;

                // start collecting the output
                $out = "";

                // check if there is a title
                if ( $title ) {
                    // add the title to the ouput
                    $out .= $args['before_title'] . $title . $args['after_title'];
                }

                // set the params
                $params = array( 
                    'post_type'      => 'download', 
                    'posts_per_page' => $limit, 
                    'post_status'    => 'publish', 
                    'orderby'        => 'comment_count', 
                 );

                // get the most commented downloads
                $most_commented = get_posts( $params );

                // check the downloads
                if ( is_null( $most_commented ) || empty( $most_commented ) ) {
                    // return if there are no downloads
                    return;

                } else {
                    // start the list output
                    $out .= "<ul class=\"widget-most-commented\">\n";

                    // set the link structure
                    $link = "<a href=\"%s\" title=\"%s\" class=\"%s\" rel=\"bookmark\">%s</a>\n";
                    
                    // filter the thumbnail size
                    $thumbnail_size = apply_filters( 'edd_widgets_most_commented_thumbnail_size', array( $thumbnail_size, $thumbnail_size ) );

                    // loop trough all downloads
                    foreach ( $most_commented as $download ) {
                        // get the title 
                        $title = apply_filters( 'the_title', $download->post_title, $download->ID );
						$title_attr = apply_filters( 'the_title_attribute', $download->post_title, $download->ID );

                        // get the post thumbnail
                        if ( $thumbnail === 1 && function_exists( 'has_post_thumbnail' ) && has_post_thumbnail( $download->ID ) ) {
                            $post_thumbnail = get_the_post_thumbnail( $download->ID, $thumbnail_size, array( 'title' => esc_attr( $title_attr ) ) ) . "\n";
                            $out .= "<li class=\"widget-download-with-thumbnail\">\n";
                            $out .= sprintf( $link, get_permalink( $download->ID ), esc_attr( $title_attr ), 'widget-download-thumb', $post_thumbnail );
                        } else {
                            $out .= "<li>\n";
                        }

                        // append the download's title
                        $out .= sprintf( $link, get_permalink( $download->ID ), esc_attr( $title_attr ), 'widget-download-title', $title );
                        
                        // get the price
                        if ( $show_price === 1 ) {
                            if ( edd_has_variable_prices( $download->ID ) ) {
                                $price = edd_price_range( $download->ID );
                            } else {
                                $price = edd_currency_filter( edd_get_download_price( $download->ID ) );
                            }
                            $out .= sprintf( "<span class=\"widget-download-price\">%s</span>\n", $price ); 
                        }
                        
                        // finish this element
                        $out .= "</li>\n";
                    }
                    // finish the list
                    $out .= "</ul>\n";
                }

                // set the widget's containers
                $cache = $args['before_widget'] . $out . $args['after_widget'];

                // store the result on a temporal transient
                set_transient( 'edd_widgets_most_commented', $cache );

            }

            echo $cache;

        }


        /**
         * Update
         *
         * @return   array
         * @since    1.0
        */

        function update( $new_instance, $old_instance )
        {     
            $instance = $old_instance;

            // sanitize title
            $instance['title'] = strip_tags( $new_instance['title'] );

            // sanitize limit
            $instance['limit'] = strip_tags( $new_instance['limit'] );
            $instance['limit'] = ( ( bool ) preg_match( '/^\-?[0-9]+$/', $instance['limit'] ) ) && $instance['limit'] > -2 ? $instance['limit'] : 4;

            // sanitize show price
            $instance['show_price'] = strip_tags( $new_instance['show_price'] );
            $instance['show_price'] = $instance['show_price'] === '1' ? 1 : 0;
            
            // sanitize thumbnail
            $instance['thumbnail'] = strip_tags( $new_instance['thumbnail'] );
            $instance['thumbnail'] = $instance['thumbnail'] === '1' ? 1 : 0;

            // sanitize thumbnail size
            $instance['thumbnail_size'] = strip_tags( $new_instance['thumbnail_size'] );
            $instance['thumbnail_size'] = ( ( bool ) preg_match( '/^[0-9]+$/', $instance['thumbnail_size'] ) ) ? $instance['thumbnail_size'] : 80;

            // delete cache
            $this->delete_cache();

            return $instance;
        }


        /**
         * Delete Cache
         *
         * @return   void
         * @since    1.0
        */    

        function delete_cache()
        {
            delete_transient( 'edd_widgets_most_commented' );
        }

        /**
         * Form
         *
         * @return   void
         * @since    1.0
        */

        function form( $instance )
        {
            $title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
            $limit = isset( $instance['limit'] ) ? esc_attr( $instance['limit'] ) : 4;
            $show_price = isset( $instance['show_price'] ) ? esc_attr( $instance['show_price'] ) : 0;            
            $thumbnail = isset( $instance['thumbnail'] ) ? esc_attr( $instance['thumbnail'] ) : 0;
            $thumbnail_size = isset( $instance['thumbnail_size'] ) ? esc_attr( $instance['thumbnail_size'] ) : 80;

            ?>
                <p>
                    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'edd-widgets-pack' ); ?></label>
                    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
                </p>
                <p>
                    <label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php printf( __('Number of %s to show:', 'edd-widgets-pack' ), edd_get_label_plural( true ) ); ?></label>
                    <input class="small" size="3" id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="text" value="<?php echo $limit; ?>"/>
                </p>
                <p>
                    <input id="<?php echo $this->get_field_id( 'show_price' ); ?>" name="<?php echo $this->get_field_name( 'show_price' ); ?>" type="checkbox" value="1" <?php checked( '1', $show_price ); ?>/>
                    <label for="<?php echo $this->get_field_id( 'show_price' ); ?>"><?php _e( 'Display price?', 'edd-widgets-pack' ); ?></label> 
                </p>                
                <p>
                    <input id="<?php echo $this->get_field_id( 'thumbnail' ); ?>" name="<?php echo $this->get_field_name( 'thumbnail' ); ?>" type="checkbox" value="1" <?php checked( '1', $thumbnail ); ?>/>
                    <label for="<?php echo $this->get_field_id( 'thumbnail' ); ?>"><?php _e( 'Display thumbnails?', 'edd-widgets-pack' ); ?></label> 
                </p>
                <p>
                    <label for="<?php echo $this->get_field_id( 'thumbnail_size' ); ?>"><?php _e( 'Size of the thumbnails, e.g. <em>80</em> = 80x80px', 'edd-widgets-pack' ); ?></label> 
                    <input class="widefat" id="<?php echo $this->get_field_id( 'thumbnail_size' ); ?>" name="<?php echo $this->get_field_name( 'thumbnail_size' ); ?>" type="text" value="<?php echo $thumbnail_size; ?>" />
                </p>
            <?php
        }

    }
}


/**
 * Register Most Commented Widget
 *  
 * @access   private
 * @return   void
 * @since    1.0
*/

if ( ! function_exists( 'edd_widgets_pack_register_most_commented_widget' ) ) {
    function edd_widgets_pack_register_most_commented_widget() {
        register_widget( 'EDD_Most_Commented' );
    }
}
add_action( 'widgets_init', 'edd_widgets_pack_register_most_commented_widget', 10 );