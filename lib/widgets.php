<?php
/**
 * This file handles all widgets initialization and other widget specific functionality
 */

function widget_manager_widgets_init() {
	
	
	
	// content_by_tag
	if (elgg_is_active_plugin("blog") || elgg_is_active_plugin("file") || elgg_is_active_plugin("pages")) {
		elgg_register_widget_type("content_by_tag", elgg_echo("widgets:content_by_tag:name"), elgg_echo("widgets:content_by_tag:description"), array("profile", "dashboard", "index", "groups"), true);
	}
	
	// entity_statistics
	elgg_register_widget_type("entity_statistics", elgg_echo("widgets:entity_statistics:title"), elgg_echo("widgets:entity_statistics:description"), array("index"));
	
	// free_html
	elgg_register_widget_type("free_html", elgg_echo("widgets:free_html:title"), elgg_echo("widgets:free_html:description"), array("profile", "dashboard", "index", "groups"), true);

	// index_login
	elgg_register_widget_type("index_login", elgg_echo("login"), elgg_echo("widget_manager:widgets:index_login:description"), array("index"));
	
	// likes
	//elgg_register_widget_type("likes", elgg_echo("widgets:likes:title"), elgg_echo("widgets:likes:description"), "index,groups,profile,dashboard", true);
	
	// tagcloud
	elgg_register_widget_type("tagcloud", elgg_echo("tagcloud"), elgg_echo("widgets:tagcloud:description"), array("profile", "dashboard", "index", "groups"), false);
	
	// user_search
	elgg_register_widget_type("user_search", elgg_echo("widgets:user_search:title"), elgg_echo("widgets:user_search:description"), array("admin"));

	// rss widget
	// load SimplePie autoloader
	require_once(elgg_get_plugins_path() . "widget_manager/widgets/rss/vendors/simplepie/autoloader.php");
	
	elgg_register_widget_type("rss", elgg_echo("widgets:rss:title"), elgg_echo("widgets:rss:description"), array("profile", "dashboard", "index", "groups"), true);
	
	// extend CSS
	elgg_extend_view("css/elgg", "widgets/rss/css");
	
	// make cache directory
	if (!is_dir(elgg_get_data_path() . "/widgets/")) {
		mkdir(elgg_get_data_path() . "/widgets/");
	}
	
	if (!is_dir(elgg_get_data_path() . "/widgets/rss/")) {
		mkdir(elgg_get_data_path() . "/widgets/rss/");
	}
	
	// set cache settings
	define("WIDGETS_RSS_CACHE_LOCATION", elgg_get_data_path() . "widgets/rss/");
	define("WIDGETS_RSS_CACHE_DURATION", 600);

	// register cron for cleanup
	elgg_register_plugin_hook_handler("cron", "daily", "widget_manager_widgets_rss_cron_handler");

	// image slider
	elgg_extend_view("css/elgg", "widgets/image_slider/css");
	elgg_register_widget_type("image_slider", elgg_echo("widget_manager:widgets:image_slider:name"), elgg_echo("widget_manager:widgets:image_slider:description"), array("index", "groups"), true);
	
	// index activity
	elgg_register_widget_type("index_activity", elgg_echo("activity"), elgg_echo("widget_manager:widgets:index_activity:description"), array("index"), true);
	
	// bookmarks
	if (elgg_is_active_plugin("bookmarks")) {
		elgg_register_widget_type("index_bookmarks", elgg_echo("bookmarks"), elgg_echo("widget_manager:widgets:index_bookmarks:description"), array("index"), true);
	}
	
	// twitter_search
	elgg_register_widget_type("twitter_search", elgg_echo("widgets:twitter_search:name"), elgg_echo("widgets:twitter_search:description"), array("profile", "dashboard", "index", "groups"), true);
	elgg_register_plugin_hook_handler("widget_settings", "twitter_search", "widget_manager_widgets_twitter_search_settings_save_hook");
	
	// messages
	if (elgg_is_active_plugin("messages")) {
		elgg_register_widget_type("messages", elgg_echo("messages"), elgg_echo("widgets:messages:description"), array("dashboard", "index"), false);
	}
	
	// index_members_online
	elgg_register_widget_type("index_members_online", elgg_echo("widget_manager:widgets:index_members_online:name"), elgg_echo("widget_manager:widgets:index_members_online:description"), array("index"), true);

	// index_members
	elgg_register_widget_type("index_members", elgg_echo("widget_manager:widgets:index_members:name"), elgg_echo("widget_manager:widgets:index_members:description"), array("index"), true);
	
	// favorites
	elgg_register_widget_type("favorites", elgg_echo("widgets:favorites:title"), elgg_echo("widgets:favorites:description"), array("dashboard"));
	elgg_register_event_handler("pagesetup", "system", "widget_manager_widgets_favorites_pagesetup");
	elgg_register_action("favorite/toggle", elgg_get_plugins_path() . "widget_manager/actions/favorites/toggle.php");
	elgg_extend_view("js/elgg", "widgets/favorites/js");
	
	// register widget urls hook
	elgg_register_plugin_hook_handler('widget_url', 'widget_manager', "widget_manager_widgets_url_hook_handler");
}

/**
 * Removes cached rss feeds
 *
 * @param string $hook   name of the hook
 * @param string $type   type of the hook
 * @param string $return current return value
 * @param string $params hook parameters
 *
 * @return void
 */
function widget_manager_widgets_rss_cron_handler($hook, $type, $return, $params) {
	if ($fh = opendir(WIDGETS_RSS_CACHE_LOCATION)) {
		while ($filename = readdir($fh)) {
			if (is_file(WIDGETS_RSS_CACHE_LOCATION . $filename)) {
				if (filemtime(WIDGETS_RSS_CACHE_LOCATION . $filename) < (time() - (24 * 60 * 60))) {
					// remove the cached files
					unlink(WIDGETS_RSS_CACHE_LOCATION . $filename);
				}
			}
		}
	}
}


/**
 * Returns urls for widget titles
 *
 * @param string $hook   name of the hook
 * @param string $type   type of the hook
 * @param string $return current return value
 * @param string $params hook parameters
 *
 * @return string
 */
function widget_manager_widgets_url_hook_handler($hook, $type, $return, $params) {
	$result = $return;
	
	if ($result) {
		// someone else provided already a result
		return $result;
	}
	
	$widget = $params["entity"];
	if (!($widget instanceof ElggWidget)) {
		// not a widget
		return $result;
	}
	
	switch($widget->handler) {
		case "index_activity":
			$result = "/activity";
			break;
		case "index_bookmarks":
			$result = "/bookmarks/all";
			break;
		case "messages":
			$user = elgg_get_logged_in_user_entity();
			if ($user) {
				$result = "/messages/inbox/" . $user->username;
			}
			break;
		case "index_members_online":
		case "index_members":
			if (elgg_is_active_plugin("members")) {
				$result = "/members";
			}
			break;
	}
		
	return $result;
}

/**
 * Strips data-widget-id from submitted script code and saves that
 *
 * @param string $hook   name of the hook
 * @param string $type   type of the hook
 * @param string $return current return value
 * @param string $params hook parameters
 *
 * @return void
 */
function widget_manager_widgets_twitter_search_settings_save_hook($hook, $type, $return, $params) {
	$widget = elgg_extract("widget", $params);
	if ($widget && ($entity_type == "twitter_search")) {
		$embed_code = elgg_extract("embed_code", get_input("params", array(), false)); // do not strip code

		if ($embed_code) {
				
			$start_pos = strpos($embed_code, 'data-widget-id="') + strlen('data-widget-id="');
			$end_pos = strpos($embed_code, '"', $start_pos );
				
			$widget_id = filter_tags(substr($embed_code, $start_pos, $end_pos - $start_pos));
				
			if ($widget_id) {
				$widget->widget_id = $widget_id;
			} else {
				register_error(elgg_echo("widgets:twitter_search:embed_code:error"));
			}
		}
	}
}

/**
 * Function to register menu items for favorites widget during pagesetup
 *
 * @return void
 */
function widget_manager_widgets_favorites_pagesetup() {
	if (widget_manager_widgets_favorites_has_widget()) {
		if ($favorite = widget_manager_widgets_favorites_is_linked()) {
			$text = elgg_view_icon("star-alt");
			$href = "action/favorite/toggle?guid=" . $favorite->getGUID();
			$title = elgg_echo("widgets:favorites:menu:remove");
		} else {
			$text = elgg_view_icon("star-empty");
			$href = "action/favorite/toggle?link=" . elgg_normalize_url(current_page_url());
			$title = elgg_echo("widgets:favorites:menu:add");
		}
		
		elgg_register_menu_item("extras", array(
			"name" => "widget_favorites",
			"title" => $title,
			"href" => $href,
			"text" => $text
		));
	}
}

/**
 * Checks if a user has the favorites widget
 *
 * @param int $owner_guid GUID of the user that should own the widget, defaults to logged in user guid
 *
 * @return boolean
 */
function widget_manager_widgets_favorites_has_widget($owner_guid = 0) {
	$result = false;

	if (empty($owner_guid)) {
		if ($user_guid = elgg_get_logged_in_user_guid()) {
			$owner_guid = $user_guid;
		}
	}

	if (!empty($owner_guid)) {
		$options = array(
			"type" => "object",
			"subtype" => "widget",
			"private_setting_name_value_pairs" => array("handler" => "favorites"),
			"count" => true,
			"owner_guid" => $owner_guid
		);

		if (elgg_get_entities_from_private_settings($options)) {
			$result = true;
		}
	}

	return $result;
}

/**
 * Returns the favorite object related to a given url
 *
 * @param string $url url to check, defaults to current page if empty
 *
 * @return false|ElggObject
 */
function widget_manager_widgets_favorites_is_linked($url = "") {
	$result = false;

	if (empty($url)) {
		$url = current_page_url();
	}

	if (!empty($url)) {
		$options = array(
			"type" => "object",
			"subtype" => "widget_favorite",
			"joins" => array("JOIN " . elgg_get_config("dbprefix") . "objects_entity oe ON e.guid = oe.guid"),
			"wheres" => array("oe.description = '" . sanitise_string($url) . "'"),
			"limit" => 1
		);

		if ($entities = elgg_get_entities($options)) {
			$result = $entities[0];
		}
	}

	return $result;
}