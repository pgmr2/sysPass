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

namespace SP\Tests\Providers\Auth\Ldap;

use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use SP\Core\Events\EventDispatcherInterface;
use SP\Core\Exceptions\SPException;
use SP\DataModel\UserLoginData;
use SP\Domain\Auth\Ports\LdapActionsInterface;
use SP\Domain\Auth\Ports\LdapInterface;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Providers\Auth\Ldap\AttributeCollection;
use SP\Providers\Auth\Ldap\LdapAuth;
use SP\Providers\Auth\Ldap\LdapException;
use SP\Tests\UnitaryTestCase;

/**
 * Class LdapAuthTest
 *
 * @group unitary
 */
class LdapAuthTest extends UnitaryTestCase
{

    private LdapInterface|MockObject            $ldap;
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private ConfigDataInterface|MockObject      $configData;
    private LdapAuth                            $ldapAuth;

    /**
     * @throws Exception
     */
    public function testAuthenticate()
    {
        $userLoginData = new UserLoginData();
        $userLoginData->setLoginUser(self::$faker->userName);
        $userLoginData->setLoginPass(self::$faker->password);

        $ldapActions = $this->createMock(LdapActionsInterface::class);

        $connectCounter = new InvokedCount(2);

        $this->ldap
            ->expects($connectCounter)
            ->method('connect')
            ->with(
                new Callback(static fn($user) => match ($connectCounter->numberOfInvocations()) {
                    1 => $user === null,
                    2 => !empty($user),
                    default => false
                }),
                new Callback(static fn($pass) => match ($connectCounter->numberOfInvocations()) {
                    1 => $pass === null,
                    2 => !empty($pass),
                    default => false
                })
            );

        $filter = 'test';

        $this->ldap
            ->expects(self::once())
            ->method('getUserDnFilter')
            ->with($userLoginData->getLoginUser())
            ->willReturn($filter);

        $this->ldap
            ->expects(self::once())
            ->method('actions')
            ->willReturn($ldapActions);

        $attributes = $this->buildAttributes();
        $attributes->set('expire', 0);

        $ldapActions
            ->expects(self::once())
            ->method('getAttributes')
            ->with($filter)
            ->willReturn($attributes);

        $this->ldap
            ->expects(self::once())
            ->method('isUserInGroup')
            ->with($attributes->get('dn'), $userLoginData->getLoginUser(), $attributes->get('group'))
            ->willReturn(true);

        $out = $this->ldapAuth->authenticate($userLoginData);

        self::assertTrue($out->isOk());
    }

    /**
     * @return AttributeCollection
     */
    private function buildAttributes(): AttributeCollection
    {
        return new AttributeCollection([
                                           'dn' => self::$faker->userName,
                                           'group' => [
                                               self::$faker->company,
                                               self::$faker->company,
                                               self::$faker->company,
                                           ],
                                           'fullname' => self::$faker->name,
                                           'name' => self::$faker->firstName,
                                           'sn' => self::$faker->lastName,
                                           'mail' => self::$faker->email,
                                           'expire' => self::$faker->unixTime,
                                       ]);
    }

    /**
     * @throws Exception
     */
    public function testAuthenticateWithExpireFail()
    {
        $userLoginData = new UserLoginData();
        $userLoginData->setLoginUser(self::$faker->userName);
        $userLoginData->setLoginPass(self::$faker->password);

        $ldapActions = $this->createMock(LdapActionsInterface::class);

        $this->ldap
            ->expects(self::once())
            ->method('connect')
            ->with(null, null);

        $filter = 'test';

        $this->ldap
            ->expects(self::once())
            ->method('getUserDnFilter')
            ->with($userLoginData->getLoginUser())
            ->willReturn($filter);

        $this->ldap
            ->expects(self::once())
            ->method('actions')
            ->willReturn($ldapActions);

        $attributes = $this->buildAttributes();

        $ldapActions
            ->expects(self::once())
            ->method('getAttributes')
            ->with($filter)
            ->willReturn($attributes);

        $this->ldap
            ->expects(self::once())
            ->method('isUserInGroup')
            ->with($attributes->get('dn'), $userLoginData->getLoginUser(), $attributes->get('group'))
            ->willReturn(true);

        $out = $this->ldapAuth->authenticate($userLoginData);

        self::assertFalse($out->isOk());
    }

    /**
     * @throws Exception
     */
    public function testAuthenticateWithGroupFail()
    {
        $userLoginData = new UserLoginData();
        $userLoginData->setLoginUser(self::$faker->userName);
        $userLoginData->setLoginPass(self::$faker->password);

        $ldapActions = $this->createMock(LdapActionsInterface::class);

        $this->ldap
            ->expects(self::once())
            ->method('connect')
            ->with(null, null);

        $filter = 'test';

        $this->ldap
            ->expects(self::once())
            ->method('getUserDnFilter')
            ->with($userLoginData->getLoginUser())
            ->willReturn($filter);

        $this->ldap
            ->expects(self::once())
            ->method('actions')
            ->willReturn($ldapActions);

        $attributes = $this->buildAttributes();

        $ldapActions
            ->expects(self::once())
            ->method('getAttributes')
            ->with($filter)
            ->willReturn($attributes);

        $this->ldap
            ->expects(self::once())
            ->method('isUserInGroup')
            ->with($attributes->get('dn'), $userLoginData->getLoginUser(), $attributes->get('group'))
            ->willReturn(false);

        $out = $this->ldapAuth->authenticate($userLoginData);

        self::assertFalse($out->isOk());
    }

    /**
     * @throws Exception
     */
    public function testAuthenticateFailConnect()
    {
        $userLoginData = new UserLoginData();
        $userLoginData->setLoginUser(self::$faker->userName);
        $userLoginData->setLoginPass(self::$faker->password);

        $ldapActions = $this->createMock(LdapActionsInterface::class);

        $this->ldap
            ->expects(self::once())
            ->method('connect')
            ->willThrowException(new LdapException('Exception', SPException::ERROR, null, 1));

        $filter = 'test';

        $this->ldap
            ->expects(self::never())
            ->method('getUserDnFilter');

        $this->ldap
            ->expects(self::never())
            ->method('actions');

        $ldapActions
            ->expects(self::never())
            ->method('getAttributes');

        $out = $this->ldapAuth->authenticate($userLoginData);

        self::assertFalse($out->isOk());
        self::assertEquals(1, $out->getStatusCode());
    }

    public function testIsAuthGrantedFalseWhenDatabaseEnabled()
    {
        $this->configData
            ->expects(self::once())
            ->method('isLdapDatabaseEnabled')
            ->willReturn(true);

        self::assertFalse($this->ldapAuth->isAuthGranted());
    }

    public function testIsAuthGrantedTrueWhenDatabaseDisabled()
    {
        $this->configData
            ->expects(self::once())
            ->method('isLdapDatabaseEnabled')
            ->willReturn(false);

        self::assertTrue($this->ldapAuth->isAuthGranted());
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->ldap = $this->createMock(LdapInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->configData = $this->createMock(ConfigDataInterface::class);

        $this->ldapAuth = new LdapAuth($this->ldap, $this->eventDispatcher, $this->configData);
    }
}
