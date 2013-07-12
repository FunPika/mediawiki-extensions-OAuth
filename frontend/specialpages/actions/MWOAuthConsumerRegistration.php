<?php
/*
 (c) Aaron Schulz 2013, GPL

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License along
 with this program; if not, write to the Free Software Foundation, Inc.,
 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 http://www.gnu.org/copyleft/gpl.html
*/

/**
 * Page that has registration request form and consumer update form
 */
class MWOAuthConsumerRegistration extends SpecialPage {
	protected static $stageKeyMap = array(
		MWOAuthConsumer::STAGE_PROPOSED => 'proposed',
		MWOAuthConsumer::STAGE_REJECTED => 'rejected',
		MWOAuthConsumer::STAGE_EXPIRED  => 'expired',
		MWOAuthConsumer::STAGE_APPROVED => 'approved',
		MWOAuthConsumer::STAGE_DISABLED => 'disabled',
	);

	public function __construct() {
		parent::__construct( 'MWOAuthConsumerRegistration', 'mwoauthproposeconsumer' );
	}

	public function execute( $par ) {
		$user = $this->getUser();
		$request = $this->getRequest();

		$block = $user->getBlock();
		if ( $block ) {
			throw new UserBlockedError( $block );
		} elseif ( wfReadOnly() ) {
			throw new ReadOnlyError();
		}

		$this->setHeaders();
		$this->getOutput()->disallowUserJs();

		// Format is Special:MWOAuthConsumerRegistration[/propose|/list|/update/<consumer key>]
		$navigation = explode( '/', $par );
		$action = isset( $navigation[0] ) ? $navigation[0] : null;
		$consumerKey = isset( $navigation[1] ) ? $navigation[1] : null;

		switch ( $action ) {
		case 'propose':
			if ( !$user->isAllowed( 'mwoauthproposeconsumer' ) ) {
				throw new PermissionsError( 'mwoauthproposeconsumer' );
			}

			$form = new HTMLForm(
				array(
					'name' => array(
						'type' => 'text',
						'label-message' => 'mwoauth-consumer-name',
						'size' => '45',
						'required' => true
					),
					'version' => array(
						'type' => 'text',
						'label-message' => 'mwoauth-consumer-version',
						'required' => true,
						'default' => "1.0"
					),
					'description' => array(
						'type' => 'textarea',
						'label-message' => 'mwoauth-consumer-description',
						'required' => true,
						'rows' => 5
					),
					'callbackUrl' => array(
						'type' => 'text',
						'label-message' => 'mwoauth-consumer-callbackurl',
						'required' => true
					),
					'email' => array(
						'type' => 'text',
						'label-message' => 'mwoauth-consumer-email',
						'required' => true
					),
					'wiki' => array(
						'type' => 'text',
						'label-message' => 'mwoauth-consumer-wiki',
						'required' => true,
						'default' => '*'
					),
					'grants'  => array(
						'type' => 'checkmatrix',
						'label-message' => 'mwoauth-consumer-grantsneeded',
						'columns' => array(
							$this->msg( 'mwoauth-consumer-required-grant' )->escaped() => 'grant'
						),
						'rows' => array_combine(
							array_map( 'MWOAuthUtils::grantName', MWOAuthUtils::getValidGrants() ),
							MWOAuthUtils::getValidGrants()
						),
						// @TODO: tooltips
					),
					'restrictions' => array(
						'type' => 'textarea',
						'label-message' => 'mwoauth-consumer-restrictions-json',
						'required' => true,
						'default' => FormatJSON::encode( MWOAuthConsumer::newRestrictions() ),
						'rows' => 5
					),
					'rsaKey' => array(
						'type' => 'textarea',
						'label-message' => 'mwoauth-consumer-rsakey',
						'required' => false,
						'default' => '',
						'rows' => 5
					),
					'action' => array(
						'type'    => 'hidden',
						'default' => 'propose'
					)
				),
				$this->getContext()
			);
			$form->setSubmitCallback( function( array $data, IContextSource $context ) {
				$data['grants'] = FormatJSON::encode( // adapt form to controller
					preg_replace( '/^grant-/', '', $data['grants'] ) );
				$controller = new MWOAuthConsumerSubmitControl( $context, $data );
				return $controller->submit();
			} );
			$form->setWrapperLegendMsg( 'mwoauthconsumerregistration-propose-legend' );
			$form->setSubmitTextMsg( 'mwoauthconsumerregistration-propose-submit' );
			$form->addPreText(
				$this->msg( 'mwoauthconsumerregistration-propose-text' )->parseAsBlock() );

			$status = $form->show();
			if ( $status instanceof Status && $status->isOk() ) {
				$this->getOutput()->addWikiMsg( 'mwoauthconsumerregistration-proposed',
					$status->value['result']->get( 'consumerKey' ),
					$status->value['result']->get( 'secretKey' ) );
				$this->getOutput()->returnToMain();
			}
			break;
		case 'update':
			if ( !$user->isAllowed( 'mwoauthupdateconsumer' ) ) {
				throw new PermissionsError( 'mwoauthupdateconsumer' );
			}

			$dbr = MWOAuthUtils::getCentralDB( DB_SLAVE );
			$cmr = MWOAuthDAOAccessControl::wrap(
				MWOAuthConsumer::newFromKey( $dbr, $consumerKey ), $this->getContext() );
			if ( !$cmr ) {
				$this->getOutput()->addWikiMsg( 'mwoauth-invalid-consumer-key' );
				return;
			} elseif ( $cmr->get( 'deleted' ) && !$user->isAllowed( 'mwoauthviewsuppressed' ) ) {
				throw new PermissionsError( 'mwoauthviewsuppressed' );
			} elseif ( $cmr->get( 'userId' ) !== $user->getId() ) {
				// No not show private information to other users
				$this->getOutput()->addWikiMsg( 'mwoauth-invalid-consumer-key' );
				return;
			}

			$form = new HTMLForm(
				array(
					'nameShown' => array(
						'type' => 'info',
						'label-message' => 'mwoauth-consumer-name',
						'size' => '45',
						'default' => $cmr->get( 'name' )
					),
					'version' => array(
						'type' => 'info',
						'label-message' => 'mwoauth-consumer-version',
						'default' => $cmr->get( 'version' )
					),
					'consumerKeyShown' => array(
						'type' => 'info',
						'label-message' => 'mwoauth-consumer-key',
						'size' => '40',
						'default' => $cmr->get( 'consumerKey' )
					),
					'restrictions' => array(
						'type' => 'textarea',
						'label-message' => 'mwoauth-consumer-restrictions-json',
						'required' => true,
						'default' => FormatJSON::encode( $cmr->getDAO()->get( 'restrictions' ) ),
						'rows' => 5
					),
					'secretKey' => array(
						'type' => 'password',
						'label-message' => 'mwoauth-consumer-secretkey',
						'size' => '45',
						'required' => false,
						'default' => $cmr->getDAO()->get( 'secretKey' ),
					),
					'rsaKey' => array(
						'type' => 'textarea',
						'label-message' => 'mwoauth-consumer-rsakey',
						'required' => false,
						'default' => $cmr->getDAO()->get( 'rsaKey' ),
						'rows' => 5
					),
					'reason' => array(
						'type' => 'text',
						'label-message' => 'mwoauth-consumer-reason',
						'required' => true
					),
					'consumerKey' => array(
						'type' => 'hidden',
						'default' => $cmr->get( 'consumerKey' )
					),
					'action' => array(
						'type'    => 'hidden',
						'default' => 'update'
					)
				),
				$this->getContext()
			);
			$form->setSubmitCallback( function( array $data, IContextSource $context ) {
				$controller = new MWOAuthConsumerSubmitControl( $context, $data );
				return $controller->submit();
			} );
			$form->setWrapperLegendMsg( 'mwoauthconsumerregistration-update-legend' );
			$form->setSubmitTextMsg( 'mwoauthconsumerregistration-update-submit' );
			$form->addPreText(
				$this->msg( 'mwoauthconsumerregistration-update-text' )->parseAsBlock() );

			$status = $form->show();
			if ( $status instanceof Status && $status->isOk() ) {
				$this->getOutput()->addWikiMsg( 'mwoauthconsumerregistration-updated' );
				$this->getOutput()->returnToMain();
			} else {
				$out = $this->getOutput();
				// Show all of the status updates
				$logPage = new LogPage( 'mwoauthconsumer' );
				$out->addHTML( Xml::element( 'h2', null, $logPage->getName()->text() ) );
				LogEventsList::showLogExtract( $out, 'mwoauthconsumer', '', '',
					array(
						'conds'  => array(
							'ls_field' => 'OAuthConsumer', 'ls_value' => $cmr->get( 'consumerKey' ) ),
						'flags'  => LogEventsList::NO_EXTRA_USER_LINKS
					)
				);
			}
			break;
		case 'list':
			$pager = new MWOAuthListMyConsumersPager( $this, array(), $this->getUser() );
			if ( $pager->getNumRows() ) {
				$this->getOutput()->addHTML( $pager->getNavigationBar() );
				$this->getOutput()->addHTML( $pager->getBody() );
				$this->getOutput()->addHTML( $pager->getNavigationBar() );
			} else {
				$this->getOutput()->addWikiMsg( "mwoauthconsumerregistration-none" );
			}
			# Every 30th view, prune old deleted items
			if ( 0 == mt_rand( 0, 29 ) ) {
				MWOAuthUtils::runAutoMaintenance( MWOAuthUtils::getCentralDB( DB_MASTER ) );
			}
			break;
		default:
			$this->getOutput()->addWikiMsg( 'mwoauthconsumerregistration-maintext' );
		}
	}

	/**
	 * @param DatabaseBase $db
	 * @param sdtclass $row
	 * @return string
	 */
	public function formatRow( $db, $row ) {
		$cmr = MWOAuthDAOAccessControl::wrap(
			MWOAuthConsumer::newFromRow( $db, $row ), $this->getContext() );

		$link = Linker::linkKnown(
			$this->getTitle( 'update/' . $cmr->get( 'consumerKey' ) ),
			$this->msg( 'mwoauthconsumerregistration-manage' )->escaped()
		);

		$time = $this->getLanguage()->timeanddate(
			wfTimestamp( TS_MW, $cmr->get( 'registration' ) ), true );

		$stageKey = self::$stageKeyMap[$cmr->get( 'stage' )];
		$encStageKey = htmlspecialchars( $stageKey ); // sanity
		$r = "<li class='mw-mwoauthconsumerregistration-type-{$encStageKey}'>";

		$r .= $time . " (<strong>{$link}</strong>)";

		// Show last log entry (@TODO: title namespace?)
		// @TODO: inject DB
		$logHtml = '';
		LogEventsList::showLogExtract( $logHtml, 'mwoauthconsumer', '', '',
			array(
				'conds'  => array(
					'ls_field' => 'OAuthConsumer', 'ls_value' => $cmr->get( 'consumerKey' ) ),
				'lim'    => 1,
				'flags'  => LogEventsList::NO_EXTRA_USER_LINKS
			)
		);

		$lang = $this->getLanguage();
		$data = array(
			'mwoauthconsumerregistration-name' => htmlspecialchars(
				$cmr->get( 'name', function( $s ) use ( $cmr ) {
					return $s . ' [' . $cmr->get( 'version' ) . ']'; } )
			),
			'mwoauthconsumerregistration-description' => htmlspecialchars(
				$cmr->get( 'description', function( $s ) use ( $lang ) {
					return $lang->truncate( $s, 10024 ); } )
			),
			'mwoauthconsumerregistration-email' => htmlspecialchars( $cmr->get( 'email' ) ),
			'mwoauthconsumerregistration-consumerkey' => htmlspecialchars( $cmr->get( 'consumerKey' ) ),
			'mwoauthconsumerregistration-lastchange' => $logHtml
		);

		$r .= "<table class='mw-mwoauthconsumerregistration-body-{$encStageKey}' " .
			"cellspacing='1' cellpadding='3' border='1' width='100%'>";
		foreach ( $data as $msg => $encValue ) {
			$r .= '<tr>' .
				'<td><strong>' . $this->msg( $msg )->escaped() . '</strong></td>' .
				'<td width=\'90%\'>' . $encValue . '</td>' .
				'</tr>';
		}
		$r .= '</table>';

		$r .= '</li>';

		return $r;
	}
}

/**
 * Query to list out consumers
 *
 * @TODO: use UserCache
 */
class MWOAuthListMyConsumersPager extends ReverseChronologicalPager {
	public $mForm, $mConds;

	function __construct( $form, $conds, $user ) {
		$this->mForm = $form;
		$this->mConds = $conds;

		$this->mConds['oarc_user_id'] = $user instanceof $user ? $user->getId() : $user;
		if ( !$this->getUser()->isAllowed( 'mwoauthviewsuppressed' ) ) {
			$this->mConds['oarc_deleted'] = 0;
		}

		parent::__construct();
		# Treat 20 as the default limit, since each entry takes up 5 rows.
		$urlLimit = $this->mRequest->getInt( 'limit' );
		$this->mLimit = $urlLimit ? $urlLimit : 20;
	}

	/**
	 * @return Title
	 */
	function getTitle() {
		return $this->mForm->getFullTitle();
	}

	/**
	 * @param $row
	 * @return string
	 */
	function formatRow( $row ) {
		return $this->mForm->formatRow( $this->mDb, $row );
	}

	/**
	 * @return string
	 */
	function getStartBody() {
		if ( $this->getNumRows() ) {
			return '<ul>';
		} else {
			return '';
		}
	}

	/**
	 * @return string
	 */
	function getEndBody() {
		if ( $this->getNumRows() ) {
			return '</ul>';
		} else {
			return '';
		}
	}

	/**
	 * @return array
	 */
	function getQueryInfo() {
		return array(
			'tables' => array( 'oauth_registered_consumer' ),
			'fields' => array( '*' ),
			'conds'  => $this->mConds
		);
	}

	/**
	 * @return string
	 */
	function getIndexField() {
		return 'oarc_stage_timestamp';
	}
}
