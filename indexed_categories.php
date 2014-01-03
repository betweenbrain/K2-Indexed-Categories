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

	/**
	 * Constructor
	 */
	function __construct(&$subject, $results)
	{
		parent::__construct($subject, $results);
		$this->app = JFactory::getApplication();
		$this->db  = JFactory::getDbo();
		$this->log = JLog::getInstance();
	}

	/**
	 * Function to Update item's extra_fields_search data with tag names
	 *
	 * @param $row
	 * @param $isNew
	 */
	function onAfterK2Save(&$row, $isNew)
	{

		if ($this->app->isAdmin())
		{

			$categories    = $this->getCategories($row->id);
			$categoryNames = null;

			foreach ($categories as $category)
			{
				$categoryNames .= $category->name . ' ';
			}

			$this->setK2ItemPluginsData($row->id, $categories, 'categories');

			$query = 'UPDATE ' . $this->db->nameQuote('#__k2_items') . '
				SET ' . $this->db->nameQuote('extra_fields_search') . ' = CONCAT(
					' . $this->db->nameQuote('extra_fields_search') . ',' . $this->db->Quote($categoryNames) . '
				)
				WHERE id = ' . $this->db->Quote($row->id) . '';
			$this->db->setQuery($query);
			$this->db->query();
		}
	}

	/**
	 * function to fetch an item's categories
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	private function getCategories($id)
	{

		$query = 'SELECT catid
			FROM ' . $this->db->nameQuote('#__k2_items') . '
			WHERE Id = ' . $this->db->Quote($id) . '
			AND published = 1';

		$this->db->setQuery($query);
		$catIds[] = $this->db->loadResult();

		$addCatsPlugin = JPluginHelper::isEnabled('k2', 'k2additonalcategories');

		if ($addCatsPlugin)
		{

			$query = 'SELECT catid
				FROM ' . $this->db->nameQuote('#__k2_additional_categories') . '
				WHERE itemID = ' . $this->db->Quote($id);

			$this->db->setQuery($query);
			$addCats = $this->db->loadResultArray();

			foreach ($addCats as $addCat)
			{
				$catIds[] = $addCat;
			}
		}

		$query = 'SELECT name
			FROM ' . $this->db->nameQuote('#__k2_categories') . '
			WHERE Id IN (' . implode(',', $catIds) . ')
			AND published = 1';

		$this->db->setQuery($query);
		$categories = $this->db->loadObjectList();

		return $categories;
	}

	private function setK2ItemPluginsData($id, $data, $type)
	{
		$query = 'SELECT ' . $this->db->nameQuote('plugins') .
			' FROM ' . $this->db->nameQuote('#__k2_items') .
			' WHERE id = ' . $this->db->Quote($id) . '';

		$this->db->setQuery($query);
		$plugins = $this->db->loadResult();

		$plugins = parse_ini_string($plugins, false, INI_SCANNER_RAW);

		if (!($plugins[$type]))
		{
			$data  = json_encode($data);
			$query = 'UPDATE ' . $this->db->nameQuote('#__k2_itemsz') . '
					SET ' . $this->db->nameQuote('plugins') . ' = CONCAT(
						' . $this->db->nameQuote('plugins') . ',' . $this->db->Quote($type . '=' . $data . "\n") . '
					)
					WHERE id = ' . $this->db->Quote($id) . '';
			$this->db->setQuery($query);
			$this->db->query();
		}
	}
}