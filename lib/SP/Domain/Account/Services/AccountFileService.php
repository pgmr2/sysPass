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

namespace SP\Domain\Account\Services;

use SP\Core\Application;
use SP\DataModel\FileData;
use SP\DataModel\FileExtData;
use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Ports\AccountFileRepositoryInterface;
use SP\Domain\Account\Ports\AccountFileServiceInterface;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\InvalidImageException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Util\FileUtil;
use SP\Util\ImageUtilInterface;

use function SP\__u;

/**
 * Class AccountFileService
 *
 * @package SP\Domain\Account\Services
 */
final class AccountFileService extends Service implements AccountFileServiceInterface
{

    public function __construct(
        Application                            $application,
        private AccountFileRepositoryInterface $accountFileRepository,
        private ImageUtilInterface             $imageUtil
    ) {
        parent::__construct($application);
    }

    /**
     * Creates an item
     *
     * @param FileData $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws InvalidImageException
     * @throws QueryException
     */
    public function create(FileData $itemData): int
    {
        if (FileUtil::isImage($itemData)) {
            $itemData->setThumb($this->imageUtil->createThumbnail($itemData->getContent()));
        } else {
            $itemData->setThumb('no_thumb');
        }

        return $this->accountFileRepository->create($itemData);
    }

    /**
     * Returns the file with its content
     *
     * @param int $id
     *
     * @return FileExtData|null
     * @throws SPException
     */
    public function getById(int $id): ?FileExtData
    {
        return $this->accountFileRepository->getById($id)->getData();
    }

    /**
     * Deletes all the items for given ids
     *
     * @param int[] $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): int
    {
        $count = $this->accountFileRepository->deleteByIdBatch($ids);

        if ($count !== count($ids)) {
            throw new ServiceException(
                __u('Error while deleting the files'),
                SPException::WARNING
            );
        }

        return $count;
    }

    /**
     * Deletes an item
     *
     * @param int $id
     *
     * @return AccountFileServiceInterface
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): AccountFileServiceInterface
    {
        if (!$this->accountFileRepository->delete($id)) {
            throw new NoSuchItemException(__u('File not found'));
        }

        return $this;
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $searchData
     *
     * @return QueryResult
     */
    public function search(ItemSearchData $searchData): QueryResult
    {
        return $this->accountFileRepository->search($searchData);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return FileData[]
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getByAccountId(int $id): array
    {
        return $this->accountFileRepository->getByAccountId($id)->getDataAsArray();
    }
}