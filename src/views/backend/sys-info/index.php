<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Info\widgets\sysinfo\SysInfoWidget;
use yii\web\View;

/**
 * Страница системной информации
 *
 * @var View $this
 */

$this->title = 'Системная информация';
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="sys-info-index">
    <h1><?= $this->title ?></h1>

    <?= SysInfoWidget::widget([
        'updateInterval' => 3000, // 3 секунды
        'autoRefresh' => true,
    ]) ?>
</div>
