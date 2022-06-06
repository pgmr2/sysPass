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

namespace SP\Modules\Web\Controllers\AccountManager;

use Exception;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\AccountHistoryServiceInterface;
use SP\Domain\Account\AccountPresetServiceInterface;
use SP\Domain\Account\AccountServiceInterface;
use SP\Domain\Account\Services\AccountBulkRequest;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\AccountForm;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;
use SP\Util\Util;

/**
 * Class AccountManagerController
 *
 * @package SP\Modules\Web\Controllers
 */
final class SaveBulkEditController extends ControllerBase
{
    use JsonTrait, ItemTrait;

    private AccountServiceInterface        $accountService;
    private AccountHistoryServiceInterface $accountHistoryService;
    private AccountForm                    $accountForm;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        AccountServiceInterface $accountService,
        AccountHistoryServiceInterface $accountHistoryService,
        AccountPresetServiceInterface $accountPresetService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->accountService = $accountService;
        $this->accountHistoryService = $accountHistoryService;
        $this->accountForm = new AccountForm($application, $this->request, $accountPresetService);

        $this->checkLoggedIn();
    }

    /**
     * saveBulkEditAction
     *
     * @return bool
     * @throws \JsonException
     */
    public function saveBulkEditAction(): bool
    {
        try {
            $this->accountForm->validate(ActionsInterface::ACCOUNTMGR_BULK_EDIT);

            $request = new AccountBulkRequest(
                Util::itemsIdAdapter($this->request->analyzeString('itemsId')),
                $this->accountForm->getItemData()
            );
            $request->setDeleteHistory($this->request->analyzeBool('delete_history', false));

            if ($request->isDeleteHistory()) {
                $this->accountHistoryService->deleteByAccountIdBatch($request->getItemsId());
            }

            $this->accountService->updateBulk($request);

            $this->eventDispatcher->notifyEvent(
                'edit.account.bulk',
                new Event($this, EventMessage::factory()->addDescription(__u('Accounts updated')))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Accounts updated'));
        } catch (Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }
}