<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $phone
 * @property string $email
 * @property string $telegram
 * @property string $work_hours_from
 * @property string $work_hours_to
 * @property int $updated_at
 */
class ContactInfo extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%contact_info}}';
    }

    public static function singleton(): self
    {
        $model = static::findOne(1);
        if ($model !== null) {
            return $model;
        }

        return new self([
            'id' => 1,
            'phone' => '',
            'email' => '',
            'telegram' => '',
            'work_hours_from' => '10:00',
            'work_hours_to' => '22:00',
            'updated_at' => time(),
        ]);
    }

    public function rules(): array
    {
        return [
            [['phone'], 'string', 'max' => 32],
            [['email'], 'string', 'max' => 255],
            [['email'], 'email', 'skipOnEmpty' => true],
            [['telegram'], 'string', 'max' => 255],
            [['work_hours_from', 'work_hours_to'], 'required'],
            [['work_hours_from', 'work_hours_to'], 'match', 'pattern' => '/^([01]\d|2[0-3]):[0-5]\d$/'],
            [['updated_at'], 'integer'],
            [['work_hours_to'], 'validateWorkHoursRange'],
        ];
    }

    public function validateWorkHoursRange(string $attribute): void
    {
        if ($this->hasErrors('work_hours_from') || $this->hasErrors($attribute)) {
            return;
        }

        if ($this->work_hours_from >= $this->work_hours_to) {
            $this->addError($attribute, Yii::t('app', 'End time must be later than start time.'));
        }
    }

    public function attributeLabels(): array
    {
        return [
            'phone' => Yii::t('app', 'Phone'),
            'email' => Yii::t('app', 'Email'),
            'telegram' => Yii::t('app', 'Telegram'),
            'work_hours_from' => Yii::t('app', 'Working hours from'),
            'work_hours_to' => Yii::t('app', 'Working hours to'),
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $this->updated_at = time();
        $this->phone = trim($this->phone);
        $this->email = trim($this->email);
        $this->telegram = trim($this->telegram);

        return true;
    }

    public function getWorkHoursLabel(): string
    {
        return $this->work_hours_from . '–' . $this->work_hours_to;
    }

    /** @return array<string, mixed> */
    public function toApiArray(): array
    {
        return [
            'phone' => $this->phone !== '' ? $this->phone : null,
            'email' => $this->email !== '' ? $this->email : null,
            'telegram' => $this->telegram !== '' ? $this->telegram : null,
            'work_hours' => [
                'from' => $this->work_hours_from,
                'to' => $this->work_hours_to,
            ],
            'work_hours_label' => $this->getWorkHoursLabel(),
        ];
    }
}
