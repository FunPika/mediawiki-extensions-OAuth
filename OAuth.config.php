<?php

# ######## Configuration variables ########
# IMPORTANT: DO NOT EDIT THIS FILE
# When configuring globals, set them at LocalSettings.php instead

/**
 * @var string Wiki ID of OAuth management wiki
 * On wiki farms, it makes sense to set this to a wiki that acts as a portal
 * site, is decidated to management, or just handles login/authentication. It
 * can, however, be set to any wiki if the farm. For single-wiki sites or farms
 * where each wiki manages consumers separately, it should be left as false.
 */
$wgMWOAuthCentralWiki = false;

/**
 * @var bool Whether shared global user IDs are stored in the oauth tables
 * On wiki farms with a central authentication system (with integer user IDs)
 * that share a single OAuth management wiki, this must be set to true. If wikis
 * have a central authentication system but have their own OAuth management, then
 * this can be either true or false. Otherwise it should always be set to false.
 *
 * Setting this to true requires an MWOAuth aware authentication extension.
 *
 * This value should not be changed after the fact to avoid ambigious IDs.
 * Proper user ID migration should be done before any such changes.
 */
$wgMWOAuthSharedUserIDs = false;

/**
 * @var string Extension to use as the source of shared user IDs if enabled
 *
 * This has no effect if $wgMWOAuthSharedUserIDs is set to false.
 */
$wgMWOAuthSharedUserSource = false;

/**
 * @var Array Map of (grant => right => boolean)
 * Users authorize consumers (like Apps) to act on their behalf but only with
 * a subset of the user's normal account rights (signed off on by the user).
 * The possible rights to grant to a consumer are bundled into groups called
 * "grants". Each grant defines some rights it lets consumers inherit from the
 * account they may act on behalf of. Note that a user granting a right does
 * nothing if that user does not actually have that right to begin with.
 */
$wgMWOAuthGrantPermissions = array();

// @TODO: clean up grants
// @TODO: auto-include read/editsemiprotected rights?

$wgMWOAuthGrantPermissions['useoauth']['autoconfirmed'] = true;
$wgMWOAuthGrantPermissions['useoauth']['autopatrol'] = true;
$wgMWOAuthGrantPermissions['useoauth']['autoreview'] = true;
$wgMWOAuthGrantPermissions['useoauth']['editsemiprotected'] = true;
$wgMWOAuthGrantPermissions['useoauth']['ipblock-exempt'] = true;
$wgMWOAuthGrantPermissions['useoauth']['nominornewtalk'] = true;
$wgMWOAuthGrantPermissions['useoauth']['patrolmarks'] = true;
$wgMWOAuthGrantPermissions['useoauth']['proxyunbannable'] = true;
$wgMWOAuthGrantPermissions['useoauth']['purge'] = true;
$wgMWOAuthGrantPermissions['useoauth']['read'] = true;
$wgMWOAuthGrantPermissions['useoauth']['skipcaptcha'] = true;
$wgMWOAuthGrantPermissions['useoauth']['torunblocked'] = true;
$wgMWOAuthGrantPermissions['useoauth']['unblockself'] = true;
$wgMWOAuthGrantPermissions['useoauth']['writeapi'] = true;

$wgMWOAuthGrantPermissions['highvolume']['bot'] = true;
$wgMWOAuthGrantPermissions['highvolume']['apihighlimits'] = true;
$wgMWOAuthGrantPermissions['highvolume']['noratelimit'] = true;
$wgMWOAuthGrantPermissions['highvolume']['markbotedits'] = true;

$wgMWOAuthGrantPermissions['editpage']['edit'] = true;
$wgMWOAuthGrantPermissions['editpage']['minoredit'] = true;

$wgMWOAuthGrantPermissions['editprotected'] = $wgMWOAuthGrantPermissions['editpage'];
$wgMWOAuthGrantPermissions['editprotected']['editprotected'] = true;

$wgMWOAuthGrantPermissions['editmycssjs'] = $wgMWOAuthGrantPermissions['editpage'];
$wgMWOAuthGrantPermissions['editmycssjs']['editmyusercss'] = true;
$wgMWOAuthGrantPermissions['editmycssjs']['editmyuserjs'] = true;

$wgMWOAuthGrantPermissions['editinterface'] = $wgMWOAuthGrantPermissions['editpage'];
$wgMWOAuthGrantPermissions['editinterface']['editinterface'] = true;
$wgMWOAuthGrantPermissions['editinterface']['editusercss'] = true;
$wgMWOAuthGrantPermissions['editinterface']['edituserjs'] = true;

$wgMWOAuthGrantPermissions['createeditmovepage'] = $wgMWOAuthGrantPermissions['editpage'];
$wgMWOAuthGrantPermissions['createeditmovepage']['createpage'] = true;
$wgMWOAuthGrantPermissions['createeditmovepage']['createtalk'] = true;
$wgMWOAuthGrantPermissions['createeditmovepage']['move'] = true;
$wgMWOAuthGrantPermissions['createeditmovepage']['move-rootuserpages'] = true;
$wgMWOAuthGrantPermissions['createeditmovepage']['move-subpages'] = true;

$wgMWOAuthGrantPermissions['uploadfile']['upload'] = true;
$wgMWOAuthGrantPermissions['uploadfile']['reupload-own'] = true;

$wgMWOAuthGrantPermissions['uploadeditmovefile'] = $wgMWOAuthGrantPermissions['uploadfile'];
$wgMWOAuthGrantPermissions['uploadeditmovefile']['reupload'] = true;
$wgMWOAuthGrantPermissions['uploadeditmovefile']['reupload-shared'] = true;
$wgMWOAuthGrantPermissions['uploadeditmovefile']['upload_by_url'] = true;
$wgMWOAuthGrantPermissions['uploadeditmovefile']['movefile'] = true;
$wgMWOAuthGrantPermissions['uploadeditmovefile']['suppressredirect'] = true;

$wgMWOAuthGrantPermissions['patrol']['patrol'] = true;

$wgMWOAuthGrantPermissions['rollback']['rollback'] = true;

$wgMWOAuthGrantPermissions['blockusers']['block'] = true;
$wgMWOAuthGrantPermissions['blockusers']['blockemail'] = true;

$wgMWOAuthGrantPermissions['viewdeleted']['browsearchive'] = true;
$wgMWOAuthGrantPermissions['viewdeleted']['deletedhistory'] = true;
$wgMWOAuthGrantPermissions['viewdeleted']['deletedtext'] = true;

$wgMWOAuthGrantPermissions['delete'] = $wgMWOAuthGrantPermissions['editpage'] +
	$wgMWOAuthGrantPermissions['viewdeleted'];
$wgMWOAuthGrantPermissions['delete']['delete'] = true;
$wgMWOAuthGrantPermissions['delete']['bigdelete'] = true;
$wgMWOAuthGrantPermissions['delete']['deletelogentry'] = true;
$wgMWOAuthGrantPermissions['delete']['deleterevision'] = true;
$wgMWOAuthGrantPermissions['delete']['undelete'] = true;

$wgMWOAuthGrantPermissions['protect'] = $wgMWOAuthGrantPermissions['editprotected'];
$wgMWOAuthGrantPermissions['protect']['protect'] = true;

$wgMWOAuthGrantPermissions['oversight']['hideuser'] = true;
$wgMWOAuthGrantPermissions['oversight']['suppressrevision'] = true;
$wgMWOAuthGrantPermissions['oversight']['suppressionlog'] = true;

$wgMWOAuthGrantPermissions['viewmywatchlist']['viewmywatchlist'] = true;

$wgMWOAuthGrantPermissions['editmywatchlist']['editmywatchlist'] = true;

$wgMWOAuthGrantPermissions['sendemail']['sendemail'] = true;

/** @var integer Seconds after which an idle consumer request is marked as "expired" */
$wgMWOAuthRequestExpirationAge = 30 * 86400;

$wgAvailableRights[] = 'mwoauthproposeconsumer';
$wgAvailableRights[] = 'mwoauthupdateownconsumer';
$wgAvailableRights[] = 'mwoauthmanageconsumer';
$wgAvailableRights[] = 'mwoauthsuppress';
$wgAvailableRights[] = 'mwoauthviewsuppressed';
$wgAvailableRights[] = 'mwoauthviewprivate';
$wgAvailableRights[] = 'mwoauthmanagemygrants';

$wgGroupPermissions['user']['mwoauthmanagemygrants'] = true;

/** @var bool Require HTTPs for user transactions that might send out secret tokens */
$wgMWOAuthSecureTokenTransfer = false;

/** @var array List of API module classes to disable when OAuth is used for the request. */
$wgMWOauthDisabledApiModules = array(
	'ApiLogin',
	'ApiLogout',
);

/**
 * @var string Secret to add to HMAC of token secrets
 * A cryptographically random string, used as an extra protection for secrets stored in the
 * database. This can use the wiki's $wgSecretKey, but in multi-wiki configurations, this needs
 * to be the same for all wikis.
 */
$wgOAuthSecretKey = $wgSecretKey;

# End of configuration variables.
# ########
