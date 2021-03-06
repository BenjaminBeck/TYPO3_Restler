<?php
namespace Aoe\Restler\Tests\Unit\System\Restler;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Aoe\Restler\System\Restler\Builder;
use Aoe\Restler\Tests\Unit\BaseTest;
use Luracast\Restler\Defaults;
use Luracast\Restler\Restler;
use Luracast\Restler\Scope;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * @package Restler
 * @subpackage Tests
 *
 * @covers \Aoe\Restler\System\Restler\Builder
 */
class BuilderTest extends BaseTest
{
    /**
     * @var Builder
     */
    protected $builder;
    /**
     * original config of the restler-Extension
     * @var array
     */
    protected $originalRestlerConfigurationClasses;
    /**
     * original server-configuration ($_SERVER)
     * @var array
     */
    protected $originalServerVars;
    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    protected $extensionConfigurationMock;
    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * setup
     */
    protected function setUp()
    {
        parent::setUp();

        $this->originalRestlerConfigurationClasses = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['restler']['restlerConfigurationClasses'];
        $this->originalServerVars = $_SERVER;

        $this->extensionConfigurationMock = $this->getMockBuilder('Aoe\\Restler\\Configuration\\ExtensionConfiguration')
            ->disableOriginalConstructor()->getMock();
        $this->objectManagerMock = $this->getMockBuilder('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface')
            ->disableOriginalConstructor()->getMock();

        $this->builder = new Builder($this->extensionConfigurationMock, $this->objectManagerMock);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['restler']['restlerConfigurationClasses'] = $this->originalRestlerConfigurationClasses;
        $_SERVER = $this->originalServerVars;
        parent::tearDown();
    }

    /**
     * @test
     */
    public function canCreateRestlerObject()
    {
        $this->extensionConfigurationMock
            ->expects($this->once())->method('isProductionContextSet')
            ->will($this->returnValue(false));
        $this->extensionConfigurationMock
            ->expects($this->once())->method('isCacheRefreshingEnabled')
            ->will($this->returnValue(true));

        $typo3CacheMock = $this->getMockBuilder('Aoe\\Restler\\System\\TYPO3\\Cache')->disableOriginalConstructor()->getMock();
        $this->objectManagerMock
            ->expects($this->once())->method('get')->with('Aoe\\Restler\\System\\TYPO3\\Cache')
            ->will($this->returnValue($typo3CacheMock));

        $createdObj = $this->callUnaccessibleMethodOfObject($this->builder, 'createRestlerObject');
        $this->assertInstanceOf('Aoe\Restler\System\Restler\RestlerExtended', $createdObj);
    }

    /**
     * @test
     */
    public function canConfigureRestlerObject()
    {
        $restlerObj = $this->getMockBuilder('Aoe\\Restler\\System\\Restler\\RestlerExtended')->disableOriginalConstructor()->getMock();

        $configurationClass = 'Aoe\\Restler\\Tests\\Unit\\System\\Restler\\Fixtures\\ValidConfiguration';
        $configurationMock = $this->getMockBuilder($configurationClass)->disableOriginalConstructor()->getMock();
        $configurationMock->expects($this->once())->method('configureRestler')->with($restlerObj);

        // override test-restler-configuration
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['restler']['restlerConfigurationClasses'] = array($configurationClass);
        unset($GLOBALS['TYPO3_Restler']['restlerConfigurationClasses']);

        $this->objectManagerMock
            ->expects($this->once())->method('get')->with($configurationClass)
            ->will($this->returnValue($configurationMock));

        $this->callUnaccessibleMethodOfObject($this->builder, 'configureRestler', array($restlerObj));
    }

    /**
     * @test
     */
    public function canConfigureRestlerWithExternalConfigurationClassObject()
    {
        $restlerObj = $this->getMockBuilder('Aoe\\Restler\\System\\Restler\\RestlerExtended')->disableOriginalConstructor()->getMock();

        $configurationClass = 'Aoe\\Restler\\Tests\\Unit\\System\\Restler\\Fixtures\\ValidConfiguration';
        $configurationMock = $this->getMockBuilder($configurationClass)->disableOriginalConstructor()->getMock();
        $configurationMock->expects($this->exactly(2))->method('configureRestler')->with($restlerObj);

        // override test-restler-configuration
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['restler']['restlerConfigurationClasses'] = array($configurationClass);
        $GLOBALS['TYPO3_Restler']['restlerConfigurationClasses'] = array($configurationClass);

        $this->objectManagerMock
            ->expects($this->exactly(2))->method('get')->with($configurationClass)
            ->will($this->returnValue($configurationMock));

        $this->callUnaccessibleMethodOfObject($this->builder, 'configureRestler', array($restlerObj));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function canNotConfigureRestlerObjectWhenConfigurationOfRestlerClassesIsNoArray()
    {
        // override test-restler-configuration
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['restler']['restlerConfigurationClasses'] = '';

        $restlerObj = $this->getMockBuilder('Aoe\\Restler\\System\\Restler\\RestlerExtended')->disableOriginalConstructor()->getMock();
        $this->callUnaccessibleMethodOfObject($this->builder, 'configureRestler', array($restlerObj));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function canNotConfigureRestlerObjectWhenConfigurationOfRestlerClassesIsEmptyArray()
    {
        // override test-restler-configuration
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['restler']['restlerConfigurationClasses'] = array();

        $restlerObj = $this->getMockBuilder('Aoe\\Restler\\System\\Restler\\RestlerExtended')->disableOriginalConstructor()->getMock();
        $this->callUnaccessibleMethodOfObject($this->builder, 'configureRestler', array($restlerObj));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function canNotConfigureRestlerObjectWhenConfigurationOfRestlerClassDoesNotImplementRequiredInterface()
    {
        $restlerObj = $this->getMockBuilder('Aoe\\Restler\\System\\Restler\\RestlerExtended')->disableOriginalConstructor()->getMock();

        $configurationClass = 'Aoe\\Restler\\Tests\\Unit\\System\\Restler\\Fixtures\\InvalidConfiguration';
        $configurationMock = $this->getMockBuilder($configurationClass)->disableOriginalConstructor()->getMock();

        // override test-restler-configuration
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['restler']['restlerConfigurationClasses'] = array($configurationClass);

        $this->objectManagerMock
            ->expects($this->once())->method('get')->with($configurationClass)
            ->will($this->returnValue($configurationMock));

        $this->callUnaccessibleMethodOfObject($this->builder, 'configureRestler', array($restlerObj));
    }

    /**
     * @test
     */
    public function canSetAutoLoading()
    {
        // set object-property (which the builder should update)
        Scope::$resolver = null;

        $requestedClass = 'Aoe\\Restler\\Configuration\\ExtensionConfiguration';
        $this->objectManagerMock
            ->expects($this->once())->method('get')->with($requestedClass)
            ->will($this->returnValue($this->extensionConfigurationMock));

        $this->callUnaccessibleMethodOfObject($this->builder, 'setAutoLoading');

        $closureToCreateObjectsViaObjectManager = Scope::$resolver;
        $this->assertTrue(is_callable($closureToCreateObjectsViaObjectManager));
        $createdObj = $closureToCreateObjectsViaObjectManager($requestedClass);
        $this->assertEquals($createdObj, $this->extensionConfigurationMock);
    }

    /**
     * @test
     */
    public function canSetCacheDirectory()
    {
        // set object-property (which the builder should update)
        Defaults::$cacheDirectory = '';

        $this->callUnaccessibleMethodOfObject($this->builder, 'setCacheDirectory');
        $this->assertEquals(PATH_site . 'typo3temp/tx_restler', Defaults::$cacheDirectory);
    }

    /**
     * @test
     */
    public function canSetServerConfiguration()
    {
        // set variables (which the builder should use/update)
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PORT'] = '80';

        $this->callUnaccessibleMethodOfObject($this->builder, 'setServerConfiguration');
        $this->assertEquals('443', $_SERVER['SERVER_PORT']);
    }

    /**
     * @test
     */
    public function addApiControllerClassesFromLocalConf()
    {
        // setup
        $backupGlobals = $GLOBALS['TYPO3_Restler']['addApiClass'];
        unset($GLOBALS['TYPO3_Restler']['addApiClass']);
        $GLOBALS['TYPO3_Restler']['addApiClass']['foopath'][] = 'BarController';

        $restlerObj = $this->getMockBuilder('Aoe\\Restler\\System\\Restler\\RestlerExtended')->disableOriginalConstructor()->getMock();
        $restlerObj->expects($this->once())->method('addAPIClass')->with('BarController', 'foopath');

        //verifiy
        $this->callUnaccessibleMethodOfObject($this->builder, 'addApiClassesByGlobalArray', array($restlerObj));

        //tear down
        $GLOBALS['TYPO3_Restler']['addApiClass'] = $backupGlobals;
    }
}
