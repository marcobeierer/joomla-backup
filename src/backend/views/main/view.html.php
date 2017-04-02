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

		$params = JComponentHelper::getParams('com_backup');

		$accessKey = $params->get('access_key', '');
		$encryptionPassword = $params->get('encryption_password', '');
		$debugMode = $params->get('debug_mode', '0');

		$this->hasValidAccessKey = $accessKey && strlen($accessKey) >= 16;
		$this->hasValidEncryptionPassword = $encryptionPassword && strlen($encryptionPassword) >= 16; 

		$this->phpVersionToOld = version_compare(PHP_VERSION, '5.6.0') === -1;
		$this->zipLibVersionToOld = version_compare(phpversion('zip'), '1.12.4') === -1;

		$this->logData = $this->logData($debugMode === '1');

		parent::display();
	}

	function logData($debugMode) {
		if (!$debugMode) {
			return false;
		}

		$config = JFactory::getConfig();

		$logPath = $config->get('log_path');
		$logsFilepath = $logPath . '/com_backup.errors.php'; // also used in backup.php and logs_controller.php

		if (!file_exists($logsFilepath)) {
			return false;
		}

		return file_get_contents($logsFilepath);
	}
}
