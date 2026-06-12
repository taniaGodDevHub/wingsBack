<?php

declare(strict_types=1);

namespace app\controllers\admin;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

abstract class BaseAdminController extends Controller
{
    public $layout = 'main';

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => static fn (): bool => static::canAccess(),
                    ],
                ],
                'denyCallback' => static function (): void {
                    if (Yii::$app->user->isGuest) {
                        Yii::$app->user->loginRequired();
                    }

                    throw new ForbiddenHttpException(Yii::t('app', 'Access denied.'));
                },
            ],
        ];
    }

    public static function canAccess(): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        return Yii::$app->user->can('admin')
            || Yii::$app->user->can('manageUsers')
            || Yii::$app->user->can('manageRbac')
            || Yii::$app->user->can('manageCatalog');
    }

    public static function canManageCatalog(): bool
    {
        return !Yii::$app->user->isGuest
            && (Yii::$app->user->can('admin') || Yii::$app->user->can('manageCatalog'));
    }

    public static function canManageUsers(): bool
    {
        return !Yii::$app->user->isGuest
            && (Yii::$app->user->can('admin') || Yii::$app->user->can('manageUsers'));
    }

    public static function canManageRbac(): bool
    {
        return !Yii::$app->user->isGuest
            && (Yii::$app->user->can('admin') || Yii::$app->user->can('manageRbac'));
    }
}
