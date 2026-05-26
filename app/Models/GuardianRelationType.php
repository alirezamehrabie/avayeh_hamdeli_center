<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuardianRelationType extends Model
{
    public const TITLE_FATHER = 'پدر';
    public const TITLE_MOTHER = 'مادر';
    public const TITLE_STEPFATHER = 'نا پدری';
    public const TITLE_STEPMOTHER = 'نا مادری';

    protected $fillable = ['title'];

    public static function fatherLikeTitles(): array
    {
        return [
            self::TITLE_FATHER,
            self::TITLE_STEPFATHER,
        ];
    }

    public static function motherLikeTitles(): array
    {
        return [
            self::TITLE_MOTHER,
            self::TITLE_STEPMOTHER,
        ];
    }
}
