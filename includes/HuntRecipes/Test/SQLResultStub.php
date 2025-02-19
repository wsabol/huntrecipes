<?php

namespace HuntRecipes\Test;

class SQLResultStub {
    public readonly int $num_rows;
    private readonly array $data;
    private int $position = 0;

    public function __construct(array $data) {
        $this->data = $data;
        $this->num_rows = count($data);
    }

    public function fetch_object(): object|false|null {
        if ($this->position >= count($this->data)) {
            return null;
        }
        return (object)$this->data[$this->position++];
    }
}
