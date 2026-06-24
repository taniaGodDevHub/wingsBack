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
 *     schema="GuestSessionId",
 *     type="string",
 *     description="Стабильный ID гостевой сессии. Генерируется на фронтенде один раз (UUID/случайная строка), хранится в браузере. Передаётся в заголовке X-Session-ID и/или в поле session_id.",
 *     example="7f3c2a9e1b0046d8a2f59c8e4d1a003b"
 * )
 *
 * @OA\Schema(
 *     schema="GuestSyncCartResult",
 *     @OA\Property(property="merged_items_count", type="integer", description="Сколько позиций перенесено из гостевой корзины"),
 *     @OA\Property(property="result_cart_id", type="integer", description="ID активной корзины пользователя"),
 *     @OA\Property(property="result_items_count", type="integer", description="Итоговое количество единиц товара в корзине")
 * )
 *
 * @OA\Schema(
 *     schema="GuestSyncFavoritesResult",
 *     @OA\Property(property="merged_count", type="integer", description="Сколько товаров добавлено из гостевого избранного"),
 *     @OA\Property(property="result_total", type="integer", description="Итоговое число товаров в избранном пользователя")
 * )
 *
 * @OA\Schema(
 *     schema="GuestSyncResponse",
 *     description="Результат автоматического объединения при auth verify/login/register. Повторный вызов sync идемпотентен.",
 *     @OA\Property(property="skipped", type="boolean", description="true — session_id не был передан ни в заголовке, ни в теле"),
 *     @OA\Property(property="reason", type="string", nullable=true, example="no_session", description="Причина пропуска merge"),
 *     @OA\Property(property="cart", ref="#/components/schemas/GuestSyncCartResult", description="Присутствует, если skipped=false"),
 *     @OA\Property(property="favorites", ref="#/components/schemas/GuestSyncFavoritesResult", description="Присутствует, если skipped=false")
 * )
 *
 * @OA\Schema(
 *     schema="TokenResponse",
 *     description="JWT после входа или регистрации. Поле guest_sync содержит автоматический merge корзины и избранного.",
 *     @OA\Property(property="access_token", type="string"),
 *     @OA\Property(property="refresh_token", type="string"),
 *     @OA\Property(property="token_type", type="string", example="bearer"),
 *     @OA\Property(property="guest_sync", ref="#/components/schemas/GuestSyncResponse")
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
 *     description="Ответ после запроса SMS/email-кода. В mock-режиме SMS (`smsMockMode`) поле activation_code содержит одноразовый код для вёрстки и тестов.",
 *     @OA\Property(property="ok", type="boolean", example=true),
 *     @OA\Property(property="record_id", type="string", format="uuid", description="Идентификатор challenge — передать в verify/login вместе с кодом"),
 *     @OA\Property(property="activation_code", type="string", example="123456", description="Одноразовый код. Присутствует при smsMockMode или exposeActivationCode; в продакшене с реальной SMS отсутствует")
 * )
 *
 * @OA\Schema(
 *     schema="PhoneLoginCodeResponse",
 *     description="Ответ phone_login_get_code. В mock-режиме SMS код возвращается в теле ответа для входа без реальной отправки.",
 *     @OA\Property(property="ok", type="boolean", example=true),
 *     @OA\Property(property="record_id", type="string", format="uuid", description="Идентификатор challenge для login_phone_with_code"),
 *     @OA\Property(property="code", type="string", example="123456", description="Одноразовый код входа. Присутствует при smsMockMode или exposeActivationCode"),
 *     @OA\Property(property="activation_code", type="string", example="123456", description="Дубликат code для обратной совместимости")
 * )
 *
 * @OA\Schema(
 *     schema="EmailConfirmationResponse",
 *     @OA\Property(property="ok", type="boolean", example=true),
 *     @OA\Property(property="record_id", type="string", format="uuid"),
 *     @OA\Property(property="ttl_seconds", type="integer", example=300)
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
 *     description="Ручное объединение после входа. Достаточно передать session_id в теле **или** заголовок X-Session-ID (оба — необязательно, но хотя бы один обязателен). Требуется Authorization: Bearer.",
 *     @OA\Property(property="session_id", ref="#/components/schemas/GuestSessionId")
 * )
 *
 * @OA\Schema(
 *     schema="FavoriteProductRequest",
 *     required={"product_id"},
 *     @OA\Property(property="product_id", type="integer"),
 *     @OA\Property(property="session_id", ref="#/components/schemas/GuestSessionId", nullable=true, description="Для гостя, если не передан X-Session-ID")
 * )
 *
 * @OA\Schema(
 *     schema="CartItemActionResponse",
 *     @OA\Property(property="cart_id", type="integer"),
 *     @OA\Property(property="product_id", type="integer"),
 *     @OA\Property(property="size_value", type="string", example="M", description="Выбранный размер товара"),
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
 *     @OA\Property(property="query", type="string", example="Москва Тверская 7", description="Полный адрес в одной строке: город, улица, дом"),
 *     @OA\Property(property="count", type="integer", default=10, description="Количество подсказок (1–20)")
 * )
 *
 * @OA\Schema(
 *     schema="DaDataAddressSuggestionData",
 *     @OA\Property(property="city_fias_id", type="string", nullable=true, description="ФИАС ID города — для расчёта доставки"),
 *     @OA\Property(property="address_fias_id", type="string", nullable=true),
 *     @OA\Property(property="house_fias_id", type="string", nullable=true),
 *     @OA\Property(property="geo_lat", type="string", nullable=true),
 *     @OA\Property(property="geo_lon", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="DaDataAddressSuggestion",
 *     description="Подсказка полного адреса",
 *     @OA\Property(property="value", type="string", example="г Москва, ул Тверская, д 7", description="Краткая подпись для списка подсказок"),
 *     @OA\Property(property="full_address", type="string", example="125009, г Москва, ул Тверская, д 7", description="Полный адрес с почтовым индексом"),
 *     @OA\Property(property="postal_code", type="string", nullable=true, example="125009", description="Почтовый индекс отдельным полем"),
 *     @OA\Property(property="city_name", type="string", nullable=true, example="г Москва"),
 *     @OA\Property(property="data", ref="#/components/schemas/DaDataAddressSuggestionData")
 * )
 *
 * @OA\Schema(
 *     schema="DaDataSuggestResponse",
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/DaDataAddressSuggestion")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="DeliveryAddressSuggestion",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/DaDataAddressSuggestion"),
 *         @OA\Schema(@OA\Property(property="pvz_code", type="string", nullable=true, description="Код ПВЗ; null для курьерской доставки"))
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="ProductImageShowcase",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="image_url", type="string"),
 *     @OA\Property(property="sort_order", type="integer")
 * )
 *
 * @OA\Schema(
 *     schema="CategoryRef",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="slug", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="CatalogProductShowcase",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="slug", type="string"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="price", type="number", format="float"),
 *     @OA\Property(property="old_price", type="number", format="float", nullable=true),
 *     @OA\Property(property="is_available", type="boolean"),
 *     @OA\Property(property="is_bestseller", type="boolean"),
 *     @OA\Property(property="is_featured_home", type="boolean"),
 *     @OA\Property(property="images", type="array", @OA\Items(ref="#/components/schemas/ProductImageShowcase")),
 *     @OA\Property(property="categories", type="array", @OA\Items(ref="#/components/schemas/CategoryRef"))
 * )
 *
 * @OA\Schema(
 *     schema="HomePageContentResponse",
 *     description="Визуальный контент главной страницы: баннеры, блок «О нас», категории по полу и нижний баннер",
 *     @OA\Property(
 *         property="banners",
 *         type="array",
 *         description="Активные баннеры слайд-шоу, отсортированные по sort_order",
 *         @OA\Items(ref="#/components/schemas/ShowcaseBanner")
 *     ),
 *     @OA\Property(
 *         property="about",
 *         ref="#/components/schemas/ShowcaseAbout",
 *         nullable=true,
 *         description="Блок «О нас»; null, если не заполнены заголовок и изображение"
 *     ),
 *     @OA\Property(
 *         property="categories",
 *         type="array",
 *         description="Блоки категорий по полу (мужское/женское) с изображениями",
 *         @OA\Items(ref="#/components/schemas/ShowcaseHomeCategory")
 *     ),
 *     @OA\Property(
 *         property="bottom_banner",
 *         ref="#/components/schemas/ShowcaseBottomBanner",
 *         nullable=true,
 *         description="Нижний баннер; null, если не заполнены изображение и текст кнопки"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ShowcaseBottomBanner",
 *     description="Нижний баннер на главной",
 *     @OA\Property(property="image_url", type="string", format="uri"),
 *     @OA\Property(property="button_text", type="string", example="Перейти в каталог"),
 *     @OA\Property(property="button_url", type="string", nullable=true, example="/catalog")
 * )
 *
 * @OA\Schema(
 *     schema="ShowcaseResponse",
 *     @OA\Property(property="page", type="integer"),
 *     @OA\Property(property="pages", type="integer"),
 *     @OA\Property(property="total", type="integer"),
 *     @OA\Property(property="items", type="array", @OA\Items(ref="#/components/schemas/CatalogProductShowcase")),
 *     @OA\Property(
 *         property="banners",
 *         type="array",
 *         description="Баннеры главной (дублирует `/api/catalog/home`)",
 *         @OA\Items(ref="#/components/schemas/ShowcaseBanner")
 *     ),
 *     @OA\Property(
 *         property="about",
 *         ref="#/components/schemas/ShowcaseAbout",
 *         nullable=true,
 *         description="Блок «О нас»; присутствует только если заполнен"
 *     ),
 *     @OA\Property(
 *         property="categories",
 *         type="array",
 *         description="Категории по полу с изображениями; присутствует только если есть загруженные изображения",
 *         @OA\Items(ref="#/components/schemas/ShowcaseHomeCategory")
 *     ),
 *     @OA\Property(
 *         property="bottom_banner",
 *         ref="#/components/schemas/ShowcaseBottomBanner",
 *         nullable=true,
 *         description="Нижний баннер; присутствует только если заполнен"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ShowcaseHomeCategory",
 *     description="Блок категории на главной по полу",
 *     @OA\Property(property="gender", type="string", enum={"male","female"}, example="male", description="Код пола"),
 *     @OA\Property(property="name", type="string", example="Мужской", description="Отображаемое название из справочника пола"),
 *     @OA\Property(property="image_url", type="string", format="uri", example="https://example.com/uploads/home-categories/category_male_a1b2c3d4.webp")
 * )
 *
 * @OA\Schema(
 *     schema="ShowcaseAbout",
 *     description="Блок «О нас» на главной",
 *     @OA\Property(property="title", type="string", example="О нас"),
 *     @OA\Property(property="image_url", type="string", format="uri", example="https://example.com/uploads/about/about_a1b2c3d4.webp")
 * )
 *
 * @OA\Schema(
 *     schema="ShowcaseBanner",
 *     description="Баннер слайд-шоу на главной",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="image_url", type="string", format="uri", example="https://example.com/uploads/banners/banner_a1b2c3d4.webp"),
 *     @OA\Property(property="title", type="string", nullable=true, example="Новая коллекция"),
 *     @OA\Property(property="text", type="string", nullable=true, example="Скидки до 30% на весенние модели"),
 *     @OA\Property(property="button_text", type="string", example="Перейти в каталог"),
 *     @OA\Property(property="button_url", type="string", nullable=true, example="/catalog"),
 *     @OA\Property(property="sort_order", type="integer", example=1)
 * )
 *
 * @OA\Schema(
 *     schema="CategoryTreeNode",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="slug", type="string"),
 *     @OA\Property(property="related", type="boolean"),
 *     @OA\Property(property="children", type="array", @OA\Items(ref="#/components/schemas/CategoryTreeNode"))
 * )
 *
 * @OA\Schema(
 *     schema="CatalogProductColor",
 *     @OA\Property(property="id", type="integer", example=1001, description="ID из справочника color"),
 *     @OA\Property(property="slug", type="string", example="chernyy", description="Slug цвета из справочника color"),
 *     @OA\Property(property="name", type="string", example="Черный"),
 *     @OA\Property(property="hex", type="string", example="#111111")
 * )
 *
 * @OA\Schema(
 *     schema="CatalogSearchProduct",
 *     @OA\Property(property="id", type="integer", example=101),
 *     @OA\Property(property="slug", type="string", example="oversize-hoodie-black"),
 *     @OA\Property(property="name", type="string", example="Oversize Hoodie"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Мягкий оверсайз худи из плотного хлопка"),
 *     @OA\Property(property="price", type="number", format="float", example=5990),
 *     @OA\Property(property="old_price", type="number", format="float", nullable=true, example=7490),
 *     @OA\Property(property="is_available", type="boolean", example=true),
 *     @OA\Property(property="images", type="array", @OA\Items(ref="#/components/schemas/ProductImageShowcase")),
 *     @OA\Property(property="categories", type="array", @OA\Items(ref="#/components/schemas/CategoryRef")),
 *     @OA\Property(property="gender", type="string", enum={"male","female","unisex"}, example="unisex"),
 *     @OA\Property(property="sizes", type="array", @OA\Items(type="string"), example={"S","M","L"}, description="Размеры в наличии (INT), только is_in_stock=true"),
 *     @OA\Property(property="color", ref="#/components/schemas/CatalogProductColor", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="CatalogProductSizeChartRow",
 *     description="Строка таблицы размеров конкретного товара",
 *     @OA\Property(property="rus_label", type="string", example="44", description="Размер RUS"),
 *     @OA\Property(property="size_value", type="string", example="S", description="Международный размер (INT)"),
 *     @OA\Property(property="chest_circumference", type="string", example="92", description="Обхват груди, см"),
 *     @OA\Property(property="is_in_stock", type="boolean", example=true, description="Есть ли размер в наличии")
 * )
 *
 * @OA\Schema(
 *     schema="CatalogProductGroupVariant",
 *     description="Вариант товара в группе (другой цвет той же модели)",
 *     @OA\Property(property="slug", type="string", example="oversize-hoodie-white", description="Slug товара-варианта для перехода на карточку"),
 *     @OA\Property(property="color", ref="#/components/schemas/CatalogProductColor", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="CatalogProductGroup",
 *     description="Группа связанных товаров (варианты одной модели по цветам). Slug группы имеет префикс group-",
 *     @OA\Property(property="id", type="integer", example=5),
 *     @OA\Property(property="name", type="string", example="Худи"),
 *     @OA\Property(property="slug", type="string", example="group-hudi", description="Уникальный slug группы с префиксом group-"),
 *     @OA\Property(
 *         property="variants",
 *         type="array",
 *         description="Доступные товары группы (is_available=true), включая текущий",
 *         @OA\Items(ref="#/components/schemas/CatalogProductGroupVariant")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="CatalogProductDetail",
 *     description="Детальная карточка товара. Возвращается только эндпоинтом GET /api/catalog/product/{slug}",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/CatalogSearchProduct"),
 *         @OA\Schema(
 *             @OA\Property(property="description", type="string", nullable=true, description="Текстовое описание товара"),
 *             @OA\Property(
 *                 property="size_chart",
 *                 type="array",
 *                 description="Полная таблица размеров товара (все строки справочника с флагом наличия)",
 *                 @OA\Items(ref="#/components/schemas/CatalogProductSizeChartRow")
 *             ),
 *             @OA\Property(
 *                 property="group",
 *                 ref="#/components/schemas/CatalogProductGroup",
 *                 nullable=true,
 *                 description="Связанная группа вариантов; null, если товар не в группе"
 *             )
 *         )
 *     },
 *     example={
 *         "id": 101,
 *         "slug": "oversize-hoodie-black",
 *         "name": "Oversize Hoodie",
 *         "description": "Мягкий оверсайз худи из плотного хлопка",
 *         "price": 5990,
 *         "old_price": 7490,
 *         "is_available": true,
 *         "images": {{"id": 1, "image_url": "/uploads/products/hoodie-black.jpg", "sort_order": 0}},
 *         "categories": {{"id": 3, "name": "Худи", "slug": "hoodies"}},
 *         "gender": "unisex",
 *         "sizes": {"S", "M"},
 *         "color": {"id": 1001, "slug": "chernyy", "name": "Черный", "hex": "#111111"},
 *         "size_chart": {
 *             {"rus_label": "44", "size_value": "S", "chest_circumference": "92", "is_in_stock": true},
 *             {"rus_label": "46", "size_value": "M", "chest_circumference": "96", "is_in_stock": true},
 *             {"rus_label": "48", "size_value": "L", "chest_circumference": "100", "is_in_stock": false}
 *         },
 *         "group": {
 *             "id": 5,
 *             "name": "Худи",
 *             "slug": "group-hudi",
 *             "variants": {
 *                 {"slug": "oversize-hoodie-black", "color": {"id": 1001, "slug": "chernyy", "name": "Черный", "hex": "#111111"}},
 *                 {"slug": "oversize-hoodie-white", "color": {"id": 1002, "slug": "belyy", "name": "Белый", "hex": "#ffffff"}}
 *             }
 *         }
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="CatalogFeatureFilters",
 *     type="object",
 *     description="Фильтры по атрибутам каталога. Передаётся в query-параметре feature_filters как JSON-строка.",
 *     @OA\Property(
 *         property="color",
 *         type="array",
 *         description="Фильтр по цвету: массив ID цвета (integer) и/или slug (string)",
 *         @OA\Items(oneOf={
 *             @OA\Schema(type="integer", example=1001),
 *             @OA\Schema(type="string", example="chernyy")
 *         })
 *     ),
 *     @OA\AdditionalProperties(
 *         type="array",
 *         description="Фильтр по атрибуту: ключ — ID атрибута (число или строка), значение — массив ID значений атрибута",
 *         @OA\Items(type="integer", example=101)
 *     ),
 *     example={"color"={"chernyy","belyy",1001},"5"={101,102}}
 * )
 *
 * @OA\Schema(
 *     schema="CatalogFilterValue",
 *     @OA\Property(property="id", description="ID цвета (блок color) или ID значения атрибута (feature_*); для size/gender — строковое значение", oneOf={
 *         @OA\Schema(type="integer", example=1001),
 *         @OA\Schema(type="string", example="M")
 *     }),
 *     @OA\Property(property="slug", type="string", example="chernyy", description="Slug цвета; только в блоке фильтра color. В feature_filters можно передать slug вместо id"),
 *     @OA\Property(property="name", type="string", example="Черный"),
 *     @OA\Property(property="hex", type="string", example="#111111", description="Только для фильтра color"),
 *     @OA\Property(property="count", type="integer", example=12, description="Число товаров с этим значением при текущих фильтрах")
 * )
 *
 * @OA\Schema(
 *     schema="CatalogFilterBlock",
 *     @OA\Property(property="id", type="string", example="category", description="category, color, size, gender или feature_{id}"),
 *     @OA\Property(property="type", type="string", example="list"),
 *     @OA\Property(property="name_ru", type="string"),
 *     @OA\Property(property="values", type="array", @OA\Items(ref="#/components/schemas/CatalogFilterValue"))
 * )
 *
 * @OA\Schema(
 *     schema="CatalogAvailableFilters",
 *     @OA\Property(property="filters", type="array", @OA\Items(ref="#/components/schemas/CatalogFilterBlock")),
 *     @OA\Property(
 *         property="price",
 *         type="object",
 *         @OA\Property(property="min", type="number", format="float"),
 *         @OA\Property(property="max", type="number", format="float")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="CatalogSearchResponse",
 *     @OA\Property(property="page", type="integer", example=1),
 *     @OA\Property(property="pages", type="integer", example=10),
 *     @OA\Property(property="page_size", type="integer", example=60),
 *     @OA\Property(property="total", type="integer", example=574),
 *     @OA\Property(property="items", type="array", @OA\Items(ref="#/components/schemas/CatalogSearchProduct")),
 *     @OA\Property(property="available_filters", ref="#/components/schemas/CatalogAvailableFilters"),
 *     @OA\Property(property="category", ref="#/components/schemas/CategoryRef", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="CartAddRequest",
 *     required={"product_id","size_value"},
 *     @OA\Property(property="product_id", type="integer"),
 *     @OA\Property(property="size_value", type="string", example="M", description="Размер из доступных для товара (см. sizes в каталоге)"),
 *     @OA\Property(property="quantity", type="integer", default=1),
 *     @OA\Property(property="cart_id", type="integer", nullable=true),
 *     @OA\Property(property="session_id", ref="#/components/schemas/GuestSessionId", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="CartProductRequest",
 *     required={"product_id","size_value"},
 *     @OA\Property(property="product_id", type="integer"),
 *     @OA\Property(property="size_value", type="string", example="M", description="Размер позиции в корзине"),
 *     @OA\Property(property="cart_id", type="integer", nullable=true),
 *     @OA\Property(property="session_id", ref="#/components/schemas/GuestSessionId", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="CartProductInfo",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="slug", type="string"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="brand", type="string", nullable=true),
 *     @OA\Property(
 *         property="images",
 *         type="array",
 *         @OA\Items(@OA\Property(property="url", type="string"))
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="CartListItem",
 *     @OA\Property(property="product_id", type="integer"),
 *     @OA\Property(property="size_value", type="string", example="M"),
 *     @OA\Property(property="cart_id", type="integer"),
 *     @OA\Property(property="quantity", type="integer"),
 *     @OA\Property(property="unit_price", type="number", format="float"),
 *     @OA\Property(property="price_show", type="number", format="float"),
 *     @OA\Property(property="product_info", ref="#/components/schemas/CartProductInfo")
 * )
 *
 * @OA\Schema(
 *     schema="CartListResponse",
 *     @OA\Property(property="cart_id", type="integer", nullable=true),
 *     @OA\Property(property="items", type="array", @OA\Items(ref="#/components/schemas/CartListItem")),
 *     @OA\Property(
 *         property="summary",
 *         type="object",
 *         @OA\Property(property="items_count", type="integer"),
 *         @OA\Property(property="total_amount", type="number", format="float")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="CartCountResponse",
 *     @OA\Property(property="selected_items_count", type="integer"),
 *     @OA\Property(property="selected_total_amount", type="number", format="float")
 * )
 *
 * @OA\Schema(
 *     schema="FavoriteProduct",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="slug", type="string"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="price", type="number", format="float")
 * )
 *
 * @OA\Schema(
 *     schema="FavoriteListItem",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="product", ref="#/components/schemas/FavoriteProduct")
 * )
 *
 * @OA\Schema(
 *     schema="FavoritesListResponse",
 *     @OA\Property(property="page", type="integer"),
 *     @OA\Property(property="pages", type="integer"),
 *     @OA\Property(property="total", type="integer"),
 *     @OA\Property(property="items", type="array", @OA\Items(ref="#/components/schemas/FavoriteListItem"))
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
 *     @OA\Property(property="order_id", type="integer"),
 *     @OA\Property(property="delivery_method_id", type="integer", default=1),
 *     @OA\Property(property="city_fias_id", type="string"),
 *     @OA\Property(property="destination_id", type="string"),
 *     @OA\Property(property="destination_address", type="string"),
 *     @OA\Property(property="payment_method", type="string", default="cash"),
 *     @OA\Property(property="payment_time", type="string", nullable=true)
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
 *     summary="JWT-токены с автоматическим merge",
 *     value={
 *         "access_token": "eyJ...",
 *         "refresh_token": "eyJ...",
 *         "token_type": "bearer",
 *         "guest_sync": {
 *             "skipped": false,
 *             "cart": {"merged_items_count": 2, "result_cart_id": 15, "result_items_count": 3},
 *             "favorites": {"merged_count": 1, "result_total": 4}
 *         }
 *     }
 * )
 *
 * @OA\Examples(
 *     example="token-response-no-session",
 *     summary="JWT без session_id (merge пропущен)",
 *     value={
 *         "access_token": "eyJ...",
 *         "refresh_token": "eyJ...",
 *         "token_type": "bearer",
 *         "guest_sync": {"skipped": true, "reason": "no_session"}
 *     }
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
 *     summary="Код подтверждения (mock SMS)",
 *     value={"ok": true, "record_id": "550e8400-e29b-41d4-a716-446655440000", "activation_code": "123456"}
 * )
 *
 * @OA\Examples(
 *     example="phone-login-code-response",
 *     summary="Код входа по телефону (mock SMS)",
 *     value={"ok": true, "record_id": "550e8400-e29b-41d4-a716-446655440000", "code": "123456", "activation_code": "123456"}
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
 *         @OA\Schema(@OA\Property(property="detail", type="string", example="Unauthorized"))
 *     )
 * )
 *
 * @OA\Response(
 *     response="notFound",
 *     description="Ресурс не найден",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(@OA\Property(property="detail", type="string", example="Product not found"))
 *     )
 * )
 *
 * @OA\Response(
 *     response="validationError",
 *     description="Ошибка валидации",
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(
 *             @OA\Property(
 *                 property="detail",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="loc", type="array", @OA\Items(type="string")),
 *                     @OA\Property(property="msg", type="string"),
 *                     @OA\Property(property="type", type="string")
 *                 )
 *             )
 *         )
 *     )
 * )
 */
class ApiComponents
{
}
