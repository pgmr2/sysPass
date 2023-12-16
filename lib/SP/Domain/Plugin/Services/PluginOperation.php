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

namespace SP\Domain\Plugin\Services;

use Defuse\Crypto\Exception\CryptoException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\NoSuchPropertyException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Plugin\Ports\PluginDataInterface;
use SP\Domain\Plugin\Ports\PluginOperationInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Plugin\Repositories\PluginDataModel;

/**
 * Class PluginOperation
 */
final class PluginOperation implements PluginOperationInterface
{
    public function __construct(
        private readonly PluginDataInterface $pluginDataService,
        private readonly string              $pluginName
    ) {
    }

    /**
     * @param int $itemId
     * @param mixed $data
     *
     * @return int
     * @throws CryptoException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    public function create(int $itemId, mixed $data): int
    {
        $itemData = new PluginDataModel(['name' => $this->pluginName, 'itemId' => $itemId, 'data' => serialize($data)]);

        return $this->pluginDataService->create($itemData)->getLastId();
    }

    /**
     * @param int $itemId
     * @param mixed $data
     *
     * @return int
     * @throws CryptoException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    public function update(int $itemId, mixed $data): int
    {
        $itemData = new PluginDataModel(['name' => $this->pluginName, 'itemId' => $itemId, 'data' => serialize($data)]);

        return $this->pluginDataService->update($itemData);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $itemId): void
    {
        $this->pluginDataService->deleteByItemId($this->pluginName, $itemId);
    }

    /**
     * @template T
     *
     * @param int $itemId
     * @param class-string<T>|null $class
     *
     * @return mixed|null
     * @throws ConstraintException
     * @throws CryptoException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    public function get(int $itemId, ?string $class = null): mixed
    {
        try {
            return $this->pluginDataService
                ->getByItemId($this->pluginName, $itemId)
                ->hydrate($class);
        } catch (NoSuchItemException $e) {
            return null;
        }
    }
}