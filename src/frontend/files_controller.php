<?php
/*
 * @copyright  Copyright (C) 2017 Marco Beierer. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die('Restricted access');

class FilesController extends JControllerLegacy {
	private $mediaBasePath;
	private $backupsBasePath;

	function __construct($properties = null) {
		// TODO shared with BackupsController
		$this->mediaBasePath = sprintf('%s/media/com_backup', JPATH_ROOT);
		$this->backupsBasePath = sprintf('%s/backups', $this->mediaBasePath);

		parent::__construct($properties);
	}

	function __destruct() {
	}

	function getFiles() {
		$files = [];

		$iterator = new RecursiveDirectoryIterator($this->backupsBasePath);
		$iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);

		$paths = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
		foreach ($paths as $path) {
			$path = realpath($path);

			if (is_file($path)) {
				$files[] = str_replace($this->backupsBasePath. '/', '', $path);
			}
		}

		$jsonData = json_encode($files, JSON_PRETTY_PRINT);
		if ($jsonData === false) {
			JLog::add('could not encode as json: ' . $files, JLog::ERROR, 'com_backup');
			throw new Exception(JText::_('COM_BACKUP_INTERNAL_SERVER_ERROR'), 500);
		}

		echo $jsonData;
		exit;
	}

	function cleanup() {
		$iterator = new RecursiveDirectoryIterator($this->backupsBasePath);
		$iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);

		$test = !(JFactory::getApplication()->input->get('test') === 'false');

		$paths = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
		foreach ($paths as $path) {
			$path = realpath($path);

			if (is_file($path)) {
				if (!preg_match('/\/index.html$/', $path) && !preg_match('/\.htaccess$/', $path)) {
					if ($test) {
						JLog::add('test, this file would be deleted: ' . $path, JLog::DEBUG, 'com_backup');
						continue;
					}

					if (!unlink($path)) {
						JLog::add('could not delete file: ' . $path, JLog::ERROR, 'com_backup');
						throw new Exception(JText::_('COM_BACKUP_INTERNAL_SERVER_ERROR'), 500);
					}

					JLog::add('deleted file: ' . $path, JLog::DEBUG, 'com_backup');
				}
			}
		}

		exit;
	}
}
