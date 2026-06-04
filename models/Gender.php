<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $sort_order
 * @property bool $is_active
 */
class Gender extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%gender}}';
    }

    public function rules(): array
    {
        return [
            [['code', 'name'], 'required'],
            [['code'], 'string', 'max' => 16],
            [['name'], 'string', 'max' => 100],
            [['sort_order'], 'integer'],
            [['is_active'], 'boolean'],
            [['code'], 'unique'],
        ];
    }

    /** @return array<string, string> code => name */
    public static function getDropdownOptions(): array
    {
        $options = ['' => '—'];
        $rows = static::find()
            ->where(['is_active' => true])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->all();

        foreach ($rows as $row) {
            $options[$row->code] = $row->name;
        }

        return $options;
    }

    /** @return list<string> */
    public static function getActiveCodes(): array
    {
        return static::find()
            ->select('code')
            ->where(['is_active' => true])
            ->orderBy(['sort_order' => SORT_ASC])
            ->column();
    }
}
