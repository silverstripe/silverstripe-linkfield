<?php

namespace SilverStripe\LinkField\Tasks;

use Exception;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\BuildTask;
use SilverStripe\LinkField\Models\EmailLink;
use SilverStripe\LinkField\Models\ExternalLink;
use SilverStripe\LinkField\Models\FileLink;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Models\PhoneLink;
use SilverStripe\LinkField\Models\SiteTreeLink;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLInsert;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Versioned\Versioned;

/**
 * @codeCoverageIgnore
 */
class LinkableMigrationTask extends BuildTask
{
    protected const TABLE_BASE = 'LinkableLink';
    protected const TABLE_LIVE = 'LinkableLink_Live';
    protected const TABLE_VERSIONS = 'LinkableLink_Versions';

    protected const TABLE_MAP_LINK = [
        self::TABLE_BASE => 'LinkField_Link',
        self::TABLE_LIVE => 'LinkField_Link_Live',
        self::TABLE_VERSIONS => 'LinkField_Link_Versions',
    ];

    protected const TABLE_MAP_EMAIL_LINK = [
        self::TABLE_BASE => 'LinkField_EmailLink',
        self::TABLE_LIVE => 'LinkField_EmailLink_Live',
        self::TABLE_VERSIONS => 'LinkField_EmailLink_Versions',
    ];

    protected const TABLE_MAP_EXTERNAL_LINK = [
        self::TABLE_BASE => 'LinkField_ExternalLink',
        self::TABLE_LIVE => 'LinkField_ExternalLink_Live',
        self::TABLE_VERSIONS => 'LinkField_ExternalLink_Versions',
    ];

    protected const TABLE_MAP_FILE_LINK = [
        self::TABLE_BASE => 'LinkField_FileLink',
        self::TABLE_LIVE => 'LinkField_FileLink_Live',
        self::TABLE_VERSIONS => 'LinkField_FileLink_Versions',
    ];

    protected const TABLE_MAP_PHONE_LINK = [
        self::TABLE_BASE => 'LinkField_PhoneLink',
        self::TABLE_LIVE => 'LinkField_PhoneLink_Live',
        self::TABLE_VERSIONS => 'LinkField_PhoneLink_Versions',
    ];

    protected const TABLE_MAP_SITE_TREE_LINK = [
        self::TABLE_BASE => 'LinkField_SiteTreeLink',
        self::TABLE_LIVE => 'LinkField_SiteTreeLink_Live',
        self::TABLE_VERSIONS => 'LinkField_SiteTreeLink_Versions',
    ];

    private static array $versions_mapping_global = [
        'RecordID' => 'RecordID',
        'Version' => 'Version',
    ];

    private static array $versions_mapping_base_only = [
        'WasPublished' => 'WasPublished',
        'WasDeleted' => 'WasDeleted',
        'WasDraft' => 'WasDraft',
        'AuthorID' => 'AuthorID',
        'PublisherID' => 'PublisherID',
    ];

    /**
     * LinkableLink field => LinkField_Link field
     */
    private static array $link_mapping = [
        'ID' => 'ID',
        'LastEdited' => 'LastEdited',
        'Created' => 'Created',
        'Title' => 'Title',
        'OpenInNewWindow' => 'OpenInNew',
    ];

    /**
     * LinkableLink field => LinkField_EmailLink field
     */
    private static array $email_mapping = [
        'ID' => 'ID',
        'Email' => 'Email',
    ];

    /**
     * LinkableLink field => LinkField_ExternalLink field
     */
    private static array $external_mapping = [
        'ID' => 'ID',
        'URL' => 'ExternalUrl',
    ];

    /**
     * LinkableLink field => LinkField_FileLink field
     */
    private static array $file_mapping = [
        'ID' => 'ID',
        'FileID' => 'FileID',
    ];

    /**
     * LinkableLink field => LinkField_PhoneLink field
     */
    private static array $phone_mapping = [
        'ID' => 'ID',
        'Phone' => 'Phone',
    ];

    /**
     * LinkableLink field => LinkField_SiteTreeLink field
     */
    private static array $sitetree_mapping = [
        'ID' => 'ID',
        'SiteTreeID' => 'PageID',
        'Anchor' => 'Anchor',
    ];

    private static $segment = 'linkable-migration-task';

    protected $title = 'Linkable Migration Task';

    protected $description = 'Truncate LinkField records and migrate from Linkable records';

    /**
     * @param HTTPRequest $request
     * @return void
     * @throws Exception
     */
    public function run($request): void
    {
        // Check that we have matching Versioned states between Linkable and LinkField
        if (!$this->versionedStatusMatches()) {
            throw new Exception(
                'Linkable and LinkField do not have matching Versioned applications. Make sure that both are'
                . ' either un-Versioned or Versioned'
            );
        }

        // If we're un-Versioned then it's just going to be the base table
        $tables = [
            self::TABLE_BASE,
        ];

        // Since we passed the versionedStatusMatches() step, then we can just check if Link is Versioned, and we can
        // safely assume that the Linkable Versioned tables also exist
        if (Link::singleton()->hasExtension(Versioned::class)) {
            // Add the _Live and _Versions tables to the list of things we need to copy
            $tables[] = self::TABLE_LIVE;
            $tables[] = self::TABLE_VERSIONS;
        }

        // We expect your LinkField tables to be completely clear before migration is kicked off
        $this->truncateLinkFieldTables();

        foreach ($tables as $table) {
            // Grab any/all records from the desired table (base, live, versions)
            $linkableResults = SQLSelect::create('*', $table)->execute();

            // Nothing to see here
            if ($linkableResults->numRecords() === 0) {
                echo sprintf("Nothing to process for `%s`\r\n", $table);

                continue;
            }

            echo sprintf("Processing `%s`\r\n", $table);

            // Loop through each DB record
            foreach ($linkableResults as $linkableData) {
                // We now need to determine what type of Link the original Linkable record was, because we're going to
                // have to process each of those slightly differently
                switch ($linkableData['Type']) {
                    case 'Email':
                        $this->insertEmail($linkableData, $table);

                        break;
                    case 'URL':
                        $this->insertExternal($linkableData, $table);

                        break;
                    case 'File':
                        $this->insertFile($linkableData, $table);

                        break;
                    case 'Phone':
                        $this->insertPhone($linkableData, $table);

                        break;
                    case 'SiteTree':
                        $this->insertSiteTree($linkableData, $table);

                        break;
                }
            }

            echo sprintf("%d records inserted, finished processing `%s`\r\n", $linkableResults->numRecords(), $table);
        }
    }

    /**
     * Check to see if there is the existence of a _Live table for Linkable (indicating that it was Versioned)
     * @return bool
     */
    protected function versionedStatusMatches(): bool
    {
        $wasVersioned = DB::query('SHOW TABLES LIKE \'LinkableLink_Live\';')->numRecords() > 0;
        $isVersioned = Link::singleton()->hasExtension(Versioned::class);

        return $wasVersioned === $isVersioned;
    }

    /**
     * We expect your LinkField tables to be completely clear before migration is kicked off
     * This method will delete all data in the new tables providing a clear start and the ability
     * to repeat this dev task
     *
     * @return void
     */
    protected function truncateLinkFieldTables(): void
    {
        $tables = [
            'LinkField_Link',
            'LinkField_EmailLink',
            'LinkField_ExternalLink',
            'LinkField_FileLink',
            'LinkField_PhoneLink',
            'LinkField_SiteTreeLink',
        ];
        $versioned = [
            '_Live',
            '_Versions',
        ];
        $isVersioned = Link::singleton()->hasExtension(Versioned::class);

        foreach ($tables as $table) {
            DB::get_conn()->clearTable($table);

            if (!$isVersioned) {
                continue;
            }

            foreach ($versioned as $tableSuffix) {
                DB::get_conn()->clearTable($table . $tableSuffix);
            }
        }
    }

    /**
     * Create assignments from the old field values to the new fields based on provided configuration
     *
     * @param array $config
     * @param array $linkableData
     * @param string $originTable
     * @return array
     */
    protected function getAssignmentsForMapping(array $config, array $linkableData, string $originTable): array
    {
        // If we're processing the _Versions table, then we need to add all the Version table field assignments
        if ($originTable === self::TABLE_VERSIONS) {
            $config += $this->config()->get('versions_mapping_global');
        }

        // We're now going to start assigning values to the new fields (as you've specified in your config)
        $assignments = [];

        // Loop through each config
        foreach ($config as $oldField => $newField) {
            // Assign the new field to equal whatever value was in the original record (based on the old field name)
            $assignments[$newField] = $linkableData[$oldField];
        }

        return $assignments;
    }

    /**
     * Create new generic link record based on provided data
     *
     * @param string $className
     * @param array $linkableData
     * @param string $originTable
     * @return void
     */
    protected function insertLink(string $className, array $linkableData, string $originTable): void
    {
        $config = $this->config()->get('link_mapping');

        // If we're processing the _Versions table, then we need to add all the Version table field assignments that are
        // specifically for the base record (such as all the "WasPublished", "WasDraft", etc fields)
        if ($originTable === self::TABLE_VERSIONS) {
            $config += $this->config()->get('versions_mapping_global');
        }

        // These assignments are based on our config
        $assignments = $this->getAssignmentsForMapping(
            $config,
            $linkableData,
            $originTable
        );
        // We also need to add ClassName for the base table, and this is not configurable
        $assignments['ClassName'] = $className;

        // Find out what the corresponding table is for the origin table
        $newTable = self::TABLE_MAP_LINK[$originTable];

        // Insert our new record
        SQLInsert::create($newTable, $assignments)->execute();
    }

    /**
     * Insert new record for email type link
     *
     * @param array $linkableData
     * @param string $originTable
     * @return void
     */
    protected function insertEmail(array $linkableData, string $originTable): void
    {
        // Insert the base record for this EmailLink
        $this->insertLink(EmailLink::class, $linkableData, $originTable);

        $newTable = self::TABLE_MAP_EMAIL_LINK[$originTable];

        $assignments = $this->getAssignmentsForMapping(
            $this->config()->get('email_mapping'),
            $linkableData,
            $originTable
        );

        SQLInsert::create($newTable, $assignments)->execute();
    }

    /**
     * Insert new record for external type link
     *
     * @param array $linkableData
     * @param string $originTable
     * @return void
     */
    protected function insertExternal(array $linkableData, string $originTable): void
    {
        // Insert the base record for this ExternalLink
        $this->insertLink(ExternalLink::class, $linkableData, $originTable);

        $newTable = self::TABLE_MAP_EXTERNAL_LINK[$originTable];

        $assignments = $this->getAssignmentsForMapping(
            $this->config()->get('external_mapping'),
            $linkableData,
            $originTable
        );

        SQLInsert::create($newTable, $assignments)->execute();
    }

    /**
     * Insert new record for file type link
     *
     * @param array $linkableData
     * @param string $originTable
     * @return void
     */
    protected function insertFile(array $linkableData, string $originTable): void
    {
        // Insert the base record for this FileLink
        $this->insertLink(FileLink::class, $linkableData, $originTable);

        $newTable = self::TABLE_MAP_FILE_LINK[$originTable];

        $assignments = $this->getAssignmentsForMapping(
            $this->config()->get('file_mapping'),
            $linkableData,
            $originTable
        );

        SQLInsert::create($newTable, $assignments)->execute();
    }

    /**
     * Insert new record for phone type link
     *
     * @param array $linkableData
     * @param string $originTable
     * @return void
     */
    protected function insertPhone(array $linkableData, string $originTable): void
    {
        // Insert the base record for this PhoneLink
        $this->insertLink(PhoneLink::class, $linkableData, $originTable);

        $newTable = self::TABLE_MAP_PHONE_LINK[$originTable];

        $assignments = $this->getAssignmentsForMapping(
            $this->config()->get('phone_mapping'),
            $linkableData,
            $originTable
        );

        SQLInsert::create($newTable, $assignments)->execute();
    }

    /**
     * Insert new record for site tree (internal) type link
     *
     * @param array $linkableData
     * @param string $originTable
     * @return void
     */
    protected function insertSiteTree(array $linkableData, string $originTable): void
    {
        // Insert the base record for this SiteTreeLink
        $this->insertLink(SiteTreeLink::class, $linkableData, $originTable);

        $newTable = self::TABLE_MAP_SITE_TREE_LINK[$originTable];

        $assignments = $this->getAssignmentsForMapping(
            $this->config()->get('sitetree_mapping'),
            $linkableData,
            $originTable
        );

        SQLInsert::create($newTable, $assignments)->execute();
    }
}
