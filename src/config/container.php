<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Info\providers\ApplicationMetricProvider;
use Besnovatyj\Info\providers\DatabaseMetricProvider;
use Besnovatyj\Info\providers\DockerMetricProvider;
use Besnovatyj\Info\providers\NginxMetricProvider;
use Besnovatyj\Info\providers\PhpMetricProvider;
use Besnovatyj\Info\providers\RedisMetricProvider;
use Besnovatyj\Info\providers\SystemMetricProvider;
use Besnovatyj\Info\services\SysInfoService;

/**
 * Конфигурация DI контейнера для модуля Info
 */


return function (\yii\di\Container $container): void {
    // Провайдеры метрик (stateless, можно использовать как singletons)
    $container->setSingleton(SystemMetricProvider::class, SystemMetricProvider::class);
    $container->setSingleton(PhpMetricProvider::class, PhpMetricProvider::class);
    $container->setSingleton(DockerMetricProvider::class, DockerMetricProvider::class);
    $container->setSingleton(DatabaseMetricProvider::class, DatabaseMetricProvider::class);
    $container->setSingleton(RedisMetricProvider::class, RedisMetricProvider::class);
    $container->setSingleton(NginxMetricProvider::class, NginxMetricProvider::class);
    $container->setSingleton(ApplicationMetricProvider::class, ApplicationMetricProvider::class);

    // Сервис с инъекцией всех провайдеров
    $container->setSingleton(SysInfoService::class, fn() => new SysInfoService(
        systemProvider: $container->get(SystemMetricProvider::class),
        phpProvider: $container->get(PhpMetricProvider::class),
        dockerProvider: $container->get(DockerMetricProvider::class),
        databaseProvider: $container->get(DatabaseMetricProvider::class),
        redisProvider: $container->get(RedisMetricProvider::class),
        nginxProvider: $container->get(NginxMetricProvider::class),
        applicationProvider: $container->get(ApplicationMetricProvider::class),
    ));
};

//return [
//    'singletons' => [
//        SystemMetricProvider::class => SystemMetricProvider::class,
//        PhpMetricProvider::class => PhpMetricProvider::class,
//        DockerMetricProvider::class => DockerMetricProvider::class,
//        DatabaseMetricProvider::class => DatabaseMetricProvider::class,
//        RedisMetricProvider::class => RedisMetricProvider::class,
//        ApplicationMetricProvider::class => ApplicationMetricProvider::class,
//
//        // Сервис с инъекцией всех провайдеров
//        SysInfoService::class => [
//            'class' => SysInfoService::class,
//            '__construct()' => [
//                Instance::of(SystemMetricProvider::class),
//                Instance::of(PhpMetricProvider::class),
//                Instance::of(DockerMetricProvider::class),
//                Instance::of(DatabaseMetricProvider::class),
//                Instance::of(RedisMetricProvider::class),
//                Instance::of(ApplicationMetricProvider::class),
//            ],
//        ],
//    ],
//];
