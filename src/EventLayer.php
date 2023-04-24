<?php

namespace Combindma\FacebookPixel;

use Illuminate\Support\Arr;

class EventLayer
{
    protected array $data;

    public function __construct($data = [])
    {
        $this->data = $data;
    }

    public function set(string $eventName, array $parameters = [], string $eventID = null): void
    {
        $this->data = Arr::add($this->data, $eventName, ['data' => $parameters, 'event_id' => $eventID]);
    }

    public function merge(array $newData): void
    {
        $this->data = array_merge($this->data, $newData);
    }

    public function clear(): void
    {
        $this->data = [];
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
