<?php

/*
 * Copyright (C) 2016  Mark A. Hershberger
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace PageProtect;

use Action;
use Article;
use DatabaseUpdater;
use IContextSource;
use Message;
use OutputPage;
use RequestContext;
use Title;
use User;
use WikiPage;
use Xml;

class Hook {
	static protected $protections = [ 'read', 'edit' ];
	static protected $suffix = "AllowedGroup";

	/**
	 * Called immediately after this extension is loaded.
	 */
	public static function initExtension() {
		$ctx = RequestContext::getMain();
		$ctx->getOutput()->addModules( 'ext.pageProtect' );
	}

	/**
	 * Allows to modify the types of protection that can be applied.
	 *
	 * @param Title $title the title we want to look at
	 * @param array &$types types of protection available
	 *
	 * @return bool
	 */
	public static function onTitleGetRestrictionTypes( Title $title,
													   array &$types ) {
		// Remove the default move option
		// FIXME Need to create a migration script.
		$types = array_diff( $types, self::$protections );
		return true;
	}

	/**
	 * Called after all protection type fieldsets are made in the form.
	 *
	 * @param Article $article the title being (un)protected
	 * @param string &$output the html form so far
	 *
	 * @return bool
	 */
	public static function onProtectionFormBuildForm( Article $article,
													  &$output ) {
		$page = $article->getPage();
		$ctx = $article->getContext();
		$user = $ctx->getUser();
		if ( !$article->exists() ) {
			return true;
		}

		$isAllowed
			= $article->getTitle()->userCan( "pageprotect-by-group", $user );
		$disabledAttrib = $isAllowed ? [] : [ 'disabled' => 'disabled' ];
		$pageProtections = self::getCurrentProtections( $page );

		foreach ( self::$protections as $prot ) {
			$protections = isset( $pageProtections['perm'][$prot] )
						 ? $pageProtections['perm'][$prot]
						 : [];
			$output .= self::getProtectFormlet( $prot, $disabledAttrib,
												$protections );
		}

		return true;
	}

	/**
	 * Get an array of the current protections that this page has.
	 *
	 * @param Article $article the page to check
	 *
	 * @return array permissions for this page in the following format:
	 *   [ [ permission1 => [ groupId, ... ],
	 *       permission2 => [ groupId, ... ] ],
	 *     [ group1 = [ permission, ... ],
	 *       ...
	 *   ] ]
	 */
	public static function getCurrentProtections( WikiPage $page ) {
		// FIXME: need caching here
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( 'pageprotect',
							 [ 'ppt_permission', 'ppt_group' ],
							 [ 'ppt_page' => $page->getId() ],
							 __METHOD__ );
		$rowResult = [];
		if ( count( $res ) > 0 ) {
			foreach ( $res as $row ) {
				$rowResult['perm'][$row->ppt_permission][] = $row->ppt_group;
				$rowResult['group'][$row->ppt_group][] = $row->ppt_permission;
			}
		}

		return $rowResult;
	}

	/**
	 * Provides a piece of the form that we can use.
	 *
	 * @param string $type the type of protection being done
	 * @param array $disabledAttrib what to provide for the disabled attribute
	 * @param array $pageProtections the protections on the page
	 *
	 * @return string the html to display
	 */
	protected static function getProtectFormlet( $type,
												 array $disabledAttrib,
												 array $pageProtections ) {
		$output = "<tr><td>";
		$output .= Xml::openElement( 'fieldset' );
		$output .= "<legend>" .
				wfMessage( "pageprotect-$type-limit-legend" )->parse() .
				"</legend>";

		# Add a "no group restrictions" level
		$groupList = User::getAllGroups();
		# Show all groups in a <select>...
		$attribs = [
			'id'    => $type . self::$suffix,
			'name'  => $type . self::$suffix . '[]',
			'multiple' => true,
			'size'  => count( $groupList ),
		] + $disabledAttrib;
		$inverted = array_flip( $pageProtections );

		$output .= Xml::openElement( 'select', $attribs );
		foreach ( $groupList as $group ) {
			$label = wfMessage( 'group-' . $group )->text();
			$output .= Xml::option( $label, $group,
									isset( $inverted[ $group ] ) );
		}

		return $output . Xml::closeElement( 'select' ) . "</td></tr>";
	}

	/**
	 * Called when a protection form is submitted.
	 *
	 * @param Article $article the title being (un)protected
	 * @param string &$error the html message string of an error
	 * @param string $reasonstr the reason the user is giving for this change
	 *
	 * @return bool
	 */
	public static function onProtectionFormSave( Article $article,
												 &$error,
												 $reasonstr
	) {
		$ctx = $article->getContext();
		$req = $ctx->getRequest();
		$pageId = $article->getPage()->getId();
		$groups = [];
		$dbw = wfGetDB( DB_MASTER );

		$dbw->begin( __METHOD__ );
		$dbw->delete( 'pageprotect', [ 'ppt_page' => $pageId ], __METHOD__ );
		foreach ( self::$protections as $prot ) {
			$groups = $req->getArray( $prot . self::$suffix );
			if ( count( $groups ) > 0 ) {
				foreach ( $groups as $group ) {
					$dbw->insert( 'pageprotect',
								  [ 'ppt_page' => $pageId,
									'ppt_permission' => $prot,
									'ppt_group' => $group ],
								  __METHOD__ );
				}
			}
		}
		$dbw->commit( __METHOD__ );
		// FIXME: Log the action?
		return true;
	}

	/**
	 * Called after the protection log extract is shown.
	 *
	 * @param Article $article the title being (un)protected
	 * @param OutputPage $out the output
	 */
	public static function onProtectionFormShowLog( Article $article,
													OutputPage $out ) {
	}

	/**
	 * Hook invoked before article protection is processed.
	 *
	 * @param Article $article the article being protected
	 * @param User $user the user doing the protection
	 * @param bool $protect whether protect or an unprotect
	 * @param string $reason Reason for protection
	 * @param bool $moveonly move only or not
	 */
	public static function onArticleProtect( Article $article, User $user,
											 bool $protect, $reason,
											 bool $moveonly ) {
		wfDebugLog( __METHOD__, "in ArticleProtect" );
	}

	/**
	 * Hook invoked after article protection is processed
	 *
	 * @param Article $article the article object that was protected
	 * @param User $user the user object who did the protection
	 * @param bool $protect whether it was a protect or an unprotect
	 * @param string $reason Reason for protection
	 * @param bool $moveonly whether it was for move only or not
	 */
	public static function onArticleProtectComplete( Article $article,
													 User $user,
													 bool $protect,
													 $reason,
													 bool $moveonly
	) {
		wfDebugLog( __METHOD__, "in ArticleProtectComplete" );
	}

	/**
	 * Executed before the file is streamed to the user by img_auth.php
	 *
	 * @param Title $title title object for file as it would appear
	 *			for the upload page
	 * @param string &$path the original file and path name when img_auth was
	 *     invoked by the the web server
	 * @param string &$name the name only component of the file
	 * @param array &$result The location to pass back results of the hook
	 *			routine (only used if failed)
	 */
	public static function onImgAuthBeforeStream( Title $title, &$path,
												  &$name, &$result
	) {
		$user = RequestContext::getMain()->getUser();
		$pageProtections = self::getCurrentProtections(
			WikiPage::factory( $title ) );
		$inGroups = $user->getGroups();

		if ( isset( $pageProtections['perm']['read'] ) ) {
			$actionGroups = $pageProtections['perm']['read'];

			$intersection = array_intersect( $inGroups, $actionGroups );
			if ( count( $intersection ) === 0 ) {
				$result = [ 'img-auth-accessdenied',
							'img-auth-noread' ];
				return false;
			}
		}
		return true;
	}

	/**
	 * Determines if this title is one that can be protected.
	 *
	 * @param Title $title the page to check
	 *
	 * @return bool
	 */
	protected static function protectableTitle( Title $title ) {
		return $title->getNamespace() >= 0;
	}

	/**
	 * To interrupt/advise the "user can do X to Y article" check
	 *
	 * @param Title $title the title in question
	 * @param User $user the current user
	 * @param string $action action concerning the title in question
	 * @param any &$result can the user perform this action?
	 */
	public static function onGetUserPermissionsErrors( Title $title, User $user,
													   $action,
													   &$result ) {
		if ( !self::protectableTitle( $title ) ) {
			return true;
		}
		$pageProtections = self::getCurrentProtections(
			WikiPage::factory( $title ) );
		$inGroups = $user->getGroups();

		if ( isset( $pageProtections['perm'][$action] ) ) {
			$actionGroups = $pageProtections['perm'][$action];
			$intersection = array_intersect( $inGroups, $actionGroups );
			if ( count( $intersection ) === 0 ) {
				$result = wfMessage( "pageprotect-group-member",
									 [ [ 'list' => $actionGroups, 'type' => 'comma' ] ] );
				return false;
			}
		}
		return true;
	}

	/**
	 * Fired when MediaWiki is updated to allow extensions to update
	 * the database.
	 *
	 * @param DatabaseUpdater $updater the db handle
	 * @return bool always true
	 */
	public static function onLoadExtensionSchemaUpdates(
		DatabaseUpdater $updater
	) {
		$updater->addExtensionTable( 'pageprotect', __DIR__
									 . "/../sql/schema.sql" );
		return true;
	}

	/**
	 * Create the config handler
	 *
	 * @return GlobalVarConfig object
	 */
	public static function makeConfig() {
		return new GlobalVarConfig( "PageProtect" );
	}
}
