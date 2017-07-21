<?php
/*
	Plugin Name: WooCommerce Report Generator
	Description: Generates reports based on WooCommerce Orders
	Version: 1.0.0
	Author: <a href="https://github.com/lkarinja">Leejae Karinja</a>
	License: GPL3
	License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

/*
	WooCommerce Report Generator
	Copyright (C) 2017 Leejae Karinja

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
*/

// Prevents execution outside of core WordPress
if(!defined('ABSPATH'))
{
	exit;
}

// Defines path to this plugin
define('WC_REPORT_GENERATOR_PATH', plugin_dir_path(__FILE__));

// Include the Formatter
include_once(WC_REPORT_GENERATOR_PATH . 'includes/formatter.php');

// If the class for the plugin is not defined
if(!class_exists('WC_Report_Generator'))
{
	// Define the class for the plugin
	class WC_Report_Generator
	{
		/**
		 * Plugin constructor
		 */
		public function __construct()
		{
			// Used for debugging, allows us to 'echo' for JS 'alert()' and such
			ob_start();

			// Set plugin textdomain for the Admin Pages
			$this->textdomain = 'wc-report-generator';

			// On every page load
			add_action('init', array($this, 'init'));
		}

		/**
		 * Creates a controller page for the Plugin in the Admin Menu
		 */
		public function init()
		{
			// Add page in the admin
			add_action('admin_menu', array($this, 'add_admin_page'));
		}

		/**
		 * Adds a controller page under WooCommerce -> Report Generator
		 *
		 * Parts of this function are referenced from Terry Tsang (http://shop.terrytsang.com) Extra Fee Option Plugin (http://terrytsang.com/shop/shop/woocommerce-extra-fee-option/)
		 * Licensed under GPL2
		 */
		public function add_admin_page()
		{
			add_submenu_page(
				'woocommerce',
				__('Report Generator', $this->textdomain),
				__('Report Generator', $this->textdomain),
				'manage_options',
				'wc-report-generator',
				array(
					$this,
					'admin_menu_controller'
				)
			);
		}

		/**
		 * Plugin controller page in the Admin Menu
		 *
		 * Parts of this function are referenced from Terry Tsang (http://shop.terrytsang.com) Extra Fee Option Plugin (http://terrytsang.com/shop/shop/woocommerce-extra-fee-option/)
		 * Licensed under GPL2
		 */
		public function admin_menu_controller()
		{
			// If a generate was requested
			if(isset($_POST['generate']))
			{
				// Generate a report of all orders
				$this->generate_report();
			}

			$actionurl = $_SERVER['REQUEST_URI'];

			// HTML/inline PHP for the options page
			?>
			<h3><?php _e('Report Generator', $this->textdomain); ?></h3>
			<form action="<?php echo $actionurl; ?>" method="post">
				<input class="button-primary" type="submit" name="generate" value="<?php _e('Generate Test Report', $this->textdomain); ?>" id="submitbutton" />
			</form>
			<?php
		}

		/**
		 *
		 */
		public function generate_report(){
			// Get orders
			$orders = self::get_orders();
			$orders_array = array();

			//$items_array = array();

			// For each order retrieved
			foreach($orders as $order)
			{
				// Get the ID
				$order_id = $order->ID;
				// Get the Order Object
				$wc_order = wc_get_order($order_id);
				// Get all data associated with the order
				$order_data = $wc_order->get_data();

				//$order_items = $wc_order->get_items();

				// Make all objects contained in the order into arrays
				array_walk($order_data, 'Formatter::to_array_of_arrays');

				// Filter the results based on specified keys
				$order_data = Formatter::filter_results($order_data);

				// Push the filtered data to the desired array
				array_push($orders_array, $order_data);

				//array_push($items_array, $order_items);
			}

			// CSS Formatting for the Table
			$css = "
				<style>
				table, th, td {
					border: 1px solid black;
				}
				</style>
			";
			echo $css;

			// Print the array as a table in the admin page
			echo Formatter::arrays_as_table($orders_array);
		}

		/**
		 * Gets all WooCommerce orders
		 */
		public static function get_orders()
		{
			// Data to query
			$query_data = array(
				'post_type' => wc_get_order_types(),
				'post_status'=> array_keys(wc_get_order_statuses()),
				'posts_per_page' => '-1',
			);
			// Resulting posts retrieved
			$posts = get_posts($query_data);

			return $posts;
		}

	}
	// Create new instance of 'WC_Report_Generator' class
	$wc_report_generator = new WC_Report_Generator();
}
