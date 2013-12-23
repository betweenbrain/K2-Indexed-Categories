<?php defined('_JEXEC') or die;

/**
 * File       indexed_categories.php
 * Created    12/16/13 2:30 PM
 * Author     Matt Thomas | matt@betweenbrain.com | http://betweenbrain.com
 * Support    https://github.com/betweenbrain/
 * Copyright  Copyright (C) 2013 betweenbrain llc. All Rights Reserved.
 * License    GNU GPL v3 or later
 */

// Load the K2 Plugin API
JLoader::register('K2Plugin', JPATH_ADMINISTRATOR . '/components/com_k2/lib/k2plugin.php');

// Instantiate class for K2 plugin events
class plgK2Indexed_categories extends K2Plugin
{

	var $pluginName = 'indexed_categories';
	var $pluginNameHumanReadable = 'K2 - Indexed Categories';

	function plgK2Indexed_categories(& $subject, $results)
	{
		parent::__construct($subject, $results);
	}

	/**
	 * Function to Update item's extra_fields_search data with tag names
	 *
	 * @param $row
	 * @param $isNew
	 */
	function onAfterK2Save(&$row, $isNew)
	{

		$app = JFactory::getApplication();
		$db  = JFactory::getDbo();

		if ($app->isAdmin())
		{

			$categories    = $this->fetchCategories($row->id);
			$categoryNames = null;

			foreach ($categories as $category)
			{
				$categoryNames .= $category->name . ' ';
			}

			$query = 'UPDATE ' . $db->nameQuote('#__k2_items') . '
				SET ' . $db->nameQuote('extra_fields_search') . ' = CONCAT(
					' . $db->nameQuote('extra_fields_search') . ',' . $db->Quote($categoryNames) . '
				)
				WHERE id = ' . $db->Quote($row->id) . '';
			$db->setQuery($query);
			$db->query();
		}
	}

	/**
	 * function to fetch an item's categories
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	private function fetchCategories($id)
	{

		$db = JFactory::getDbo();

		$query = 'SELECT catid
			FROM ' . $db->nameQuote('#__k2_items') . '
			WHERE Id = ' . $db->Quote($id) . '
			AND published = 1';

		$db->setQuery($query);
		$catIds[] = $db->loadResult();

		if (file_exists(JPATH_SITE . '/plugins/k2/k2additonalcategories.php'))
		{

			$query = 'SELECT catid
				FROM ' . $db->nameQuote('#__k2_additional_categories') . '
				WHERE itemID = ' . $db->Quote($id);

			$db->setQuery($query);
			$addCats = $db->loadResultArray();

			foreach ($addCats as $addCat)
			{
				$catIds[] = $addCat;
			}
		}

		$query = 'SELECT name
			FROM ' . $db->nameQuote('#__k2_categories') . '
			WHERE Id IN (' . implode(',', $catIds) . ')
			AND published = 1';

		$db->setQuery($query);
		$categories = $db->loadObjectList();

		return $categories;
	}
}