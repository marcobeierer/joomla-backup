<?php
/*
 * @copyright  Copyright (C) 2016 Marco Beierer. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die('Restricted access');

//require_once(JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'shared_functions.php');

class BackupViewMain extends JViewLegacy {
	function display($tmpl = null) {
		JToolbarHelper::title(JText::_('COM_BACKUP'));

		if (JFactory::getUser()->authorise('core.admin', 'com_backup')) {
			JToolbarHelper::preferences('com_backup');
		}

		//JHtml::_('jquery.framework');

		//$doc = JFactory::getDocument();
		//$params = JComponentHelper::getParams('com_backup');

		// TODO check if all necessary function available and secure keys are set

		parent::display();
	}

}
