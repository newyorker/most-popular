<?php
/**
 * Plugin Name: Most Popular
 * Plugin URI: http://github.com/newyorker/most-popular
 * Description: Generate a module called Most Popular to show the most visited stories on a site
 * Version: 0.1
 * Author: Michael Donohoe
 * Author URI: http://donohoe.io
 * License: GPL2
 *
 * Copyright 2014  Michael Donohoe  (email : donohoe@newyorker.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as 
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function most_popular_insert() {
	$status = "cached";
	if ( false === ( $most_popular_html = get_transient( 'most_popular_html' ) ) ) {
		$most_popular_html = MostPopular::generateModule();
		$status = "regenerated";
	}
	print $most_popular_html . "<!-- " . $status . " -->\n";
}

class MostPopular {
	public static $VERSION		= "0.1";
	private $NAME;
	private $MENU_SLUG			= "most-popular";
	private $MENU_TITLE			= "Most Popular";
	private $MENU_PAGE_TITLE	= "Most Popular > Settings";
	private $OPTIONS_KEY		= "most-popular";
	private $CAPABILITY			= "manage_options";
	private $OPTION_DEFAULTS	= array(
		"api_source"		=> "none",
		"api_key"			=> "",
		"api_secret"		=> "",
		"refresh_rate"		=> 20,
		"number_of_results" => 5
	);

	function MostPopular() {
		add_action('admin_menu',   array(&$this, 'addSettingsSubMenu'));
		add_action('admin_footer', array(&$this, 'displayAdminWarning'));
	}

	/**
	* Generate the MP HTML and save it until its time to regenerate it
	*/
	public function generateModule() {

		$options = get_option("most-popular");

		if(!isset($options["api_source"]) || empty($options["api_source"]) || $options["api_source"]=="none") {
			return "<!-- Most Popular disabled -->";
		}

		$results = MostPopular::fetchDataFromParsley($options);
		$len   = count($results['data']);
		$limit = ($len > intval($options["number_of_results"])) ? intval($options["number_of_results"]) : $len;

		$html  = "\n<div id=\"most-popular\">\n  <h5>Most&nbsp;<br>Popular</h5>\n  <ol>\n";

		for ($i = 0; $i < $limit; $i++) {
			$item  = $results['data'][$i];
			$title = str_replace(array(" : The New Yorker", ": The New Yorker", "- The New Yorker"), "", $item['title']);
			$html .= "    <li>\n      <p><a href=\"" . $item["url"] . "?src=mp\">" . $title . "</a></p>\n      <h3>By " . $item["author"] . "</h3>\n    </li>\n";
		}

		$html .= "  </ol>\n</div>\n";

		set_transient( 'most_popular_html', $html, MINUTE_IN_SECONDS * intval($options["refresh_rate"]) );

		return $html;
	}

	/**
	* Reach out to Parsely and ask them whats going on
	*/
	public function fetchDataFromParsley($options) {
		$params	 = "&days=3&page=1&limit=8&sort=_hits";
		$url	 = "https://api.parsely.com/v2/analytics/posts?apikey=" . $options['api_key'] . "&secret=" . $options['api_secret'] . $params;		

		$content = wp_remote_get($url, array('timeout' => 200) );
		$body    = wp_remote_retrieve_body($content);

		return json_decode($body, true);
	} 

	/**
	* Settings
	*/
	public function displaySettings() {
		if (!current_user_can($this->CAPABILITY)) {
			wp_die(__('Sorry but you do not have sufficient permissions to access this page.'));
		}

		$errors = array();
		$isSaved = false;
		$options = $this->getOptions();

		if (isset($_POST["isMostPopularSettings"]) && $_POST["isMostPopularSettings"] == 'Y') {

			if (empty($_POST["api_source"])) {
				array_push($errors, "Please specify a Source.");
			} else {
				$options["api_source"] = sanitize_text_field($_POST["api_source"]);

				if ($options["api_source"] !== "none") {
					$options["api_source"] = "parsley"; /* Only this supported so far */
				}
			}

			if (empty($_POST["api_key"])) {
				array_push($errors, "Please specify the API Key");
			} else {
				$options["api_key"] = sanitize_text_field($_POST["api_key"]);
			}

			if (empty($_POST["api_secret"])) {
				array_push($errors, "Please specify the Secret");
			} else {
				$options["api_secret"] = sanitize_text_field($_POST["api_secret"]);
			}

			if (empty($_POST["refresh_rate"]) || $_POST["refresh_rate"] === 0) {
				array_push($errors, "Please tell us how often we should refresh the information");
			} else {
				$options["refresh_rate"] = sanitize_text_field($_POST["refresh_rate"]);
			}

			if (empty($_POST["number_of_results"]) || $_POST["number_of_results"] === 0) {
				array_push($errors, "Please tell us how many results should be displayed");
			} else {
				$options["number_of_results"] = sanitize_text_field($_POST["number_of_results"]);
			}

			if (empty($errors)) {
				update_option($this->OPTIONS_KEY, $options);
				$isSaved = true;
			}
		}
		include("settings.php");
	}

	/**
	* Add settings page in WordPress Settings menu.
	*/
	public function addSettingsSubMenu() {
		add_options_page($this->MENU_PAGE_TITLE,
						 $this->MENU_TITLE,
						 $this->CAPABILITY,
						 $this->MENU_SLUG,
						 array(&$this, 'displaySettings'));
	}

	/**
	* Adds a 'Settings' link to the Plugins screen in WP admin
	*/
	public function addPluginMetaLinks($links) {
		array_unshift($links, '<a href="'. $this->getSettingsURL() . '">' . __('Settings'));
		return $links;
	}

	/**
	* Show warning if not properly setup yet
	*/
	public function displayAdminWarning() {
		$options = $this->getOptions();
		if (!isset($options['api_key']) || empty($options['api_key'])) {
			?>
			<div id='message' class='error'>
				<p><strong>Most Popular plugin is not active.</strong> You need to <a href='<?php echo $this->getSettingsURL(); ?>'>update settings</a> to get things going.</p>
			</div>
			<?php
		}
	}

	/**
	* Get the URL of the plugin settings page
	*/
	private function getSettingsURL() {
		return admin_url('options-general.php?page=' . $this->MENU_SLUG);
	}

	/**
	* Returns options
	*/
	private function getOptions() {
		$options = get_option($this->OPTIONS_KEY);
		if ($options === false) {
			$options = $this->OPTION_DEFAULTS;
		} else {
			$options = array_merge($this->OPTION_DEFAULTS, $options);
		}
		return $options;
	}

	public function printSuccessMessage($message) {
		?>
		<div class='success'><p><strong><?php print esc_html($message); ?></strong></p></div>
		<?php
	}

	public function printErrorMessage($message) {
		?>
		<div id='message' class='error'><p><strong><?php print esc_html($message); ?></strong></p></div>
		<?php
	}

	public function printSelectTag($name, $options, $selectedOption="") {
		$tag = '<select name="' . esc_attr($name) . '" id="' . esc_attr($name) . '">';
		foreach ($options as $key => $val) {
			$tag .= '<option value="' . esc_attr($key) . '"';
			if ($selectedOption == $key) { $tag .= ' selected="selected"'; }
			$tag .= '>'. esc_html($val) . '</option>';
		}
		$tag .= '</select>';
		print $tag;
	}

	public function printTextTag($name, $value, $options=array()) {
		$tag = '<input type="text" name="' . esc_attr($name). '" id="' . esc_attr($name) . '" value="' . esc_attr($value) . '"';
		foreach ($options as $key => $val) {
			$tag .= ' ' . esc_attr($key) . '="' . esc_attr($val) . '"';
		}
		$tag .= ' />';
		print $tag;
	}
}

if (class_exists('MostPopular')) {
	define('MOST_POPULAR_VERSION', MostPopular::$VERSION);
	$mostpopular = new MostPopular();
}
?>
