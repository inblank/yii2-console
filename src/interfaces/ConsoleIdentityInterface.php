<?php

namespace inblank\yii2\console\interfaces;

/**
 * Интерфейс ConsoleIdentityInterface для консольных пользователей
 */
interface ConsoleIdentityInterface
{
    /**
     * Метод получения данных пользователя по логину в консоли
     * @param string $login Логин пользователя в консоли
     * @return mixed
     */
    public static function findIdentityByLogin(string $login): ?ConsoleIdentityInterface;

    /**
     * Получение id пользователя
     * @return mixed
     */
    public function getId();
}