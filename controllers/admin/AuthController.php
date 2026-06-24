<?php

declare(strict_types=1);

namespace app\controllers\admin;

use app\models\AdminLoginForm;
use app\models\AdminRequestPasswordResetForm;
use app\models\AdminResetPasswordForm;
use app\models\AdminSignupForm;
use app\services\AdminPasswordResetService;
use Yii;
use yii\base\Security;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;
use yii\web\TooManyRequestsHttpException;

class AuthController extends Controller
{
    public $layout = 'admin-auth';

    public function __construct(
        $id,
        $module,
        private readonly Security $security,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'register', 'request-password-reset', 'reset-password'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actionLogin(): Response|string
    {
        if (!Yii::$app->user->isGuest && BaseAdminController::canAccess()) {
            return $this->redirect(Yii::$app->user->getReturnUrl(['/admin/product/index']));
        }

        if (!Yii::$app->user->isGuest) {
            return $this->render('login', [
                'model' => new AdminLoginForm($this->security),
                'alreadySignedIn' => true,
            ]);
        }

        $model = new AdminLoginForm($this->security);

        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->redirectAfterAuth();
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
            'alreadySignedIn' => false,
        ]);
    }

    public function actionRegister(): Response|string
    {
        if (!Yii::$app->user->isGuest && BaseAdminController::canAccess()) {
            return $this->redirect(Yii::$app->user->getReturnUrl(['/admin/product/index']));
        }

        if (!Yii::$app->user->isGuest) {
            Yii::$app->session->setFlash(
                'warning',
                Yii::t('app', 'You are signed in but do not have admin access yet.'),
            );

            return $this->redirect(['login']);
        }

        $model = new AdminSignupForm();

        if ($model->load(Yii::$app->request->post())) {
            $user = $model->signup();
            if ($user !== null) {
                Yii::$app->user->login($user, 3600 * 24 * 30);
                Yii::$app->session->setFlash(
                    'success',
                    Yii::t('app', 'Registration completed. Contact an administrator to get admin access.'),
                );

                return $this->redirect(['login']);
            }
        }

        return $this->render('register', ['model' => $model]);
    }

    public function actionRequestPasswordReset(): Response|string
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['login']);
        }

        $model = new AdminRequestPasswordResetForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            try {
                $recordId = (new AdminPasswordResetService())->requestReset($model->email);
            } catch (TooManyRequestsHttpException $e) {
                Yii::$app->session->setFlash('warning', $e->getMessage());

                return $this->redirect(['request-password-reset']);
            }

            Yii::$app->session->set('admin_password_reset_email', mb_strtolower(trim($model->email)));
            if ($recordId !== null) {
                Yii::$app->session->set('admin_password_reset_record_id', $recordId);
            }

            Yii::$app->session->setFlash(
                'success',
                Yii::t('app', 'If this email is registered, we have sent a password reset code.'),
            );

            return $this->redirect(['reset-password']);
        }

        return $this->render('request-password-reset', ['model' => $model]);
    }

    public function actionResetPassword(): Response|string
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['login']);
        }

        $model = new AdminResetPasswordForm();
        $model->email = (string) Yii::$app->session->get('admin_password_reset_email', '');
        $model->recordId = (string) Yii::$app->session->get('admin_password_reset_record_id', '');

        if ($model->load(Yii::$app->request->post())) {
            $model->recordId = (string) Yii::$app->session->get('admin_password_reset_record_id', $model->recordId);
            if ($model->resetPassword()) {
                Yii::$app->session->remove('admin_password_reset_email');
                Yii::$app->session->remove('admin_password_reset_record_id');
                Yii::$app->session->setFlash('success', Yii::t('app', 'Password has been reset. You can sign in now.'));

                return $this->redirect(['login']);
            }
        }

        return $this->render('reset-password', ['model' => $model]);
    }

    public function actionLogout(): Response
    {
        Yii::$app->user->logout();

        return $this->redirect(['login']);
    }

    private function redirectAfterAuth(): Response
    {
        if (!BaseAdminController::canAccess()) {
            Yii::$app->user->logout();
            Yii::$app->session->setFlash(
                'error',
                Yii::t('app', 'This account does not have admin access. Sign in with an administrator account.'),
            );

            return $this->redirect(['login']);
        }

        return $this->redirect(Yii::$app->user->getReturnUrl(['/admin/product/index']));
    }
}
