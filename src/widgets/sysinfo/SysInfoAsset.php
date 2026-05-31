<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Info\widgets\sysinfo;

use yii\web\AssetBundle;

/**
 * Asset bundle для виджета системной информации
 */
class SysInfoAsset extends AssetBundle
{
    /**
     * @var string Путь к директории с assets
     */
    public $sourcePath = __DIR__ . '/media';

    /**
     * @var array CSS файлы
     */
    public $css = [
        'css/sysinfo.css',
    ];

    /**
     * @var array JavaScript файлы
     */
    public $js = [
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
        'js/dist/index.js',
    ];

    /**
     * @var array Зависимости от других asset bundles
     */
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
        'yii\bootstrap5\BootstrapPluginAsset',
    ];

    /**
     * @var array Опции для JS тегов
     */
    public $jsOptions = [
        'type' => 'module', // ES6 модули
    ];
}
