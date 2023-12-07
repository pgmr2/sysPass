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

namespace SP\Modules\Web\Controllers\Helpers\Account;

use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Session as CryptSession;
use SP\Domain\Account\Adapters\AccountPassData;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\FileNotFoundException;
use SP\Domain\Crypt\Ports\MasterPassServiceInterface;
use SP\Domain\Http\RequestInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Modules\Web\Controllers\Helpers\HelperBase;
use SP\Modules\Web\Controllers\Helpers\HelperException;
use SP\Mvc\View\TemplateInterface;
use SP\Util\ImageUtil;
use SP\Util\ImageUtilInterface;

/**
 * Class AccountPasswordHelper
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
final class AccountPasswordHelper extends HelperBase
{
    private Acl                        $acl;
    private ImageUtil                  $imageUtil;
    private MasterPassServiceInterface $masterPassService;

    public function __construct(
        Application $application,
        TemplateInterface $template,
        RequestInterface $request,
        Acl $acl,
        ImageUtilInterface $imageUtil,
        MasterPassServiceInterface $masterPassService
    ) {
        parent::__construct($application, $template, $request);

        $this->acl = $acl;
        $this->imageUtil = $imageUtil;
        $this->masterPassService = $masterPassService;
    }

    /**
     * @param  AccountPassData  $accountData
     *
     * @param  bool  $useImage
     *
     * @return array
     * @throws BadFormatException
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     * @throws FileNotFoundException
     * @throws HelperException
     * @throws NoSuchItemException
     * @throws ServiceException
     */
    public function getPasswordView(
        AccountPassData $accountData,
        bool $useImage
    ): array {
        $this->checkActionAccess();

        $this->view->addTemplate('viewpass');

        $this->view->assign('header', __('Account Password'));
        $this->view->assign('isImage', (int)$useImage);

        $pass = $this->getPasswordClear($accountData);

        if ($useImage) {
            $this->view->assign(
                'login',
                $this->imageUtil->convertText($accountData->getLogin())
            );
            $this->view->assign(
                'pass',
                $this->imageUtil->convertText($pass)
            );
        } else {
            $this->view->assign('login', $accountData->getLogin());
            $this->view->assign(
                'pass',
                htmlspecialchars($pass, ENT_COMPAT)
            );
        }

        return [
            'useimage' => $useImage,
            'html'     => $this->view->render(),
        ];
    }

    /**
     * @throws HelperException
     */
    private function checkActionAccess(): void
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_VIEW_PASS)) {
            throw new HelperException(__u('You don\'t have permission to access this account'));
        }
    }

    /**
     * Returns account's password
     *
     * @param  AccountPassData  $accountData
     *
     * @return string
     * @throws BadFormatException
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     * @throws HelperException
     * @throws NoSuchItemException
     * @throws ServiceException
     */
    public function getPasswordClear(AccountPassData $accountData): string
    {
        $this->checkActionAccess();

        if (!$this->masterPassService->checkUserUpdateMPass($this->context->getUserData()->getLastUpdateMPass())) {
            throw new HelperException(
                __('Master password updated')
                .'<br>'
                .__('Please, restart the session for update it')
            );
        }

        return trim(
            Crypt::decrypt(
                $accountData->getPass(),
                $accountData->getKey(),
                CryptSession::getSessionKey($this->context)
            )
        );
    }
}
