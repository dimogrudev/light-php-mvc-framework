<?php

namespace Framework\Models;

class Customer extends \Core\Extensions\ActiveRecord
{
    /** @var string $fullName */
    public string $fullName;

    /** @var int $age */
    public int $age;

    /** @var string $placeOfBirth */
    public string $placeOfBirth;

    public static function tableName()
    {
        return 'customers';
    }
}
