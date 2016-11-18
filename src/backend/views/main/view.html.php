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
		
		$params = JComponentHelper::getParams('com_backup');

		$accessKey = $params->get('access_key', '');
		$encryptionPassword = $params->get('encryption_password', '');

		$this->hasValidAccessKey = $accessKey && strlen($accessKey) >= 16;
		$this->hasValidEncryptionPassword = $encryptionPassword && strlen($encryptionPassword) >= 16; 

		$this->phpVersionToOld = version_compare(PHP_VERSION, '5.6.0') === -1;
		$this->zipLibVersionToOld = version_compare(phpversion('zip'), '1.12.4') === -1;

		// TODO check if all necessary function available and secure keys are set

		parent::display();
	}

}
