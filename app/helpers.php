<?php

if (!function_exists('permission_action')) {
    /**
     * Convert a permission name like "create admin" into a short action label like "Create".
     * Used in the admin panel permission checkbox labels.
     */
    function permission_action(string $permissionName): string
    {
        // Extract the first word (the action verb) and capitalise it
        $parts = explode(' ', $permissionName);
        return ucfirst($parts[0] ?? $permissionName);
    }
}

if (!function_exists('permission_description')) {
    /**
     * Return a human-readable description for a given permission name.
     * Used as helper text beneath each permission checkbox.
     */
    function permission_description(string $permissionName): string
    {
        $descriptions = [
            // dashboard
            'view dashboard'        => 'Access the admin dashboard overview.',

            // admin users
            'view admins'           => 'View the list of all admin users.',
            'create admin'          => 'Create new admin user accounts.',
            'edit admin'            => 'Edit existing admin user details and roles.',
            'delete admin'          => 'Permanently delete an admin user account.',

            // roles
            'view roles'            => 'View all system roles and their permissions.',
            'create role'           => 'Create a new role with assigned permissions.',
            'edit role'             => 'Modify an existing role and its permissions.',
            'delete role'           => 'Delete a role from the system.',

            // clubs
            'view clubs'            => 'Browse and search all registered clubs.',
            'create club'           => 'Register a new club in the system.',
            'edit club'             => 'Update club information and settings.',
            'delete club'           => 'Permanently remove a club from the system.',
            'verify club'           => 'Verify or unverify a club account.',

            // players
            'view players'          => 'Browse and search all registered players.',
            'edit player'           => 'Update player profile information.',
            'delete player'         => 'Permanently remove a player account.',
            'toggle player status'  => 'Activate or deactivate a player account.',

            // settings
            'view settings'         => 'View global system settings.',
            'edit settings'         => 'Update global system configuration.',
        ];

        return $descriptions[$permissionName]
            ?? 'Allows the user to ' . $permissionName . '.';
    }
}
