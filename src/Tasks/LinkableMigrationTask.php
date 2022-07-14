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

    private const TABLE_BASE = 'LinkableLink';
    private const TABLE_LIVE = 'LinkableLink_Live';
    private const TABLE_VERSIONS = 'LinkableLink_Versions';

    private const TABLE_MAP_LINK = [
        self::TABLE_BASE => 'LinkField_Link',
        self::TABLE_LIVE => 'LinkField_Link_Live',
        self::TABLE_VERSIONS => 'LinkField_Link_Versions',
    ];

    private const TABLE_MAP_EMAIL_LINK = [
        self::TABLE_BASE => 'LinkField_EmailLink',
        self::TABLE_LIVE => 'LinkField_EmailLink_Live',
        self::TABLE_VERSIONS => 'LinkField_EmailLink_Versions',
    ];

    private const TABLE_MAP_EXTERNAL_LINK = [
        self::TABLE_BASE => 'LinkField_ExternalLink',
        self::TABLE_LIVE => 'LinkField_ExternalLink_Live',
        self::TABLE_VERSIONS => 'LinkField_ExternalLink_Versions',
    ];

    private const TABLE_MAP_FILE_LINK = [
        self::TABLE_BASE => 'LinkField_FileLink',
        self::TABLE_LIVE => 'LinkField_FileLink_Live',
        self::TABLE_VERSIONS => 'LinkField_FileLink_Versions',
    ];

    private const TABLE_MAP_PHONE_LINK = [
        self::TABLE_BASE => 'LinkField_PhoneLink',
        self::TABLE_LIVE => 'LinkField_PhoneLink_Live',
        self::TABLE_VERSIONS => 'LinkField_PhoneLink_Versions',
    ];

    private const TABLE_MAP_SITE_TREE_LINK = [
        self::TABLE_BASE => 'LinkField_SiteTreeLink',
        self::TABLE_LIVE => 'LinkField_SiteTreeLink_Live',
        self::TABLE_VERSIONS => 'LinkField_SiteTreeLink_Versions',
    ];

    private static $versions_mapping_global = [
        'RecordID' => 'RecordID',
        'Version' => 'Version',
    ];

    private static $versions_mapping_base_only = [
        'WasPublished' => 'WasPublished',
        'WasDeleted' => 'WasDeleted',
        'WasDraft' => 'WasDraft',
        'AuthorID' => 'AuthorID',
        'PublisherID' => 'PublisherID',
    ];

    /**
     * LinkableLink field => LinkField_Link field
     */
    private static $link_mapping = [
        'ID' => 'ID',
        'LastEdited' => 'LastEdited',
        'Created' => 'Created',
        'Title' => 'Title',
        'OpenInNewWindow' => 'OpenInNew',
    ];

    /**
     * LinkableLink field => LinkField_EmailLink field
     */
    private static $email_mapping = [
        'ID' => 'ID',
        'Email' => 'Email',
    ];

    /**
     * LinkableLink field => LinkField_ExternalLink field
     */
    private static $external_mapping = [
        'ID' => 'ID',
        'URL' => 'ExternalUrl',
    ];

    /**
     * LinkableLink field => LinkField_FileLink field
     */
    private static $file_mapping = [
        'ID' => 'ID',
        'FileID' => 'FileID',
    ];

    /**
     * LinkableLink field => LinkField_PhoneLink field
     */
    private static $phone_mapping = [
        'ID' => 'ID',
        'Phone' => 'Phone',
    ];

    /**
     * LinkableLink field => LinkField_SiteTreeLink field
     */
    private static $sitetree_mapping = [
        'ID' => 'ID',
        'SiteTreeID' => 'PageID',
        'Anchor' => 'Anchor',
    ];

    /**
     * @var string
     */
    private static $segment = 'linkable-migration-task';

    /**
     * @var string
     */
    protected $title = 'Linkable Migration Task';

    /**
     * @var string
     */
    protected $description = 'Truncate LinkField records and migrate from Linkable records';

    /**
     * @param HTTPRequest $request
     */
    public function run($request)
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
            foreach ($linkableResults as $linkableArray) {
                // We now need to determine what type of Link the original Linkable record was, because we're going to
                // have to process each of those slightly differently
                switch ($linkableArray['Type']) {
                    case 'Email':
                        $this->insertEmail($linkableArray, $table);

                        break;
                    case 'URL':
                        $this->insertExternal($linkableArray, $table);

                        break;
                    case 'File':
                        $this->insertFile($linkableArray, $table);

                        break;
                    case 'Phone':
                        $this->insertPhone($linkableArray, $table);

                        break;
                    case 'SiteTree':
                        $this->insertSiteTree($linkableArray, $table);

                        break;
                }
            }

            echo sprintf("Finished processing `%s`\r\n", $table);
        }
    }

    private function versionedStatusMatches(): bool
    {
        // Check to see if there is the existence of a _Live table for Linkable (indicating that it was Versioned)
        $wasVersioned = DB::query('SHOW TABLES LIKE \'LinkableLink_Live\';')->numRecords() > 0;
        $isVersioned = Link::singleton()->hasExtension(Versioned::class);

        return $wasVersioned === $isVersioned;
    }

    private function truncateLinkFieldTables(): void
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
            DB::query(sprintf('TRUNCATE TABLE %s', $table));

            if (!$isVersioned) {
                continue;
            }

            foreach ($versioned as $append) {
                DB::query(sprintf('TRUNCATE TABLE %s%s', $table, $append));
            }
        }
    }

    private function getAssignmentsForMapping(array $config, array $linkableArray, string $originTable): array
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
            $assignments[$newField] = $linkableArray[$oldField];
        }

        return $assignments;
    }

    private function insertLink(string $className, array $linkableArray, string $originTable): void
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
            $linkableArray,
            $originTable
        );
        // We also need to add ClassName for the base table, and this is not configurable
        $assignments['ClassName'] = $className;

        // Find out what the corresponding table is for the origin table
        $newTable = self::TABLE_MAP_LINK[$originTable];

        // Insert our new record
        SQLInsert::create($newTable, $assignments)->execute();
    }

    private function insertEmail(array $linkableArray, string $originTable): void
    {
        // Insert the base record for this EmailLink
        $this->insertLink(EmailLink::class, $linkableArray, $originTable);

        $newTable = self::TABLE_MAP_EMAIL_LINK[$originTable];

        $assignments = $this->getAssignmentsForMapping(
            $this->config()->get('email_mapping'),
            $linkableArray,
            $originTable
        );

        SQLInsert::create($newTable, $assignments)->execute();
    }

    private function insertExternal(array $linkableArray, string $originTable): void
    {
        // Insert the base record for this ExternalLink
        $this->insertLink(ExternalLink::class, $linkableArray, $originTable);

        $newTable = self::TABLE_MAP_EXTERNAL_LINK[$originTable];

        $assignments = $this->getAssignmentsForMapping(
            $this->config()->get('external_mapping'),
            $linkableArray,
            $originTable
        );

        SQLInsert::create($newTable, $assignments)->execute();
    }

    private function insertFile(array $linkableArray, string $originTable): void
    {
        // Insert the base record for this FileLink
        $this->insertLink(FileLink::class, $linkableArray, $originTable);

        $newTable = self::TABLE_MAP_FILE_LINK[$originTable];

        $assignments = $this->getAssignmentsForMapping(
            $this->config()->get('file_mapping'),
            $linkableArray,
            $originTable
        );

        SQLInsert::create($newTable, $assignments)->execute();
    }

    private function insertPhone(array $linkableArray, string $originTable): void
    {
        // Insert the base record for this PhoneLink
        $this->insertLink(PhoneLink::class, $linkableArray, $originTable);

        $newTable = self::TABLE_MAP_PHONE_LINK[$originTable];

        $assignments = $this->getAssignmentsForMapping(
            $this->config()->get('phone_mapping'),
            $linkableArray,
            $originTable
        );

        SQLInsert::create($newTable, $assignments)->execute();
    }

    private function insertSiteTree(array $linkableArray, string $originTable): void
    {
        // Insert the base record for this SiteTreeLink
        $this->insertLink(SiteTreeLink::class, $linkableArray, $originTable);

        $newTable = self::TABLE_MAP_SITE_TREE_LINK[$originTable];

        $assignments = $this->getAssignmentsForMapping(
            $this->config()->get('sitetree_mapping'),
            $linkableArray,
            $originTable
        );

        SQLInsert::create($newTable, $assignments)->execute();
    }
}
