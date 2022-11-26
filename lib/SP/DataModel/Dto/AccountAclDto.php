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

namespace SP\DataModel\Dto;

use SP\DataModel\AccountSearchVData;
use SP\DataModel\ItemData;

/**
 * Class AccountAclDto
 *
 * @package SP\DataModel\Dto
 */
final class AccountAclDto
{
    private int $accountId;
    private int $userId;
    private int $userGroupId;
    private int $dateEdit;
    /**
     * @var ItemData[]
     */
    private array $usersId;
    /**
     * @var ItemData[]
     */
    private array $userGroupsId;

    /**
     * @param  int  $accountId
     * @param  int  $userId
     * @param  array  $usersId
     * @param  int  $userGroupId
     * @param  array  $userGroupsId
     * @param  int  $dateEdit
     */
    public function __construct(
        int $accountId,
        int $userId,
        array $usersId,
        int $userGroupId,
        array $userGroupsId,
        int $dateEdit
    ) {
        $this->accountId = $accountId;
        $this->userId = $userId;
        $this->usersId = self::buildFromItemData($usersId);
        $this->userGroupId = $userGroupId;
        $this->userGroupsId = self::buildFromItemData($userGroupsId);
        $this->dateEdit = $dateEdit;
    }

    /**
     * @param  ItemData[]  $items
     *
     * @return array
     */
    private static function buildFromItemData(array $items): array
    {
        return array_filter($items, static fn($value) => $value instanceof ItemData);
    }

    /**
     * @param  AccountEnrichedDto  $accountDetailsResponse
     *
     * @return AccountAclDto
     */
    public static function makeFromAccount(AccountEnrichedDto $accountDetailsResponse): AccountAclDto
    {
        return new self(
            $accountDetailsResponse->getId(),
            $accountDetailsResponse->getAccountVData()->getUserId(),
            $accountDetailsResponse->getUsers(),
            $accountDetailsResponse->getAccountVData()->getUserGroupId(),
            $accountDetailsResponse->getUserGroups(),
            strtotime($accountDetailsResponse->getAccountVData()->getDateEdit())
        );
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUserGroupId(): int
    {
        return $this->userGroupId;
    }

    public function getDateEdit(): int
    {
        return $this->dateEdit;
    }

    /**
     * @param  AccountSearchVData  $accountSearchVData
     *
     * @param  array  $users
     * @param  array  $userGroups
     *
     * @return AccountAclDto
     */
    public static function makeFromAccountSearch(
        AccountSearchVData $accountSearchVData,
        array $users,
        array $userGroups
    ): AccountAclDto {
        return new self(
            $accountSearchVData->getId(),
            $accountSearchVData->getUserId(),
            $users,
            $accountSearchVData->getUserGroupId(),
            $userGroups,
            strtotime($accountSearchVData->getDateEdit())
        );
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    /**
     * @return ItemData[]
     */
    public function getUsersId(): array
    {
        return $this->usersId;
    }

    /**
     * @return ItemData[]
     */
    public function getUserGroupsId(): array
    {
        return $this->userGroupsId;
    }

}
