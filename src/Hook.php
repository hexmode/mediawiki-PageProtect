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
use OutputPage;
use Article;
use Title;
use User;

class Hook {
	/**
	 * Allows to modify the types of protection that can be applied.
	 *
	 * @param Title $title the title we want to look at
	 * @param array &$types types of protection available
	 */
	public static function onTitleGetRestrictionTypes( Title $title, array &$types ) {
	}

	/**
	 * Called after all protection type fieldsets are made in the form.
	 *
	 * @param Article $article the title being (un)protected
	 * @param string &$output the html form so far
	 */
	public static function onProtectionFormBuildForm( Article $article, string &$output ) {
	}

	/**
	 * Called when a protection form is submitted.
	 *
	 * @param Article $article the title being (un)protected
	 * @param string &$output the html message string of an error
	 */
	public static function onProtectionFormSave( Article $article, string &$output ) {
	}

	/**
	 * Called after the protection log extract is shown.
	 *
	 * @param Article $article the title being (un)protected
	 * @param OutputPage $out the output
	 */
	public static function onProtectionFormShowLog( Article $article, OutputPage $out ) {
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
	public static function onArticleProtect(
		Article $article, User $user, bool $protect, string $reason, bool $moveonly
	) {
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
	public static function onArticleProtectComplete(
		Article $article, User $user, bool $protect, string $reason, bool $moveonly
	) {
	}

	/**
	 * Executed before the file is streamed to the user by img_auth.php
	 *
	 * @param Title $title title object for file as it would appear for the upload page
	 * @param string &$path the original file and path name when img_auth was
	 *     invoked by the the web server
	 * @param string &$name the name only component of the file
	 * @param array &$result The location to pass back results of the hook routine
	 *     (only used if failed)
	 */
	public static function onImgAuthBeforeStream(
		Title $title, string &$path, string &$name, array &$result
	) {
	}

	/**
	 * To interrupt/advise the "user can do X to Y article" check
	 *
	 * @param Title $title the title in question
	 * @param User $user the current user
	 * @param string $action action concerning the title in question
	 * @param bool &$result can the user perform this action?
	 */
	public static function onUserCan( Title $title, User $user, string $action, bool &$result ) {
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
