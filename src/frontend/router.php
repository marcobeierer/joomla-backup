<?php
/*
 * @copyright  Copyright (C) 2016 - 2017 Marco Beierer. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die('Restricted access');

function BackupParseRoute($segments) {
	$vars = array();
	$count = count($segments);
	$method = $_SERVER['REQUEST_METHOD'];

	$view = $segments[0];
	$filename = '';

	if ($view == 'backups') {
		$vars['view'] = $view;

		if ($count > 1) {
			$filename = preg_replace('/:/', '-', $segments[1], 1); // replace first : with - because the segment was modified by Joomla // TODO is there a cleaner solution? for example a router event?
			$vars['filename'] = $filename;
		}

		if ($method == 'POST' && $filename == '') {
			$vars['task'] = 'createBackup';
		} 
		else if ($method == 'GET' && $filename != '') {
			$vars['task'] = 'downloadBackup';
		}
		else if ($method == 'DELETE' && $filename != '') {
			$vars['task'] = 'deleteBackup';
		}
		else {
			unset($vars['view']);
			unset($vars['filename']);
		}
	}
	else if ($view == 'logs') {
		$vars['view'] = $view;

		switch($method) {
		case 'GET':
			$vars['task'] = 'fetchLogs';
			break;
		default:
			unset($vars['view']);
		}
	}
	else if ($view == 'lock') {
		$vars['view'] = $view;

		switch($method) {
		case 'POST':
			$vars['task'] = 'lock';
			break;
		case 'GET':
			$vars['task'] = 'isLockedResponse';
			break;
		case 'DELETE':
			$vars['task'] = 'unlock';
			break;
		default:
			unset($vars['view']);
		}
	} 
	else if ($view = 'files') {
		$vars['view'] = $view;

		switch($method) {
		case 'GET':
			$vars['task'] = 'getFiles';
			break;
		case 'DELETE':
			$vars['task'] = 'cleanup';
			break;
		default:
			unset($vars['view']);
		}
	}
	/*else if ($view == 'cleanup') {
		$vars['view'] = $view;

		switch($method) {
		case 'POST':
			$vars['task'] = 'cleanup';
			break;
		default:
			unset($vars['view']);
		}
	}*/

	$vars['format'] = 'raw';
	return $vars;
}
