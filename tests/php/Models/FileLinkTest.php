<?php

namespace SilverStripe\LinkField\Tests\Models;

use ReflectionMethod;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\LinkField\Models\FileLink;
use SilverStripe\LinkField\Tests\Models\FileLinkTest\TestFileCanView;
use SilverStripe\LinkField\Tests\Models\FileLinkTest\TestFileCannotView;
use SilverStripe\Assets\File;

class FileLinkTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        TestFileCanView::class,
        TestFileCannotView::class,
    ];

    public function testGetDescription(): void
    {
        // FileLink without a page
        $link = new FileLink();
        $this->assertSame('File does not exist', $link->getDescription());
        // FileLink with a page though cannot view the page
        $file = new TestFileCannotView(['Name' => 'not-allowed']);
        $file->setFromLocalFile(realpath(__DIR__ .'/FileLinkTest/file-a.png'), 'file-a.png');
        $file->write();
        $link->File = $file->ID;
        $link->write();
        $this->assertSame('Cannot view file', $link->getDescription());
        // FileLink with a page that and can view the page
        $file = new TestFileCanView(['Name' => 'allowed']);
        $file->setFromLocalFile(realpath(__DIR__ .'/FileLinkTest/file-b.png'), 'file-b.png');
        $file->write();
        $link->File = $file->ID;
        $link->write();
        $this->assertSame('file-b.png', $link->getDescription());
    }

    public function testGetDefaultTitle(): void
    {
        $reflectionGetDefaultTitle = new ReflectionMethod(FileLink::class, 'getDefaultTitle');
        $reflectionGetDefaultTitle->setAccessible(true);

        // File does not exist
        $link = new FileLink();
        $this->assertSame('(File missing)', $reflectionGetDefaultTitle->invoke($link));
        // File exists in DB but not in filesystem
        // Note that a 'Title' field will be derived from the 'Name' field in File::onBeforeWrite()
        $file = new TestFileCanView(['Name' => 'My test file']);
        $file->write();
        $link->File = $file->ID;
        $link->write();
        $this->assertSame('(File missing)', $reflectionGetDefaultTitle->invoke($link));
        // File actually exists
        $file->setFromLocalFile(realpath(__DIR__ .'/FileLinkTest/file-b.png'), 'file-b.png');
        $file->write();
        $link->File = $file->ID;
        $link->write();
        $this->assertSame('My test file', $reflectionGetDefaultTitle->invoke($link));
    }
}
