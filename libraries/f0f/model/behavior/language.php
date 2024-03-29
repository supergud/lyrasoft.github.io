<?php 
/**
 * @package     FrameworkOnFramework
 * @subpackage  model
 * @copyright   Copyright (C) 2010 - 2015 Nicholas K. Dionysopoulos / Akeeba Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// Protect from unauthorized access
defined('F0F_INCLUDED') or die;

/**
 * FrameworkOnFramework model behavior class to filter front-end access to items
 * based on the language.
 *
 * @package  FrameworkOnFramework
 * @since    2.1
 */
class F0FModelBehaviorLanguage extends F0FModelBehavior
{
	/**
	 * This event runs before we have built the query used to fetch a record
	 * list in a model. It is used to blacklist the language filter
	 *
	 * @param   F0FModel        &$model  The model which calls this event
	 * @param   JDatabaseQuery  &$query  The model which calls this event
	 *
	 * @return  void
	 */
	public function onBeforeBuildQuery(&$model, &$query)
	{
		if (F0FPlatform::getInstance()->isFrontend())
		{
			$model->blacklistFilters('language');
		}
	}

	/**
	 * This event runs after we have built the query used to fetch a record
	 * list in a model. It is used to apply automatic query filters.
	 *
	 * @param   F0FModel        &$model  The model which calls this event
	 * @param   JDatabaseQuery  &$query  The model which calls this event
	 *
	 * @return  void
	 */
	public function onAfterBuildQuery(&$model, &$query)
	{
		// This behavior only applies to the front-end.
		if (!F0FPlatform::getInstance()->isFrontend())
		{
			return;
		}

		// Get the name of the language field
		$table = $model->getTable();
		$languageField = $table->getColumnAlias('language');

		// Make sure the access field actually exists
		if (!in_array($languageField, $table->getKnownFields()))
		{
			return;
		}

		// Make sure it is a multilingual site and get a list of languages
		$app = JFactory::getApplication();
		$hasLanguageFilter = method_exists($app, 'getLanguageFilter');

		if ($hasLanguageFilter)
		{
			$hasLanguageFilter = $app->getLanguageFilter();
		}

		if (!$hasLanguageFilter)
		{
			return;
		}

		$lang_filter_plugin = JPluginHelper::getPlugin('system', 'languagefilter');
		$lang_filter_params = new JRegistry($lang_filter_plugin->params);

		$languages = array('*');

		if ($lang_filter_params->get('remove_default_prefix'))
		{
			// Get default site language
			$lg = F0FPlatform::getInstance()->getLanguage();
			$languages[] = $lg->getTag();
		}
		else
		{
			$languages[] = JFactory::getApplication()->input->getCmd('language', '*');
		}

		// Filter out double languages
		$languages = array_unique($languages);

		// And filter the query output by these languages
		$db = F0FPlatform::getInstance()->getDbo();

		// Alias
		$alias = $model->getTableAlias();
		$alias = $alias ? $db->qn($alias) . '.' : '';

		$languages = array_map(array($db, 'quote'), $languages);
		$query->where($alias . $db->qn($languageField) . ' IN (' . implode(',', $languages) . ')');
	}

	/**
	 * The event runs after F0FModel has called F0FTable and retrieved a single
	 * item from the database. It is used to apply automatic filters.
	 *
	 * @param   F0FModel  &$model   The model which was called
	 * @param   F0FTable  &$record  The record loaded from the databae
	 *
	 * @return  void
	 */
	public function onAfterGetItem(&$model, &$record)
	{
		if ($record instanceof F0FTable)
		{
			$fieldName = $record->getColumnAlias('language');

			// Make sure the field actually exists
			if (!in_array($fieldName, $record->getKnownFields()))
			{
				return;
			}

			// Make sure it is a multilingual site and get a list of languages
			$app = JFactory::getApplication();
			$hasLanguageFilter = method_exists($app, 'getLanguageFilter');

			if ($hasLanguageFilter)
			{
				$hasLanguageFilter = $app->getLanguageFilter();
			}

			if (!$hasLanguageFilter)
			{
				return;
			}

			$lang_filter_plugin = JPluginHelper::getPlugin('system', 'languagefilter');
			$lang_filter_params = new JRegistry($lang_filter_plugin->params);

			$languages = array('*');

			if ($lang_filter_params->get('remove_default_prefix'))
			{
				// Get default site language
				$lg = F0FPlatform::getInstance()->getLanguage();
				$languages[] = $lg->getTag();
			}
			else
			{
				$languages[] = JFactory::getApplication()->input->getCmd('language', '*');
			}

			// Filter out double languages
			$languages = array_unique($languages);

			if (!in_array($record->$fieldName, $languages))
			{
				$record = null;
			}
		}
	}
}
