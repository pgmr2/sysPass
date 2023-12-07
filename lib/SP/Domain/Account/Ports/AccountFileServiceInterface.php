<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Account\Ports;

use SP\DataModel\FileData;
use SP\DataModel\FileExtData;
use SP\DataModel\ItemSearchData;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\InvalidImageException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AccountFileService
 *
 * @package SP\Domain\Account\Services
 */
interface AccountFileServiceInterface
{
    /**
     * Creates an item
     *
     * @throws InvalidImageException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(FileData $itemData): int;

    /**
     * Returns the item for given id
     *
     * @param  int  $id
     *
     * @return FileExtData|null
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $id): ?FileExtData;

    /**
     * Deletes all the items for given ids
     *
     * @param  int[]  $ids
     *
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int;

    /**
     * Deletes an item
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): AccountFileServiceInterface;

    /**
     * Searches for items by a given filter
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $searchData): QueryResult;

    /**
     * Returns the item for given id
     *
     * @return FileData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByAccountId(int $id): array;
}