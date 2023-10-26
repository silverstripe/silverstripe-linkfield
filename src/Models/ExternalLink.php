<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\Forms\FieldList;

/**
 * A link to an external URL.
 *
 * @property string $ExternalUrl
 */
class ExternalLink extends Link
{
    private static string $table_name = 'LinkField_ExternalLink';
    private static string $singular_name = 'External url';
    private static string $plural_name = 'External urls';

    private static array $db = [
        'ExternalUrl' => 'Varchar',
    ];

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();
        $fields
            ->dataFieldByName('ExternalUrl')
            ->setTitle(_t(__CLASS__ . '.ExternalUrl', 'External url'))
            ->setDescription(
                _t(
                    __CLASS__ . '.ExternalUrl_Description',
                    'Prepend protocol, EG: http:// or https://'
                )
            );
        return $fields;
    }

    public function generateLinkDescription(array $data): string
    {
        return isset($data['ExternalUrl']) ? $data['ExternalUrl'] : '';
    }

    public function getURL(): string
    {
        return $this->ExternalUrl ?? '';
    }

    public function fieldLabels($includerelations = true)
    {
        return array_merge(parent::fieldLabels($includerelations), [
            'ExternalUrl' => _t(__CLASS__ . '.ExternalUrl', 'External url'),
            'ExternalUrl_Description' => _t(
                __CLASS__ . '.ExternalUrl',
                'Prepend protocol, EG: http:// or https://'
            ),
        ]);
    }
}
