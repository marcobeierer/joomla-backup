<?php
/*
 * @copyright  Copyright (C) 2016 - 2017 Marco Beierer. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die('Restricted access');

class LogsController extends JControllerLegacy {
	function __construct($properties = null) {
		parent::__construct($properties);
	}

	function __destruct() {
	}

	function fetchLogs() {
		$config = JFactory::getConfig();

		$logPath = $config->get('log_path');
		$logsFilepath = $logPath . '/com_backup.errors.php'; // also used in backup.php // TODO possible to get from JLog?

		JLog::add('logsFilepath: ' . $logsFilepath, JLog::DEBUG, 'com_backup');

		header('Content-Type: text/plain');

		while (ob_get_level() > 0) {
			if (@ob_end_clean() === false) {
				JLog::add('could not flush and disable the output buffer, a reason could be that output buffer is globally disabled', JLog::ERROR, 'com_backup');
				throw new Exception(JText::_('COM_BACKUP_INTERNAL_SERVER_ERROR'), 500);
			}
		}

		if (!file_exists($logsFilepath)) {
			$this->setStatusCode(204); // 204 = no content
			exit;
		}

		readfile($logsFilepath);
		exit; // $app->close() prevents display of error messages
	}

	function setStatusCode($statusCode) {
        if (function_exists('http_response_code')) {
            http_response_code($statusCode);
        }
        else { // fix for PHP version older than 5.4.0
            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
            header($protocol . ' ' . $statusCode . ' ');
        }
    }
}
