<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\api\BaseApiController;
use yii\filters\auth\HttpBearerAuth;
use app\components\auth\JwtService;
use app\services\AddressService;
use app\services\AuthService;
use app\services\ProfileService;
use OpenApi\Annotations as OA;
use Yii;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\UnauthorizedHttpException;

/**
 * @OA\Tag(
 *     name="Авторизация",
 *     description="Регистрация, вход и управление JWT-токенами"
 * )
 * @OA\Tag(
 *     name="Профиль",
 *     description="Профиль пользователя и адреса доставки"
 * )
 *
 * @OA\Post(
 *     path="/api/auth/check_user",
 *     summary="Проверить: существует ли пользователь",
 *     description="actionCheckUser — Определяет, нужна регистрация или вход по телефону/email",
 *     operationId="actionCheckUser",
 *     tags={"Авторизация"},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Данные для проверки",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/CheckUserRequest")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Результат проверки",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/CheckUserResponse"),
 *             @OA\Examples(example=200, ref="#/components/examples/check-user-response")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/auth/phone_registration_confirmed",
 *     summary="Начать: регистрацию по телефону",
 *     description="actionPhoneRegistrationConfirmed — Отправляет SMS с кодом подтверждения для регистрации",
 *     operationId="actionPhoneRegistrationConfirmed",
 *     tags={"Авторизация"},
 *     @OA\Parameter(
 *         name="phone_number",
 *         in="query",
 *         description="Номер телефона в международном формате",
 *         required=true,
 *         @OA\Schema(type="string", example="+79991234567")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Код подтверждения отправлен",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/ChallengeOkResponse"),
 *             @OA\Examples(example=200, ref="#/components/examples/challenge-ok-response")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/auth/phone_login_get_code",
 *     summary="Получить: код входа по телефону",
 *     description="actionPhoneLoginGetCode — Отправляет SMS с кодом для входа существующему пользователю",
 *     operationId="actionPhoneLoginGetCode",
 *     tags={"Авторизация"},
 *     @OA\Parameter(
 *         name="phone_number",
 *         in="query",
 *         description="Номер телефона зарегистрированного пользователя",
 *         required=true,
 *         @OA\Schema(type="string", example="+79991234567")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Код подтверждения отправлен",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/ChallengeOkResponse"),
 *             @OA\Examples(example=200, ref="#/components/examples/challenge-ok-response")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/auth/email_registration_confirmed",
 *     summary="Начать: регистрацию по email",
 *     description="actionEmailRegistrationConfirmed — Отправляет письмо с кодом подтверждения для регистрации",
 *     operationId="actionEmailRegistrationConfirmed",
 *     tags={"Авторизация"},
 *     @OA\Parameter(
 *         name="email",
 *         in="query",
 *         description="Email для регистрации",
 *         required=true,
 *         @OA\Schema(type="string", example="user@example.com")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Код подтверждения отправлен",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/ChallengeOkResponse"),
 *             @OA\Examples(example=200, ref="#/components/examples/challenge-ok-response")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/auth/email_login_get_code",
 *     summary="Получить: код входа по email",
 *     description="actionEmailLoginGetCode — Отправляет письмо с кодом для входа. Email можно передать в query или в теле запроса",
 *     operationId="actionEmailLoginGetCode",
 *     tags={"Авторизация"},
 *     @OA\Parameter(
 *         name="email",
 *         in="query",
 *         description="Email зарегистрированного пользователя",
 *         required=false,
 *         @OA\Schema(type="string", example="user@example.com")
 *     ),
 *     @OA\RequestBody(
 *         required=false,
 *         description="Альтернативный способ передать email",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(@OA\Property(property="email", type="string", example="user@example.com"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Идентификатор записи и код (в dev-режиме)",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/EmailLoginCodeResponse")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/auth/verify_phone_registration",
 *     summary="Подтвердить: регистрацию по телефону",
 *     description="actionVerifyPhoneRegistration — Проверяет SMS-код и создаёт аккаунт, возвращает JWT-токены",
 *     operationId="actionVerifyPhoneRegistration",
 *     tags={"Авторизация"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"phone_number","code","record_id"},
 *                 @OA\Property(property="phone_number", type="string", example="+79991234567"),
 *                 @OA\Property(property="code", type="string", example="123456"),
 *                 @OA\Property(property="record_id", type="string", format="uuid")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="JWT-токены",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/TokenResponse"),
 *             @OA\Examples(example=200, ref="#/components/examples/token-response")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/auth/verify_email_registration",
 *     summary="Подтвердить: регистрацию по email",
 *     description="actionVerifyEmailRegistration — Проверяет код из письма и создаёт аккаунт, возвращает JWT-токены",
 *     operationId="actionVerifyEmailRegistration",
 *     tags={"Авторизация"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"email","code","record_id"},
 *                 @OA\Property(property="email", type="string", example="user@example.com"),
 *                 @OA\Property(property="code", type="string", example="123456"),
 *                 @OA\Property(property="record_id", type="string", format="uuid")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="JWT-токены",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/TokenResponse"),
 *             @OA\Examples(example=200, ref="#/components/examples/token-response")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/auth/login_phone_with_code",
 *     summary="Войти: по SMS-коду",
 *     description="actionLoginPhoneWithCode — Авторизация существующего пользователя по коду из SMS",
 *     operationId="actionLoginPhoneWithCode",
 *     tags={"Авторизация"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"code","record_id"},
 *                 @OA\Property(property="code", type="string", example="123456"),
 *                 @OA\Property(property="record_id", type="string", format="uuid")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="JWT-токены",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/TokenResponse"),
 *             @OA\Examples(example=200, ref="#/components/examples/token-response")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/auth/login_email_with_code",
 *     summary="Войти: по коду из email",
 *     description="actionLoginEmailWithCode — Авторизация существующего пользователя по коду из письма",
 *     operationId="actionLoginEmailWithCode",
 *     tags={"Авторизация"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"code","record_id"},
 *                 @OA\Property(property="code", type="string", example="123456"),
 *                 @OA\Property(property="record_id", type="string", format="uuid")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="JWT-токены",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/TokenResponse"),
 *             @OA\Examples(example=200, ref="#/components/examples/token-response")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/auth/refresh_token",
 *     summary="Обновить: JWT-токены",
 *     description="actionRefreshToken — Выдаёт новую пару access/refresh token. Refresh token передаётся в теле или заголовке Refresh-Token",
 *     operationId="actionRefreshToken",
 *     tags={"Авторизация"},
 *     security={{"refreshToken": {}}},
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(@OA\Property(property="refresh_token", type="string"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Новые JWT-токены",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/TokenResponse"),
 *             @OA\Examples(example=200, ref="#/components/examples/token-response")
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/auth/my",
 *     summary="Получить: текущий пользователь",
 *     description="actionMy — Краткая информация об авторизованном пользователе",
 *     operationId="actionMy",
 *     tags={"Авторизация"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Профиль пользователя",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/UserProfileResponse")
 *         )
 *     ),
 *     @OA\Response(response=401, ref="#/components/responses/unauthorized")
 * )
 *
 * @OA\Get(
 *     path="/api/auth/profile",
 *     summary="Получить: профиль пользователя",
 *     description="actionProfile — Расширенная информация профиля",
 *     operationId="actionProfile",
 *     tags={"Профиль"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Профиль",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/ProfileResponse")
 *         )
 *     ),
 *     @OA\Response(response=401, ref="#/components/responses/unauthorized")
 * )
 *
 * @OA\Patch(
 *     path="/api/auth/profile",
 *     summary="Обновить: профиль пользователя",
 *     description="actionProfile — Частичное обновление данных профиля",
 *     operationId="actionProfilePatch",
 *     tags={"Профиль"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/ProfileUpdateRequest")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Обновлённый профиль",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/ProfileResponse")
 *         )
 *     ),
 *     @OA\Response(response=401, ref="#/components/responses/unauthorized")
 * )
 *
 * @OA\Post(
 *     path="/api/auth/send_email_confirmation",
 *     summary="Отправить: код подтверждения email",
 *     description="actionSendEmailConfirmation — Отправляет код для подтверждения email в профиле",
 *     operationId="actionSendEmailConfirmation",
 *     tags={"Профиль"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"email"},
 *                 @OA\Property(property="email", type="string", example="user@example.com")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Код отправлен",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/ChallengeOkResponse")
 *         )
 *     ),
 *     @OA\Response(response=401, ref="#/components/responses/unauthorized")
 * )
 *
 * @OA\Post(
 *     path="/api/auth/verify_email_confirmation",
 *     summary="Подтвердить: email в профиле",
 *     description="actionVerifyEmailConfirmation — Проверяет код и помечает email как подтверждённый",
 *     operationId="actionVerifyEmailConfirmation",
 *     tags={"Профиль"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"email","code","record_id"},
 *                 @OA\Property(property="email", type="string"),
 *                 @OA\Property(property="code", type="string"),
 *                 @OA\Property(property="record_id", type="string", format="uuid")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Email подтверждён",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(property="ok", type="boolean", example=true),
 *                 @OA\Property(property="email_confirmed", type="boolean")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, ref="#/components/responses/unauthorized")
 * )
 *
 * @OA\Get(
 *     path="/api/auth/my_addresses",
 *     summary="Получить: адреса пользователя",
 *     description="actionMyAddresses — Список сохранённых адресов доставки",
 *     operationId="actionMyAddresses",
 *     tags={"Профиль"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Список адресов",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/UserAddressesResponse")
 *         )
 *     ),
 *     @OA\Response(response=401, ref="#/components/responses/unauthorized")
 * )
 *
 * @OA\Post(
 *     path="/api/auth/add_address",
 *     summary="Добавить: адрес доставки",
 *     description="actionAddAddress — Сохраняет новый адрес пользователя",
 *     operationId="actionAddAddress",
 *     tags={"Профиль"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"full_address"},
 *                 @OA\Property(property="city_id", type="integer"),
 *                 @OA\Property(property="city_fias_id", type="string"),
 *                 @OA\Property(property="fias_id", type="string"),
 *                 @OA\Property(property="kladr_id", type="string"),
 *                 @OA\Property(property="city_name", type="string"),
 *                 @OA\Property(property="region", type="string"),
 *                 @OA\Property(property="postal_code", type="string"),
 *                 @OA\Property(property="latitude", type="string"),
 *                 @OA\Property(property="longitude", type="string"),
 *                 @OA\Property(property="full_address", type="string")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Созданный адрес",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/UserAddressShort")
 *         )
 *     ),
 *     @OA\Response(response=401, ref="#/components/responses/unauthorized")
 * )
 *
 * @OA\Patch(
 *     path="/api/update_address/{address_id}",
 *     summary="Обновить: адрес доставки",
 *     description="actionUpdateAddress — Частичное обновление адреса по ID",
 *     operationId="actionUpdateAddress",
 *     tags={"Профиль"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="address_id",
 *         in="path",
 *         description="ID адреса",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(property="city_id", type="integer"),
 *                 @OA\Property(property="city_fias_id", type="string"),
 *                 @OA\Property(property="fias_id", type="string"),
 *                 @OA\Property(property="kladr_id", type="string"),
 *                 @OA\Property(property="city_name", type="string"),
 *                 @OA\Property(property="region", type="string"),
 *                 @OA\Property(property="postal_code", type="string"),
 *                 @OA\Property(property="latitude", type="string"),
 *                 @OA\Property(property="longitude", type="string"),
 *                 @OA\Property(property="full_address", type="string")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Обновлённый адрес",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/UserAddressShort")
 *         )
 *     ),
 *     @OA\Response(response=401, ref="#/components/responses/unauthorized")
 * )
 *
 * @OA\Delete(
 *     path="/api/auth/delete_address/{address_id}",
 *     summary="Удалить: адрес доставки",
 *     description="actionDeleteAddress — Удаляет адрес пользователя по ID",
 *     operationId="actionDeleteAddress",
 *     tags={"Профиль"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="address_id",
 *         in="path",
 *         description="ID адреса",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(response=200, ref="#/components/responses/delete-item"),
 *     @OA\Response(response=401, ref="#/components/responses/unauthorized")
 * )
 */
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
