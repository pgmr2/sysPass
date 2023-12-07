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

namespace SP\Domain\User\Ports;

use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

/**
 * Class UserToUserGroupService
 *
 * @package SP\Domain\Common\Services\UserGroup
 */
interface UserToUserGroupServiceInterface
{
    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(int $id, array $users): int;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(int $id, array $users): int;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsersByGroupId(int $id): array;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById(int $id): array;

    /**
     * Checks whether the user is included in the group
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkUserInGroup(int $groupId, int $userId): bool;

    /**
     * Returns the groups which the user belongs to
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getGroupsForUser(int $userId): array;
}