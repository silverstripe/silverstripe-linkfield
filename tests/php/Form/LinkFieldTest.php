<?php

namespace SilverStripe\LinkField\Tests\Form;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Tests\Models\LinkTest\LinkOwner;

class LinkFieldTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        LinkOwner::class,
    ];

    /**
     * When we save a link into the has_one of a record, we also need to save
     * the Owner has_one on the link itself.
     */
    public function testSaveInto()
    {
        // Prepare fixtures (need new records for this)
        $field = new LinkField('Link');
        $link = new Link();
        $link->write();
        $owner = new LinkOwner();
        $owner->write();

        // Save link into owner
        $field->setValue($link->ID);
        $field->saveInto($owner);
        // Get the link again - the new values are in the DB.
        $link = Link::get()->byID($link->ID);

        // Validate
        $this->assertSame($link->ID, $owner->LinkID);
        $this->assertSame($owner->ID, $link->OwnerID);
        $this->assertSame($owner->ClassName, $link->OwnerClass);
        $this->assertSame('Link', $link->OwnerRelation);
    }
}
