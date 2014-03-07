=== WP Posts Filter ===
Contributors: olezhek.net
Donate link: 
Tags: post, filter, tag, category
Requires at least: 3.0
Tested up to: 3.3.2
Stable tag: 0.2

This plugin filters posts by category or tag to list them at the particular page.

== Description ==

This plugin filters posts by tags and/or categories. It allows to filter posts for the main page as well as for a particular page. You can set up a filter for any page but it’ll work only if you’ve placed the shortcode at that page. There is also the possibility to turn off the filter for a particular page not losing the page's filter settings.

= The plugin filters posts by three ways: =
1. Category
2. Tag
3. both Category and Tag

= Few notes about the plugin behavior =
1. the plugin outputs post if and only if the post has at least one category that is marked in plugin's settings page,
2. the plugin outputs post if and only if the post contains at least one tag that is marked in plugin's settings page,
3. the plugin outputs post "by category and tag" if and only if the post has the category **and** the tag that are marked in plugin's settings page.

You can set the number of posts per page for a particular page. By default, "Posts per page" (Settings -> Reading) option is used.

= Shortcode =

You need to place the shortcode to make the filter work at the particular page. With that shortcode, you can also setup some options.
Here is how it looks by default:

`[wppf]`

This way, the plugin filters posts using default settings or settings defined in the plugin settings page. Here is the full writing of the shortcode:

`[wppf heading_tag="h2" heading_class="entry-title" content_tag="div" content_class="entry-content" per_page="10"]`

Parameters definition:

* `heading_tag` - html tag for the post title. `h2` by default,
* `heading_class` - css style for the post title. `entry-title` by default,
* `content_tag` - html tag for the post excerpt. `div` by default,
* `content_class` - css style for the post excerpt. `entry-content` by default,
* `per_page` - number of items per page. Settings -> Reading "Posts per page" or 10 by default,

== Installation ==

1. Download and unpack the archive containing the plugin to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Setup a filter for the desired page
3. Place the shortcode in that page. You don't have to do this if you settting up the filter for the main page

== Frequently Asked Questions ==

= This plugin doesn't work! =

For this plugin, I've used techniques that are recommended by WordPress development team. The main problem for now is the possible plugin inconsistency with other plugins. If you've faced with the plugin malfunctioning:
1. Turn off (do not uninstall, of course!) all the plugins but WP Posts Filter and check if it works.
2. (regardless of what you've got in the pt.1) Start a new issue [here](https://bitbucket.org/olezhek/wp-posts-filter/issues/new "New issue for WP Posts Filter at bitbucket.org") or [here](http://wordpress.org/tags/wp-posts-filter?forum_id=10#postform "New discussion topic at wordpress.org") and provide a list of all plugins you have, WordPress version, WP Posts Filter version, results of the pt.1 with the detailed description of a problem.
... and I'll help you.
 

== Screenshots ==

1. Plugin settings page
2. Showing filtered posts at the main page
3. Showing filtered posts at the particular page with the pagination

== Other notes ==

You have to have JavaScript enabled in your browser so the plugin settings page could work properly.
Plugin home page: http://olezhek.net/codez/wp-posts-filter/
Plugin mirror repo: https://bitbucket.org/olezhek/wp-posts-filter/
Any comments/suggestions/ideas on this plugin would be appreciated.
If you want to create a translation for this plugin, you can use wp-posts-filter.pot file as the starting point (located in the root directory of the plugin). More info about plugin internationalization could be found [here](http://codex.wordpress.org/I18n_for_WordPress_Developers "I18n for WordPress Developers").
Contact me if you like to add a translation for your language to this plugin.

== Changelog ==

= 0.2 =
* Added a feature to put selected posts in a custom place of a page.
* Added a possibility to set up styles and tags of selected posts in the settings page.
* Fixed display of the page navigation links. They're now wrapped inside `div` tags with classes `nav-previous` and `nav-next` for "previous page" and "next page" links respectively.
* Fixed display of tags and categories lists in the settings page.

= 0.1 =
* Initial version

== Upgrade Notice ==

= 0.2 =

* Since version 0.2 has a feature to place selected posts in a custom place of the page, you'll probably find that the page display changed. This way, you need to place the shortcode in the place you need.

== Arbitrary section ==

