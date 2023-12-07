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

namespace SP\Domain\User\Services;

use SP\Core\Application;
use SP\DataModel\ItemSearchData;
use SP\DataModel\ProfileData;
use SP\DataModel\UserProfileData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Common\Services\ServiceItemTrait;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Ports\UserProfileRepositoryInterface;
use SP\Domain\User\Ports\UserProfileServiceInterface;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\User\Repositories\UserProfileRepository;
use SP\Util\Util;

use function SP\__u;

/**
 * Class UserProfileService
 *
 * @package SP\Domain\Common\Services\UserProfile
 */
final class UserProfileService extends Service implements UserProfileServiceInterface
{
    use ServiceItemTrait;

    protected UserProfileRepository $userProfileRepository;

    public function __construct(Application $application, UserProfileRepositoryInterface $userProfileRepository)
    {
        parent::__construct($application);

        $this->userProfileRepository = $userProfileRepository;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById(int $id): UserProfileData
    {
        $result = $this->userProfileRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Profile not found'));
        }

        $userProfileData = $result->getData();
        $userProfileData->setProfile(Util::unserialize(ProfileData::class, $userProfileData->getProfile()));

        return $userProfileData;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        return $this->userProfileRepository->search($itemSearchData);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): UserProfileServiceInterface
    {
        if ($this->userProfileRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Profile not found'), SPException::INFO);
        }

        return $this;
    }

    /**
     * @param int[] $ids
     *
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int
    {
        $count = $this->userProfileRepository->deleteByIdBatch($ids);

        if ($count !== count($ids)) {
            throw new ServiceException(
                __u('Error while removing the profiles'),
                SPException::WARNING
            );
        }

        return $count;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function create(UserProfileData $itemData): int
    {
        return $this->userProfileRepository->create($itemData);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     * @throws ServiceException
     */
    public function update(UserProfileData $itemData): void
    {
        $update = $this->userProfileRepository->update($itemData);

        if ($update === 0) {
            throw new ServiceException(__u('Error while updating the profile'));
        }
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getUsersForProfile(int $id): array
    {
        return $this->userProfileRepository->getUsersForProfile($id)->getDataAsArray();
    }

    /**
     * Get all items from the service's repository
     *
     * @return UserProfileData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllBasic(): array
    {
        return $this->userProfileRepository->getAll()->getDataAsArray();
    }
}