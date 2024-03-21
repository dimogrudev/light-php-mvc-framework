<?php

namespace Framework\Models;

class Customer extends \Core\ActiveRecord
{
    /** @var string */
    public $fullName;

    /** @var int */
    public $age;

    /** @var string */
    public $placeOfBirth;

    public static function tableName()
    {
        return 'customers';
    }
}
