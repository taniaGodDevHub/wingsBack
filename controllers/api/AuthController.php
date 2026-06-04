<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\api\BaseApiController;
use yii\filters\auth\HttpBearerAuth;
use app\components\auth\JwtService;
use app\services\AddressService;
use app\services\AuthService;
use app\services\ProfileService;
use Yii;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\UnauthorizedHttpException;

class AuthController extends BaseApiController
{
    private AuthService $authService;
    private ProfileService $profileService;
    private AddressService $addressService;

    public function init(): void
    {
        parent::init();
        $this->authService = new AuthService();
        $this->profileService = new ProfileService();
        $this->addressService = new AddressService();
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\CompositeAuth::class,
            'authMethods' => [
                HttpBearerAuth::class,
            ],
            'except' => [
                'check-user',
                'phone-registration-confirmed',
                'phone-login-get-code',
                'email-registration-confirmed',
                'email-login-get-code',
                'verify-phone-registration',
                'verify-email-registration',
                'login-phone-with-code',
                'login-email-with-code',
                'refresh-token',
            ],
        ];
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'check-user' => ['POST'],
                'phone-registration-confirmed' => ['POST'],
                'phone-login-get-code' => ['POST'],
                'email-registration-confirmed' => ['POST'],
                'email-login-get-code' => ['POST'],
                'verify-phone-registration' => ['POST'],
                'verify-email-registration' => ['POST'],
                'login-phone-with-code' => ['POST'],
                'login-email-with-code' => ['POST'],
                'refresh-token' => ['POST'],
                'my' => ['GET'],
                'profile' => ['GET', 'PATCH'],
                'send-email-confirmation' => ['POST'],
                'verify-email-confirmation' => ['POST'],
                'my-addresses' => ['GET'],
                'add-address' => ['POST'],
                'update-address' => ['PATCH'],
                'delete-address' => ['DELETE'],
            ],
        ];

        return $behaviors;
    }

    public function actionCheckUser(): array
    {
        $body = Yii::$app->request->bodyParams;
        $isEmail = filter_var($body['is_email'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $this->authService->checkUser(
            $body['phone_number'] ?? null,
            $body['email'] ?? null,
            $isEmail,
        );
    }

    public function actionPhoneRegistrationConfirmed(): array
    {
        $phone = Yii::$app->request->get('phone_number', '');
        if ($phone === '') {
            throw new BadRequestHttpException('phone_number is required.');
        }

        return $this->authService->startPhoneRegistration((string) $phone);
    }

    public function actionPhoneLoginGetCode(): array
    {
        $phone = Yii::$app->request->get('phone_number', '');
        if ($phone === '') {
            throw new BadRequestHttpException('phone_number is required.');
        }

        return $this->authService->startPhoneLogin((string) $phone);
    }

    public function actionEmailRegistrationConfirmed(): array
    {
        $email = Yii::$app->request->get('email', '');
        if ($email === '') {
            throw new BadRequestHttpException('email is required.');
        }

        return $this->authService->startEmailRegistration((string) $email);
    }

    public function actionEmailLoginGetCode(): array
    {
        $email = Yii::$app->request->get('email', '');
        if ($email === '') {
            $body = Yii::$app->request->bodyParams;
            $email = (string) ($body['email'] ?? '');
        }
        if ($email === '') {
            throw new BadRequestHttpException('email is required.');
        }

        return $this->authService->startEmailLogin($email);
    }

    public function actionVerifyPhoneRegistration(): array
    {
        $body = Yii::$app->request->bodyParams;

        return $this->authService->verifyPhoneRegistration(
            (string) ($body['phone_number'] ?? ''),
            (string) ($body['code'] ?? ''),
            (string) ($body['record_id'] ?? ''),
        );
    }

    public function actionVerifyEmailRegistration(): array
    {
        $body = Yii::$app->request->bodyParams;

        return $this->authService->verifyEmailRegistration(
            (string) ($body['email'] ?? ''),
            (string) ($body['code'] ?? ''),
            (string) ($body['record_id'] ?? ''),
        );
    }

    public function actionLoginPhoneWithCode(): array
    {
        $body = Yii::$app->request->bodyParams;

        return $this->authService->loginPhoneWithCode(
            (string) ($body['code'] ?? ''),
            (string) ($body['record_id'] ?? ''),
        );
    }

    public function actionLoginEmailWithCode(): array
    {
        $body = Yii::$app->request->bodyParams;

        return $this->authService->loginEmailWithCode(
            (string) ($body['code'] ?? ''),
            (string) ($body['record_id'] ?? ''),
        );
    }

    public function actionRefreshToken(): array
    {
        $body = Yii::$app->request->bodyParams;
        $refresh = (string) ($body['refresh_token'] ?? '');
        if ($refresh === '') {
            $refresh = (string) Yii::$app->request->headers->get('Refresh-Token', '');
        }
        if ($refresh === '') {
            throw new BadRequestHttpException('refresh_token is required.');
        }

        /** @var JwtService $jwt */
        $jwt = Yii::$app->jwt;
        $tokens = $jwt->rotateRefreshToken($refresh);
        if ($tokens === null) {
            throw new UnauthorizedHttpException('Invalid or expired refresh token.');
        }

        return $tokens;
    }

    public function actionMy(): array
    {
        $user = Yii::$app->user->identity;
        if ($user === null) {
            throw new UnauthorizedHttpException();
        }
        $profile = $user->profile;

        return [
            'id' => (int) $user->id,
            'username' => $user->username,
            'email' => $profile?->email,
            'name' => $profile?->name,
            'f' => $profile?->f,
            'i' => $profile?->i,
            'phone_number' => $profile?->phone_number,
        ];
    }

    public function actionProfile(): array
    {
        $user = $this->requireUser();

        if (Yii::$app->request->isPatch) {
            return $this->profileService->updateProfile($user, Yii::$app->request->bodyParams);
        }

        return $this->profileService->getProfile($user);
    }

    public function actionSendEmailConfirmation(): array
    {
        $user = $this->requireUser();
        $email = (string) (Yii::$app->request->bodyParams['email'] ?? '');

        return $this->profileService->sendEmailConfirmation($user, $email);
    }

    public function actionVerifyEmailConfirmation(): array
    {
        $user = $this->requireUser();
        $body = Yii::$app->request->bodyParams;

        return $this->profileService->verifyEmailConfirmation(
            $user,
            (string) ($body['email'] ?? ''),
            (string) ($body['code'] ?? ''),
            (string) ($body['record_id'] ?? ''),
        );
    }

    public function actionMyAddresses(): array
    {
        $user = $this->requireUser();

        return $this->addressService->list((int) $user->id);
    }

    public function actionAddAddress(): array
    {
        $user = $this->requireUser();

        return $this->addressService->add((int) $user->id, Yii::$app->request->bodyParams);
    }

    public function actionUpdateAddress(int $address_id): array
    {
        $user = $this->requireUser();

        return $this->addressService->update((int) $user->id, $address_id, Yii::$app->request->bodyParams);
    }

    public function actionDeleteAddress(int $address_id): array
    {
        $user = $this->requireUser();

        return $this->addressService->delete((int) $user->id, $address_id);
    }

    private function requireUser(): \app\models\User
    {
        $user = Yii::$app->user->identity;
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        return $user;
    }
}
