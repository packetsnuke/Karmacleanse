<?php
/**
 * Template Name: Home 
 *
 * The home page template displays a the blurb plus a single blog page.
 *
 * @package WooFramework
 * @subpackage Template
 */

 global $woo_options;
 get_header();
 
/**
 * The Variables
 *
 * Setup default variables, overriding them if the "Theme Options" have been saved.
 */
	
	$settings = array(
					'thumb_w' => 450, 
					'thumb_h' => 450, 
					'thumb_align' => 'alignright'
					);
					
	$settings = woo_get_dynamic_values( $settings );
?>
    <!-- #content Starts -->
    <div id="content" class="col-full">
    <section id="main" class="col-left"> 
        <?php woo_main_before(); ?>
        
 <?php
            if ( have_posts() ) { $count = 0;
                while ( have_posts() ) { the_post(); $count++;
        ?>                                                           
            <article <?php post_class(); ?>>
                
                <header>
                    <h1><?php the_title(); ?></h1>
                </header>
                
                <section class="entry">
                    <?php the_content(); ?>

                    <?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'woothemes' ), 'after' => '</div>' ) ); ?>
                </section><!-- /.entry -->

                <?php edit_post_link( __( '{ Edit }', 'woothemes' ), '<span class="small">', '</span>' ); ?>
                
            </article><!-- /.post -->
            
            <?php
                // Determine wether or not to display comments here, based on "Theme Options".
                if ( isset( $woo_options['woo_comments'] ) && in_array( $woo_options['woo_comments'], array( 'page', 'both' ) ) ) {
                    comments_template();
                }

                } // End WHILE Loop
            } else {
        ?>
            <article <?php post_class(); ?>>
                <p><?php _e( 'Sorry, no posts matched your criteria.', 'woothemes' ); ?></p>
            </article><!-- /.post -->
        <?php } // End IF Statement ?>      
		

        
        <?php woo_main_after(); ?>

        <?php
        	if ( get_query_var( 'paged') ) { $paged = get_query_var( 'paged' ); } elseif ( get_query_var( 'page') ) { $paged = get_query_var( 'page' ); } else { $paged = 1; }
        	
        	$query_args = array(
        						'post_type' => 'post', 
        						'paged' => $paged
        					);
        	
        	$query_args = apply_filters( 'woo_blog_template_query_args', $query_args ); // Do not remove. Used to exclude categories from displaying here.
        	
        	remove_filter( 'pre_get_posts', 'woo_exclude_categories_homepage' );
        	
        	query_posts( $query_args );
        	
        	if ( have_posts() ) {
        		$count = 0;
        		while ( have_posts() ) { the_post(); $count++;
        ?>                                                            
            <!-- Post Starts -->
            <article <?php post_class(); ?>>

                <?php if ( isset( $woo_options['woo_post_content'] ) && $woo_options['woo_post_content'] != 'content' ) { woo_image( 'width=' . $settings['thumb_w'] . '&height=' . $settings['thumb_h'] . '&class=thumbnail ' . $settings['thumb_align'] ); } ?>
                
                <header>
                	<h1><a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h1>
                </header>
                
                <?php woo_post_meta(); ?>
                
                <section class="entry fix">
					<?php global $more; $more = 0; ?>	                                        
                    <?php if ( isset( $woo_options['woo_post_content'] ) && $woo_options['woo_post_content'] == 'content' ) { the_content(__( 'Read More...', 'woothemes' ) ); } else { the_excerpt(); } ?>
                </section>
    			
                <footer class="post-more">      
					<span class="comments"><?php comments_popup_link( __( 'Leave a comment', 'woothemes' ), __( '1 Comment', 'woothemes' ), __( '% Comments', 'woothemes' ) ); ?></span>
                	<?php if ( isset( $woo_options['woo_post_content'] ) && $woo_options['woo_post_content'] == 'excerpt' ) { ?>
                    <span class="read-more"><a href="<?php the_permalink(); ?>" title="<?php esc_attr_e( 'Continue Reading &rarr;', 'woothemes' ); ?>"><?php _e( 'Continue Reading &rarr;', 'woothemes' ); ?></a></span>
                    <?php } ?>
                </footer>   
    
            </article><!-- /.post -->
                                                
        <?php
        		} // End WHILE Loop
        	
        	} else {
        ?>
            <article <?php post_class(); ?>>
                <p><?php _e( 'Sorry, no posts matched your criteria.', 'woothemes' ); ?></p>
            </article><!-- /.post -->
        <?php } // End IF Statement ?> 
        
        <?php woo_loop_after(); ?> 
    
        <?php woo_pagenav(); ?>
		<?php wp_reset_query(); ?>                

        </section><!-- /#main -->
        
        <?php woo_main_after(); ?>
            
		<?php get_sidebar(); ?>

    </div><!-- /#content -->    
		
<?php get_footer(); ?>