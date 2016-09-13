<?php
/*
 * @copyright  Copyright (C) 2016 Marco Beierer. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die('Restricted access');

$app = JFactory::getApplication();
$input = $app->input;

$params = JComponentHelper::getParams('com_backup');
$accessKey = $params->get('access_key', '');
$encryptionPassword = $params->get('encryption_password', '');

$logLevel = JLog::ALL;
if ($input->get('debug', '0') !== '1') {
	$logLevel &= ~JLog::DEBUG;
}

JLog::addLogger(
	array(
		'text_file' => 'com_backup.errors.php'
	),
	$logLevel,
	array('com_backup')
);

$accessKeyRequest = preg_replace('/^Bearer /', '', $_SERVER['HTTP_AUTHORIZATION']);
if ($accessKeyRequest === NULL) {
	JLog::add('removal of Bearer failed, authorization value was: ' . $_SERVER['HTTP_AUTHORIZATION'], JLog::ERROR, 'com_backup');
	throw new Exception(JText::_('COM_BACKUP_BAD_REQUEST'), 400);
}


//JLog::add(print_r($_SERVER, true), JLog::ERROR, 'com_backup');

// TODO check if access key and encryption password are safe enough

if (!$accessKey || !$accessKeyRequest || !$encryptionPassword || strlen($accessKey) < 16 || strlen($encryptionPassword) < 16 || $accessKey !== $accessKeyRequest) {
	throw new Exception(JText::_('COM_BACKUP_UNAUTHORIZED'), 401);
}

$view = $input->getWord('view', '');
$task = $input->getCmd('task', '');

if ($view == '' || $task == '') {
	JLog::add('view or task param had no value', JLog::ERROR, 'com_backup');
	throw new Exception(JText::_('COM_BACKUP_BAD_REQUEST'), 400);
}


require_once(JPATH_COMPONENT . '/controller.php');

$controller = JControllerLegacy::getInstance('Backup');
$controller->execute($task);
$controller->redirect();
?>
