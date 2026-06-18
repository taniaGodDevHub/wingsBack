<?php

declare(strict_types=1);

namespace app\docs;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         title="Wings API",
 *         version="1.0.0",
 *         description="REST API интернет-магазина: авторизация, каталог, корзина, избранное, заказы и доставка.
 *
 * **Гостевая сессия (корзина и избранное)**
 * - Фронтенд один раз генерирует стабильный `session_id` (например UUID) и сохраняет в браузере.
 * - Гость передаёт `X-Session-ID: {session_id}` во всех запросах корзины и избранного (альтернатива — поле `session_id` в JSON или query).
 * - Авторизованный пользователь передаёт `Authorization: Bearer {access_token}`.
 * - Один список избранного и одна активная корзина на пользователя или на гостевую сессию.
 *
 * **Объединение после входа / регистрации**
 * 1. Автоматически: при `verify_*` / `login_*` с `session_id` (заголовок или body) в ответе `TokenResponse.guest_sync` переносятся корзина и избранное.
 * 2. Вручную (идемпотентно): параллельно `POST /api/cart-client/sync` и `POST /api/favorites/sync` с Bearer + `session_id`.
 * Если `session_id` не передан при auth, `guest_sync.skipped=true` — передайте `session_id` в ручных sync.
 *
 * **SMS (временный mock-режим)**
 * Пока не подключён SMS-провайдер, включён `smsMockMode`: SMS не отправляется, а одноразовый код возвращается в ответе
 * `phone_registration_confirmed` и `phone_login_get_code` (поля `activation_code` и `code`).
 * Фронтенд использует `record_id` + `code` для `verify_phone_registration` / `login_phone_with_code`.
 * После подключения SMS.ru установите `smsMockMode=false` и укажите `smsRuApiId` — код перестанет приходить в ответе API."
 *     ),
 *     @OA\Server(url="/", description="API-сервер")
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="JWT access token в заголовке Authorization: Bearer {token}"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="sessionId",
 *     type="apiKey",
 *     in="header",
 *     name="X-Session-ID",
 *     description="ID гостевой сессии браузера. Создаётся на фронтенде один раз и остаётся неизменным. Обязателен для гостевых запросов корзины и избранного. Для merge после входа передаётся вместе с Bearer на auth verify/login или на /sync."
 * )
 * @OA\SecurityScheme(
 *     securityScheme="refreshToken",
 *     type="apiKey",
 *     in="header",
 *     name="Refresh-Token",
 *     description="Refresh token для обновления JWT"
 * )
 */
class OpenApiSpec
{
}
