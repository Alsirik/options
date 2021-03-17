<?php

namespace Siryk\Options;

class Options
{
    protected $options = [];

    private $callbacks = [];

    public function __construct($values = [])
    {
        $this->setMany($values);
    }

    function setDefaultCallback($key, callable $callable)
    {
        $key = $this->keyNormalize($key);
        $this->callbacks[$key] = $callable;
    }

    public function setMany(iterable $array)
    {
        foreach ($array as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function set(string $key, $value)
    {
        $key = $this->keyNormalize($key);
        $this->options[$key] = $value;
    }

    public function has(string $key): bool
    {
        $key = $this->keyNormalize($key);
        return array_key_exists($key, $this->options);
    }

    public function get(string $key, $default = null)
    {
        $key = $this->keyNormalize($key);
        return $this->has($key) ? $this->options[$key] : $this->getDefaultValue($key, $default);
    }

    private function getDefaultValue($key, $userDefault)
    {
        $callback = $this->callbacks[$key] ?? null;
        if ($callback) {
            return $callback($this, $key, $userDefault);
        }
        return $userDefault;
    }

    private function keyNormalize($key): string
    {
        return trim($key);
    }

}