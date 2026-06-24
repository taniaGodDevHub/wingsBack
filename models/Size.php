<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $rus_label
 * @property string $size_value
 * @property string $default_chest_circumference
 * @property int $sort_order
 */
class Size extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%size}}';
    }

    public function rules(): array
    {
        return [
            [['rus_label', 'size_value', 'default_chest_circumference'], 'required'],
            [['rus_label', 'size_value', 'default_chest_circumference'], 'string', 'max' => 16],
            [['sort_order'], 'integer', 'min' => 0],
            [['size_value'], 'unique'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'rus_label' => Yii::t('app', 'RUS size'),
            'size_value' => Yii::t('app', 'INT size'),
            'default_chest_circumference' => Yii::t('app', 'Default chest circumference'),
            'sort_order' => Yii::t('app', 'Sort order'),
        ];
    }

    /** @return self[] */
    public static function findAllOrdered(): array
    {
        return static::find()
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
    }

    /** @return string[] */
    public static function getStandardSizeValues(): array
    {
        return static::find()
            ->select('size_value')
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->column();
    }
}
