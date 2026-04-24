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
        return '1.0.0';
    }

    public static function getAuthor(): string
    {
        return 'Your Name';
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
        return 'fas fa-dice-five';
    }

    public static function getMenuRoute(): string
    {
        return 'data_generator_index';
    }
}
