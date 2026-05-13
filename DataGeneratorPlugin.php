<?php

namespace App\Plugins\DataGenerator;

use App\Shared\Plugin\Abstracts\AbstractAdminPlugin;

class DataGeneratorPlugin extends AbstractAdminPlugin
{
    public static function getName(): string
    {
        return 'DataGeneratorPlugin';
    }

    public static function getVersion(): string
    {
        return '2.0.0';
    }

    public static function getAuthor(): string
    {
        return 'Agantar';
    }

    public static function getDescription(): string
    {
        return 'A plugin to generate data for testing purposes.';
    }

    public static function getMenuLabel(): string
    {
        return 'Générateur de données';
    }

    public static function getMenuIcon(): string
    {
        return 'fas fa-square-plus';
    }

    public static function getMenuRoute(): string
    {
        return 'data_generator_index';
    }

    public static function getMenuSlug(): string
    {
        return 'data-generator';
    }
}
