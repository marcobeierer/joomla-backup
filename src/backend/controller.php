<?php
/*
 * @copyright  Copyright (C) 2016 Marco Beierer. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die('Restricted access');

class BackupController extends JControllerLegacy {
	function display($cacheable = false, $urlparams = array()) {
		$this->input->set('view', 'main');
		parent::display($cacheable, $urlparams);
	}
}
