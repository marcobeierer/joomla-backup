<?php
/*
 * @copyright  Copyright (C) 2016 Marco Beierer. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_COMPONENT_SITE . '/libs/mysqldump.php');

class BackupController extends JControllerLegacy {
	private $mediaBasePath;
	private $backupsBasePath;
	private $lockFilepath;

	function __construct($properties = null) {
		$this->mediaBasePath = sprintf('%s/media/com_backup', JPATH_ROOT);
		$this->backupsBasePath = sprintf('%s/backups', $this->mediaBasePath);
		$this->lockFilepath = sprintf('%s/tmp/backup.lock', $this->backupsBasePath);

		if ($this->isLocked()) {
			throw new Exception(JText::_('COM_BACKUP_CONFLICT'), 409);
		} else {
			$this->lock();
		}

		parent::__construct($properties);
	}

	function __destruct() {
		$this->unlock();
	}

	function createBackup() {
		//$filenameBase = sprintf('%s', date('Y.m.d-H:i:s'));
		$date = date('c');
		$filenameBase = sprintf('%s', $date);

		$sqlDumpPath = sprintf('%s/sql', $this->backupsBasePath);
		$sqlDumpFilepath = sprintf('%s/%s.sql', $sqlDumpPath, $filenameBase);
		$zipFilepath = sprintf('%s/tmp/%s-files.zip', $this->backupsBasePath, $filenameBase);

		try {
			$metaData = $this->createBackupx($date, $filenameBase, $sqlDumpPath, $sqlDumpFilepath, $zipFilepath);
		} catch (\Exception $e) {
			$this->cleanup($sqlDumpFilepath, $zipFilepath);
			throw $e;
		}

		$this->cleanup($sqlDumpFilepath, $zipFilepath);

		$contentType = 'application/json';
		JFactory::getDocument()->setMimeEncoding($contentType);
		JResponse::setHeader('Content-Type', $contentType, true);

		http_response_code(201); // created
		echo json_encode($metaData);
	}

	private function lock() {
		if (!touch($this->lockFilepath)) {
			JLog::add('could not create lock file: ' . $this->lockFilepath, JLog::ERROR, 'com_backup');
			throw new Exception(JText::_('COM_BACKUP_INTERNAL_SERVER_ERROR'), 500);
		}
	}

	private function unlock() {
		if (file_exists($this->lockFilepath)) {
			if (!unlink($this->lockFilepath)) {
				JLog::add('could not delete lock file: ' . $this->lockFilepath, JLog::ERROR, 'com_backup');
			}
		}

	}

	private function isLocked() {
		if (file_exists($this->lockFilepath)) {
			JLog::add('lock file exists, backup already started or last backup was canceled', JLog::ERROR, 'com_backup');
			return true;
		} else {
			return false;
		}
	}

	// TODO expose via API?
	private function cleanup($sqlDumpFilepath, $zipFilepath) {
		if (file_exists($sqlDumpFilepath)) {
			if (!unlink($sqlDumpFilepath)) {
				JLog::add('could not delete sql dump file: ' . $sqlDumpFilepath, JLog::WARNING, 'com_backup');
			}
		}

		if (file_exists($zipFilepath)) {
			if (!unlink($zipFilepath)) {
				JLog::add('could not delete tmp zip file: ' . $zipFilepath, JLog::WARNING, 'com_backup');
			}
		}
	}

	private function setTimeLimit() {
		if (set_time_limit(0) === false) {
			JLog::add('call of set_time_limit() failed, probably PHP safeMode is enabled', JLog::ERROR, 'com_backup');
			throw new Exception(JText::_('COM_BACKUP_INTERNAL_SERVER_ERROR'), 500);
		}
	}

	private function createBackupx($date, $filenameBase, $sqlDumpPath, $sqlDumpFilepath, $zipFilepath) {
		$this->setTimeLimit();

		// init vars
		$task = new CreateBackupTask;

		$params = JComponentHelper::getParams('com_backup');
		$config = JFactory::getConfig();

		// create database dump
		if (!$task->createDBDump($config->get('dbtype'), $config->get('host'), $config->get('db'), $config->get('user'), $config->get('password'), $sqlDumpFilepath, $sqlDumpPath)) {
			JLog::add('could not create database dump', JLog::ERROR, 'com_backup');
			throw new Exception(JText::_('COM_BACKUP_INTERNAL_SERVER_ERROR'), 500);
		}

		// create zip archive
		$ignoreFilesUnderPaths = array(
			sprintf('%s/files/', $this->backupsBasePath),
			sprintf('%s/sql/', $this->backupsBasePath),
			sprintf('%s/tmp/', $this->backupsBasePath)
		);

		if (!$task->createZIPArchive(JPATH_ROOT, $zipFilepath, $sqlDumpFilepath, $ignoreFilesUnderPaths)) {
			JLog::add('could not create zip archive', JLog::ERROR, 'com_backup');
			throw new Exception(JText::_('COM_BACKUP_INTERNAL_SERVER_ERROR'), 500);
		}

		// encrypt zip archive
		$password = $params->get('encryption_password', '');

		$failedOrMetaData = $task->encryptZIPArchive($password, $zipFilepath, $this->backupsBasePath, $date, $filenameBase);
		if ($failedOrMetaData === false) {
			JLog::add('could not encrypt zip archive', JLog::ERROR, 'com_backup');
			throw new Exception(JText::_('COM_BACKUP_INTERNAL_SERVER_ERROR'), 500);
		}

		return $failedOrMetaData;
	}

	function downloadBackup() {
		$this->setTimeLimit(); // TODO necessary?

		$app = JFactory::getApplication();
		$input = $app->input;

		$filename = $input->get('filename', '', 'RAW');
		if ($filename == '') {
			JLog::add('filename was not provided', JLog::ERROR, 'com_backup');
			throw new Exception(JText::_('COM_BACKUP_BAD_REQUEST'), 400);
		}

		$backupsDirectoryPath = sprintf('%s/files', $this->backupsBasePath);
		$iterator = new DirectoryIterator($backupsDirectoryPath);

		$filepath = false;

		JLog::add('filename: ' . $filename, JLog::DEBUG, 'com_backup');

		// iteration is necessary to make sure that just the files in the 'files' directory can be downloaded, thus for security
		foreach ($iterator as $item) {
			JLog::add('isFile: ' . $item->isFile(), JLog::DEBUG, 'com_backup');
			JLog::add('getFilename: ' . $item->getFilename(), JLog::DEBUG, 'com_backup');

			if ($item->isFile() && $item->getFilename() === $filename) {
				$filepath = $item->getPathname();
				break;
			}
		}

		if ($filepath === false) {
			throw new Exception(JText::_('COM_BACKUP_NOT_FOUND'), 404);
		}

		JLog::add('filepath: ' . $filepath, JLog::DEBUG, 'com_backup');

		header('Content-Type: application/octet-stream');
		header(sprintf('Content-Disposition: attachment; filename="%s"', $filename));
		header(sprintf('Content-Length: %d', filesize($filepath)));

		JLog::add('filesize: ' . filesize($filepath), JLog::DEBUG, 'com_backup');

		while (ob_get_level() > 0) {
			if (@ob_end_clean() === false) {
				JLog::add('could not flush and disable the output buffer, a reason could be that output buffer is globally disabled', JLog::ERROR, 'com_backup');
				throw new Exception(JText::_('COM_BACKUP_INTERNAL_SERVER_ERROR'), 500);
			}
		}

		readfile($filepath);
		exit; // TODO enough? $app->close() necessary? close() may corrupt download?
	}

	function deleteBackup() {
		$this->setTimeLimit(); // TODO necessary?

		$app = JFactory::getApplication();
		$input = $app->input;

		$filename = $input->get('filename', '', 'RAW');
		if ($filename == '') {
			JLog::add('filename was not provided', JLog::ERROR, 'com_backup');
			throw new Exception(JText::_('COM_BACKUP_BAD_REQUEST'), 400);
		}

		$backupsDirectoryPath = sprintf('%s/files', $this->backupsBasePath);
		$iterator = new DirectoryIterator($backupsDirectoryPath);

		$filepath = false;

		JLog::add('filename: ' . $filename, JLog::DEBUG, 'com_backup');

		// iteration is necessary to make sure that just the files in the 'files' directory can be deleted, thus for security
		foreach ($iterator as $item) {
			JLog::add('isFile: ' . $item->isFile(), JLog::DEBUG, 'com_backup');
			JLog::add('getFilename: ' . $item->getFilename(), JLog::DEBUG, 'com_backup');

			if ($item->isFile() && $item->getFilename() === $filename) {
				$filepath = $item->getPathname();
				break;
			}
		}

		if ($filepath === false) {
			throw new Exception(JText::_('COM_BACKUP_NOT_FOUND'), 404);
		}

		if (!unlink($filepath)) {
			JLog::add('could not delete backup: ' . $filepath, JLog::ERROR, 'com_backup');
			throw new Exception(JText::_('COM_BACKUP_INTERNAL_SERVER_ERROR'), 500);
		}

		$metaDataFilepath = $filepath . '.json';
		if (!unlink($filepath . '.json')) {
			JLog::add('could not delete meta data: ' . $metaDataFilepath, JLog::ERROR, 'com_backup');
			throw new Exception(JText::_('COM_BACKUP_INTERNAL_SERVER_ERROR'), 500);
		}
	}
}

class CreateBackupTask {
	function createDBDump($dbDriver, $dbHost, $dbName, $dbUser, $dbPassword, $destFilepath, $destPath) { // TODO remove need for destPath param
		if (!is_dir($destPath)) {
			JLog::add('destPath is not a directory', JLog::ERROR, 'com_backup');
			return false;
		}

		if (file_exists($destFilepath)) {
			JLog::add('destFilepath already exists', JLog::ERROR, 'com_backup');
			return false;
		}

		if ($dbDriver == 'mysqli') {
			$dbDriver = 'mysql';
		}

		if ($dbDriver != 'mysql') {
			JLog::add('only MySQL database is supported', JLog::ERROR, 'com_backup');
			return false;
		}

		try {
			$dump = new Ifsnop\Mysqldump\Mysqldump(sprintf('%s:host=%s;dbname=%s', $dbDriver, $dbHost, $dbName), $dbUser, $dbPassword);
			$dump->start($destFilepath);
		} catch (\Exception $e) {
			JLog::add('mysqldump error: ' . $e->getMessage(), JLog::ERROR, 'com_backup');
			return false;
		}

		return true;
	}

	function createZIPArchive($source, $destination, $sqlDumpFilepath, $ignoreFilesUnderPaths = array()) {
		if (!file_exists($source)) {
			JLog::add('source does not exist', JLog::ERROR, 'com_backup');
			return false;
		}

		if (!is_dir($source)) {
			JLog::add('source is not a directory', JLog::ERROR, 'com_backup');
			return false;
		}

		$archive = new ZipArchive();

		$successOrError = $archive->open($destination, ZIPARCHIVE::CREATE);
		if ($successOrError !== true) {
			JLog::add('open zip error: ' . $successOrError, JLog::ERROR, 'com_backup');
			return false;
		}

		$source = realpath($source);

		$filesDirectory = '001_files';
		$sourceStat = stat($source);

		if ($sourceStat === false) {
			JLog::add('could not stat source dir ' . $source, JLog::ERROR, 'com_backup');
			return false;
		}

		if (!$archive->addEmptyDir($filesDirectory)) {
			JLog::add('could not add empty dir ' . $filesDirectory, JLog::ERROR, 'com_backup');
			return false;
		}
		
		if (!$archive->setExternalAttributesName($filesDirectory . '/', ZipArchive::OPSYS_UNIX, $sourceStat['mode'] << 16)) {
			JLog::add('could not set external attributes for dir ' . $filesDirectory, JLog::ERROR, 'com_backup');
			return false;
		}

		$databaseFilename = '002_database.sql';
		$databaseStat = stat($sqlDumpFilepath);

		if ($databaseStat === false) {
			JLog::add('could not stat database file ' . $sqlDumpFilepath, JLog::ERROR, 'com_backup');
			return false;
		}

		if (!$archive->addFile($sqlDumpFilepath, $databaseFilename)) {
			JLog::add('could not add sql file ' . $sqlDumpFilepath, JLog::ERROR, 'com_backup');
			return false;
		}

		if (!$archive->setExternalAttributesName($databaseFilename, ZipArchive::OPSYS_UNIX, $databaseStat['mode'] << 16)) {
			JLog::add('could not set external attributes for file ' . $databaseFilename, JLog::ERROR, 'com_backup');
			return false;
		}

		$iterator = new RecursiveDirectoryIterator($source);
		$iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);

		$paths = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
		// TODO is there a cleaner way, where methods like x->isFile() and x->getFilename are provided? thus like used in download()
		foreach ($paths as $path) {
			$path = realpath($path);
			$stat = stat($path);

			if (is_dir($path)) {
				$pathInArchive = $filesDirectory . '/' . str_replace($source . '/', '', $path . '/');
				if (!$archive->addEmptyDir($pathInArchive)) {
					JLog::add('could not add empty dir ' . $path, JLog::ERROR, 'com_backup');
					return false;
				}

				if (!$archive->setExternalAttributesName($pathInArchive, ZipArchive::OPSYS_UNIX, $stat['mode'] << 16)) {
					JLog::add('could not set external attributes for dir ' . $pathInArchive, JLog::ERROR, 'com_backup');
					return false;
				}
			} else if (is_file($path)) {
				foreach ($ignoreFilesUnderPaths as $ignorePath) {
					if (stripos($path, $ignorePath) === 0 && basename($path) != 'index.html') { // TODO fine a better solution to allow index.html
						JLog::add('file ignored: ' . $path, JLog::DEBUG, 'com_backup');
						continue 2;
					}
				}

				$pathInArchive = $filesDirectory . '/' . str_replace($source . '/', '', $path);
				if (!$archive->addFile($path, $pathInArchive)) {
					JLog::add('could not add file ' . $path, JLog::ERROR, 'com_backup');
					return false;
				}

				if (!$archive->setExternalAttributesName($pathInArchive, ZipArchive::OPSYS_UNIX, $stat['mode'] << 16)) {
					JLog::add('could not set external attributes for file ' . $pathInArchive, JLog::ERROR, 'com_backup');
					return false;
				}
			} else {
				JLog::add('path was not a file nor a directory' . $path, JLog::WARNING, 'com_backup');
			}

		}

		return $archive->close();
	}

	private function randomBytes($blockSize) {
		if (function_exists('random_bytes')) {
			return random_bytes($blockSize);
		} else if (function_exists('mcrypt_create_iv')) {
			return mcrypt_create_iv($blockSize, MCRYPT_DEV_URANDOM);
		} else {
			return openssl_random_pseudo_bytes($blockSize);
		}
	}

	function encryptZIPArchive($password, $source, $destinationBasePath, $date, $filenameBase) {
		if (!file_exists($source)) {
			JLog::add('source archive does not exist: ' . $source, JLog::ERROR, 'com_backup');
			return false;
		}

		if (!is_file($source)) {
			JLog::add('source is not a file: ' . $source, JLog::ERROR, 'com_backup');
			return false;
		}

		$encryptionMode = 'aes-256-cbc';
		if (!in_array($encryptionMode, openssl_get_cipher_methods(true))) {
			JLog::add('aes-256-cbc not supported', JLog::ERROR, 'com_backup');
			return false;
		}
		$blockSize = openssl_cipher_iv_length($encryptionMode);

		$keyHashAlgorithm = 'sha512';
		if (!in_array($keyHashAlgorithm, hash_algos())) {
			JLog::add('sha512 for PBKDF2 not supported', JLog::ERROR, 'com_backup');
			return false;
		}

		$hmacHashAlgorithm = 'sha256';
		if (!in_array($hmacHashAlgorithm, hash_algos())) {
			JLog::add('sha256 for HMAC not supported', JLog::ERROR, 'com_backup');
			return false;
		}

		$salt = $this->randomBytes($blockSize);
		$iterations = 50000;

		$key = hash_pbkdf2($keyHashAlgorithm, $password, $salt, $iterations, 0, true);
		if (strlen($key) != 64) {
			JLog::add('master key hash has not 64 bytes', JLog::ERROR, 'com_backup');
			return false;
		}
		$keyEncryption = substr($key, 0, 32);
		$keyHMAC = substr($key, -32);

		$iv = $this->randomBytes($blockSize);

		$tmpDestinationFilepath = sprintf('%s/tmp/%s.zip.enc', $destinationBasePath, $filenameBase);
		if (file_exists($tmpDestinationFilepath)) { // dest already exists
			JLog::add('tmp file already exists', JLog::ERROR, 'com_backup');
			return false;
		}

		$initialIV = $iv;
		$chunkSize = $blockSize * 256; // chunk size has to be multiple of cipher block size

		$sourceFile = fopen($source, 'r');
		$destFile = fopen($tmpDestinationFilepath, 'w');

		while (!feof($sourceFile)) {
			$data = fread($sourceFile, $chunkSize);

			$options = OPENSSL_RAW_DATA;
			if (strlen($data) == $chunkSize) {
				$options |= OPENSSL_ZERO_PADDING; // add zero padding for all chuncks expect of last one
			}

			$encryptedData = openssl_encrypt($data, $encryptionMode, $keyEncryption, $options, $iv);
			fwrite($destFile, $encryptedData);

			$iv = substr($encryptedData, -$blockSize);
		}

		fclose($sourceFile);
		fclose($destFile);

		$finalDestinationFilename = sprintf('%s-backup-salt_%s-iv_%s.zip.enc', $filenameBase, bin2hex($salt), bin2hex($initialIV));
		$finalDestinationFilepath = sprintf('%s/files/%s', $destinationBasePath, $finalDestinationFilename);
		if (file_exists($finalDestinationFilepath)) {
			if (!unlink($tmpDestinationFilepath)) {
				JLog::add('could not delete tmp destination filepath: ' . $tmpDestinationFilepath, JLog::WARNING, 'com_backup');
			}

			JLog::add('final destination already exists: ' . $finalDestinationFilepath, JLog::ERROR, 'com_backup');
			return false;
		}

		$success = rename($tmpDestinationFilepath, $finalDestinationFilepath);
		if ($success === false) {
			JLog::add('could not rename tmp destination filepath to final filepath: ' . $tmpDestinationFilepath . ' to ' . $finalDestinationFilepath, JLog::ERROR, 'com_backup');
			return false;
		}

		$hmacOfArchive = hash_hmac_file($hmacHashAlgorithm, $finalDestinationFilepath, $keyHMAC);
		$hmacOfIV = hash_hmac($hmacHashAlgorithm, $initialIV, $keyHMAC);

		$hashAlgorithm = $hmacHashAlgorithm;
		$hashOfFile = hash_file($hashAlgorithm, $finalDestinationFilepath);

		$metaData = new stdClass;
		$metaData->WebsiteURL = JURI::base();
		$metaData->Date = $date;
		$metaData->Filename = $finalDestinationFilename;
		$metaData->PBKDF2HashAlgorithm = $keyHashAlgorithm;
		$metaData->PBKDF2Salt = bin2hex($salt);
		$metaData->PBKDF2Iterations = $iterations;
		$metaData->HashAlgorithm = $hashAlgorithm;
		$metaData->HashOfFile = $hashOfFile;
		$metaData->EncryptionMode = $encryptionMode;
		$metaData->IV = bin2hex($initialIV);
		$metaData->HMACHashAlgorithm = $hmacHashAlgorithm;
		$metaData->HMACOfArchive = $hmacOfArchive;
		$metaData->HMACOfIV = $hmacOfIV;

		$jsonData = json_encode($metaData, JSON_PRETTY_PRINT);

		$metaDataFilepath = sprintf('%s.json', $finalDestinationFilepath);
		if (file_exists($metaDataFilepath)) {
			JLog::add('meta data file already exists: ' . $metaDataFilepath, JLog::ERROR, 'com_backup');
			return false;
		}

		$success = file_put_contents($metaDataFilepath, $jsonData);
		if ($success === false) {
			JLog::add('could not write meta data to file: ' . $metaDataFilepath, JLog::ERROR, 'com_backup');
			return false;
		}

		return $metaData;
	}
}
