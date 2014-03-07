=== Single Categories ===

	Contributors: Florian Rückert
	Tags: category, categories, single, page, posts, post to page, category to page, post category, 
		post, einzel, categorie, artikel, artikel zu kategorie, einzel, einzelkategorie, seite, kategorie auf seite
	Requires at least: 3.0 
	Tested up to: 3.5.1
	Stable tag: 1.4.3
	License: GPLv3 or later

	
== License ==

 Copyright 2012-2013 by Florian Rückert 

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

== Note ==
 Just a while longer

 Sry for let you waiting, but i can't support the plugin at the moment.
 I'll answer all you're questions soon. Hope you don't mind waiting
 Greetz Flo
	
== Description ==
	
	Single Categories:
	
	
	This plugin allows you to filter your categorys and assign them to single pages (static pages).
	
	You also can add a description or any other text before you display 
	the posts of the choosen category. 
	For a step by step (und eine Deutsche Anleitung) tutorial, 
	visit http://www.finbey.de/wp/plugins/single_categories .
	
	If you like this plugin, please rate it :)
	
	If any bugs appear:
	
	- Write a Support ticket
	- mail me to "f[{dot}]rueckert[{at}]finbey{[dot]}de"  
		--But please.. Add a meaningful subject.
		
	See the FAQ for frequently asked questions


== Installation ==

	Please Visit http://www.finbey.de/wp/plugins/single_categories for a step by step tutorial (Deutsche Anleitung enthalten)

	1. Install Plugin
	2. Add [category_id=x] to the page you want to show your post of a category 
		(x is the category id you want to display. For example: [category_id=3]).
	2.1 See FAQ for "Howto find category id"
	3. Finish. That was easy was it? :)
	
	
== Frequently Asked Questions ==

	= Why is it untranslated? =
	
		When you don't set you language in you wp-config.php file, 
		the plugin don't know what to translate.
			
	= How can I manually set my language in wp-config.php? = 
	
		1. Goto your Wordpress directory
		2. Open the wp-config.php file
		3. Search the string "WPLANG"
			ther is a line with that "define ('WPLANG', '');"
		4. Set your language iso between the 2nd inverted comma.
		
		e.g:
		'de_DE' for german
		'en_EN' for english
		'es_ES' for Spain
		'fr_FR' for french 
		
		so when you edited it (to english), it should looks like that:
		define ('WPLANG', 'en_EN'); 
		
	= What if my language isn't supported? =
		
		If your language is not supported, you can translate it by your own :)
		You can move to my plugin site, and download the Main file.
		
		When you work like in the description, and translate this few word, 
		upload it to my site and in the next update this language is supported to.
		(Your WP can support it as far you are finished - follow the description :-D )
		
		PLEASE DO NOT CHANGE THE LANGUAGE STRINGS IN CODE!
		IF YOU DO SO AND UPDATE ALL YOUR WORK GETS LOST
		
	= How do I translate for my own? =
	
		To translate by your own, you need a programm like pedit(http://www.poedit.net/)
		When you installed poedit, you can download a blank translation file from 
		http://www.finbey.de/wp/plugins/single_categories/finbey_singlecat-default.po
		
		then thera are just a few steps to go.
		
		1. start poedit
		2. File > new catalogue from pot file
		3. Change (left on bottom) the filetype from GNU-GETTEXT... ->
			to All filetypes
		4. Choose the downloaded file
		5. Fill out the fields like translator and email.
		6. Fill out language with your lang code (like en_EN)
			 (2nd have to be uppercase)
		7. Don't fill out plural..
		8. Press OK
		9. Select a place to save the file an rename it to 
			"finbey_singlecat-eg_EG.po" (eg_eg is a placeholder for your language)
		10. Klick on the english words, and on bottom is the translation box.
		11. Fill in the correct translation (for the plugin) and click on the next one 
		12. If you're finish, upload your .po file, to 
			http://www.finbey.de/wp/plugins/single_categories/upload.php 
		13. I'll add it to the plugin. ATTENTION: if you upload the correct tranlation, 
			you'll get an description, how to manually add your tranlation (so you can use it)
		
		
	= I can't see an numeric Category id on my site = 	
	
		If its like you can't see some category id in links, you got 2 options
		
		1. Change the permalinks settings

			- Go to (in you backend or also called wp-admin) to settings
			- then permalinks (may write other in your language don't know)(in left sidebar )
			- choose an option like "http://www.site.com?p=123"
		
		2. Get id from categories itself
		
			An other Method, is that you go to Posts, categories and hover 
			over the category names, there should be displayed an
			url onb bottom of your browser. The category id is the tag_ID 
			so if tag_ID=3 is displayed in the link, the category id is 3.
		
	= What should I do if I'll find a bug or an error? =
	
		Open the support form on the plugin site, or write me a mail.
		The plugin site and my mail adress are written in the description.
		

== Changelog == 
	= 1.4.3 =
	* Add Support for WP 3.5
	
	= 1.4.2 =
	* Add faq entrys
	* Fix bug (Read more always there)
	
	= 1.4.1 =
	* Fix author link bug
	
	= 1.4 =
	* Add special funcs

	= 1.3 =
	* Add default language translation package

	= 1.2 =
	* FAQ
	
	= 1.0 =
	* Make stable
	* Fixed language bugs
	* Changed FAQ

	= 0.10 =
	* Fix translation bug
	* Add FAQ
	
	= 0.9 =
	* Add multilinguality
	* Add english

	= 0.8.1 =
	* Change/Add descriptions

	= 0.8 =
	* Add "More feature" (Plugin support more link)
	* Test on Older WP (req. 3.0 or later)

	= 0.7 =
	* Remove Hard Coded Filter
	* Add Tag [category_id=x] to filter
	* Filter performance
	* Add feature to show text before category output

	= 0.6 =
	* Add html to display like WP

	= 0.5 =
	* Display comments
	* Add WP structur

	= 0.4 =
	* Show content
	* Validate links
	* Get linkdata from WP

	= 0.3 = 
	* Add filter for categorys on pages
	* Show headlines 
	* make klickable

	= 0.2 =
	* Add hard-coded category-filter

	= 0.1 =
	* Created plugin 



