<?php

namespace HuntRecipes\Base;

use HuntRecipes\Database\SqlController;

/**
 * abstract class Common_Object
 *
 */
abstract class Common_Object {

    /**
     * Returns members of this class as an object
     *
     * @return object
     */
    public function toObject(): object {
        $obj = (object)[];
        $r = new \ReflectionClass(get_class($this));
        $props = $r->getProperties();
        foreach ($props as $p) {
            if (!$p->isPublic()) {
                continue;
            }
            $p = $p->getName();
            if (!in_array($p, ['conn'])) {
                $obj->$p = $this->$p;
            }
        }
        return $obj;
    }

    abstract protected function exists_in_db(): bool;

    abstract protected function update_from_db(): void;

    abstract public function save_to_db(): bool;

    abstract public function delete_from_db(): bool;

    abstract public static function list(SqlController $conn, array $props): array;

}
