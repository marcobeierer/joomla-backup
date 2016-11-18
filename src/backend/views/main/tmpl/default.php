<?php
/*
 * @copyright  Copyright (C) 2016 Marco Beierer. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');
?>

<div class="bootstrap3" style="margin-top: 10px;">
	<?php if ($this->phpVersionToOld): ?>
		<div class="alert alert-error">
			The Backup component needs at least PHP 5.6 and does not work with your current PHP version. Please upgrade your PHP version.
		</div>
	<?php endif; ?>

	<?php if ($this->zipLibVersionToOld): ?>
		<div class="alert alert-error">
			The Backup component needs at least the version 1.12.4 of the PHP zip extension and does not work with the current version of your zip extension. Upgrading your PHP version might help to solve the problem.
		</div>
	<?php endif; ?>

	<?php if (!$this->hasValidAccessKey): ?>
		<div class="alert alert-error">
			No access key set or access key is not long enough. It should have at least 16 characters.
		</div>
	<?php endif; ?>

	<?php if (!$this->hasValidEncryptionPassword): ?>
		<div class="alert alert-error">
			No encryption password set or encryption password is not long enough. It should have at least 16 characters.
		</div>
	<?php endif; ?>

	<h3>Credits</h3>
	<p>The Backup component for Joomla is developed and maintained by <a href="https://www.marcobeierer.com">Marco Beierer</a>. It is part of the <a href="https://www.websitetools.pro">Website Tools Professional</a> project.</p>
</div>
