<?php
/*
 * @copyright  Copyright (C) 2017 Marco Beierer. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die('Restricted access');

class LockController extends JControllerLegacy {
	private $lockFilepath;

	function __construct($properties = null) {
		// TODO shared with BackupsController
		$mediaBasePath = sprintf('%s/media/com_backup', JPATH_ROOT);
		$backupsBasePath = sprintf('%s/backups', $mediaBasePath);
		$this->lockFilepath = sprintf('%s/tmp/backup.lock', $backupsBasePath);

		parent::__construct($properties);
	}

	function lock() {
		if (!touch($this->lockFilepath)) {
			JLog::add('could not create lock file: ' . $this->lockFilepath, JLog::ERROR, 'com_backup');
			throw new Exception(JText::_('COM_BACKUP_INTERNAL_SERVER_ERROR'), 500);
		}
	}

	function unlock() {
		if (file_exists($this->lockFilepath)) {
			if (!unlink($this->lockFilepath)) {
				JLog::add('could not delete lock file: ' . $this->lockFilepath, JLog::ERROR, 'com_backup');
			}
		}
	}

	function isLockedResponse() {
		echo json_encode($this->isLocked());
		exit;
	}

	function isLocked() {
		if (file_exists($this->lockFilepath)) {
			JLog::add('lock file exists: backup already started or last backup was canceled', JLog::WARNING, 'com_backup');
			return true;
		} else {
			return false;
		}
	}
}
