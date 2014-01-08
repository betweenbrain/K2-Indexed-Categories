<?php defined('_JEXEC') or die;

/**
 * File       indexed_categories.php
 * Created    12/16/13 2:30 PM
 * Author     Matt Thomas | matt@betweenbrain.com | http://betweenbrain.com
 * Support    https://github.com/betweenbrain/
 * Copyright  Copyright (C) 2013 betweenbrain llc. All Rights Reserved.
 * License    GNU GPL v3 or later
 */

jimport('joomla.error.log');

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

			$categories = $this->getCategories($row->id);
			$this->setExtraFieldsSearchData($row->id, $categories);
			$this->setpluginsData($row->id, $categories, 'categories');
		}
	}

	/**
	 * Adds data to the extra_fields_search column of a K2 item
	 *
	 * @param $id
	 * @param $data
	 */
	private function setExtraFieldsSearchData($id, $data)
	{
		$data  = implode(' ', $data);
		$query = 'UPDATE ' . $this->db->nameQuote('#__k2_items') . '
			SET ' . $this->db->nameQuote('extra_fields_search') . ' = CONCAT(
				' . $this->db->nameQuote('extra_fields_search') . ',' . $this->db->Quote($data) . '
			)
			WHERE id = ' . $this->db->Quote($id) . '';
		$this->db->setQuery($query);
		$this->db->query();
		$this->checkDbError();
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
		$this->checkDbError();

		$addCatsPlugin = JPluginHelper::isEnabled('k2', 'k2additonalcategories');

		if ($addCatsPlugin)
		{

			$query = 'SELECT catid
				FROM ' . $this->db->nameQuote('#__k2_additional_categories') . '
				WHERE itemID = ' . $this->db->Quote($id);

			$this->db->setQuery($query);
			$addCats = $this->db->loadResultArray();
			$this->checkDbError();

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
		$categories = $this->db->loadResultArray();
		$this->checkDbError();

		return $categories;
	}

	private function setpluginsData($id, $data, $type)
	{

		$pluginsData  = $this->getpluginsData($id);
		$pluginsArray = parse_ini_string($pluginsData, false, INI_SCANNER_RAW);
		if ($data)
		{
			$pluginsArray[$type] = implode('|', $data);
		}
		else
		{
			unset($pluginsArray[$type]);
		}
		$pluginData = null;
		foreach ($pluginsArray as $key => $value)
		{
			$pluginData .= "$key=" . $value . "\n";
		}

		$query = 'UPDATE ' . $this->db->nameQuote('#__k2_items') .
			' SET ' . $this->db->nameQuote('plugins') . '=\'' . $pluginData . '\'' .
			' WHERE id = ' . $this->db->Quote($id) . '';

		$this->db->setQuery($query);
		$this->db->query();
		$this->checkDbError();
	}

	private function getpluginsData($id)
	{
		$query = 'SELECT ' . $this->db->nameQuote('plugins') .
			' FROM ' . $this->db->nameQuote('#__k2_items') .
			' WHERE id = ' . $this->db->Quote($id) . '';

		$this->db->setQuery($query);
		$pluginsData = $this->db->loadResult();
		$this->checkDbError();

		return $pluginsData;
	}

	/**
	 * Checks for any database errors after running a query
	 *
	 * @throws Exception
	 */
	private function checkDbError($backtrace = null)
	{
		if ($error = $this->db->getErrorMsg())
		{
			if ($backtrace)
			{
				$e = new Exception();
				$error .= "\n" . $e->getTraceAsString();
			}

			$this->log->addEntry(array('LEVEL' => '1', 'STATUS' => 'Database Error:', 'COMMENT' => $error));
			JError::raiseWarning(100, $error);
		}
	}
}