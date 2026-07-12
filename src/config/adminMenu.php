<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

return [
    // System Info
    [
        'label' => 'System info',
        'iconClass' => 'bi bi-info-square me-1',
        'url' => ['/Info/backend/sys-info/index'],
        'active' => static function () {
            return str_contains(\Yii::$app->request->url, 'Info/backend/sys-info');
        },
        '_meta' => [
            'placements' => [
                [
                    'location' => 'left-sidebar',
                    'group' => null,
                    'priority' => 100,
                ],
                [
                    'location' => 'right-sidebar',
                    'group' => null,
                    'priority' => 100,
                ],
            ],
        ],
    ]
];
