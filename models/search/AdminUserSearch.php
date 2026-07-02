<?php

declare(strict_types=1);

namespace app\models\search;

use app\models\ShopOrder;
use app\models\User;
use app\models\UserAddress;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

final class AdminUserSearch extends Model
{
    public ?string $q = null;
    public ?string $status = null;

    public function rules(): array
    {
        return [
            [['q', 'status'], 'trim'],
            [['q', 'status'], 'string'],
            ['status', 'in', 'range' => ['', 'active', 'deleted', 'with_orders']],
        ];
    }

    /** @param array<string, mixed> $params */
    public function search(array $params): ActiveDataProvider
    {
        $query = User::find()
            ->alias('u')
            ->joinWith(['profile p'], false);

        $this->load($params);
        if (!$this->validate()) {
            return new ActiveDataProvider([
                'query' => $query->where('0=1'),
                'pagination' => ['pageSize' => 20],
            ]);
        }

        if ($this->status === 'active') {
            $query->andWhere(['u.status' => User::STATUS_ACTIVE]);
        } elseif ($this->status === 'deleted') {
            $query->andWhere(['u.status' => User::STATUS_DELETED]);
        } elseif ($this->status === 'with_orders') {
            $query->andWhere([
                'exists',
                ShopOrder::find()
                    ->alias('o')
                    ->select(new Expression('1'))
                    ->where('o.user_id = u.id')
                    ->andWhere(['not', ['o.status' => ShopOrder::STATUS_DRAFT]]),
            ]);
        }

        $search = trim((string) $this->q);
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->andWhere([
                'or',
                ['like', 'u.username', $like, false],
                ['like', 'p.email', $like, false],
                ['like', 'p.phone_number', $like, false],
                ['like', 'p.name', $like, false],
                ['like', 'p.f', $like, false],
                ['like', 'p.i', $like, false],
                ['like', 'p.surname', $like, false],
            ]);
        }

        return new ActiveDataProvider([
            'query' => $query->orderBy(['u.id' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);
    }
}
