<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Common\Config;

use Propel\Common\Config\ConfigurationManager;
use Propel\Common\Config\Exception\InvalidArgumentException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ConfigurationManagerTest extends ConfigTestCase
{
    use DataProviderTrait;

    /**
     * Current working directory
     */
    private $currentDir;

    /**
     * Directory in which to create temporary fixtures
     */
    private $fixturesDir;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->currentDir = getcwd();
        $this->fixturesDir = realpath(__DIR__ . '/../../../../Fixtures') . '/Configuration';

        $this->getFilesystem()->mkdir($this->fixturesDir);
        chdir($this->fixturesDir);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        chdir($this->currentDir);
        $this->getFileSystem()->remove($this->fixturesDir);
    }

    /**
     * @return void
     */
    public function testLoadConfigFileInCurrentDirectory()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new TestableConfigurationManager();
        $actual = $manager->get();

        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }

    /**
     * @return void
     */
    public function testLoadConfigFileInConfigSubdirectory()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->getFilesystem()->dumpFile('config/propel.yaml', $yamlConf);

        $manager = new TestableConfigurationManager();
        $actual = $manager->get();

        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }

    /**
     * @return void
     */
    public function testLoadConfigFileInConfSubdirectory()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->getFilesystem()->dumpFile('conf/propel.yaml', $yamlConf);

        $manager = new TestableConfigurationManager();
        $actual = $manager->get();

        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }

    /**
     * @return void
     */
    public function testNotExistingConfigFileLoadsDefaultSettingsAndDoesNotThrowExceptions()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->getFilesystem()->dumpFile('doctrine.yaml', $yamlConf);

        $manager = new TestableConfigurationManager();
        $this->assertInstanceOf(ConfigurationManager::class, $manager);
    }

    /**
     * @return void
     */
    public function testBackupConfigFilesAreIgnored()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml.bak', $yamlConf);
        $this->getFilesystem()->dumpFile('propel.yaml~', $yamlConf);

        $manager = new TestableConfigurationManager();
        $actual = $manager->get();

        $this->assertArrayNotHasKey('bar', $actual);
        $this->assertArrayNotHasKey('baz', $actual);
    }

    /**
     * @return void
     */
    public function testUnsupportedExtensionsAreIgnored()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->getFilesystem()->dumpFile('propel.log', $yamlConf);

        $manager = new TestableConfigurationManager();
        $actual = $manager->get();

        $this->assertArrayNotHasKey('bar', $actual);
        $this->assertArrayNotHasKey('baz', $actual);
    }

    /**
     * @return void
     */
    public function testMoreThanOneConfigurationFileInSameDirectoryThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Propel expects only one configuration file');

        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $iniConf = <<<EOF
foo = bar
bar = baz
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);
        $this->getFilesystem()->dumpFile('propel.ini', $iniConf);

        $manager = new TestableConfigurationManager();
    }

    /**
     * @return void
     */
    public function testMoreThanOneConfigurationFileInDifferentDirectoriesThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Propel expects only one configuration file');

        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $iniConf = <<<EOF
foo = bar
bar = baz
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);
        $this->getFilesystem()->dumpFile('conf/propel.ini', $iniConf);

        $manager = new TestableConfigurationManager();
    }

    /**
     * @return void
     */
    public function testGetSection()
    {
        $yamlConf = <<<EOF
runtime:
    foo: bar
    bar: baz
buildtime:
    bfoo: bbar
    bbar: bbaz
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new TestableConfigurationManager();
        $actual = $manager->getSection('buildtime');

        $this->assertEquals('bbar', $actual['bfoo']);
        $this->assertEquals('bbaz', $actual['bbar']);
    }

    /**
     * @return void
     */
    public function testLoadGivenConfigFile()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->getFilesystem()->dumpFile('myDir/mySubdir/myConfigFile.yaml', $yamlConf);

        $manager = new TestableConfigurationManager('myDir/mySubdir/myConfigFile.yaml');
        $actual = $manager->get();

        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $actual);
    }

    /**
     * @return void
     */
    public function testLoadAlsoDistConfigFile()
    {
        $yamlConf = <<<EOF
buildtime:
    bfoo: bbar
    bbar: bbaz
EOF;
        $yamlDistConf = <<<EOF
runtime:
    foo: bar
    bar: baz
EOF;

        $this->getFilesystem()->dumpFile('propel.yaml.dist', $yamlDistConf);
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new TestableConfigurationManager();
        $actual = $manager->get();

        $this->assertEquals(['bfoo' => 'bbar', 'bbar' => 'bbaz'], $actual['buildtime']);
        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $actual['runtime']);
    }

    /**
     * @return void
     */
    public function testLoadOnlyDistFile()
    {
        $yamlDistConf = <<<EOF
runtime:
    foo: bar
    bar: baz
EOF;

        $this->getFilesystem()->dumpFile('propel.yaml.dist', $yamlDistConf);

        $manager = new TestableConfigurationManager();
        $actual = $manager->get();

        $this->assertEquals(['runtime' => ['foo' => 'bar', 'bar' => 'baz']], $actual);
    }

    /**
     * @return void
     */
    public function testLoadGivenFileAndDist()
    {
        $yamlConf = <<<EOF
buildtime:
    bfoo: bbar
    bbar: bbaz
EOF;
        $yamlDistConf = <<<EOF
runtime:
    foo: bar
    bar: baz
EOF;
        $this->getFilesystem()->dumpFile('myDir/mySubdir/myConfigFile.yaml', $yamlConf);
        $this->getFilesystem()->dumpFile('myDir/mySubdir/myConfigFile.yaml.dist', $yamlDistConf);

        $manager = new TestableConfigurationManager('myDir/mySubdir/myConfigFile.yaml');
        $actual = $manager->get();

        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $actual['runtime']);
        $this->assertEquals(['bfoo' => 'bbar', 'bbar' => 'bbaz'], $actual['buildtime']);
    }

    /**
     * @return void
     */
    public function testLoadDistGivenFileOnly()
    {
        $yamlDistConf = <<<EOF
runtime:
    foo: bar
    bar: baz
EOF;
        $this->getFilesystem()->dumpFile('myDir/mySubdir/myConfigFile.yaml.dist', $yamlDistConf);

        $manager = new TestableConfigurationManager('myDir/mySubdir/myConfigFile.yaml.dist');
        $actual = $manager->get();

        $this->assertEquals(['runtime' => ['foo' => 'bar', 'bar' => 'baz']], $actual);
    }

    /**
     * @return void
     */
    public function testLoadInGivenDirectory()
    {
        $yamlConf = <<<EOF
buildtime:
    bfoo: bbar
    bbar: bbaz
EOF;
        $yamlDistConf = <<<EOF
runtime:
    foo: bar
    bar: baz
EOF;
        $this->getFilesystem()->dumpFile('myDir/mySubdir/propel.yaml', $yamlConf);
        $this->getFilesystem()->dumpFile('myDir/mySubdir/propel.yaml.dist', $yamlDistConf);

        $manager = new TestableConfigurationManager('myDir/mySubdir/');
        $actual = $manager->get();

        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $actual['runtime']);
        $this->assertEquals(['bfoo' => 'bbar', 'bbar' => 'bbaz'], $actual['buildtime']);
    }

    /**
     * @return void
     */
    public function testMergeExtraProperties()
    {
        $extraConf = [
            'buildtime' => [
                'bfoo' => 'extrabar',
            ],
            'extralevel' => [
                'extra1' => 'val1',
                'extra2' => 'val2',
            ],
        ];

        $yamlConf = <<<EOF
runtime:
    foo: bar
    bar: baz
buildtime:
    bfoo: bbar
    bbar: bbaz
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new TestableConfigurationManager(null, $extraConf);
        $actual = $manager->get();

        $this->assertEquals($actual['runtime'], ['foo' => 'bar', 'bar' => 'baz']);
        $this->assertEquals($actual['buildtime'], ['bfoo' => 'extrabar', 'bbar' => 'bbaz']);
        $this->assertEquals($actual['extralevel'], ['extra1' => 'val1', 'extra2' => 'val2']);
    }

    /**
     * @return void
     */
    public function testInvalidHierarchyTrowsException()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unrecognized options "foo, bar" under "propel"');

        $yamlConf = <<<EOF
runtime:
    foo: bar
    bar: baz
buildtime:
    bfoo: bbar
    bbar: bbaz
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        new ConfigurationManager();
    }

    /**
     * @return void
     */
    public function testNotDefineRuntimeAndGeneratorSectionUsesDefaultConnections()
    {
        $yamlConf = <<<EOF
propel:
  general:
      project: MyAwesomeProject
      version: 2.0.0-dev
  database:
    connections:
        default:
            adapter: sqlite
            classname: Propel\Runtime\Connection\ConnectionWrapper
            dsn: sqlite:memory
            user:
            password:
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager();

        $this->assertArrayHasKey('runtime', $manager->get());
        $this->assertArrayHasKey('generator', $manager->get());

        $this->assertArrayHasKey('connections', $manager->getSection('runtime'));
        $this->assertArrayHasKey('connections', $manager->getSection('generator'));

        $this->assertEquals(['default'], $manager->get()['runtime']['connections']);
        $this->assertEquals(['default'], $manager->get()['generator']['connections']);
    }

    /**
     * @return void
     */
    public function testNotDefineDatabaseSectionTrowsException()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The child node "database" at path "propel" must be configured');

        $yamlConf = <<<EOF
propel:
  general:
      project: MyAwesomeProject
      version: 2.0.0-dev
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        new ConfigurationManager();
    }

    /**
     * @return void
     */
    public function testDotInConnectionNamesArentAccepted()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Dots are not allowed in connection names');

        $yamlConf = <<<EOF
propel:
  database:
      connections:
          mysource.name:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
  runtime:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
  generator:
      defaultConnection: mysource
      connections:
          - mysource
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        new ConfigurationManager();
    }

    /**
     * @dataProvider providerForInvalidConnections
     *
     * @return void
     */
    public function testRuntimeOrGeneratorConnectionIsNotInConfiguredConnectionsThrowsException($yamlConf, $section)
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);
        new ConfigurationManager();
    }

    /**
     * @dataProvider providerForInvalidDefaultConnection
     *
     * @return void
     */
    public function testRuntimeOrGeneratorDefaultConnectionIsNotInConfiguredConnectionsThrowsException($yamlConf, $section)
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);
        new ConfigurationManager();
    }

    /**
     * @return void
     */
    public function testLoadValidConfigurationFile()
    {
        $yamlConf = <<<EOF
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
              attributes:
          yoursource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=yourdb
              user: root
              password:
              attributes:
  runtime:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
  generator:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager();
        $actual = $manager->getSection('runtime');

        $this->assertEquals($actual['defaultConnection'], 'mysource');
        $this->assertEquals($actual['connections'], ['mysource', 'yoursource']);
    }

    /**
     * @return void
     */
    public function testSomeDeafults()
    {
        $yamlConf = <<<EOF
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
              attributes:
          yoursource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=yourdb
              user: root
              password:
              attributes:
  runtime:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
  generator:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager();
        $actual = $manager->get();

        $this->assertTrue($actual['generator']['namespaceAutoPackage']);
        $this->assertEquals($actual['generator']['dateTime']['dateTimeClass'], 'DateTime');
        $this->assertFalse($actual['generator']['schema']['autoPackage']);
        $this->assertEquals($actual['generator']['objectModel']['pluralizerClass'], '\Propel\Common\Pluralizer\StandardEnglishPluralizer');
        $this->assertEquals($actual['generator']['objectModel']['builders']['objectstub'], '\Propel\Generator\Builder\Om\ExtensionObjectBuilder');
    }

    /**
     * @return void
     */
    public function testGetConfigProperty()
    {
        $yamlConf = <<<EOF
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
              attributes:
          yoursource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=yourdb
              user: root
              password:
              attributes:
  runtime:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
  generator:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager();
        $this->assertEquals('mysource', $manager->getConfigProperty('runtime.defaultConnection'));
        $this->assertEquals('yoursource', $manager->getConfigProperty('runtime.connections.1'));
        $this->assertEquals('root', $manager->getConfigProperty('database.connections.mysource.user'));
    }

    /**
     * @return void
     */
    public function testGetConfigPropertyBadNameThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configuration property name');

        $yamlConf = <<<EOF
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
              attributes:
          yoursource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=yourdb
              user: root
              password:
              attributes:
  runtime:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
  generator:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager();
        $manager->getConfigProperty(10);
    }

    /**
     * @return void
     */
    public function testGetConfigPropertyBadName()
    {
        $yamlConf = <<<EOF
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
              attributes:
          yoursource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=yourdb
              user: root
              password:
              attributes:
  runtime:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
  generator:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager();
        $value = $manager->getConfigProperty('database.connections.adapter');

        $this->assertNull($value);
    }

    /**
     * @return void
     */
    public function testProcessWithParam()
    {
        $configs = [
            'propel' => [
                'database' => [
                    'connections' => [
                        'default' => [
                            'adapter' => 'sqlite',
                            'classname' => 'Propel\Runtime\Connection\DebugPDO',
                            'dsn' => 'sqlite::memory:',
                            'user' => '',
                            'password' => '',
                            'model_paths' => [
                                'src',
                                'vendor',
                            ],
                        ],
                    ],
                ],
                'runtime' => [
                    'defaultConnection' => 'default',
                    'connections' => ['default'],
                ],
                'generator' => [
                    'defaultConnection' => 'default',
                    'connections' => ['default'],
                ],
            ],
        ];

        $manager = new NotLoadingConfigurationManager($configs);
        $actual = $manager->GetSection('database')['connections'];

        $this->assertEquals($configs['propel']['database']['connections'], $actual);
    }

    /**
     * @return void
     */
    public function testProcessWrongParameter()
    {
        $manager = new NotLoadingConfigurationManager(null);

        $this->assertEmpty($manager->get());
    }

    /**
     * @return void
     */
    public function testGetConfigurationParametersArrayTest()
    {
        $yamlConf = <<<EOF
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
              model_paths:
                - src
          yoursource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=yourdb
              user: root
              password:
  runtime:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
  generator:
      defaultConnection: mysource
      connections:
          - mysource
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $expectedRuntime = [
            'mysource' => [
                'adapter' => 'mysql',
                'classname' => 'Propel\Runtime\Connection\DebugPDO',
                'dsn' => 'mysql:host=localhost;dbname=mydb',
                'user' => 'root',
                'password' => '',
                'model_paths' => [
                    'src',
                ],
            ],
            'yoursource' => [
                'adapter' => 'mysql',
                'classname' => 'Propel\Runtime\Connection\DebugPDO',
                'dsn' => 'mysql:host=localhost;dbname=yourdb',
                'user' => 'root',
                'password' => '',
                'model_paths' => [
                    'src',
                    'vendor',
                ],
            ],
        ];

        $expectedGenerator = [
            'mysource' => [
                'adapter' => 'mysql',
                'classname' => 'Propel\Runtime\Connection\DebugPDO',
                'dsn' => 'mysql:host=localhost;dbname=mydb',
                'user' => 'root',
                'password' => '',
                'model_paths' => [
                    'src',
                ],
            ],
        ];

        $manager = new ConfigurationManager();
        $this->assertEquals($expectedRuntime, $manager->getConnectionParametersArray('runtime'));
        $this->assertEquals($expectedRuntime, $manager->getConnectionParametersArray()); //default `runtime`
        $this->assertEquals($expectedGenerator, $manager->getConnectionParametersArray('generator'));
        $this->assertNull($manager->getConnectionParametersArray('bad_section'));
    }

    /**
     * @return void
     */
    public function testSetConnectionsIfNotDefined()
    {
        $yamlConf = <<<EOF
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
          yoursource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=yourdb
              user: root
              password:
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);
        $manager = new ConfigurationManager();

        $this->assertEquals('mysource', $manager->getSection('generator')['defaultConnection']);
        $this->assertEquals('mysource', $manager->getSection('runtime')['defaultConnection']);
        $this->assertEquals(['mysource', 'yoursource'], $manager->getSection('generator')['connections']);
        $this->assertEquals(['mysource', 'yoursource'], $manager->getSection('runtime')['connections']);
    }
}

class TestableConfigurationManager extends ConfigurationManager
{
    public function __construct($filename = 'propel', $extraConf = null)
    {
        $this->load($filename, $extraConf);
    }
}

class NotLoadingConfigurationManager extends ConfigurationManager
{
    public function __construct($configs = null)
    {
        $this->process($configs);
    }
}
