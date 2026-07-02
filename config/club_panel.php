<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Club panel sidebar navigation
    |--------------------------------------------------------------------------
    |
    | Each item needs a registered route name. Optional "active" accepts a
    | route pattern for highlighting nested routes (e.g. club.fixtures.*).
    |
    */
    'menu' => [
        [
            'section' => 'Main',
            'items' => [
                [
                    'route' => 'club.dashboard',
                    'label' => 'Dashboard',
                    'icon' => 'fas fa-th-large',
                    'active' => 'club.dashboard',
                ],
            ],
        ],
        [
            'section' => 'Management',
            'items' => [
                [
                    'route' => 'club.profile.index',
                    'label' => 'My Club',
                    'icon' => 'fas fa-building',
                    'active' => 'club.profile.index',
                ],
                [
                    'route' => 'club.profile.edit',
                    'label' => 'Edit Club',
                    'icon' => 'fas fa-edit',
                    'active' => 'club.profile.edit',
                ],
                [
                    'route' => 'club.squads.index',
                    'label' => 'Squads',
                    'icon' => 'fas fa-users',
                    'active' => 'club.squads.*',
                ],
                [
                    'route' => 'club.fixtures.index',
                    'label' => 'Fixtures',
                    'icon' => 'fas fa-calendar-alt',
                    'active' => 'club.fixtures.*',
                ],
                [
                    'route' => 'club.players.index',
                    'label' => 'Players',
                    'icon' => 'fas fa-user-friends',
                    'active' => 'club.players.*',
                ],
            ],
        ],
        [
            'section' => 'More',
            'items' => [
                [
                    'route' => 'club.invitations.index',
                    'label' => 'Invitations',
                    'icon' => 'fas fa-envelope',
                    'active' => 'club.invitations.*',
                ],
                [
                    'route' => 'club.scoring.index',
                    'label' => 'Scoring',
                    'icon' => 'fas fa-baseball-ball',
                    'active' => 'club.scoring.*',
                ],
            ],
        ],
    ],

];
