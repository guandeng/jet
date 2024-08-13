<?php

/*
 * This file is part of friendsofhyperf/jet.
 *
 * @link     https://github.com/friendsofhyperf/jet
 * @document https://github.com/friendsofhyperf/jet/blob/main/README.md
 * @contact  huangdijia@gmail.com
 */

namespace FriendsOfHyperf\Jet;

abstract class Facade
{
    /**
     * @var array
     */
    protected static $instances = array();

    /**
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @return Client
     */
    protected static function getFacadeRoot()
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    /**
     * @param Client|string $name
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @return Client
     */
    protected static function resolveFacadeInstance($name)
    {
        if (is_object($name)) {
            return $name;
        }

        if (isset(static::$instances[$name])) {
            return static::$instances[$name];
        }

        return static::$instances[$name] = ClientFactory::create($name);
    }

    /**
     * @throws \RuntimeException
     * @return Client|string
     */
    protected static function getFacadeAccessor()
    {
        throw new \RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array(array(static::getFacadeRoot(), $name), $arguments);
    }
}
