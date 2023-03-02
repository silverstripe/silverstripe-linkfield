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
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLInsert;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Versioned\Versioned;

/**
 * This migration task is provided without the promise that it will follow semver and without promising official support
 * and maintenance. We have, however, made our absolute best effort to check that it works. It is a development task,
 * and as such we expect you to test this locally before running it on any production environments
 *
 * @codeCoverageIgnore
 */
class LinkableMigrationTask extends BuildTask
{
    protected const TABLE_BASE = 'LinkableLink';
    protected const TABLE_LIVE = 'LinkableLink_Live';
    protected const TABLE_VERSIONS = 'LinkableLink_Versions';

    protected const TABLE_MAP_LINK = [
        self::TABLE_BASE => '%s',
        self::TABLE_LIVE => '%s_Live',
        self::TABLE_VERSIONS => '%s_Versions',
    ];

    protected const TABLE_MAP_EMAIL_LINK = [
        self::TABLE_BASE => '%s',
        self::TABLE_LIVE => '%s_Live',
        self::TABLE_VERSIONS => '%s_Versions',
    ];

    protected const TABLE_MAP_EXTERNAL_LINK = [
        self::TABLE_BASE => '%s',
        self::TABLE_LIVE => '%s_Live',
        self::TABLE_VERSIONS => '%s_Versions',
    ];

    protected const TABLE_MAP_FILE_LINK = [
        self::TABLE_BASE => '%s',
        self::TABLE_LIVE => '%s_Live',
        self::TABLE_VERSIONS => '%s_Versions',
    ];

    protected const TABLE_MAP_PHONE_LINK = [
        self::TABLE_BASE => '%s',
        self::TABLE_LIVE => '%s_Live',
        self::TABLE_VERSIONS => '%s_Versions',
    ];

    protected const TABLE_MAP_SITE_TREE_LINK = [
        self::TABLE_BASE => '%s',
        self::TABLE_LIVE => '%s_Live',
        self::TABLE_VERSIONS => '%s_Versions',
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
        // @see insertSiteTree() for the migration of the Anchor field
    ];

    /**
     * @see insertSiteTree() for the migration of the Anchor field
     */
    private static string $sitetree_anchor_from = 'Anchor';

    /**
     * @see insertSiteTree() for the migration of the Anchor field
     */
    private static string $sitetree_anchor_to = 'Anchor';

    /**
     * @see insertSiteTree() for the migration of the Anchor field
     */
    private static ?string $sitetree_query_params_from = 'Anchor';

    /**
     * @see insertSiteTree() for the migration of the Anchor field
     */
    private static ?string $sitetree_query_params_to = null;

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
        $newTable = sprintf(self::TABLE_MAP_LINK[$originTable], DataObject::getSchema()->tableName(Link::class));

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

        $newTable = sprintf(
            self::TABLE_MAP_EMAIL_LINK[$originTable],
            DataObject::getSchema()->tableName(EmailLink::class)
        );

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

        $newTable = sprintf(
            self::TABLE_MAP_EXTERNAL_LINK[$originTable],
            DataObject::getSchema()->tableName(ExternalLink::class)
        );

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

        $newTable = sprintf(
            self::TABLE_MAP_FILE_LINK[$originTable],
            DataObject::getSchema()->tableName(FileLink::class)
        );

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

        $newTable = sprintf(
            self::TABLE_MAP_PHONE_LINK[$originTable],
            DataObject::getSchema()->tableName(PhoneLink::class)
        );

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

        $newTable = sprintf(
            self::TABLE_MAP_SITE_TREE_LINK[$originTable],
            DataObject::getSchema()->tableName(SiteTreeLink::class)
        );

        $assignments = $this->getAssignmentsForMapping(
            $this->config()->get('sitetree_mapping'),
            $linkableData,
            $originTable
        );

        // Special case for the Anchor field. Linkable supports query params and/or anchors, but the Linkfield module
        // only supports anchors. Linkable also requires that you prepend the #, and Linkfield requires you to *not*
        $anchorFrom = $this->config()->get('sitetree_anchor_from');
        $anchorTo = $this->config()->get('sitetree_anchor_to');

        $assignments[$anchorTo] = $this->getAnchorString($linkableData[$anchorFrom]);

        // Linkable supports adding query params and anchors together in the Anchor field. This module does not. If you
        // would like to add support for query params, then you will need to have created (probably through an
        // extension) a new and separate field (EG: QueryParams) on SiteTreeLink. You can then update the config for
        // $sitetree_query_params_to to the name of the field you created (EG: QueryParams)
        $queryParamsFrom = $this->config()->get('sitetree_query_params_from');
        $queryParamsTo = $this->config()->get('sitetree_query_params_to');

        if ($queryParamsFrom && $queryParamsTo) {
            $assignments[$queryParamsTo] = $this->getQueryString($linkableData[$queryParamsFrom]);
        }

        SQLInsert::create($newTable, $assignments)->execute();
    }

    protected function getAnchorString(?string $originalAnchor): ?string
    {
        if (!$originalAnchor) {
            return null;
        }

        // We know that Linkable requires users to include a hash (#) for any anchors that they want. If we don't find
        // a hash then there is no anchor here
        if (!str_contains($originalAnchor, '#')) {
            return null;
        }

        $firstChar = $originalAnchor[0] ?? null;

        // Linkable supported query params (?) and anchors (#) in the same Anchor field. We know that query params must
        // be provided before anchor, so if the first char is an anchor then we can just trim that and return;
        if ($firstChar === '#') {
            return ltrim($originalAnchor, '#');
        }

        // The only remaining possibility is that there is a string before the hash
        // Explode the string at the first #, and we would expect there to always be exactly 2 parts
        $parts = explode('#', $originalAnchor, 2);

        // return the second part
        return $parts[1];
    }

    /**
     * This method is not used (out of the box) as part of the migration process. This has been provided so that if
     * you have a need for it, you can extend this class and access it
     */
    protected function getQueryString(?string $originalAnchor): ?string
    {
        if (!$originalAnchor) {
            return null;
        }

        // We know that Linkable requires users to include a ? for any query params that they want. If we don't find
        // a ? then there are no query params here
        if (!str_contains($originalAnchor, '?')) {
            return null;
        }

        // Linkable supported query params (?) and anchors (#) in the same Anchor field. We know that query params must
        // be provided before anchor, so if there are no anchors in the string, then we can just trim the ? and return
        if (!str_contains($originalAnchor, '#')) {
            return ltrim($originalAnchor, '?');
        }

        // The only remaining possibility is that there are query params followed by an anchor
        // Explode the string at the first #, and we would expect there to always be exactly 2 parts
        $parts = explode('#', $originalAnchor, 2);

        // return the first part (the query params) after trimming the prepended ?
        return ltrim($parts[1], '?');
    }
}
