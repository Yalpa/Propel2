<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Common\Config\Loader;

use Propel\Common\Config\FileLocator;
use Propel\Common\Config\Loader\XmlFileLoader;
use Propel\Tests\Common\Config\ConfigTestCase;

class XmlFileLoaderTest extends ConfigTestCase
{
    protected $loader;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->loader = new XmlFileLoader(new FileLocator(sys_get_temp_dir()));
    }

    /**
     * @return void
     */
    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.xml'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.xml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.yml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar.dist'), '->supports() returns true if the resource is loadable');
    }

    /**
     * @return void
     */
    public function testXmlFileCanBeLoaded()
    {
        $content = <<< XML
<?xml version='1.0' standalone='yes'?>
<properties>
  <foo>bar</foo>
  <bar>baz</bar>
</properties>
XML;
        $this->dumpTempFile('parameters.xml', $content);

        $test = $this->loader->load('parameters.xml');
        $this->assertEquals('bar', $test['foo']);
        $this->assertEquals('baz', $test['bar']);
    }

    /**
     * @return void
     */
    public function testXmlFileDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The file "inexistent.xml" does not exist (in:');

        $this->loader->load('inexistent.xml');
    }

    /**
     * @return void
     */
    public function testXmlFileHasInvalidContent()
    {
        $this->expectException(\Propel\Common\Config\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid xml content");

        $content = <<<EOF
not xml content
only plain
text
EOF;
        $this->dumpTempFile('nonvalid.xml', $content);

        @$this->loader->load('nonvalid.xml');
    }

    /**
     * @return void
     */
    public function testXmlFileIsEmpty()
    {
        $content = '';
        $this->dumpTempFile('empty.xml', $content);

        $actual = $this->loader->load('empty.xml');

        $this->assertEquals([], $actual);
    }

    /**
     * @requires OS ^(?!Win.*)
     *
     * @return void
     */
    public function testXmlFileNotReadableThrowsException()
    {
        $this->expectException(\Propel\Common\Config\Exception\InputOutputException::class);
        $this->expectExceptionMessage("You don't have permissions to access configuration file notreadable.xml.");

        $content = <<< XML
<?xml version='1.0' standalone='yes'?>
<properties>
  <foo>bar</foo>
  <bar>baz</bar>
</properties>
XML;

        $this->dumpTempFile('notreadable.xml', $content);
        $this->getFilesystem()->chmod(sys_get_temp_dir() . '/notreadable.xml', 0200);

        $actual = $this->loader->load('notreadable.xml');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}
