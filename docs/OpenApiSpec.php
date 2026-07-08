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
 * `GET /api/blago/order/{code}` — информация о заказе по коду благо: номер заказа, дата, сумма заказа и сумма благо.
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
 * `POST /api/auth/news_subscription` — отдельный метод подписки/отписки от рассылки (`news_subscribed: true|false`).
 * `PATCH /api/auth/profile` также поддерживает `news_subscribed`, но рекомендуется использовать отдельный endpoint подписки.
 *
 * **Подсказки адреса (DaData)**
 * `POST /api/dadata/suggest/city` или `POST /api/delivery/suggest-city` — подсказки населённого пункта (город). Используйте `data.city_fias_id` и `postal_code` для `GET /api/delivery/pvz`.
 * `POST /api/delivery/suggest-address` — основной эндпоинт checkout: подсказки полного адреса (город, улица, дом в одной строке) через DaData.
 * Поля body: `query` (обязательно), `count` (1–20, по умолчанию 10), `delivery_method_id` (1 — ПВЗ, 2 — курьер, по умолчанию 2).
 * Авторизация не требуется. Ответ: status success и массив data — value, full_address, postal_code, city_name, data (city_fias_id, geo_lat, geo_lon), pvz_code (null для курьера).
 *
 * **Пункты выдачи СДЭК**
 * `GET /api/delivery/pvz` — список ПВЗ с пагинацией (по 10, параметр `count` до 20).
 * Обязателен `city_fias_id` (город из DaData, см. `POST /api/dadata/suggest/city`). Рекомендуется также `postal_code` из подсказки — для определения кода города в СДЭК. Если пользователь выбрал только город — `page=1`, при `meta.has_more=true` запрашивайте `page=2`, `page=3`…
 * Если адрес уточнён через suggest-address — дополнительно `postal_code`, `geo_lat`, `geo_lon` (и при необходимости `fias_guid`) из ответа подсказки; пункты сортируются по близости, в ответе `distance_km`."
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
