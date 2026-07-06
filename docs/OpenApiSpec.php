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
 * После подключения SMS.ru установите `smsMockMode=false` и укажите `smsRuApiId` — код перестанет приходить в ответе API.
 *
 * **Карточка товара**
 * `GET /api/catalog/product/{slug}` — детальная карточка товара.
 * Те же поля товара (`description`, `attributes`, `size_chart`, `sizes`, `color`, `gender`, `group` и др.) возвращаются в списках: `GET /api/catalog/showcase`, `GET /api/catalog/search`, `GET /api/catalog/search/category/{slug}`, `GET /api/catalog/search/universal` (блок `products.data`). На витрине дополнительно: `is_bestseller`, `is_featured_home`.
 *
 * **Новости**
 * `GET /api/news` — постраничный список опубликованных статей (id, title, slug, image_url).
 * `GET /api/news/{slug}` — опубликованная статья и до 3 последних статей (кроме текущей) с полями image_url, title, slug.
 *
 * **Благо**
 * `GET /api/blago` — блок сбора блага (title, collection_start_at, collection_end_at, amount, image_url). Если блок не заполнен — 404.
 * У каждого товара в каталоге есть поле `blago` (сумма в ₽ на единицу). В корзине: `summary.blago_total` и `blago_amount` у позиции. В заказе: `blago_total`.
 *
 * **Заказы**
 * `POST /api/orders/create` — создаёт черновик; присваивает уникальный код `blago` + 4 цифры (например `blago2563`) и считает `blago_total` по товарам.
 * Код и сумма благо возвращаются также в `GET /api/orders/active`, `POST /api/orders/{id}/confirm`, `GET /api/orders/{id}`, `GET /api/orders/purchases`, `GET /api/orders/deliveries`.
 *
 * **Контакты**
 * `GET /api/contacts` — телефон, email, Telegram и время работы магазина.
 *
 * **Подписка на новости**
 * `PATCH /api/auth/profile` с `news_subscribed: true` — подписка на рассылку (нужен подтверждённый email в профиле).
 *
 * **Подсказки адреса (DaData)**
 * `POST /api/delivery/suggest-address` — основной эндпоинт: подсказки полного адреса (город, улица, дом в одной строке) через DaData.
 * Поля body: `query` (обязательно), `count` (1–20, по умолчанию 10), `delivery_method_id` (1 — ПВЗ, 2 — курьер, по умолчанию 2).
 * Авторизация не требуется. Ответ: status success и массив data — value, full_address, postal_code, city_name, data (city_fias_id, geo_lat, geo_lon), pvz_code (null для курьера)."
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
