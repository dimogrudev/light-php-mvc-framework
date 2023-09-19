<?php

namespace Core;

use Core\Modules\Database;

abstract class Model
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }
}
