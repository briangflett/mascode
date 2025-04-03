<?php

namespace Civi\Mascode\Utils;

/**
 * Generic Hook Dispatcher
 *
 * Use this to manage and reuse hook handler instances without repeated instantiation.
 */
class HookDispatcher
{

    /**
     * A container to hold one instance per class name
     * @var array<class-string, object>
     */
    protected static $instances = [];

    /**
     * Get an instance of a class (singleton-style).
     *
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public static function get(string $class): object
    {
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class();
        }
        return self::$instances[$class];
    }

    /**
     * Dispatch a method call on the class instance.
     *
     * @param class-string $class
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     */
    public static function call(string $class, string $method, ...$args)
    {
        $instance = self::get($class);
        if (!method_exists($instance, $method)) {
            throw new \RuntimeException("Method $method not found in class $class");
        }
        return $instance->$method(...$args);
    }
}
