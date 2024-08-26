<?php

declare(strict_types=1);

namespace Carte\Abilities;

if (class_exists('\Illuminate\\Contracts\\Support\\Arrayable')) {
    /**
     * @template TKey of array-key
     * @template TValue
     * @extends \Illuminate\Contracts\Support\Arrayable<TKey, TValue>
     */
    interface Arrayable extends \Illuminate\Contracts\Support\Arrayable
    {
        // ...
    }
} else {
    /**
     * @template TKey of array-key
     * @template TValue
     */
    interface Arrayable //phpcs:ignore
    {
        /**
         * Get the instance as an array.
         *
         * @return array<TKey, TValue>
         */
        public function toArray(); // phpcs:ignoreFile
    }
}
