<?php

return [
    'created' => 'Role created successfully.',
    'updated' => 'Role updated successfully.',
    'deleted' => 'Role deleted successfully.',
    'in_use' => 'This role is still assigned to :count user(s). Remove it from those users first.',

    // Resource names in the permission matrix (key = part before the dot in the permission name).
    'resources' => [
        'users' => 'Users',
        'roles' => 'Roles & permissions',
        'settings' => 'Site settings',
        'website' => 'Site appearance',
        'media' => 'Media library',
        'audit' => 'Activity log',
        'posts' => 'News',
        'announcements' => 'Announcements',
        'pages' => 'Pages',
        'categories' => 'Categories',
        'tags' => 'Tags',
        'menus' => 'Menus',
        'sliders' => 'Sliders',
        'galleries' => 'Galleries',
        'events' => 'Events',
        'downloads' => 'Downloads',
        'testimonials' => 'Testimonials',
        'features' => 'Features',
        'contact' => 'Inbox',
    ],
    'actions' => [
        'view' => 'View',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'manage' => 'Manage',
    ],
];
