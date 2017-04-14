# Joomla Backup

## Requirements
- htaccess support
- SEF and URL Rewrite enabled

## Important
The backups directory is protected by a .htaccess file. Please make sure that your webspace supports htaccess. Otherwise the backups could be downloaded by a stranger. That is unlikely because guessing the filename is nearly impossible and if someone guesses the filename, the backup is still encrypted, but an additional security layer (the htaccess protection) is never wrong.

## Issues
- The backup script is currently not able to preserve the owner and group of each file and directory, it just preserves the permissions on UNIX systems.

## Changelog

### 1.0.0-rc.6
*Release date: 14th April 2017*
- Implemented multistep mode.

### 1.0.0-rc.5
*Release date: 12th April 2017*
- Try to set max_execution_time to 0 and throw no exception if set_time_limit is not allowed.
- Implemented Lock and Unlock button in debug mode.

### 1.0.0-rc.4
*Release date: 2nd April 2017*
- Bugfix: Added logs_controller.php to manifest.

### 1.0.0-rc.3
*Release date: 2nd April 2017*
- Added check for REDIRECT_HTTP_AUTHORIZATION header if HTTP_AUTHORIZATION is not set.
- Added support for jdiction_mysqli database driver.
- Made hash algorithm selectable in options.
- Implemented logs endpoint.
- Added debug mode option.
- View logs in backend in debug mode.

### 1.0.0-rc.2
*Release date: 18th November 2016*
- Added checks in backend for valid encryption password and access key.

### 1.0.0-rc.1
*Release date: 23th October 2016*
- No changes, just new version because beta is production ready.

### 1.0.0-beta.2
*Release date: 21th September 2016*
- New folder structure of backup archive.
- RESTful API layout.
- Preserve UNIX permissions.
- Added PHP and zip extension version errors and warnings.

### 1.0.0-beta.1
*Release date: 28th June 2016*
- Initial release.
