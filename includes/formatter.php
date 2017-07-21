<?php
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

class Formatter
{

	// Keys to filter from Order Information
	const ORDER_INFORMATION = array(
		"id" => "",
		"status" => "",
		"total" => "",

		"date_created" => array( // WC_DateTime Object
			"date" => "",
		),

		"date_modified" => array( // WC_DateTime Object
			"date" => "",
		),

		"date_completed" => array( // WC_DateTime Object
			"date" => "",
		),

		"customer_id" => "",
		"billing" => array( // Array
			"first_name" => "",
			"last_name" => "",
			"email" => "",
			"phone" => "",
			"address_1" => "",
			"city" => "",
			"state" => "",
			"postcode" => "",
			"country" => "",
		),

		"fee_lines" => array( // Array
			// NO KEYS FOR ARRAY OF FEES
			// THERE CAN BE MULTIPLE ENTRIES HERE
			array( // WC_Order_Item_Fee Object
				"data" => array( // Array
					"name" => "",
					"total" => "",
				),
			),
		),

		"line_items" => array( // Array
			// NO KEYS FOR ARRAY OF ITEMS
			// THERE CAN BE MULTIPLE ENTRIES HERE
			array( // WC_Order_Item_Product Object
				"data" => array( // Array
					"name" => "",
					"product_id" => "",
					"quantity" => "",
					"total" => "",
				),
				"meta_data" => array( // Array
					// NO KEYS FOR ARRAY OF META ITEMS
					// THERE CAN BE MULTIPLE ENTRIES HERE
					array( // stdClass Object
						"key" => "",
						"value" => "",
					),
				),
			),
		),
	);

	/**
	 * 
	 */
	public static function arrays_as_table($array_data)
	{
		$html = "";

		$html .= "<table>";

		foreach($array_data as $row)
		{
			$html .= "<tr>";

			// Parse each row as an Array in a new Table
			$html .= self::nested_array_as_nested_table($row);

			$html .= "</tr>";
		}

		$html .= "</table>";

		return $html;
	}

	/**
	 * 
	 */
	public static function nested_array_as_nested_table($array_data, $new_table = false)
	{
		$html = "";

		$html .= $new_table ? "<table>" : "";

		$html .= $new_table ? "<tr>" : "";

		foreach($array_data as $name => $value)
		{
			$html .= "<td>";

			$html .= $name;

			$html .= "<br>";

			// Attempt to decode an encoded Object Array
			$decoded_value = self::encoded_object_as_array($value);
			// Attempt to parse an Object as an Array
			$array_object = self::object_as_array($value);

			// If the Value is an Array
			if(is_array($value))
			{
				// Recursively get a Table of the Nested Array
				$html .= self::nested_array_as_nested_table($value, true);
			}
			// If the Value is an Encoded Object
			elseif(is_array($decoded_value))
			{
				// Use the Decoded Object as an Array and create a Nested Table
				$html .= self::nested_array_as_nested_table($decoded_value, true);
			}
			// If the Value is an Object that can be parsed as an Array
			elseif(is_array($array_object))
			{
				// Use the Object as an Array and create a Nested Table
				$html .= self::nested_array_as_nested_table($array_object, true);
			}
			// If the Value is a regular Value
			else
			{
				$html .= $value;
			}

			$html .= "</td>";
		}

		$html .= $new_table ? "</tr>" : "";

		$html .= $new_table ? "</table>" : "";

		return $html;
	}

	/**
	 * Filters an indexed array to contain only keys found in the filter
	 * Works with nested/complex Multidimensional Arrays
	 */
	public static function filter_results($array, $filter = self::ORDER_INFORMATION)
	{
		$results = array();

		// For each key in the keys to filter from
		foreach($filter as $filter_key => $filter_value)
		{
			// For each item in the Array
			foreach($array as $array_key => $array_value)
			{
				// Remove protected key prefixes (\u0000*\u0000)
				$array_key = preg_replace('/[\x00-\x1F\x7F-\xFF\*]/', '', $array_key);

				// If the value is an Array of items
				if(is_numeric($array_key) && is_array($array_value))
				{
					// Parse each non specified indexed item with the same filter (Multiple entries of the same type)
					array_push($results, self::filter_results($array_value, $filter[0]));
				}
				// If there is a matching key pair
				elseif($filter_key === $array_key)
				{
					// If there is an Array of regular indexed items
					if(is_array($array_value))
					{
						// Recursively filter results from nested arrays
						$results[$array_key] = self::filter_results($array_value, $filter_value);
					}
					else
					{
						// Push the matching key value to the new Array
						$results[$array_key] = $array_value;
					}
				}
			}
		}

		return $results;
	}

	/**
	 * Makes a Multidimensional Array of various Objects into a Multidimensional Array of Arrays
	 * Use: array_walk($array, 'Formatter::to_array_of_arrays');
	 */
	public static function to_array_of_arrays(&$item, $key)
	{
		// If the item is an Object
		if(is_object($item))
		{
			// Get an Array of values from the Object
			$item = (array) $item;
		}
		// If the item is an Array (Cast previously or originally an Array)
		if(is_array($item))
		{
			// Recursively set all Objects of the Array to Arrays
			array_walk($item, 'Formatter::to_array_of_arrays');
		}
	}

	/**
	 * Gets an Array of Variables from an Encoded Object
	 */
	public static function encoded_object_as_array($encoded_object)
	{
		// Check to make sure the Object can be parsed as a String
		if(self::can_be_string($encoded_object))
		{
			// Decode the Object and parse it as an Array
			$decoded_object_vars = self::object_as_array(json_decode($encoded_object));

			// As long as the Object was decoded and parsed successfully as an Array
			if($decoded_object_vars)
			{
				// Return the parsed Array
				return $decoded_object_vars;
			}
		}

		return false;
	}

	/**
	 * Gets an Array of Variables from an Object
	 */
	public static function object_as_array($object)
	{
		// Check to make sure an Object was passed
		if(is_object($object))
		{
			// Get the Variables of the object as an Array
			$object_vars = get_object_vars($object);

			// As long as Variables were retrieved as an Array successfully
			if(is_array($object_vars) && !empty($object_vars))
			{
				// Return the Array
				return $object_vars;
			}
		}

		return false;
	}

	/**
	 * Rotates an Array Clockwise, keeping Key/Value pairs
	 */
	public static function rotate_array($array)
	{
		// Rotate the Keys of the Array
		$rotated_keys = self::rotate(self::_array_keys($array));
		// Rotate the Values of the Array
		$rotated_values = self::rotate(self::_array_values($array));

		// Return the Rotated Array with Matching Keys/Values
		return self::_array_combine($rotated_keys, $rotated_values);
	}

	/**
	 * Rotates an Array Clockwise
	 */
	private static function rotate($array)
	{
		// Rotate the Array using Array Manipulation
		array_unshift($array, null);
		$array = call_user_func_array('array_map', $array);

		return $array;
	}

	/**
	 * Gets Array of Keys from Array, including Nested Arrays
	 * Does not preserve Values from the Original Array
	 */
	private static function _array_keys($array)
	{
		$keys = array();

		// For each Key/Value pair in the Array
		foreach($array as $key => $value)
		{
			// If there is a Nested Array
			if(is_array($value))
			{
				// Recursively get the Keys of the Nested Array as a new Nested Array
				array_push($keys, self::_array_keys($value));
			}
			// If there is a regular Key found
			else
			{
				// Push the Key to the Value of the new Array
				array_push($keys, $key);
			}
		}

		return $keys;
	}

	/**
	 * Gets Array of Values from Array, including Nested Arrays
	 * Does not preserve Keys from the Original Array
	 */
	private static function _array_values($array)
	{
		$values = array();

		// For each Key/Value pair in the Array
		foreach ($array as $key => $value)
		{
			// If there is a Nested Array
			if(is_array($value))
			{
				// Recursively get the Values of the Nested Array as a new Nested Array
				array_push($values, self::_array_values($value));
			}
			// If there is a regular Value found
			else
			{
				// Push the Value to the Value of the new Array
				array_push($values, $value);
			}
		}

		return $values;
	}

	/**
	 * Combines two Arrays using one Array as Keys and the other Array as Values
	 */
	private static function _array_combine($keys, $values)
	{
		$array = array();

		// For each Index/Key in the Key Array
		foreach ($keys as $index => $key)
		{
			// If there is a Nested Array
			if(is_array($key))
			{
				// Recursively pair the Nested Array Keys and Values as a new Nested Array
				array_push($array, self::_array_combine($key, $values[$index]));
			}
			// If there was a Null Key
			elseif(is_null($key))
			{
				// Push the Value with a default Index
				array_push($array, $values[$index]);
			}
			// If there is a regular Key/Value pair
			else
			{
				// Push the Value with the given Key as an Index
				$array[$key] = $values[$index];
			}
		}

		return $array;
	}

	/**
	 * If a Variable can be a String
	 */
	private function can_be_string($variable)
	{
		// If the Variable...
		if(
			// Is not an Array AND
			(!is_array($variable)) &&
			(
				// Is not an Object AND can be set as a String, OR
				(!is_object($variable) && settype($variable, 'string') !== false) ||
				// Is an Object AND has a __toString method
				(is_object($variable) && method_exists($variable, '__toString'))
			)
		)
		{
			return true;
		}

		return false;
	}

}
