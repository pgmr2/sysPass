<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SPT\Config;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use PHPUnit\Framework\TestCase;
use SP\Core\Context\ContextException;
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Config\Ports\ConfigInterface;
use SP\Domain\Config\Services\ConfigFileService;
use SP\Infrastructure\File\FileException;

use function SPT\getResource;
use function SPT\recreateDir;
use function SPT\saveResource;
use function SPT\setupContext;

/**
 * Class ConfigTest
 *
 * Test de integración para comprobar el funcionamiento de la clase SP\Config\Config y sus utilidades
 *
 * @package SPT
 */
class ConfigTest extends TestCase
{
    /**
     * @var Container
     */
    protected static $dic;
    /**
     * @var string
     */
    protected static $currentConfig;

    /**
     * @throws ContextException
     */
    public static function setUpBeforeClass(): void
    {
        self::$dic = setupContext();

        // Save current config
        self::$currentConfig = getResource('config', 'config.xml');
    }

    /**
     * This method is called after the last test of this test class is run.
     */
    public static function tearDownAfterClass(): void
    {
        // Restore to the initial state
        saveResource('config', 'config.xml', self::$currentConfig);
        recreateDir(CACHE_PATH);
    }

    /**
     * Comprobar la carga de la configuración
     *
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testLoadClass(): ConfigInterface
    {
        $config = self::$dic->get(ConfigFileService::class);

        $this->assertInstanceOf(ConfigFileService::class, $config);
        $this->assertFileExists(CONFIG_FILE);

        return $config;
    }

    /**
     * Comprobar que la configuración se guarda correctamente
     *
     * @depends testLoadClass
     *
     * @param  ConfigInterface  $config
     *
     * @throws FileException
     */
    public function testSaveConfig(ConfigInterface $config)
    {
        $config->saveConfig($config->getConfigData(), false);

        $this->assertFileExists(CONFIG_FILE);
    }


    /**
     * Comprobar la carga de la configuración en el contexto
     *
     * @depends testLoadClass
     *
     * @param  ConfigInterface  $config
     */
    public function testLoadConfig(ConfigInterface $config)
    {
        $this->assertInstanceOf(ConfigData::class, $config->loadConfig());
    }

    /**
     * Comprobar la actualización de la configuración
     *
     * @depends testLoadClass
     *
     * @param  ConfigInterface  $config
     */
    public function testUpdateConfig(ConfigInterface $config)
    {
        $config->updateConfig($config->getConfigData());

        $this->assertEquals(ConfigFileService::getTimeUpdated(), $config->getConfigData()->getConfigDate());
    }

    /**
     * Comprobar la generación de una clave de actualización y que su longitud es correcta
     *
     * @depends testLoadClass
     *
     * @param  ConfigInterface  $config
     *
     * @throws EnvironmentIsBrokenException
     * @throws FileException
     */
    public function testGenerateUpgradeKey(ConfigInterface $config)
    {
        $config->generateUpgradeKey();

        $this->assertEquals(32, strlen($config->getConfigData()->getUpgradeKey()));
    }
}