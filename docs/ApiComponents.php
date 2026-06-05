<?php

declare(strict_types=1);

namespace app\docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="OkResponse",
 *     @OA\Property(property="ok", type="boolean", example=true)
 * )
 *
 * @OA\Schema(
 *     schema="TokenResponse",
 *     @OA\Property(property="access_token", type="string"),
 *     @OA\Property(property="refresh_token", type="string"),
 *     @OA\Property(property="token_type", type="string", example="bearer")
 * )
 *
 * @OA\Schema(
 *     schema="CheckUserRequest",
 *     required={"is_email"},
 *     @OA\Property(property="phone_number", type="string", example="+79991234567"),
 *     @OA\Property(property="email", type="string", example="user@example.com"),
 *     @OA\Property(property="is_email", type="boolean", description="true — проверка по email, false — по телефону")
 * )
 *
 * @OA\Schema(
 *     schema="CheckUserResponse",
 *     @OA\Property(property="register", type="boolean", description="true — нужна регистрация"),
 *     @OA\Property(property="command", type="string", example="login by phone")
 * )
 *
 * @OA\Schema(
 *     schema="ChallengeOkResponse",
 *     @OA\Property(property="ok", type="boolean", example=true),
 *     @OA\Property(property="record_id", type="string", format="uuid"),
 *     @OA\Property(property="activation_code", type="string", example="123456", description="Только в dev-режиме")
 * )
 *
 * @OA\Schema(
 *     schema="EmailLoginCodeResponse",
 *     @OA\Property(property="record_id", type="string", format="uuid"),
 *     @OA\Property(property="code", type="string", example="123456", description="Только в dev-режиме")
 * )
 *
 * @OA\Schema(
 *     schema="UserProfileResponse",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="username", type="string"),
 *     @OA\Property(property="email", type="string", nullable=true),
 *     @OA\Property(property="name", type="string", nullable=true),
 *     @OA\Property(property="f", type="string", nullable=true, description="Фамилия"),
 *     @OA\Property(property="i", type="string", nullable=true, description="Имя"),
 *     @OA\Property(property="phone_number", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ProfileResponse",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string", nullable=true),
 *     @OA\Property(property="surname", type="string", nullable=true),
 *     @OA\Property(property="gender", type="string", nullable=true),
 *     @OA\Property(property="birth_date", type="string", nullable=true, format="date"),
 *     @OA\Property(property="phone_number", type="string", nullable=true),
 *     @OA\Property(property="phone_number_confirmed", type="boolean"),
 *     @OA\Property(property="email", type="string", nullable=true),
 *     @OA\Property(property="email_confirmed", type="boolean")
 * )
 *
 * @OA\Schema(
 *     schema="ProfileUpdateRequest",
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="surname", type="string"),
 *     @OA\Property(property="gender", type="string"),
 *     @OA\Property(property="birth_date", type="string", format="date"),
 *     @OA\Property(property="password", type="string", format="password")
 * )
 *
 * @OA\Schema(
 *     schema="UserAddress",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="city_id", type="integer", nullable=true),
 *     @OA\Property(property="city_fias_id", type="string", nullable=true),
 *     @OA\Property(property="fias_id", type="string", nullable=true),
 *     @OA\Property(property="kladr_id", type="string", nullable=true),
 *     @OA\Property(property="city_name", type="string", nullable=true),
 *     @OA\Property(property="region", type="string", nullable=true),
 *     @OA\Property(property="postal_code", type="string", nullable=true),
 *     @OA\Property(property="latitude", type="string", nullable=true),
 *     @OA\Property(property="longitude", type="string", nullable=true),
 *     @OA\Property(property="full_address", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="UserAddressShort",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="city_name", type="string", nullable=true),
 *     @OA\Property(property="full_address", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="UserAddressesResponse",
 *     @OA\Property(property="addresses", type="array", @OA\Items(ref="#/components/schemas/UserAddress"))
 * )
 *
 * @OA\Schema(
 *     schema="SyncRequest",
 *     required={"session_id"},
 *     @OA\Property(property="session_id", type="string", description="ID гостевой сессии (или заголовок X-Session-ID)")
 * )
 *
 * @OA\Schema(
 *     schema="CartItemActionResponse",
 *     @OA\Property(property="cart_id", type="integer"),
 *     @OA\Property(property="product_id", type="integer"),
 *     @OA\Property(property="quantity", type="integer"),
 *     @OA\Property(property="is_in_cart", type="boolean")
 * )
 *
 * @OA\Schema(
 *     schema="CartSyncResponse",
 *     @OA\Property(property="merged_items_count", type="integer"),
 *     @OA\Property(property="result_cart_id", type="integer"),
 *     @OA\Property(property="result_items_count", type="integer")
 * )
 *
 * @OA\Schema(
 *     schema="FavoritesSyncResponse",
 *     @OA\Property(property="merged_count", type="integer"),
 *     @OA\Property(property="result_total", type="integer")
 * )
 *
 * @OA\Schema(
 *     schema="FavoriteActionResponse",
 *     @OA\Property(property="product_id", type="integer"),
 *     @OA\Property(property="is_favorite", type="boolean")
 * )
 *
 * @OA\Schema(
 *     schema="OrderCreateResponse",
 *     @OA\Property(property="order_id", type="integer"),
 *     @OA\Property(property="expires_at", type="integer", description="Unix timestamp"),
 *     @OA\Property(property="status", type="string", example="draft")
 * )
 *
 * @OA\Schema(
 *     schema="OrderConfirmResponse",
 *     @OA\Property(property="order_id", type="integer"),
 *     @OA\Property(property="status", type="string"),
 *     @OA\Property(property="payment_status", type="string"),
 *     @OA\Property(property="delivery_provider", type="string"),
 *     @OA\Property(property="payment_url", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="DaDataSuggestRequest",
 *     required={"query"},
 *     @OA\Property(property="query", type="string", description="Строка поиска"),
 *     @OA\Property(property="count", type="integer", default=10, description="Количество подсказок (1–20)"),
 *     @OA\Property(property="city_fias_id", type="string", description="ФИАС ID города для ограничения поиска адресов")
 * )
 *
 * @OA\Schema(
 *     schema="CatalogProductShowcase",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="slug", type="string"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="brand", type="string", nullable=true),
 *     @OA\Property(property="price", type="number", format="float"),
 *     @OA\Property(property="old_price", type="number", format="float", nullable=true),
 *     @OA\Property(property="images", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="is_bestseller", type="boolean"),
 *     @OA\Property(property="is_featured_home", type="boolean")
 * )
 *
 * @OA\Schema(
 *     schema="ShowcaseResponse",
 *     @OA\Property(property="page", type="integer"),
 *     @OA\Property(property="pages", type="integer"),
 *     @OA\Property(property="total", type="integer"),
 *     @OA\Property(property="items", type="array", @OA\Items(ref="#/components/schemas/CatalogProductShowcase")),
 *     @OA\Property(property="banners", type="array", @OA\Items(type="string"))
 * )
 *
 * @OA\Schema(
 *     schema="CategoryTreeNode",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="slug", type="string"),
 *     @OA\Property(property="is_related", type="boolean"),
 *     @OA\Property(property="children", type="array", @OA\Items(ref="#/components/schemas/CategoryTreeNode"))
 * )
 *
 * @OA\Schema(
 *     schema="CatalogSearchResponse",
 *     @OA\Property(property="page", type="integer"),
 *     @OA\Property(property="pages", type="integer"),
 *     @OA\Property(property="total", type="integer"),
 *     @OA\Property(property="page_size", type="integer"),
 *     @OA\Property(property="items", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="available_filters", type="object"),
 *     @OA\Property(property="category", type="object", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="CartAddRequest",
 *     required={"product_id"},
 *     @OA\Property(property="product_id", type="integer"),
 *     @OA\Property(property="quantity", type="integer", default=1),
 *     @OA\Property(property="cart_id", type="integer", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="CartListResponse",
 *     @OA\Property(property="cart_id", type="integer", nullable=true),
 *     @OA\Property(property="items", type="array", @OA\Items(type="object")),
 *     @OA\Property(
 *         property="summary",
 *         type="object",
 *         @OA\Property(property="items_count", type="integer"),
 *         @OA\Property(property="total_amount", type="number", format="float"),
 *         @OA\Property(property="currency", type="string", example="RUB")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="CartCountResponse",
 *     @OA\Property(property="selected_items_count", type="integer"),
 *     @OA\Property(property="selected_total_amount", type="number", format="float"),
 *     @OA\Property(property="currency", type="string", example="RUB")
 * )
 *
 * @OA\Schema(
 *     schema="FavoritesListResponse",
 *     @OA\Property(property="page", type="integer"),
 *     @OA\Property(property="pages", type="integer"),
 *     @OA\Property(property="total", type="integer"),
 *     @OA\Property(property="items", type="array", @OA\Items(type="object"))
 * )
 *
 * @OA\Schema(
 *     schema="OrderCreateRequest",
 *     required={"items"},
 *     @OA\Property(property="items", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="comment", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="OrderActiveResponse",
 *     @OA\Property(property="order_id", type="integer"),
 *     @OA\Property(property="expires_at", type="integer")
 * )
 *
 * @OA\Schema(
 *     schema="OrderDetailsResponse",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="status", type="string"),
 *     @OA\Property(property="expires_at", type="integer", nullable=true),
 *     @OA\Property(property="total_price", type="number", format="float"),
 *     @OA\Property(property="payment_status", type="string"),
 *     @OA\Property(property="delivery_provider", type="string", nullable=true),
 *     @OA\Property(property="delivery_method_code", type="string", nullable=true),
 *     @OA\Property(property="items", type="array", @OA\Items(type="object"))
 * )
 *
 * @OA\Schema(
 *     schema="OrderConfirmRequest",
 *     required={"city_fias_id","destination_id","destination_address"},
 *     @OA\Property(property="delivery_method_id", type="integer", default=1),
 *     @OA\Property(property="city_fias_id", type="string"),
 *     @OA\Property(property="destination_id", type="string"),
 *     @OA\Property(property="destination_address", type="string"),
 *     @OA\Property(property="payment_method", type="string", default="cash")
 * )
 *
 * @OA\Schema(
 *     schema="DeliveryCalculateRequest",
 *     required={"order_id","city_fias_id"},
 *     @OA\Property(property="order_id", type="integer"),
 *     @OA\Property(property="city_fias_id", type="string"),
 *     @OA\Property(property="delivery_method_id", type="integer", default=1)
 * )
 *
 * @OA\Schema(
 *     schema="DeliveryCalculateResponse",
 *     @OA\Property(property="provider", type="string", example="cdek"),
 *     @OA\Property(property="method_code", type="string", example="cdek_standard"),
 *     @OA\Property(property="items", type="array", @OA\Items(type="object"))
 * )
 *
 * @OA\Examples(
 *     example="token-response",
 *     summary="JWT-токены",
 *     value={"access_token": "eyJ...", "refresh_token": "eyJ...", "token_type": "bearer"}
 * )
 *
 * @OA\Examples(
 *     example="check-user-response",
 *     summary="Результат проверки пользователя",
 *     value={"register": false, "command": "login by phone"}
 * )
 *
 * @OA\Examples(
 *     example="challenge-ok-response",
 *     summary="Код подтверждения отправлен",
 *     value={"ok": true, "record_id": "550e8400-e29b-41d4-a716-446655440000", "activation_code": "123456"}
 * )
 *
 * @OA\Response(
 *     response="delete-item",
 *     description="Запись успешно удалена",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(ref="#/components/schemas/OkResponse")
 *     )
 * )
 *
 * @OA\Response(
 *     response="unauthorized",
 *     description="Требуется авторизация",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(
 *             @OA\Property(property="name", type="string", example="Unauthorized"),
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="status", type="integer", example=401)
 *         )
 *     )
 * )
 */
class ApiComponents
{
}
