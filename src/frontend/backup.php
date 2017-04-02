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
		'text_file' => 'com_backup.errors.php' // also used in logs_controller.php
	),
	$logLevel,
	array('com_backup')
);

if (version_compare(PHP_VERSION, '5.6.0') === -1) {
	JLog::add('the component requires at least PHP 5.6.0');
	throw new Exception(JText::_('COM_BACKUP_INTERNAL_SERVER_ERROR'), 500);
}

if (version_compare(phpversion('zip'), '1.12.4') === -1) {
	JLog::add('the component requires at least the version 1.12.4 of the PHP zip extension');
	throw new Exception(JText::_('COM_BACKUP_INTERNAL_SERVER_ERROR'), 500);
}

$httpAuthorization = '';
if (isset($_SERVER['HTTP_AUTHORIZATION']) && $_SERVER['HTTP_AUTHORIZATION'] !== NULL) {
	$httpAuthorization = $_SERVER['HTTP_AUTHORIZATION'];
}
else if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] !== NULL) {
	$httpAuthorization = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}
else {
	JLog::add('no HTTP_AUTHORIZATION header was provided', JLog::ERROR, 'com_backup');
	throw new Exception(JText::_('COM_BACKUP_BAD_REQUEST'), 400);
}

$accessKeyRequest = preg_replace('/^Bearer /', '', $httpAuthorization);
if ($accessKeyRequest === NULL) {
	JLog::add('removal of Bearer failed, authorization value was: ' . $httpAuthorization, JLog::ERROR, 'com_backup');
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



switch($view) {
case 'logs':
	require_once(JPATH_COMPONENT . '/logs_controller.php');
	$controller = JControllerLegacy::getInstance('Logs');
	break;
default:
	require_once(JPATH_COMPONENT . '/controller.php');
	$controller = JControllerLegacy::getInstance('Backup');
}

$controller->execute($task);
$controller->redirect();
?>
