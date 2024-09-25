<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Tasks;

use RuntimeException;
use SilverStripe\Dev\BuildTask;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Models\EmailLink;
use SilverStripe\LinkField\Models\ExternalLink;
use SilverStripe\LinkField\Models\FileLink;
use SilverStripe\LinkField\Models\PhoneLink;
use SilverStripe\LinkField\Models\SiteTreeLink;
use SilverStripe\ORM\DB;

/**
 * @deprecated 5.0.0 Will be removed without equivalent functionality.
 */
class GorriecoeMigrationTask extends BuildTask
{
    use MigrationTaskTrait;
    use ModuleMigrationTaskTrait;

    protected static string $commandName = 'gorriecoe-to-linkfield-migration-task';

    protected string $title = 'Gorriecoe to Linkfield Migration Task';

    protected static string $description = 'Migrate from gorriecoe/silverstripe-link to silverstripe/linkfield';

    /**
     * Enable via YAML configuration if you need to run this task
     */
    private static ?bool $is_enabled = false;

    /**
     * The number of links to process at once, for operations that operate on each link individually.
     * Processing links in chunks reduces the chance of hitting memory limits.
     * Set to null to process all links in a single chunk.
     */
    private static ?int $chunk_size = 1000;

    /**
     * The name of the table for the gorriecoe\Link\Models\Link model.
     *
     * Configurable since it's such a generic name, there's a chance people configured
     * it to something different to avoid collisions.
     */
    private static string $old_link_table = 'Link';

    /**
     * Mapping for columns in the base link table.
     * Doesn't include subclass columns - see link_type_columns
     */
    private static array $base_link_columns = [
        'OpenInNewWindow' => 'OpenInNew',
        'Title' => 'LinkText',
    ];

    /**
     * Mapping for different types of links, including the class to map to and
     * database column mappings.
     */
    private static array $link_type_columns = [
        'URL' => [
            'class' => ExternalLink::class,
            'fields' => [
                'URL' => 'ExternalUrl',
            ],
        ],
        'Email' => [
            'class' => EmailLink::class,
            'fields' => [
                'Email' => 'Email',
            ],
        ],
        'Phone' => [
            'class' => PhoneLink::class,
            'fields' => [
                'Phone' => 'Phone',
            ],
        ],
        'File' => [
            'class' => FileLink::class,
            'fields' => [
                'FileID' => 'FileID',
            ],
        ],
        'SiteTree' => [
            'class' => SiteTreeLink::class,
            'fields' => [
                'SiteTreeID' => 'PageID',
            ],
        ],
    ];

    /**
     * Perform the actual data migration and publish links as appropriate
     */
    public function performMigration(): void
    {
        $this->extend('beforePerformMigration');
        // Because we're using SQL INSERT with specific ID values,
        // we can't perform the migration if there are existing links because there
        // may be ID conflicts.
        if (Link::get()->exists()) {
            throw new RuntimeException('Cannot perform migration with existing silverstripe/linkfield link records.');
        }

        $this->insertBaseRows();
        $this->insertTypeSpecificRows();
        $this->updateSiteTreeRows();
        $this->migrateHasManyRelations();
        $this->migrateManyManyRelations();
        $this->setOwnerForHasOneLinks();

        $this->output->writeln("Dropping old link table '{$this->oldTableName}'");
        DB::get_conn()->query("DROP TABLE \"{$this->oldTableName}\"");

        $this->output->writeln('-----------------');
        $this->output->writeln('Bulk data migration complete. All links should be correct (but unpublished) at this stage.');
        $this->output->writeln('-----------------');

        $this->publishLinks();

        $this->output->writeln('-----------------');
        $this->output->writeln('Migration completed successfully.');
        $this->output->writeln('-----------------');
        $this->extend('afterPerformMigration');
    }
}
