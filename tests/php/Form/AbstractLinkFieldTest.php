<?php

namespace SilverStripe\LinkField\Tests\Form;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\LinkField\Form\MultiLinkField;
use SilverStripe\LinkField\Tests\Form\AbstractLinkFieldTest\TestBlock;
use SilverStripe\LinkField\Tests\Controllers\LinkFieldControllerTest\TestPhoneLink;
use SilverStripe\Forms\Form;
use ReflectionObject;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Models\PhoneLink;
use SilverStripe\LinkField\Models\EmailLink;
use SilverStripe\LinkField\Tests\Models\LinkTest\LinkOwner;

class AbstractLinkFieldTest extends SapphireTest
{
    protected static $fixture_file = 'AbstractLinkFieldTest.yml';

    protected static $extra_dataobjects = [
        LinkOwner::class,
        TestBlock::class,
        TestPhoneLink::class,
    ];

    public function testElementalNamespaceRemoved(): void
    {
        $form = new Form();
        $field = new LinkField('PageElements_1_MyLink');
        $form->setFields(new FieldList([$field]));
        $block = $this->objFromFixture(TestBlock::class, 'TestBlock01');
        $form->loadDataFrom($block);
        $reflector = new ReflectionObject($field);
        $method = $reflector->getMethod('getOwnerFields');
        $res = $method->invoke($field);
        $this->assertEquals([
            'ID' => $block->ID,
            'Class' => TestBlock::class,
            'Relation' => 'MyLink',
        ], $res);
    }

    public function testAllowedLinks(): void
    {
        // Ensure only default link subclasses are included this test
        foreach (ClassInfo::subclassesFor(Link::class) as $className) {
            if (strpos($className, 'SilverStripe\\LinkField\\Models\\') !== 0) {
                Config::modify()->set($className, 'allowed_by_default', false);
            }
        }
        // Test default allowed types
        $field = new LinkField('MyLink');
        $keys = $this->getKeysForAllowedTypes($field);
        $this->assertSame(['email', 'external', 'file', 'phone', 'sitetree'], $keys);
        // Test can disallow globally
        Config::modify()->set(PhoneLink::class, 'allowed_by_default', false);
        $keys = $this->getKeysForAllowedTypes($field);
        $this->assertSame(['email', 'external', 'file', 'sitetree'], $keys);
        // Test can override with setAllowedTypes()
        $field->setAllowedTypes([EmailLink::class, PhoneLink::class]);
        $keys = $this->getKeysForAllowedTypes($field);
        $this->assertSame(['email', 'phone'], $keys);
    }

    private function getKeysForAllowedTypes(LinkField $field): array
    {
        $rawJson = $field->getTypesProp();
        $types = json_decode($rawJson, true);
        $allowedTypes = array_filter($types, fn($type) => $type['allowed']);
        $keys = array_column($allowedTypes, 'key');
        sort($keys);
        return $keys;
    }

    public function testSaveIntoAssignsMissingRelations(): void
    {
        // Test has_one links via LinkField
        $owner = new LinkOwner();
        $link = new Link(['OwnerClass' => LinkOwner::class]);
        $link->write();

        $linkField = new LinkField('Link');
        $linkField->setSubmittedValue($link->ID);
        $linkField->saveInto($owner);
        $owner->write();

        // Check the main relation to the link is stored
        $this->assertEquals($owner->LinkID, $link->ID);

        // Check the reverse relation from the link to the "owner" object is stored
        // Re-fetch link as only the ID is passed to the form field, so $link object instance will be out of date
        $link = Link::get()->byID($link->ID);
        $this->assertEquals($link->Owner()?->ID, $owner->ID);

        // Test has_many links via MultiLinkField
        $owner = new LinkOwner();
        $linkList1 = new Link(['OwnerClass' => LinkOwner::class]);
        $linkList1->write();
        $linkList2 = new Link(['OwnerClass' => LinkOwner::class]);
        $linkList2->write();

        $multiLinkField = new MultiLinkField('LinkList');
        // POST-ed value for MultiLinkField is a string in the format [1,2,3]
        $multiLinkField->setSubmittedValue('[' . implode(',', [$linkList1->ID, $linkList2->ID]) . ']');
        $multiLinkField->saveInto($owner);
        $owner->write();

        // Check the main relations to the links are stored
        $this->assertListContains([['ID' => $linkList1->ID], ['ID' => $linkList2->ID]], $owner->LinkList());

        // Check the reverse relation from the links to the "owner" object are stored
        foreach ([$linkList1, $linkList2] as $link) {
            // Re-fetch link as only the ID is passed to the form field, so $link object instance will be out of date
            $link = Link::get()->byID($link->ID);
            $this->assertEquals($link->Owner()?->ID, $owner->ID);
        }
    }
}
