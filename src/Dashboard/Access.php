<?php

namespace Ads\Statistics\Dashboard;

/**
 * The default `viewStatistics` gate: local environment, allowed emails, or allowed roles.
 */
class Access
{
	public static function allows($user = null)
	{
		if (app()->environment('local')) {
			return true;
		}

		if (! $user) {
			return false;
		}

		return self::emailAllowed($user) || self::roleAllowed($user);
	}

	protected static function emailAllowed($user)
	{
		$allowed = array_map('strtolower', (array) config('statistics.dashboard.emails', []));
		$email = data_get($user, config('statistics.dashboard.email_attribute', 'email'));

		return $allowed && is_string($email) && in_array(strtolower(trim($email)), $allowed, true);
	}

	protected static function roleAllowed($user)
	{
		$allowed = (array) config('statistics.dashboard.roles', []);

		if (! $allowed) {
			return false;
		}

		if (method_exists($user, 'hasAnyRole')) {
			return (bool) $user->hasAnyRole($allowed);
		}

		if (method_exists($user, 'hasRole')) {
			foreach ($allowed as $role) {
				if ($user->hasRole($role)) {
					return true;
				}
			}

			return false;
		}

		// Plain column: a string, or an array/collection of role names
		$roles = data_get($user, config('statistics.dashboard.role_attribute', 'role'));
		$roles = is_object($roles) && method_exists($roles, 'toArray') ? $roles->toArray() : (array) $roles;

		$roles = array_map('strtolower', array_filter($roles, 'is_string'));

		return (bool) array_intersect($roles, array_map('strtolower', $allowed));
	}
}
