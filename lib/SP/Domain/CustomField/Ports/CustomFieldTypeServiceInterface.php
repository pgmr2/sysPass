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

namespace SP\Domain\CustomField\Ports;

use SP\DataModel\CustomFieldTypeData;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

/**
 * Class CustomFieldTypeService
 *
 * @package SP\Domain\CustomField\Services
 */
interface CustomFieldTypeServiceInterface
{
    /**
     * Get all items from the service's repository
     *
     * @return CustomFieldTypeData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllBasic(): array;

    /**
     * Returns all the items
     *
     * @return CustomFieldTypeData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById(int $id);
}