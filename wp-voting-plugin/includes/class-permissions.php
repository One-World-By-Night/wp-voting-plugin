<?php
/**
 * Unified permission system.
 *
 * Priority chain:
 *   1. If AccessSchema is configured (mode != 'none') and reachable → use it.
 *   2. Otherwise → fall back to WordPress capabilities.
 *
 * Admin actions (create / manage votes) always require WP `manage_options`
 * because they are WordPress admin-panel operations.
 *
 * Voter-facing actions (cast vote, view results) go through the priority chain
 * so that AccessSchema role paths can control per-vote eligibility.
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Permissions {

	/*
	------------------------------------------------------------------
	 *  Admin-level checks (always WP capabilities).
	 * ----------------------------------------------------------------*/

	/**
	 * Can the user create new votes?
	 */
	public static function can_create_vote( int $user_id = 0 ): bool {
		$user_id = $user_id ? $user_id : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		return user_can( $user_id, 'manage_options' )
			|| user_can( $user_id, 'wpvp_create_votes' );
	}

	/**
	 * Can the user manage (edit / delete / bulk-action) all votes?
	 */
	public static function can_manage_votes( int $user_id = 0 ): bool {
		$user_id = $user_id ? $user_id : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		return user_can( $user_id, 'manage_options' )
			|| user_can( $user_id, 'wpvp_manage_votes' );
	}

	/*
	------------------------------------------------------------------
	 *  Voter-facing checks (priority chain).
	 * ----------------------------------------------------------------*/

	/**
	 * Can the user cast a vote on a specific proposal?
	 *
	 * Checks:
	 *  1. User must be logged in.
	 *  2. Vote must be in 'open' stage.
	 *  3. Current time must be within open/close window (if set).
	 *  4. User must not have already voted (unless revoting is allowed).
	 *  5. User must pass the permission check (AccessSchema → WP fallback).
	 */
	public static function can_cast_vote( int $user_id, int $vote_id ): bool {
		if ( ! $user_id ) {
			return false;
		}

		// Admins can always vote.
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		$vote = WPVP_Database::get_vote( $vote_id );
		if ( ! $vote ) {
			return false;
		}

		// Must be open.
		if ( 'open' !== $vote->voting_stage ) {
			return false;
		}

		// Date window check.
		$now = current_time( 'mysql' );
		if ( $vote->opening_date && $now < $vote->opening_date ) {
			return false;
		}
		if ( $vote->closing_date && $now > $vote->closing_date ) {
			return false;
		}

		// Already voted?
		if ( WPVP_Database::user_has_voted( $user_id, $vote_id ) ) {
			$settings = json_decode( $vote->settings, true );
			$settings = $settings ? $settings : array();
			if ( empty( $settings['allow_revote'] ) ) {
				return false;
			}
		}

		// Voting eligibility check.
		return self::user_can_vote_on( $user_id, $vote );
	}

	/**
	 * Can the user view results for a specific vote?
	 *
	 * Logic:
	 *  - Admins: always yes.
	 *  - Completed/archived votes: anyone who was an eligible voter or who voted.
	 *  - Open votes with "show results before close": eligible voters.
	 *  - Otherwise: no.
	 */
	public static function can_view_results( int $user_id, int $vote_id ): bool {
		$vote = WPVP_Database::get_vote( $vote_id );
		if ( ! $vote ) {
			return false;
		}

		// Admins can always view results.
		if ( $user_id && user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		// Vote creator can always view results.
		if ( $user_id && (int) ( $vote->created_by ?? 0 ) === $user_id ) {
			return true;
		}

		// Public visibility → anyone can view results (including guests).
		if ( 'public' === $vote->visibility ) {
			return true;
		}

		// From here on, user must be logged in.
		if ( ! $user_id ) {
			return false;
		}

		// Private visibility → any logged-in user can view results.
		if ( 'private' === $vote->visibility ) {
			return true;
		}

		// Completed or archived → anyone who voted OR is an eligible voter can see.
		if ( in_array( $vote->voting_stage, array( 'completed', 'archived' ), true ) ) {
			// Has voted? They should definitely see the results.
			if ( WPVP_Database::user_has_voted( $user_id, $vote_id ) ) {
				return true;
			}
			// Was an eligible voter (even if they didn't vote)?
			return self::user_can_vote_on( $user_id, $vote );
		}

		// Open vote with "show results before close" enabled.
		if ( 'open' === $vote->voting_stage ) {
			$settings = json_decode( $vote->settings, true );
			$settings = $settings ? $settings : array();
			if ( ! empty( $settings['show_results_before_closing'] ) ) {
				return self::user_can_vote_on( $user_id, $vote );
			}
		}

		return false;
	}

	/**
	 * Has the user already voted on this proposal?
	 */
	public static function has_voted( int $user_id, int $vote_id ): bool {
		return WPVP_Database::user_has_voted( $user_id, $vote_id );
	}

	/**
	 * Get all eligible roles for a user to vote on this vote.
	 * Returns array of role paths/names that grant voting access.
	 *
	 * @param int    $user_id User ID.
	 * @param object $vote    Vote object.
	 * @return array Array of role paths. Empty if no eligible roles.
	 */
	public static function get_eligible_voting_roles( int $user_id, object $vote ): array {
		// Public voting = no roles needed.
		if ( 'public' === $vote->voting_eligibility ) {
			return array( 'Public' );
		}

		// Private = any logged-in user (no specific role).
		if ( 'private' === $vote->voting_eligibility ) {
			return array( 'Logged-in User' );
		}

		// Restricted: check voting_roles.
		$voting_roles = json_decode( $vote->voting_roles, true );
		if ( empty( $voting_roles ) ) {
			return array();
		}

		return self::get_matching_roles( $user_id, $voting_roles );
	}

	/*
	------------------------------------------------------------------
	 *  Internal: priority-chain access checks (view vs vote).
	 * ----------------------------------------------------------------*/

	/**
	 * Can the user VIEW this vote?
	 *
	 * Checks visibility + allowed_roles (who can SEE the vote).
	 *
	 * Priority chain:
	 *  1. Public visibility → everyone can view.
	 *  2. Private visibility → any logged-in user can view.
	 *  3. Restricted visibility → check allowed_roles via AccessSchema
	 *     (if configured) then fall back to WordPress capabilities.
	 */
	public static function can_view_vote( int $user_id, object $vote ): bool {
		// Admins can always view.
		if ( $user_id && user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		// Public votes are visible to everyone.
		if ( 'public' === $vote->visibility ) {
			return true;
		}

		// Private = any logged-in user can view.
		if ( 'private' === $vote->visibility ) {
			return $user_id > 0;
		}

		// Restricted: check allowed_roles.
		$allowed_roles = json_decode( $vote->allowed_roles, true );
		if ( empty( $allowed_roles ) ) {
			return false;
		}

		return self::check_roles( $user_id, $allowed_roles );
	}

	/**
	 * Can the user VOTE on this vote?
	 *
	 * Checks voting_eligibility + voting_roles (who can CAST a ballot).
	 *
	 * Priority chain:
	 *  1. Public voting → anyone can vote (even non-logged-in if allowed).
	 *  2. Private voting → any logged-in user can vote.
	 *  3. Restricted voting → check voting_roles via AccessSchema
	 *     (if configured) then fall back to WordPress capabilities.
	 */
	private static function user_can_vote_on( int $user_id, object $vote ): bool {
		// Public voting is open to everyone.
		if ( 'public' === $vote->voting_eligibility ) {
			return true;
		}

		// Private = any logged-in user can vote.
		if ( 'private' === $vote->voting_eligibility ) {
			return $user_id > 0;
		}

		// Restricted: check voting_roles.
		$voting_roles = json_decode( $vote->voting_roles, true );
		if ( empty( $voting_roles ) ) {
			return false;
		}

		return self::check_roles( $user_id, $voting_roles );
	}

	/**
	 * Check if user has any of the specified roles.
	 *
	 * Tries AccessSchema first (if configured), then falls back to WP roles.
	 *
	 * @param int   $user_id User ID.
	 * @param array $roles   Role paths, slugs, or capabilities.
	 * @return bool
	 */
	private static function check_roles( int $user_id, array $roles ): bool {
		if ( empty( $roles ) ) {
			return false;
		}

		// Try AccessSchema first.
		$asc_result = self::check_accessschema( $user_id, $roles );
		if ( null !== $asc_result ) {
			return $asc_result; // AccessSchema gave a definitive answer.
		}

		// Fallback: WordPress role/capability check.
		return self::check_wp_roles( $user_id, $roles );
	}

	/**
	 * Check AccessSchema roles for the user.
	 *
	 * Supports wildcard patterns in role paths:
	 *  - Single wildcard (*) matches one path segment, e.g. "Chronicle/{*}/CM"
	 *  - Double wildcard (**) matches one or more segments, e.g. "Players/{**}"
	 *
	 * Wildcard patterns are expanded against cached roles from the
	 * wpvp_accessschema_roles transient, then each concrete path is
	 * checked via the API (short-circuits on first match).
	 *
	 * @return bool|null  true/false if AccessSchema answered, null if unavailable.
	 */
	private static function check_accessschema( int $user_id, array $role_paths ): ?bool {
		$mode = get_option( 'wpvp_accessschema_mode', 'none' );
		if ( 'none' === $mode ) {
			return null; // Not configured.
		}

		$user = get_userdata( $user_id );
		if ( ! $user || empty( $user->user_email ) ) {
			return null;
		}

		$client_id = defined( 'ASC_PREFIX' ) ? strtolower( ASC_PREFIX ) : 'wpvp';

		// Get user's cached roles from user meta.
		$user_roles = get_user_meta( $user_id, "{$client_id}_accessschema_cached_roles", true );
		if ( ! is_array( $user_roles ) || empty( $user_roles ) ) {
			// No cached roles — return null to fall back to WordPress capabilities.
			return null;
		}

		// Check each allowed role path — user needs to match at least one.
		foreach ( $role_paths as $role_path ) {
			$role_path = sanitize_text_field( $role_path );
			if ( empty( $role_path ) ) {
				continue;
			}

			// Wildcard pattern - match directly against user's cached roles.
			if ( false !== strpos( $role_path, '*' ) ) {
				$regex = self::wildcard_to_regex( $role_path );
				foreach ( $user_roles as $user_role ) {
					if ( preg_match( $regex, $user_role ) ) {
						return true;
					}
				}
				continue;
			}

			// Literal path — check against user's cached roles (with children support).
			if ( self::user_has_cached_role( $user_roles, $role_path, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if user has a cached role, optionally including child roles.
	 *
	 * @param array  $user_roles      User's cached role paths.
	 * @param string $target_role     Role path to check for.
	 * @param bool   $include_children Whether to match child roles.
	 * @return bool
	 */
	private static function user_has_cached_role( array $user_roles, string $target_role, bool $include_children = false ): bool {
		// Direct match.
		if ( in_array( $target_role, $user_roles, true ) ) {
			return true;
		}

		// Check for child roles (user has target_role/something).
		if ( $include_children ) {
			$target_prefix = $target_role . '/';
			foreach ( $user_roles as $user_role ) {
				if ( 0 === strpos( $user_role, $target_prefix ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get all roles from the allowed list that the user has.
	 * Similar to check_roles() but returns matched roles instead of boolean.
	 *
	 * @param int   $user_id User ID.
	 * @param array $roles   Allowed role paths.
	 * @return array Matched role paths.
	 */
	private static function get_matching_roles( int $user_id, array $roles ): array {
		if ( empty( $roles ) ) {
			return array();
		}

		// Try AccessSchema first.
		$asc_matches = self::get_accessschema_matches( $user_id, $roles );
		if ( null !== $asc_matches ) {
			// AccessSchema is available - return only AccessSchema matches.
			return $asc_matches;
		}

		// Fallback: WordPress roles (only if AccessSchema unavailable).
		return self::get_wp_role_matches( $user_id, $roles );
	}

	/**
	 * Get AccessSchema roles that match for this user.
	 *
	 * @param int   $user_id    User ID.
	 * @param array $role_paths Allowed role paths.
	 * @return array|null Array of matched role paths, or null if AccessSchema unavailable.
	 */
	private static function get_accessschema_matches( int $user_id, array $role_paths ): ?array {
		$mode = get_option( 'wpvp_accessschema_mode', 'none' );
		if ( 'none' === $mode ) {
			return null;
		}

		$user = get_userdata( $user_id );
		if ( ! $user || empty( $user->user_email ) ) {
			return null;
		}

		$client_id  = defined( 'ASC_PREFIX' ) ? strtolower( ASC_PREFIX ) : 'wpvp';
		$user_roles = get_user_meta( $user_id, "{$client_id}_accessschema_cached_roles", true );

		if ( ! is_array( $user_roles ) || empty( $user_roles ) ) {
			return null;
		}

		$matches = array();

		foreach ( $role_paths as $role_path ) {
			$role_path = sanitize_text_field( $role_path );
			if ( empty( $role_path ) ) {
				continue;
			}

			// Wildcard pattern - match directly against user's cached roles.
			if ( false !== strpos( $role_path, '*' ) ) {
				$regex = self::wildcard_to_regex( $role_path );
				foreach ( $user_roles as $user_role ) {
					if ( preg_match( $regex, $user_role ) ) {
						$matches[] = $user_role;
					}
				}
				continue;
			}

			// Literal path.
			if ( self::user_has_cached_role( $user_roles, $role_path, true ) ) {
				// Find the exact role user has (may be child role).
				if ( in_array( $role_path, $user_roles, true ) ) {
					$matches[] = $role_path;
				} else {
					// User has child role.
					$target_prefix = $role_path . '/';
					foreach ( $user_roles as $user_role ) {
						if ( 0 === strpos( $user_role, $target_prefix ) ) {
							$matches[] = $user_role;
						}
					}
				}
			}
		}

		return $matches;
	}

	/**
	 * Get WordPress roles that match for this user.
	 *
	 * @param int   $user_id User ID.
	 * @param array $roles   Allowed roles/capabilities.
	 * @return array Matched role names.
	 */
	private static function get_wp_role_matches( int $user_id, array $roles ): array {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return array();
		}

		$matches = array();

		foreach ( $roles as $role_or_cap ) {
			$role_or_cap = sanitize_text_field( $role_or_cap );

			// Skip wildcard patterns.
			if ( false !== strpos( $role_or_cap, '*' ) ) {
				continue;
			}

			// Check as WP role.
			if ( in_array( $role_or_cap, $user->roles, true ) ) {
				$matches[] = ucfirst( $role_or_cap );
			} elseif ( user_can( $user_id, $role_or_cap ) ) {
				// Check as WP capability.
				$matches[] = ucfirst( $role_or_cap );
			}
		}

		return $matches;
	}

	/**
	 * Fallback: check if the user has any of the specified WordPress roles.
	 *
	 * The allowed_roles array may contain WP role slugs (subscriber, editor, etc.)
	 * or WP capabilities. Entries containing wildcards are skipped (not applicable
	 * to flat WP roles).
	 */
	private static function check_wp_roles( int $user_id, array $roles ): bool {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		foreach ( $roles as $role_or_cap ) {
			$role_or_cap = sanitize_text_field( $role_or_cap );

			// Skip wildcard patterns — they only apply to AccessSchema paths.
			if ( false !== strpos( $role_or_cap, '*' ) ) {
				continue;
			}

			// Check as a WP role first.
			if ( in_array( $role_or_cap, $user->roles, true ) ) {
				return true;
			}

			// Check as a WP capability.
			if ( user_can( $user_id, $role_or_cap ) ) {
				return true;
			}
		}

		return false;
	}

	/*
	------------------------------------------------------------------
	 *  Wildcard pattern helpers.
	 * ----------------------------------------------------------------*/

	/**
	 * Expand a wildcard role path pattern against cached AccessSchema roles.
	 *
	 * @param string $pattern Pattern with * and/or ** segments.
	 * @return string[] Matching concrete role paths.
	 */
	private static function expand_wildcard_pattern( string $pattern ): array {
		$cached = get_transient( 'wpvp_accessschema_roles' );
		if ( empty( $cached ) || ! is_array( $cached ) ) {
			return array();
		}

		$all_paths = self::extract_cached_paths( $cached );
		$regex     = self::wildcard_to_regex( $pattern );
		$matches   = array();

		foreach ( $all_paths as $path ) {
			if ( preg_match( $regex, $path ) ) {
				$matches[] = $path;
			}
		}

		return $matches;
	}

	/**
	 * Convert a role path pattern to a regex.
	 *
	 * Supported wildcards:
	 *   *  → matches exactly one path segment (no slashes)
	 *  ** → matches one or more path segments
	 *
	 * Matching is case-insensitive.
	 *
	 * @param string $pattern e.g. "Chronicle/ * /CM" or "Players/ **"
	 * @return string Regex pattern.
	 */
	private static function wildcard_to_regex( string $pattern ): string {
		$segments    = explode( '/', $pattern );
		$regex_parts = array();

		foreach ( $segments as $seg ) {
			$seg = trim( $seg );
			if ( '**' === $seg ) {
				$regex_parts[] = '.+';
			} elseif ( '*' === $seg ) {
				$regex_parts[] = '[^/]+';
			} else {
				$regex_parts[] = preg_quote( $seg, '#' );
			}
		}

		return '#^' . implode( '/', $regex_parts ) . '$#i';
	}

	/**
	 * Extract flat role path strings from the cached AccessSchema transient.
	 *
	 * The transient may be a flat array of strings, an array of objects
	 * with 'path' or 'name' keys, or a structure with a 'roles' wrapper.
	 *
	 * @param array $cached Raw transient data.
	 * @return string[]
	 */
	/**
	 * Public accessor for extracting role paths from cached data.
	 */
	public static function extract_role_paths( array $cached ): array {
		return self::extract_cached_paths( $cached );
	}

	private static function extract_cached_paths( array $cached ): array {
		// If the data has a 'roles' key, unwrap it.
		if ( isset( $cached['roles'] ) && is_array( $cached['roles'] ) ) {
			$cached = $cached['roles'];
		}

		$paths = array();
		foreach ( $cached as $entry ) {
			if ( is_string( $entry ) ) {
				$paths[] = $entry;
			} elseif ( is_array( $entry ) && isset( $entry['path'] ) ) {
				$paths[] = $entry['path'];
			} elseif ( is_array( $entry ) && isset( $entry['name'] ) ) {
				$paths[] = $entry['name'];
			}
		}

		return $paths;
	}
}
