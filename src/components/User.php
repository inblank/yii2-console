<?php

namespace inblank\yii2\console\components;

use inblank\yii2\console\interfaces\ConsoleIdentityInterface;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\rbac\CheckAccessInterface;

/**
 * Класс объекта пользователя из unix консоли
 * @property int $id Идентификатор пользователя в системе
 * @property string $login Логин под которым пользователь идентифицирован в консоли
 * @property bool $isGuest Признак гостя, не зарегистрированного в системе пользователя
 */
class User extends Component
{
    /**
     * @var CheckAccessInterface|string|array объект или имя класса объекта для проверки прав доступа пользователя.
     * Если не задан, используется компонент authManager приложения
     */
    public $accessChecker;

    /** @var string|null имя класса объекта для свойства [[identity]] */
    public ?string $identityClass = null;

    /** @var ConsoleIdentityInterface|null Данные текущего пользователя */
    private ?ConsoleIdentityInterface $identity;

    /** @var string|null Логин под которым пользователь идентифицирован в консоли */
    private ?string $login;

    /** @var array Кэш правил доступа */
    private array $_access = [];

    /**
     * {@inheritDoc}
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();
        if ($this->identityClass === null) {
            throw new InvalidConfigException('ConsoleUser::identityClass must be set');
        }
        if ($this->accessChecker !== null) {
            $this->accessChecker = Instance::ensure($this->accessChecker, CheckAccessInterface::class);
        }
        $consoleUser = posix_getpwuid(posix_getuid());
        $this->login = empty($consoleUser['name']) ? null : $consoleUser['name'];
    }

    /**
     * Проверка, что является гостем, не зарегистрированным пользователем
     * @return bool
     */
    public function getIsGuest(): bool
    {
        return $this->login === null || $this->getIdentity() === null;
    }

    /**
     * Получение идентификатора пользователя в системе
     * @return ?int если гость, возвращает null
     */
    public function getId(): ?int
    {
        return $this->getIsGuest() ? null : $this->identity->getId();
    }

    /**
     * Проверяет, может ли пользователь выполнить действие в соответствии с указанными разрешениями
     * @param string $permissionName имя разрешения разрешение для которого проверить
     * @param array $params дополнительные параметры для правил связанным с разрешением
     * @param bool $allowCaching признак кеширования результата проверки.
     * @return bool возвращает true если операция разрешена пользователю, false если нет.
     */
    public function can(string $permissionName, array $params = [], bool $allowCaching = true): bool
    {
        if ($allowCaching && empty($params) && isset($this->_access[$permissionName])) {
            return $this->_access[$permissionName];
        }
        if (($accessChecker = $this->getAccessChecker()) === null) {
            return false;
        }
        $access = $accessChecker->checkAccess($this->getId(), $permissionName, $params);
        if ($allowCaching && empty($params)) {
            $this->_access[$permissionName] = $access;
        }
        return $access;
    }

    /**
     * Получение объекта для проверки доступа пользователя
     * @return CheckAccessInterface
     */
    protected function getAccessChecker(): CheckAccessInterface
    {
        return $this->accessChecker ?? Yii::$app->getAuthManager();
    }

    /**
     * Получение данных пользователя
     * @return ConsoleIdentityInterface|null
     */
    public function getIdentity(): ?ConsoleIdentityInterface
    {
        if (!isset($this->identity)) {
            /** @var ConsoleIdentityInterface $class */
            $class = $this->identityClass;
            $this->identity = $class::findIdentityByLogin($this->login);
        }
        return $this->identity;
    }

    /**
     * Получение логина под которым пользователь идентифицирован в консоли
     * @return string
     */
    public function getLogin(): string
    {
        return $this->login;
    }
}