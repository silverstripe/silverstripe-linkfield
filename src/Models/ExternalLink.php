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

    private static array $db = [
        'ExternalUrl' => 'Varchar',
    ];

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $linkField = $fields->dataFieldByName('ExternalUrl');
            $linkField->setTitle(_t('LinkField.EXTERNAL_URL_FIELD', 'External url'));
        });
        return parent::getCMSFields();
    }

    public function getDescription(): string
    {
        return $this->ExternalUrl ?: '';
    }

    public function getURL(): string
    {
        return $this->ExternalUrl ?: '';
    }
}
