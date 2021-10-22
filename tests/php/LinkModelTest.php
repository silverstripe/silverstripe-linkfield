<?php

namespace SilverStripe\Link\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Link\Models\Link;

class LinkModelTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = 'LinkModelTest.yml';

    public function testLinkModel(): void
    {
        $model = $this->objFromFixture(Link::class, 'link-1');

        $this->assertEquals('FormBuilderModal', $model->LinkTypeHandlerName());
    }
}
