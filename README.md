# Joomla Backup

## Requirements
- htaccess support

## Important
The backups directory is protected by a .htaccess file. Please make sure that your webspace supports htaccess. Otherwise the backups could be downloaded by a stranger. That is unlikely because guessing the filename is nearly impossible and if someone guesses the filename, the backup is still encrypted, but an additional security layer (the htaccess protection) is never wrong.

## Issues
- The backup script is currently not able to preserve the owner and group of each file and directory, it just preserves the permissions on UNIX systems.

## Changelog

### 1.0.0-rc.3
- Added check for REDIRECT_HTTP_AUTHORIZATION header if HTTP_AUTHORIZATION is not set.
- Added support for jdiction_mysqli database driver.

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
