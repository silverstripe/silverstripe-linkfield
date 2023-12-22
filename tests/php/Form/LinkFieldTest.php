<?php

namespace SilverStripe\LinkField\Tests\Form;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\LinkField\Tests\Form\LinkFieldTest\TestBlock;
use SilverStripe\LinkField\Tests\Controllers\LinkFieldControllerTest\TestPhoneLink;
use SilverStripe\Forms\Form;
use ReflectionObject;

class LinkFieldTest extends SapphireTest
{
    protected static $fixture_file = 'LinkFieldTest.yml';

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
}
