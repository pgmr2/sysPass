<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Adapters;

use League\Fractal\Resource\Collection;
use SP\Core\Acl\ActionsInterface;
use SP\DataModel\ClientData;
use SP\Mvc\Controller\ItemTrait;
use SP\Util\Link;

/**
 * Class ClientAdapter
 *
 * @package SP\Adapters
 */
final class ClientAdapter extends AdapterBase
{
    use ItemTrait;

    protected $availableIncludes = [
        'customFields'
    ];

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Services\ServiceException
     */
    public function includeCustomFields(ClientData $data): Collection
    {
        return $this->collection(
            $this->getCustomFieldsForItem(ActionsInterface::CLIENT, $data->id),
            new CustomFieldAdapter($this->configData)
        );
    }

    public function transform(ClientData $data): array
    {
        return [
            'id' => $data->getId(),
            'name' => $data->getName(),
            'description' => $data->getDescription(),
            'isGlobal' => $data->isGlobal,
            'customFields' => null,
            'links' => [
                [
                    'rel' => 'self',
                    'uri' => Link::getDeepLink($data->getId(),
                        ActionsInterface::CLIENT_VIEW,
                        $this->configData,
                        true)
                ]
            ],
        ];
    }
}