<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string $hex
 */
class Color extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%color}}';
    }

    public function rules(): array
    {
        return [
            [['name', 'hex'], 'required'],
            [['name'], 'string', 'max' => 100],
            [['hex'], 'string', 'max' => 7],
        ];
    }
}
