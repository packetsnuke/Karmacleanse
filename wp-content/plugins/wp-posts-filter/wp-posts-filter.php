<?php
/**
 * @package wp-posts-filter
 * @version 0.2
 */

/*
 * Plugin name: WP Posts Filter
 * Plugin URI: http://olezhek.net/codez/wp-posts-filter
 * Description: This plugin filters posts by category or tag to list them in the particular page.
 * Author: Oleg Lepeshchenko
 * Version: 0.2
 * Author URI: http://olezhek.net/
 * License: GPL2
 */

/*  Copyright 2012  Oleg Lepeshchenko  (email: mail@olezhek.net)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License, version 2, as
 published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

class wp_posts_filter {

	public function wppf_init( ) {
		wp_enqueue_script( 'postbox' );
		wp_enqueue_script( 'link' );
		load_plugin_textdomain( 'wp-posts-filter', false, 'wp-posts-filter/languages' );
	}

	public function wppf_filter_me( $query ) {
		global $paged;
		$wppf_opts = get_option( 'wppf_opts', array( ) );
		if ( is_array( $wppf_opts ) ) {
			if ( is_home( ) ) {
				if ( isset( $wppf_opts['frontpage'] ) ) {
					$filter_by = isset( $wppf_opts['frontpage']['filterby'] ) ? $wppf_opts['frontpage']['filterby'] : 'none';
					if ( $filter_by == 'cats' || $filter_by == 'both' ) {
						if ( isset( $wppf_opts['frontpage']['cats'] ) ) {
							$query->set( 'cat', implode( ',', $wppf_opts['frontpage']['cats'] ) );
						}
					}
					if ( $filter_by == 'tags' || $filter_by == 'both' ) {
						if ( isset( $wppf_opts['frontpage']['tags'] ) ) {
							$query->set( 'tag__in', $wppf_opts['frontpage']['tags'] );
						}
					}
				}
			}
		}
	}

	public function wppf_js( ) {
		wp_register_script( 'wppf_scripts', plugins_url( 'js/scripts.js', __FILE__ ) );
		wp_enqueue_script( 'wppf_scripts' );
	}

	public function wppf_css( ) {
		wp_register_style( 'wppf_styles', plugins_url( 'css/style.css', __FILE__ ) );
		wp_enqueue_style( 'wppf_styles' );
	}

	public function wppf_settings_page( ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'wp-posts-filter' ) );
		}
		echo '<div class="wrap">' . PHP_EOL;
		echo '<h2>' . __( 'WP Posts Filter settings', 'wp-posts-filter' ) . '</h2>' . PHP_EOL;
		echo '<form method="POST" action="options.php">' . PHP_EOL;
		settings_fields( 'wppf-opts' );
		$all_categories = get_categories( array( 'hide_empty' => 0 ) );
		$all_tags = get_tags( array( 'hide_empty' => 0 ) );
		$wppf_opts = get_option( 'wppf_opts', array( ) );
		$default = array( 'posts_per_page' => get_option( 'posts_per_page', 10 ), 'heading_tag' => 'h2', 'heading_class' => 'entry-title', 'content_tag' => 'div', 'content_class' => 'entry-content', );
		submit_button( );
		echo '<h3>' . __( 'Global settings', 'wp-posts-filter' ) . '</h3>' . PHP_EOL;
		echo '<div class="wppf-settings-values">';
		echo '<h4>' . __( 'Maximum posts per page: *', 'wp-posts-filter' ) . '</h4>' . PHP_EOL;
		echo self::wppf_input( 'text', array( 'name' => "wppf_opts[posts_per_page]", 'value' => isset( $wppf_opts['posts_per_page'] ) ? $wppf_opts['posts_per_page'] : $default['posts_per_page'], 'size' => 3, ) );
		// tags and styles settings
		$custom_options = array( 'heading_tag' => __( 'Heading tag for the posts on a page:', 'wp-posts-filter' ), 'heading_class' => __( 'Heading class for the posts on a page:', 'wp-posts-filter' ), 'content_tag' => __( 'Content tag for the posts on a page:', 'wp-posts-filter' ), 'content_class' => __( 'Content class for the posts on a page:', 'wp-posts-filter' ), );
		echo '<h3>' . __( 'Tags and styles settings **', 'wp-posts-filter' ) . '</h3>' . PHP_EOL;
		foreach ( $custom_options as $custom_opt => $title ) {
			echo '<h4>' . $title . '</h4>' . PHP_EOL;
			echo self::wppf_input( 'text', array( 'name' => "wppf_opts[$custom_opt]", 'value' => isset( $wppf_opts[$custom_opt] ) ? $wppf_opts[$custom_opt] : $default[$custom_opt], 'size' => 20, ) );
		}
		echo '<br /><br /><br /><small>' . __( '* Works for all pages except for the home page. To customize posts limit for the home page please refer to "Settings" -> "Reading" section', 'wp-posts-filter' ) . '</small><br />' . PHP_EOL;
		echo '<small>' . __( '** You can set up custom styles and tags for every page you have. To perform this please refer to the page settings below', 'wp-posts-filter' ) . '</small>' . PHP_EOL;
		echo '</div><br /><br />' . PHP_EOL;
		echo '<h3>' . __( 'Pages: ', 'wp-posts-filter' ) . '</h3>' . PHP_EOL;
		echo '<div class="wppf-settings-values">' . PHP_EOL;

		$allowed_cats = array( );
		$pages = get_pages( );

		if ( is_array( $wppf_opts ) ) {
			$allowed_cats = isset( $wppf_opts['frontpage']['cats'] ) ? $wppf_opts['frontpage']['cats'] : array( );
			$allowed_tags = isset( $wppf_opts['frontpage']['tags'] ) ? $wppf_opts['frontpage']['tags'] : array( );
		}
		echo '<div id="poststuff" class="metabox-holder">';
		add_meta_box( 'wppf_box_frontpage', __( 'Home page', 'wp-posts-filter' ) . ' <span class="postbox-title-action">[<a style="edit-box open-box" target="_blank" href="' . site_url( ) . '">' . __( 'Link', 'wp-posts-filter' ) . '</a>]</span>', array( 'wp_posts_filter', 'wppf_metabox' ), null, 'advanced', 'default', array( 'page' => 'frontpage', 'all_cats' => $all_categories, 'allowed_cats' => $allowed_cats, 'all_tags' => $all_tags, 'allowed_tags' => $allowed_tags, 'options' => $wppf_opts, 'default' => $default, ) );
		foreach ( $pages as $page ) {
			if ( is_array( $wppf_opts ) ) {
				$allowed_cats = isset( $wppf_opts[$page->ID]['cats'] ) ? $wppf_opts[$page->ID]['cats'] : array( );
				$allowed_tags = isset( $wppf_opts[$page->ID]['tags'] ) ? $wppf_opts[$page->ID]['tags'] : array( );
			}
			add_meta_box( "wppf_box_{$page->ID}", $page->post_title . ' <span class="postbox-title-action">[<a style="edit-box open-box" target="_blank" href="' . get_page_link( $page->ID ) . '">' . __( 'Link', 'wp-posts-filter' ) . '</a>]</span>', array( 'wp_posts_filter', 'wppf_metabox' ), null, 'advanced', 'default', array( 'page' => $page->ID, 'all_cats' => $all_categories, 'allowed_cats' => $allowed_cats, 'all_tags' => $all_tags, 'allowed_tags' => $allowed_tags, 'options' => $wppf_opts, 'default' => $default, ) );
		}
		do_meta_boxes( null, 'advanced', null );
		echo "</div>";
		echo '</div>' . PHP_EOL;
		submit_button( );
		echo '</form>' . PHP_EOL;
		echo '</div>' . PHP_EOL;
	}

	private function wppf_get( $what, $all, $allowed, $page = 'frontpage' ) {
		$result = '';
		$doNotCheck = true;
		if ( count( $allowed ) > 0 ) {
			$doNotCheck = false;
		}
		if ( $what == 'cats' ) {
			$title = __( 'Show pages from categories:', 'wp-posts-filter' );
		} else if ( $what == 'tags' ) {
			$title = __( 'Show pages containing tags:', 'wp-posts-filter' );
		}
		$result .= '<h4>' . $title . '</h4>' . PHP_EOL;
		$result .= '<fieldset>' . PHP_EOL;
		$checkall_opts = array( 'name' => "wppf_{$what}_{$page}_checkall", 'class' => 'checkall', );
		if ( self::wppf_check_if_all( $all, $allowed ) ) {
			$checkall_opts['checked'] = 'checked';
		}
		$result .= self::wppf_input( 'checkbox', $checkall_opts, __( 'Check all', 'wp-posts-filter' ) );
		$cnt = count( $all );
		$cols = 1;
		if ( $cnt <= 8 ) {
			$cols = 2;
		} else if ( $cnt <= 30 ) {
			$cols = 3;
		} else if ( $cnt <= 100 ) {
			$cols = 4;
		} else if ( $cnt <= 200 ) {
			$cols = 5;
		} else if ( $cnt <= 300 ) {
			$cols = 8;
		} else {
			$cols = 10;
		}
		$col_capacity = ceil( $cnt / $cols );
		$result .= '<table style="margin-left:10px;">' . PHP_EOL;
		for ( $i = 0; $i < $col_capacity; $i++ ) {
			$result .= '<tr>' . PHP_EOL;
			for ( $j = 0; $j < $cols; $j++ ) {
				$index = $j * $col_capacity + $i;
				if ( isset( $all[$index] ) ) {
					$val = $what == 'cats' ? $all[$index]->cat_ID : $all[$index]->term_id;
					$opts = array( 'name' => "wppf_opts[$page][$what][]", 'id' => "wppf_opts_{$page}_{$what}_{$val}", 'value' => $val, 'onClick' => "wppf_uncheck_check_all(\"wppf_opts_{$page}_{$what}_{$val}\")", 'class' => 'wppf_opts_checkboxes', );
					if ( ! $doNotCheck ) {
						if ( in_array( $val, $allowed ) ) {
							$opts['checked'] = 'checked';
						}
					}
					$result .= '<td>' . self::wppf_input( 'checkbox', $opts, $all[$index]->name ) . '</td>' . PHP_EOL;
				}
			}
			$result .= '</tr>' . PHP_EOL;
		}
		$result .= '</table>' . PHP_EOL;
		$result .= '</fieldset>' . PHP_EOL;
		return $result;
	}

	/**
	 * Checks whether all items are checked or not. Used for "Check all" checkbox
	 */
	private function wppf_check_if_all( $all, $allowed ) {
		$a = array( );
		foreach ( $all as $item ) {
			if ( isset( $item->cat_ID ) ) {
				$a[] = $item->cat_ID;
			} else if ( isset( $item->term_id ) ) {
				$a[] = $item->term_id;
			}
		}
		return count( array_diff( $a, $allowed ) ) != 0 ? false : true;
	}

	private function wppf_label( $text, $opts = array() ) {
		$opts_string = '';
		if ( is_array( $opts ) ) {
			foreach ( $opts as $name => $value ) {
				$opts_string .= " $name='$value'";
			}
		} else {
			return '';
		}
		return "<label$opts_string>$text</label>" . PHP_EOL;
	}

	private function wppf_input( $type, $opts = array(), $text = '' ) {
		$result = '';
		$opts_string = '';
		if ( is_array( $opts ) ) {
			foreach ( $opts as $name => $value ) {
				$opts_string .= " $name='$value'";
			}
		} else {
			return '';
		}
		$result = "<input type='$type' $opts_string /> ";
		if ( $type == 'checkbox' || $type == 'radio' ) {
			if ( $text == 'cats' ) {
				$result .= __( 'Categories', 'wp-posts-filter' );
			} else if ( $text == 'tags' ) {
				$result .= __( 'Tags', 'wp-posts-filter' );
			} else if ( $text == 'both' ) {
				$result .= __( 'Both categories and tags', 'wp-posts-filter' );
			} else if ( $text == 'none' ) {
				$result .= __( 'None', 'wp-posts-filter' );
			} else {
				$result .= $text;
			}
		}
		$result .= PHP_EOL;
		return $result;
	}

	private function wppf_filterby_block( $page, $selected = 'none', $set = array('tags', 'cats', 'both', 'none') ) {
		$result = '';
		foreach ( $set as $item ) {
			$params = array( 'name' => "wppf_opts[$page][filterby]", 'value' => $item, );
			if ( $selected == $item ) {
				$params['checked'] = 'checked';
			}
			$result .= self::wppf_input( 'radio', $params, $item );
			$result .= '<br />' . PHP_EOL;
		}
		return $result;
	}

	public function wppf_settings_menu( ) {
		add_options_page( __( 'WP Posts Filter Settings', 'wp-posts-filter' ), __( 'WP Posts Filter', 'wp-posts-filter' ), 'manage_options', 'wp-posts-filter', array( 'wp_posts_filter', 'wppf_settings_page' ) );
	}

	/**
	 * Replacement for get_previous_posts_link() WP function
	 */
	private function wppf_get_previous_posts_link( $label = null ) {
		global $paged;

		if ( null === $label )
			$label = __( '&laquo; Previous Page', 'wp-posts-filter' );

		if ( $paged > 1 ) {
			$attr = apply_filters( 'previous_posts_link_attributes', '' );
			return '<a href="' . self::wppf_previous_posts( false ) . "\" $attr>" . preg_replace( '/&([^#])(?![a-z]{1,8};)/', '&#038;$1', $label ) . '</a>';
		}
	}

	/**
	 * Replacement for previous_posts() WP function
	 */
	private function wppf_previous_posts( $echo = true ) {
		$output = esc_url( self::wppf_get_previous_posts_page_link( ) );

		if ( $echo )
			echo $output;
		else
			return $output;
	}

	/**
	 * Replacement for get_previous_posts_page_link() WP function.
	 *
	 */
	private function wppf_get_previous_posts_page_link( ) {
		global $paged;
		$nextpage = intval( $paged ) - 1;
		if ( $nextpage < 1 )
			$nextpage = 1;
		return get_pagenum_link( $nextpage );
	}

	/**
	 * Replacement for get_next_posts_link() WP function
	 * @param string $label
	 * @param int $max_num_pages - $wp_query->max_num_pages. The total number of pages. Is the result of $found_posts / $posts_per_page (WP_Query)
	 * @param int $max_page
	 */
	private function wppf_get_next_posts_link( $label = null, $max_num_pages, $max_page = 0 ) {
		global $paged;

		if ( ! $max_page )
			$max_page = $max_num_pages;

		if ( ! $paged )
			$paged = 1;

		$nextpage = intval( $paged ) + 1;

		if ( null === $label )
			$label = __( 'Next Page &raquo;', 'wp-posts-filter' );

		if ( $nextpage <= $max_page ) {
			$attr = apply_filters( 'next_posts_link_attributes', '' );
			return '<a href="' . self::wppf_next_posts( $max_page, false ) . "\" $attr>" . preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label ) . '</a>';
		}
	}

	/**
	 * Replacement for next_posts() WP function
	 */
	private function wppf_next_posts( $max_page = 0, $echo = true ) {
		$output = esc_url( self::wppf_get_next_posts_page_link( $max_page ) );
		if ( $echo )
			echo $output;
		else
			return $output;
	}

	/**
	 * Replacement for get_next_posts_page_link() WP function
	 */
	private function wppf_get_next_posts_page_link( $max_page = 0 ) {
		global $paged;
		if ( ! $paged )
			$paged = 1;
		$nextpage = intval( $paged ) + 1;
		if ( ! $max_page || $max_page >= $nextpage )
			return get_pagenum_link( $nextpage );
	}

	public function wppf_admin_init( ) {
		register_setting( 'wppf-opts', 'wppf_opts' );
	}

	/**
	 * [wppf] shortcode
	 */
	public function wppf_shortcode( $atts ) {
		global $post, $paged;
		$result = '';
		$wppf_opts = get_option( 'wppf_opts', array( ) );
		$params = array( );
		if ( isset( $wppf_opts[$post->ID]['filterby'] ) ) {
			if ( isset( $wppf_opts[$post->ID]['posts_per_page'] ) ) {
				$params['posts_per_page'] = $wppf_opts[$post->ID]['posts_per_page'];
			} else if ( isset( $wppf_opts['posts_per_page'] ) ) {
				$params['posts_per_page'] = $wppf_opts['posts_per_page'];
			} else {
				$params['posts_per_page'] = get_option( 'posts_per_page', 10 );
			}
			extract( shortcode_atts( array( 'heading_tag' => $wppf_opts['heading_tag'], 'heading_class' => $wppf_opts['heading_class'], 'content_tag' => $wppf_opts['content_tag'], 'content_class' => $wppf_opts['content_class'], 'per_page' => $params['posts_per_page'], ), $atts ) );
			if ( $wppf_opts[$post->ID]['filterby'] == 'cats' || $wppf_opts[$post->ID]['filterby'] == 'both' ) {
				if ( isset( $wppf_opts[$post->ID]['cats'] ) ) {
					$params['cat'] = implode( ',', $wppf_opts[$post->ID]['cats'] );
				}
			}
			if ( $wppf_opts[$post->ID]['filterby'] == 'tags' || $wppf_opts[$post->ID]['filterby'] == 'both' ) {
				if ( isset( $wppf_opts[$post->ID]['tags'] ) ) {
					$params['tag__in'] = implode( ',', $wppf_opts[$post->ID]['tags'] );
				}
			}
			$tmp_post = $post;
			$params['paged'] = $paged;
			$filtered_posts = new WP_Query( $params );
			foreach ( $filtered_posts->posts as $post ) {
				setup_postdata( $post );
				$result .= "<{$heading_tag} class='{$heading_class}'>
                    <a href='" . get_permalink( ) . "' title='{$post->post_title}'>
                        {$post->post_title}
                    </a>
                </{$heading_tag}>
                <{$content_tag} class='{$content_class}'>";
				$result .= get_the_excerpt( );
				$result .= "</{$content_tag}>" . PHP_EOL;
			}
			$pages = ceil( $filtered_posts->found_posts / $params['posts_per_page'] );
			if ( $pages > 1 ) {
				$result .= '<div class="navigation">' . PHP_EOL;
				$result .= '<div class="nav-previous">' . self::wppf_get_previous_posts_link( __( '&laquo; Previous Page', 'wp-posts-filter' ) ) . '</div>';
				// $result .= preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', ' &#8212; ' );
				$result .= '<div class="nav-next">' . self::wppf_get_next_posts_link( __( 'Next Page &raquo;', 'wp-posts-filter' ), $pages ) . '</div>';
				$result .= '</div>' . PHP_EOL;
			}
			$post = $tmp_post;
		}
		return $result;
	}

	public function wppf_metabox( $post, $metabox ) {
		echo '<h4>' . __( 'Filter by:', 'wp-posts-filter' ) . '</h4>' . PHP_EOL;
		echo self::wppf_filterby_block( $metabox['args']['page'], isset( $metabox['args']['options'][$metabox['args']['page']]['filterby'] ) ? $metabox['args']['options'][$metabox['args']['page']]['filterby'] : 'none' );
		// Custom option for posts_per_page
		if ( $metabox['args']['page'] != 'frontpage' ) {
			echo '<h4>' . __( 'Maximum posts per this page:', 'wp-posts-filter' ) . '</h4>' . PHP_EOL;
			$opts = array( 'name' => $metabox['args']['page'] . '_posts_per_page_sw', 'value' => 1, 'onClick' => 'wppf_toggle("' . $metabox['args']['page'] . '_posts_per_page")', 'id' => $metabox['args']['page'] . '_posts_per_page_sw', );
			$co = array( 'name' => "wppf_opts[{$metabox['args']['page']}][posts_per_page]", 'size' => 3, 'class' => $metabox['args']['page'] . '_posts_per_page', );
			if ( isset( $metabox['args']['options'][$metabox['args']['page']]['posts_per_page'] ) ) {
				$co['value'] = $metabox['args']['options'][$metabox['args']['page']]['posts_per_page'];
				$opts['checked'] = 'checked';
			} else {
				$co['value'] = $metabox['args']['default']['posts_per_page'];
			}
			if ( ! isset( $opts['checked'] ) ) {
				$co['disabled'] = 'disabled';
			}
			echo self::wppf_input( 'checkbox', $opts, __( 'Custom value: ', 'wp-posts-filter' ) );
			echo self::wppf_input( 'text', $co );
		}
		echo self::wppf_get( 'cats', $metabox['args']['all_cats'], $metabox['args']['allowed_cats'], $metabox['args']['page'] );
		echo self::wppf_get( 'tags', $metabox['args']['all_tags'], $metabox['args']['allowed_tags'], $metabox['args']['page'] );
		// Custom options for heading and contents tags and styles
		$custom_options = array( 'heading_tag' => __( 'Heading tag for the posts on this page:', 'wp-posts-filter' ), 'heading_class' => __( 'Heading class for the posts on this page:', 'wp-posts-filter' ), 'content_tag' => __( 'Content tag for the posts on this page:', 'wp-posts-filter' ), 'content_class' => __( 'Content class for the posts on this page:', 'wp-posts-filter' ), );
		foreach ( $custom_options as $custom_opt => $title ) {
			echo '<h4>' . $title . '</h4>' . PHP_EOL;
			$opts = array( 'name' => $metabox['args']['page'] . "_{$custom_opt}_sw", 'value' => 1, 'onClick' => 'wppf_toggle("' . $metabox['args']['page'] . '_' . $custom_opt . '")', 'id' => $metabox['args']['page'] . "_{$custom_opt}_sw", );
			$co = array( 'name' => "wppf_opts[{$metabox['args']['page']}][{$custom_opt}]", 'size' => 20, 'class' => $metabox['args']['page'] . "_{$custom_opt}", );
			if ( isset( $metabox['args']['options'][$metabox['args']['page']][$custom_opt] ) ) {
				$co['value'] = $metabox['args']['options'][$metabox['args']['page']][$custom_opt];
				$opts['checked'] = 'checked';
			} else if ( isset( $metabox['args']['options'][$custom_opt] ) ) {
				$co['value'] = $metabox['args']['options'][$custom_opt];
			} else {
				$co['value'] = $metabox['args']['default'][$custom_opt];
			}
			if ( ! isset( $opts['checked'] ) ) {
				$co['disabled'] = 'disabled';
			}
			echo self::wppf_input( 'checkbox', $opts, __( 'Custom value: ', 'wp-posts-filter' ) );
			echo self::wppf_input( 'text', $co );
		}
	}

	public function wppf_uninstall( ) {
		delete_option( 'wppf_opts' );
	}

}

add_action( 'admin_menu', array( 'wp_posts_filter', 'wppf_settings_menu' ) );
add_action( 'admin_print_scripts', array( 'wp_posts_filter', 'wppf_js' ) );
add_action( 'admin_print_styles', array( 'wp_posts_filter', 'wppf_css' ) );
add_action( 'admin_init', array( 'wp_posts_filter', 'wppf_admin_init' ) );
add_action( 'pre_get_posts', array( 'wp_posts_filter', 'wppf_filter_me' ) );
add_shortcode( 'wppf', array( 'wp_posts_filter', 'wppf_shortcode' ) );
add_action( 'init', array( 'wp_posts_filter', 'wppf_init' ) );
register_uninstall_hook( __FILE__, array( 'wp_posts_filter', 'wppf_uninstall' ) );
?>