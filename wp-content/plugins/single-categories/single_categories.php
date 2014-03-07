<?php
/*  
Plugin Name: Single Categories
Plugin URI: http://www.finbey.de/wp/plugins/single_categories
Description: With this Plugin, you can easy assign whole categories to one or more static pages. Multilingual support.
Version: 1.4.3
Author: Florian Rueckert
Author URI: http://www.finbey.de 
Min WP Version: 3.0
Max WP Version: 3.5
*/

//If Links shouldnt displayed, replace true and add false
// Attention, if you use, links will get forbidden and the more, comment links gets hiddden
// Default is true
$links = true;


 
define('DISPLAY_LINKS', $links);

add_action('plugins_loaded', 'init');

add_filter('the_content', 'searchSiteForCatKeyword');


function init() {
	load_plugin_textdomain('finbey_singlecat', false, basename( dirname( __FILE__ ) ) . '/languages' );
}

function searchSiteForCatKeyword($content)
{
	global $wpdb;
	
	//"finbey_singlecat" = "finbey_singlecat";
	//global "finbey_singlecat";

		if((strpos($content, '[category_id=')) != false){
			$pos1 = (strpos($content, '[category_id='));
			$pos2 = strpos ($content, ']', $pos1);
			$num = substr($content, $pos1, $pos2-$pos1);
			$num = str_replace('[category_id=', '', $num);
			$num = str_replace(']', '', $num);
			$cat_id = (int)$num;
			
			$cat_cont = loadCategory($cat_id);
			$table_name = $wpdb->prefix . "users";
			
			foreach ($cat_cont as $cat_post){
				$prev_text = '';
				$cat_post->post_content = str_replace('<!--more-->', '<breakit />', $cat_post->post_content);
				
				if((strpos($cat_post->post_content, '<breakit />')) != false){
					$pos1 = 0;
					$pos2 = strpos ($cat_post->post_content, '<breakit />', $pos1);
					$prev = substr($cat_post->post_content, $pos1, $pos2-$pos1);
					$prev_text = str_replace('<breakit />', '', $prev);
					$read_more = true;
					
				}
				$author_id = (int)$cat_post->post_author;
				
			    $query = " 
					SELECT $table_name.display_name
					FROM $table_name
					WHERE $table_name.ID = $author_id
				";
				$result = $wpdb->get_results($query);  
				$author = $result[0]->display_name;
				
				$post .= '<div id="post-xx" class="post-xx">';
				
					$post .= '<h2 class="entry-title">';
						$post .= '<br /><a rel="bookmark" title="'. __("Permalink to", "finbey_singlecat") .' '.$cat_post->post_title.'" href="'.$cat_post->guid.'">'.$cat_post->post_title.'</a>';
					$post .= '</h2>';
					
					$post .= '<div class="entry-meta">';
						$post .= '<span class="meta-prep meta-prep-author">'. __("Posted on", "finbey_singlecat").' </span>';
						$post .= '<a rel="bookmark" title="'.date('H:i', strtotime($cat_post->post_date)).'" href="'.$cat_post->guid.'"> ';
							$post .= '<span class="entry-date">'.date('d. F Y', strtotime($cat_post->post_date)).'</span>';
						$post .= '</a>';
						$post .= '<span class="meta-sep"> '. __("by", "finbey_singlecat").' </span>';
						$post .= '<span class="author vcard">';
							$post .= '<a class="url fn n" title="'. __("View all posts by", "finbey_singlecat").' '.$author.'" href="'.$cat_post->guid.'?author='.$author_id.'">'.$author.'</a>';
						$post .= '</span>';
					$post .= '</div>';
					
					$post .= '<div class="entry-content">';
						($prev_text != '') ? $post .= '<p>'.$prev_text : $post .= '<p>'.$cat_post->post_content;
						if(DISPLAY_LINKS !== false){
							if($read_more === true){
								$post .= '<br /><br /><a class="more-link" href="'.$cat_post->guid.'">';
									$post .= __("Continue reading", "finbey_singlecat");
									$post .= '<span class="meta-nav">&#8594;</span>';
								$post .= '</a>';
							}
						}
							$post .= '</p>';
					$post .= '</div>';
					
					if(DISPLAY_LINKS !== false){
						$post .= '<div class="entry-utility">';
							$post .= '<span class="comments-link">';
								$post .= '<a title="'. __("Leave a comment", "finbey_singlecat") . $cat_post->post_title.'" href="'.$cat_post->guid.'#comments">';
									($cat_post->comment_count > 1) ? $post .= $cat_post->comment_count . ' '. __("comments","finbey_singlecat") . ' ' : '';
									($cat_post->comment_count < 1) ? $post .= ' '. __("no comments", "finbey_singlecat") . ' ' : '';
									($cat_post->comment_count == 1) ? $post .= $cat_post->comment_count . ' '. __("comment", "finbey_singlecat") . ' ' : '';
								$post .= '</a>';
							$post .= '</span>';
						$post .= '</div>';
					}
					
				$post .= '</div>';
			}
			$content .= $post;
	}
	$content = str_replace('[category_id='.$num . ']', '', $content);
	
	if(DISPLAY_LINKS === false){
		$post = preg_replace('#<a(.*)>(.*)</a>#Uis', '\\2', $post);
	}
	
	return $content;
}

function loadCategory($cat_id)
{
	$args = array(
		'numberposts'     => 5,
		'offset'          => 0,
		'category'        => $cat_id,
		'orderby'         => 'post_date',
		'order'           => 'DESC',
		'post_type'       => 'post',
		'post_status'     => 'publish',
		'suppress_filters' => true 
	);
	$posts_array = get_posts( $args );
	
	return $posts_array;
}