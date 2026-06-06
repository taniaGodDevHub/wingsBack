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

    /** @return array<int, string> */
    public static function getCheckboxOptions(): array
    {
        $options = [];
        foreach (static::find()->orderBy(['name' => SORT_ASC])->all() as $color) {
            $options[(int) $color->id] = $color->name . ' (' . $color->hex . ')';
        }

        return $options;
    }
}
