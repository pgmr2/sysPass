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

use SP\Core\Application;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\NoSuchPropertyException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Plugin\Ports\PluginDataInterface;
use SP\Domain\Plugin\Ports\PluginDataRepositoryInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\Plugin\Repositories\PluginDataModel;

use function SP\__u;

/**
 * Class PluginData
 */
final class PluginData extends Service implements PluginDataInterface
{
    public function __construct(
        Application                                    $application,
        private readonly PluginDataRepositoryInterface $pluginDataRepository,
        private readonly CryptInterface                $crypt,
    ) {
        parent::__construct($application);
    }


    /**
     * Creates an item
     *
     * @param PluginDataModel $itemData
     * @return QueryResult
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     * @throws CryptException
     */
    public function create(PluginDataModel $itemData): QueryResult
    {
        return $this->pluginDataRepository->create($itemData->encrypt($this->getMasterKeyFromContext(), $this->crypt));
    }

    /**
     * Updates an item
     *
     * @param PluginDataModel $itemData
     * @return int
     * @throws ConstraintException
     * @throws CryptException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    public function update(PluginDataModel $itemData): int
    {
        return $this->pluginDataRepository->update($itemData->encrypt($this->getMasterKeyFromContext(), $this->crypt));
    }

    /**
     * Returns the item for given plugin and id
     *
     * @param string $name
     * @param int $id
     * @return PluginDataModel
     * @throws ConstraintException
     * @throws CryptException
     * @throws NoSuchItemException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws SPException
     * @throws ServiceException
     */
    public function getByItemId(string $name, int $id): PluginDataModel
    {
        $result = $this->pluginDataRepository->getByItemId($name, $id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Plugin\'s data not found'), SPException::INFO);
        }

        /** @var PluginDataModel $itemData */
        $itemData = $result->getData();

        return $itemData->decrypt($this->getMasterKeyFromContext(), $this->crypt);
    }

    /**
     * Returns the item for given id
     *
     * @param string $id
     * @return PluginDataModel[]
     * @throws ConstraintException
     * @throws CryptException
     * @throws NoSuchItemException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws SPException
     * @throws ServiceException
     */
    public function getById(string $id): array
    {
        $result = $this->pluginDataRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Plugin\'s data not found'), SPException::INFO);
        }

        $data = $result->getDataAsArray();

        array_walk(
            $data,
            function (PluginDataModel $itemData) {
                $itemData->decrypt($this->getMasterKeyFromContext(), $this->crypt);
            }
        );

        return $data;
    }

    /**
     * Returns all the items
     *
     * @return PluginDataModel[]
     * @throws ConstraintException
     * @throws CryptException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws SPException
     * @throws ServiceException
     */
    public function getAll(): array
    {
        $data = $this->pluginDataRepository->getAll()->getDataAsArray();

        array_walk(
            $data,
            function ($itemData) {
                /** @var PluginDataModel $itemData */
                $itemData->decrypt($this->getMasterKeyFromContext(), $this->crypt);
            }
        );

        return $data;
    }

    /**
     * Deletes an item
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(string $id): void
    {
        if ($this->pluginDataRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Plugin\'s data not found'), SPException::INFO);
        }
    }

    /**
     * Deletes an item
     *
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByItemId(string $name, int $itemId): void
    {
        if ($this->pluginDataRepository->deleteByItemId($name, $itemId) === 0) {
            throw new NoSuchItemException(__u('Plugin\'s data not found'), SPException::INFO);
        }
    }
}