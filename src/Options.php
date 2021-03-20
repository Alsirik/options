<?php

namespace Siryk\Options;

use LogicException;

class Options
{
    /**
     * @var array Options values
     */
    protected $values = [];

    /**
     * @var array default value getters
     */
    protected $callbacks = [];

    /**
     * @var array list of required fields
     */
    protected $required = [];

    /**
     * @var array list of available fields
     */
    protected $available = [];

    public function __construct($values = [])
    {
        $this->setMany($values);
    }

    function setDefaultCallback($key, callable $callable): self
    {
        $key = $this->keyNormalize($key);
        $this->callbacks[$key] = $callable;
        return $this;
    }

    /**
     * @param string|array $field
     * @return $this
     */
    public function addRequiredField($field): self
    {
        if (is_array($field)) {
            foreach ($field as $item) {
                $this->addRequiredField($item);
            }
        } else {
            if (!in_array($field, $this->required)) {
                $this->required[] = $this->keyNormalize($field);
            }
        }
        return $this;
    }

    /**
     * @param $field
     * @return $this
     */
    public function addAvailableField($field): self
    {
        if (is_array($field)) {
            foreach ($field as $item) {
                $this->addAvailableField($item);
            }
        } else {
            if (!in_array($field, $this->required)) {
                $this->available[] = $this->keyNormalize($field);
            }
        }
        return $this;
    }

    /**
     * @param iterable $array
     * @return $this
     */
    public function setMany(iterable $array): self
    {
        foreach ($array as $key => $value) {
            $this->set($key, $value);
        }
        return $this;
    }


    public function set(string $key, $value): self
    {
        $key = $this->keyNormalize($key);
        $this->values[$key] = $value;
        return $this;
    }

    public function has(string $key): bool
    {
        $key = $this->keyNormalize($key);
        return array_key_exists($key, $this->values);
    }

    public function get(string $key, $default = null)
    {
        $key = $this->keyNormalize($key);
        return $this->has($key) ? $this->values[$key] : $this->getDefaultValue($key, $default);
    }

    protected function getDefaultValue($key, $userDefault)
    {
        $callback = $this->callbacks[$key] ?? null;
        if ($callback) {
            return $callback($this, $key, $userDefault);
        }
        return $userDefault;
    }

    protected function keyNormalize($key): string
    {
        return trim($key);
    }

    public function only(array $array, $default = null): array
    {
        $res = [];
        foreach ($array as $field) {
            $res[$field] = $this->get($field, $default);
        }
        return $res;
    }

    protected function getAllFields(): array
    {
        $callbacks = array_keys($this->callbacks);
        $values = array_keys($this->values);
        return array_unique(array_merge($values, $callbacks));
    }

    protected function checkMissingRequired(array $existingFields)
    {
        $requiredFields = $this->required;
        $need = array_diff($requiredFields, $existingFields);
        if (count($need)) {
            throw new LogicException('missing required option(s): "' . implode('", "', $need) . '"');
        }
    }

    protected function checkUnrecognizedFields(array $existingFields)
    {
        $available = $this->available;
        if (count($available) == 0) {
            return;
        }
        $available = array_unique(array_merge($available, $this->required, array_keys($this->callbacks)));
        $unrecognized = array_diff($existingFields, $available);

        if (count($unrecognized)) {
            $message = 'unrecognized options "' . implode('", "', $unrecognized) . '". '
                . 'Available options is: "' . implode('", "', $available) . '".';
            throw new LogicException($message);
        }

    }

    /**
     * @return array
     * @throws LogicException
     */
    public function getAll(): array
    {
        $availableFields = $this->getAllFields();
        $this->checkMissingRequired($availableFields);
        $this->checkUnrecognizedFields($availableFields);

        return $this->only($availableFields);
    }


}