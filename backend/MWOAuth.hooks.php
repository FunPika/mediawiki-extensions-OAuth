<?php

namespace MediaWiki\Extensions\OAuth;

/**
 * Class containing hooked functions for an OAuth environment
 */
class MWOAuthHooks {
	/**
	 * Reserve all change tags beginning with 'OAuth CID:' (case-insensitive) so
	 * that the user may not create them
	 *
	 * @param string $tag
	 * @param \User $user
	 * @param \Status $status
	 * @return bool
	 */
	public static function onChangeTagCanCreate( $tag, \User $user, \Status &$status ) {
		if ( strpos( strtolower( $tag ), 'oauth cid:' ) === 0 ) {
			$status->fatal( 'mwoauth-tag-reserved' );
		}
		return true;
	}

	public static function onMergeAccountFromTo( \User $oUser, \User $nUser ) {
		global $wgMWOAuthSharedUserIDs;

		if ( !$wgMWOAuthSharedUserIDs ) {
			$oldid = $oUser->getId();
			$newid = $nUser->getId();
			if ( $oldid && $newid ) {
				self::doUserIdMerge( $oldid, $newid );
			}
		}

		return true;
	}

	public static function onCentralAuthGlobalUserMerged( $oldname, $newname, $oldid, $newid ) {
		global $wgMWOAuthSharedUserIDs;

		if ( $wgMWOAuthSharedUserIDs && $oldid && $newid ) {
			self::doUserIdMerge( $oldid, $newid );
		}

		return true;
	}

	protected function doUserIdMerge( $oldid, $newid ) {
		$dbw = MWOAuthUtils::getCentralDB( DB_MASTER );
		// Merge any consumers register to this user
		$dbw->update( 'oauth_registered_consumer',
			array( 'oarc_user_id' => $newid ),
			array( 'oarc_user_id' => $oldid ),
			__METHOD__
		);
		// Delete any acceptance tokens by the old user ID
		$dbw->delete( 'oauth_accepted_consumer',
			array( 'oaac_user_id' => $oldid ),
			__METHOD__
		);
	}

	/**
	 * List tags that should show as defined/active on Special:Tags
	 *
	 * Handles both the ChangeTagsListActive and ListDefinedTags hooks. Only
	 * lists those tags that are actually in use on the local wiki, to avoid
	 * flooding Special:Tags with tags for consumers that will never be making
	 * logged actions.
	 *
	 * @param boolean $activeOnly true for ChangeTagsListActive, false for ListDefinedTags
	 * @param array &$tags
	 * @return bool
	 */
	public static function getUsedConsumerTags( $activeOnly, &$tags ) {
		// Step 1: Get the list of (active) consumers' tags for this wiki
		$db = MWOAuthUtils::getCentralDB( DB_SLAVE );
		$conds = array(
			$db->makeList( array(
				'oarc_wiki = ' . $db->addQuotes( '*' ),
				'oarc_wiki = ' . $db->addQuotes( wfWikiId() ),
			), LIST_OR ),
			'oarc_deleted' => 0,
		);
		if ( $activeOnly ) {
			$conds[] = $db->makeList( array(
				'oarc_stage = ' . MWOAuthConsumer::STAGE_APPROVED,
				// Proposed consumers are active for the owner, so count them too
				'oarc_stage = ' . MWOAuthConsumer::STAGE_PROPOSED,
			), LIST_OR );
		}
		$res = $db->select(
			'oauth_registered_consumer',
			array( 'oarc_id' ),
			$conds,
			__METHOD__
		);
		$allTags = array();
		foreach ( $res as $row ) {
			$allTags[] = "OAuth CID: $row->oarc_id";
		}

		// Step 2: Return only those that are in use.
		if ( $allTags ) {
			$db = wfGetDB( DB_SLAVE );
			$res = $db->select(
				'change_tag',
				array( 'ct_tag' ),
				array( 'ct_tag' => $allTags ),
				__METHOD__,
				array( 'DISTINCT' )
			);
			foreach ( $res as $row ) {
				$tags[] = $row->ct_tag;
			}
		}

		return true;
	}
}
