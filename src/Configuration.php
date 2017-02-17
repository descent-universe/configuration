<?php
/**
 * This file is part of the Descent Framework.
 *
 * (c)2017 Matthias Kaschubowski
 *
 * This code is licensed under the MIT license,
 * a copy of the license is stored at the project root.
 */

namespace Descent\Configuration;


use Descent\Contracts\ConfigurationInterface;
use Descent\Contracts\Provider\ConfigProviderInterface;

/**
 * Class Configuration
 * @package Descent\Configuration
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @var array
     */
    private $items = [];

    /**
     * @var null|ConfigurationInterface
     */
    private $parent;

    /**
     * @var null|string
     */
    private $path;

    /**
     * Configuration constructor.
     * @param ConfigurationInterface|null $parent
     * @param string|null $path
     */
    public function __construct(ConfigurationInterface $parent = null, string $path = null)
    {
        $this->parent = null;
        $this->path = $path;
    }

    /**
     * gets a configuration value or returns the default value when the configuration path is not available.
     *
     * @param string $query
     * @param null $default
     * @return mixed
     */
    public function get($query, $default = null)
    {
        return array_fetch($this->items, $query, $default);
    }

    /**
     * checks whether a query path is given or not.
     *
     * @param $query
     * @return bool
     */
    public function has($query)
    {
        return array_ping($this->items, $query);
    }

    /**
     * sets the configuration value to the provided query path. The value will be normalized in case of an array.
     *
     * @param string $query
     * @param $value
     * @return mixed
     */
    public function set(string $query, $value)
    {
        array_extend($this->items, $query, $value);

        if ( $this->parent instanceof ConfigurationInterface ) {
            $this->parent->set("{$this->path}.{$query}", $value);
        }
    }

    /**
     * splits a configuration query path into an own configuration instance. Changes to the configuration will be
     * applied to the split context.
     *
     * @param string $query
     * @return ConfigurationInterface
     */
    public function split(string $query): ConfigurationInterface
    {
        $instance = new static($this);

        if ( ( $data = $this->get($query, $instance) ) !== $instance ) {
            $instance->items = $data;
        }

        return $instance;
    }

    /**
     * registers the provided configuration providers.
     *
     * @param ConfigProviderInterface[] ...$provider
     * @return ConfigurationInterface
     */
    public function register(ConfigProviderInterface ... $provider): ConfigurationInterface
    {
        foreach ( $provider as $current ) {
            $this->set($current->getConfigurationQuery(), $current->configuration());
        }

        return $this;
    }

    /**
     * creates the configuration from a given array. The array may contain query path notation.
     *
     * @param array $configuration
     * @return ConfigurationInterface
     */
    public static function create(array $configuration): ConfigurationInterface
    {
        $instance = new static;

        foreach ( $configuration as $key => $value ) {
            $instance->set($key, $value);
        }

        return $instance;
    }
    
}