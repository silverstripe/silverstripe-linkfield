<?php

namespace SilverStripe\LinkField\Tests\Form;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\LinkField\Tests\Form\AbstractLinkFieldTest\TestBlock;
use SilverStripe\LinkField\Tests\Controllers\LinkFieldControllerTest\TestPhoneLink;
use SilverStripe\Forms\Form;
use ReflectionObject;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Models\PhoneLink;
use SilverStripe\LinkField\Models\EmailLink;

class AbstractLinkFieldTest extends SapphireTest
{
    protected static $fixture_file = 'AbstractLinkFieldTest.yml';

    protected static $extra_dataobjects = [
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
        $method->setAccessible(true);
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
}
