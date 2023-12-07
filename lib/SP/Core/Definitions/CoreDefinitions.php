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

namespace SP\Core\Definitions;

use Aura\SqlQuery\QueryFactory;
use Klein\Klein;
use Klein\Request as KleinRequest;
use Klein\Response as KleinResponse;
use Monolog\Logger;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Container\ContainerInterface;
use SP\Core\Acl\Acl;
use SP\Core\Acl\Actions;
use SP\Core\Application;
use SP\Core\Bootstrap\UriContext;
use SP\Core\Context\ContextFactory;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\CryptPKI;
use SP\Core\Crypt\Csrf;
use SP\Core\Crypt\RequestBasedPassword;
use SP\Core\Crypt\UuidCookie;
use SP\Core\Language;
use SP\Core\MimeTypes;
use SP\Core\ProvidersHelper;
use SP\Core\UI\Theme;
use SP\Core\UI\ThemeContext;
use SP\Core\UI\ThemeIcons;
use SP\Domain\Auth\Ports\LdapActionsInterface;
use SP\Domain\Auth\Ports\LdapAuthInterface;
use SP\Domain\Auth\Ports\LdapConnectionInterface;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigInterface;
use SP\Domain\Config\Services\ConfigBackupService;
use SP\Domain\Config\Services\ConfigFileService;
use SP\Domain\Core\Acl\ActionsInterface;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Core\Context\ContextInterface;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Crypt\CryptPKIInterface;
use SP\Domain\Core\Crypt\RequestBasedPasswordInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\File\MimeTypesInterface;
use SP\Domain\Core\LanguageInterface;
use SP\Domain\Core\UI\ThemeContextInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Domain\Core\UI\ThemeInterface;
use SP\Domain\Html\MinifyInterface;
use SP\Domain\Http\RequestInterface;
use SP\Domain\Install\Adapters\InstallDataFactory;
use SP\Domain\Install\Services\DatabaseSetupInterface;
use SP\Domain\Install\Services\MysqlSetupBuilder;
use SP\Domain\Providers\MailerInterface;
use SP\Domain\Providers\MailProviderInterface;
use SP\Html\Minify;
use SP\Http\Client;
use SP\Http\Request;
use SP\Infrastructure\Database\Database;
use SP\Infrastructure\Database\DatabaseConnectionData;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\DbStorageInterface;
use SP\Infrastructure\Database\MysqlHandler;
use SP\Infrastructure\File\FileCache;
use SP\Infrastructure\File\FileCacheInterface;
use SP\Infrastructure\File\FileHandler;
use SP\Infrastructure\File\XmlHandler;
use SP\Mvc\View\Template;
use SP\Mvc\View\TemplateInterface;
use SP\Providers\Acl\AclHandler;
use SP\Providers\Auth\AuthProvider;
use SP\Providers\Auth\AuthProviderInterface;
use SP\Providers\Auth\AuthTypeEnum;
use SP\Providers\Auth\Browser\BrowserAuth;
use SP\Providers\Auth\Browser\BrowserAuthInterface;
use SP\Providers\Auth\Database\DatabaseAuth;
use SP\Providers\Auth\Database\DatabaseAuthInterface;
use SP\Providers\Auth\Ldap\LdapActions;
use SP\Providers\Auth\Ldap\LdapAuth;
use SP\Providers\Auth\Ldap\LdapBase;
use SP\Providers\Auth\Ldap\LdapConnection;
use SP\Providers\Auth\Ldap\LdapParams;
use SP\Providers\Log\DatabaseLogHandler;
use SP\Providers\Log\FileLogHandler;
use SP\Providers\Log\RemoteSyslogHandler;
use SP\Providers\Log\SyslogHandler;
use SP\Providers\Mail\MailHandler;
use SP\Providers\Mail\MailProvider;
use SP\Providers\Mail\PhpMailerWrapper;
use SP\Providers\Notification\NotificationHandler;

use function DI\autowire;
use function DI\create;
use function DI\factory;
use function DI\get;
use function SP\__u;

/**
 * Class CoreDefinitions
 */
final class CoreDefinitions
{
    public static function getDefinitions(): array
    {
        return [
            Klein::class => autowire(Klein::class),
            KleinRequest::class => factory([KleinRequest::class, 'createFromGlobals']),
            KleinResponse::class => create(KleinResponse::class),
            RequestInterface::class => autowire(Request::class),
            UriContextInterface::class => autowire(UriContext::class),
            ContextInterface::class =>
                static fn() => ContextFactory::getForModule(APP_MODULE),
            ConfigInterface::class => create(ConfigFileService::class)
                ->constructor(
                    create(XmlHandler::class)
                        ->constructor(create(FileHandler::class)->constructor(CONFIG_FILE)),
                    create(FileCache::class)->constructor(ConfigFileService::CONFIG_CACHE_FILE),
                    get(ContextInterface::class),
                    autowire(ConfigBackupService::class)
                ),
            ConfigDataInterface::class =>
                static fn(ConfigInterface $config) => $config->getConfigData(),
            DatabaseConnectionData::class => factory([DatabaseConnectionData::class, 'getFromConfig']),
            DbStorageInterface::class => autowire(MysqlHandler::class),
            ActionsInterface::class =>
                static fn() => new Actions(
                    new FileCache(Actions::ACTIONS_CACHE_FILE),
                    new XmlHandler(new FileHandler(ACTIONS_FILE))
                ),
            MimeTypesInterface::class =>
                static fn() => new MimeTypes(
                    new FileCache(MimeTypes::MIME_CACHE_FILE),
                    new XmlHandler(new FileHandler(MIMETYPES_FILE))
                ),
            Acl::class => autowire(Acl::class)
                ->constructorParameter('actions', get(ActionsInterface::class)),
            ThemeContextInterface::class => autowire(ThemeContext::class)
                ->constructorParameter('basePath', VIEW_PATH)
                ->constructorParameter('baseUri', factory([UriContextInterface::class, 'getWebRoot']))
                ->constructorParameter('module', APP_MODULE)
                ->constructorParameter('name', factory([Theme::class, 'getThemeName'])),
            ThemeIconsInterface::class => factory([ThemeIcons::class, 'loadIcons'])
                ->parameter(
                    'iconsCache',
                    create(FileCache::class)->constructor(ThemeIcons::ICONS_CACHE_FILE)
                ),
            ThemeInterface::class => autowire(Theme::class),
            TemplateInterface::class => autowire(Template::class),
            DatabaseAuthInterface::class => autowire(DatabaseAuth::class),
            BrowserAuthInterface::class => autowire(BrowserAuth::class),
            LdapParams::class => factory([LdapParams::class, 'getFrom']),
            LdapConnectionInterface::class => autowire(LdapConnection::class),
            LdapActionsInterface::class => autowire(LdapActions::class),
            LdapAuthInterface::class => autowire(LdapAuth::class)
                ->constructorParameter(
                    'ldap',
                    factory([LdapBase::class, 'factory'])
                ),
            AuthProviderInterface::class => factory(
                static function (
                    AuthProvider          $authProvider,
                    ConfigDataInterface   $configData,
                    LdapAuthInterface     $ldapAuth,
                    BrowserAuthInterface  $browserAuth,
                    DatabaseAuthInterface $databaseAuth,
                ) {
                    if ($configData->isLdapEnabled()) {
                        $authProvider->registerAuth($ldapAuth, AuthTypeEnum::Ldap);
                    }

                    if ($configData->isAuthBasicEnabled()) {
                        $authProvider->registerAuth($browserAuth, AuthTypeEnum::Browser);
                    }

                    $authProvider->registerAuth($databaseAuth, AuthTypeEnum::Database);

                    return $authProvider;
                }
            )->parameter('authProvider', autowire(AuthProvider::class)),
            Logger::class => create(Logger::class)
                ->constructor('syspass'),
            \GuzzleHttp\Client::class => create(\GuzzleHttp\Client::class)
                ->constructor(factory([Client::class, 'getOptions'])),
            Csrf::class => autowire(Csrf::class),
            LanguageInterface::class => autowire(Language::class),
            DatabaseInterface::class => autowire(Database::class),
            MailProviderInterface::class => autowire(MailProvider::class),
            MailerInterface::class => autowire(PhpMailerWrapper::class)->constructor(
                create(PHPMailer::class)->constructor(true)
            ),
            DatabaseSetupInterface::class => static function (RequestInterface $request) {
                $installData = InstallDataFactory::buildFromRequest($request);

                if ($installData->getBackendType() === 'mysql') {
                    return MysqlSetupBuilder::build($installData);
                }

                throw new SPException(__u('Unimplemented'), SPException::ERROR, __u('Wrong backend type'));
            },
            ProvidersHelper::class => factory(static function (ContainerInterface $c) {
                $configData = $c->get(ConfigDataInterface::class);

                if (!$configData->isInstalled()) {
                    return new ProvidersHelper($c->get(FileLogHandler::class));
                }

                return new ProvidersHelper(
                    $c->get(FileLogHandler::class),
                    $c->get(DatabaseLogHandler::class),
                    $c->get(MailHandler::class),
                    $c->get(SyslogHandler::class),
                    $c->get(RemoteSyslogHandler::class),
                    $c->get(AclHandler::class),
                    $c->get(NotificationHandler::class)
                );
            }),
            QueryFactory::class => create(QueryFactory::class)
                ->constructor('mysql', QueryFactory::COMMON),
            CryptInterface::class => create(Crypt::class),
            CryptPKIInterface::class => autowire(CryptPKI::class)
                ->constructorParameter('publicKeyFile', new FileHandler(CryptPKI::PUBLIC_KEY_FILE))
                ->constructorParameter('privateKeyFile', new FileHandler(CryptPKI::PRIVATE_KEY_FILE)),
            FileCacheInterface::class => create(FileCache::class),
            Application::class => autowire(Application::class),
            UuidCookie::class => factory([UuidCookie::class, 'factory'])
                ->parameter(
                    'request',
                    get(RequestInterface::class)
                ),
            RequestBasedPasswordInterface::class => autowire(RequestBasedPassword::class),
            MinifyInterface::class => autowire(Minify::class)
        ];
    }
}