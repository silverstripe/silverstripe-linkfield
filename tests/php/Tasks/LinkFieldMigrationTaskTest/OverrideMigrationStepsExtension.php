<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Tests\Tasks\LinkFieldMigrationTaskTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;
use SilverStripe\LinkField\Models\Link;

class OverrideMigrationStepsExtension extends Extension implements TestOnly
{
    public static ?bool $shouldPublishLink = null;
    public static ?bool $shouldPublishLinks = null;
    public static ?bool $needsMigration = null;
    public static array $shouldMigrateColumn = [];
    public static array $shouldDropColumn = [];

    protected function updateShouldPublishLink(Link $link, bool &$shouldPublishLink): void
    {
        if (self::$shouldPublishLink !== null) {
            $shouldPublishLink = self::$shouldPublishLink;
        }
    }

    protected function updateShouldPublishLinks(bool &$shouldPublishLinks): void
    {
        if (self::$shouldPublishLinks !== null) {
            $shouldPublishLinks = self::$shouldPublishLinks;
        }
    }

    protected function updateNeedsMigration(bool &$needsMigration): void
    {
        if (self::$needsMigration !== null) {
            $needsMigration = self::$needsMigration;
        }
    }

    protected function updateShouldMigrateColumn(string $table, string $column, bool &$shouldMigrateColumn): void
    {
        if (isset(self::$shouldMigrateColumn[$table])) {
            $shouldMigrateColumn = self::$shouldMigrateColumn[$table];
        }
    }

    protected function updateShouldDropColumn(string $table, string $column, bool &$shouldDropColumn): void
    {
        if (isset(self::$shouldDropColumn[$table])) {
            $shouldDropColumn = self::$shouldDropColumn[$table];
        }
    }
}
