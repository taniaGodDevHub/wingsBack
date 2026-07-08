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
 *     @OA\Property(property="email_confirmed", type="boolean"),
 *     @OA\Property(property="news_subscribed", type="boolean", description="Подписка на рассылку новостей на email из профиля")
 * )
 *
 * @OA\Schema(
 *     schema="ProfileUpdateRequest",
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="surname", type="string"),
 *     @OA\Property(property="gender", type="string"),
 *     @OA\Property(property="birth_date", type="string", format="date"),
 *     @OA\Property(property="password", type="string", format="password"),
 *     @OA\Property(property="news_subscribed", type="boolean", description="Подписка на рассылку новостей. Требуется email в профиле.")
 * )
 *
 * @OA\Schema(
 *     schema="NewsSubscriptionRequest",
 *     required={"news_subscribed"},
 *     @OA\Property(property="news_subscribed", type="boolean", description="true — подписать на рассылку, false — отписать")
 * )
 *
 * @OA\Schema(
 *     schema="NewsSubscriptionResponse",
 *     @OA\Property(property="ok", type="boolean", example=true),
 *     @OA\Property(property="news_subscribed", type="boolean", example=true)
 * )
 *
 * @OA\Schema(
 *     schema="UserAddress",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="is_pvz", type="boolean", description="ПВЗ СДЭК"),
 *     @OA\Property(property="pvz_code", type="string", nullable=true, description="Код ПВЗ (для is_pvz=true)"),
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
 *     @OA\Property(property="is_pvz", type="boolean"),
 *     @OA\Property(property="pvz_code", type="string", nullable=true),
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
 *     @OA\Property(property="code", type="string", example="blago2563", description="Уникальный код заказа"),
 *     @OA\Property(property="expires_at", type="integer", description="Unix timestamp"),
 *     @OA\Property(property="status", type="string", example="draft"),
 *     @OA\Property(property="blago_total", type="number", format="float", description="Сумма благо по товарам заказа")
 * )
 *
 * @OA\Schema(
 *     schema="OrderConfirmResponse",
 *     @OA\Property(property="order_id", type="integer"),
 *     @OA\Property(property="code", type="string", example="blago2563"),
 *     @OA\Property(property="status", type="string"),
 *     @OA\Property(property="payment_status", type="string"),
 *     @OA\Property(property="delivery_provider", type="string"),
 *     @OA\Property(property="delivery_cost", type="number", format="float"),
 *     @OA\Property(property="total_price", type="number", format="float"),
 *     @OA\Property(property="blago_total", type="number", format="float"),
 *     @OA\Property(property="payment_url", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="DaDataSuggestRequest",
 *     required={"query"},
 *     @OA\Property(property="query", type="string", example="Краснодар", description="Название города или полный адрес в одной строке"),
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
 *     @OA\Property(property="full_address", type="string", example="125009, г Москва, Тверской р-н, ул Тверская, д 7", description="Полный адрес с почтовым индексом"),
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
 *     description="Товар на витрине главной: полная карточка + флаги витрины",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/CatalogSearchProduct"),
 *         @OA\Schema(
 *             @OA\Property(property="is_bestseller", type="boolean"),
 *             @OA\Property(property="is_featured_home", type="boolean")
 *         )
 *     }
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
 *     schema="ShowcaseBlago",
 *     description="Блок «Благо» на главной",
 *     @OA\Property(property="title", type="string", example="Сбор на реабилитацию"),
 *     @OA\Property(property="collection_start_at", type="integer", example=1782864000, description="Unix timestamp начала сбора"),
 *     @OA\Property(property="collection_end_at", type="integer", example=1785542399, description="Unix timestamp конца сбора"),
 *     @OA\Property(property="amount", type="number", format="float", example=150000),
 *     @OA\Property(property="image_url", type="string", format="uri", example="https://example.com/uploads/blago/blago_a1b2c3d4.webp")
 * )
 *
 * @OA\Schema(
 *     schema="BlagoOrderInfoResponse",
 *     description="Данные заказа по коду благо",
 *     @OA\Property(property="order_id", type="integer", example=27, description="Номер заказа"),
 *     @OA\Property(property="code", type="string", example="blago7418"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-07-08T08:00:00+00:00", description="Дата создания заказа"),
 *     @OA\Property(property="total_price", type="number", format="float", example=5660),
 *     @OA\Property(property="blago_total", type="number", format="float", example=200)
 * )
 *
 * @OA\Schema(
 *     schema="ContactWorkHours",
 *     @OA\Property(property="from", type="string", example="10:00"),
 *     @OA\Property(property="to", type="string", example="22:00")
 * )
 *
 * @OA\Schema(
 *     schema="ContactInfoResponse",
 *     description="Контактные данные магазина",
 *     @OA\Property(property="phone", type="string", nullable=true, example="+7 (999) 123-45-67"),
 *     @OA\Property(property="email", type="string", format="email", nullable=true, example="info@wings.ru"),
 *     @OA\Property(property="telegram", type="string", nullable=true, example="@wings_shop"),
 *     @OA\Property(property="work_hours", ref="#/components/schemas/ContactWorkHours"),
 *     @OA\Property(property="work_hours_label", type="string", example="10:00–22:00")
 * )
 *
 * @OA\Schema(
 *     schema="ShowcaseAbout",
 *     description="Блок «О нас» на главной",
 *     @OA\Property(property="title", type="string", example="О нас"),
 *     @OA\Property(property="subtitle", type="string", nullable=true, example="Расширяем границы удобства"),
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
 *     description="Товар в каталоге, поиске и на витрине — полная карточка",
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
 *     @OA\Property(property="color", ref="#/components/schemas/CatalogProductColor", nullable=true),
 *     @OA\Property(
 *         property="attributes",
 *         type="array",
 *         description="Атрибуты товара и выбранные значения (цвет — в поле color)",
 *         @OA\Items(ref="#/components/schemas/CatalogProductAttribute")
 *     ),
 *     @OA\Property(
 *         property="size_chart",
 *         type="array",
 *         description="Полная таблица размеров товара (все строки справочника с флагом наличия)",
 *         @OA\Items(ref="#/components/schemas/CatalogProductSizeChartRow")
 *     ),
 *     @OA\Property(
 *         property="group",
 *         ref="#/components/schemas/CatalogProductGroup",
 *         nullable=true,
 *         description="Связанная группа вариантов; null, если товар не в группе"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="CatalogProductAttributeValue",
 *     description="Выбранное значение атрибута товара",
 *     @OA\Property(property="id", type="integer", example=1001, description="ID значения атрибута"),
 *     @OA\Property(property="name", type="string", example="Хлопок"),
 *     @OA\Property(property="slug", type="string", example="hlopok")
 * )
 *
 * @OA\Schema(
 *     schema="CatalogProductAttribute",
 *     description="Атрибут товара и его значение (кроме цвета — см. поле color)",
 *     @OA\Property(property="id", type="integer", example=12, description="ID атрибута"),
 *     @OA\Property(property="name", type="string", example="Материал"),
 *     @OA\Property(property="slug", type="string", example="material"),
 *     @OA\Property(property="code", type="string", nullable=true, example=null, description="Служебный код атрибута; для цвета — color (в attributes не включается)"),
 *     @OA\Property(property="value", ref="#/components/schemas/CatalogProductAttributeValue")
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
 *     description="Карточка товара. Используется в GET /api/catalog/product/{slug}, поиске, витрине и универсальном поиске",
 *     ref="#/components/schemas/CatalogSearchProduct",
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
 *         "attributes": {
 *             {"id": 12, "name": "Материал", "slug": "material", "code": null, "value": {"id": 1001, "name": "Хлопок", "slug": "hlopok"}},
 *             {"id": 13, "name": "Сезон", "slug": "season", "code": null, "value": {"id": 2001, "name": "Лето", "slug": "leto"}}
 *         },
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
 *     @OA\Property(property="blago_amount", type="number", format="float", description="Благо по позиции (product.blago × quantity)"),
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
 *         @OA\Property(property="total_amount", type="number", format="float"),
 *         @OA\Property(property="blago_total", type="number", format="float", description="Сумма благо по всем товарам корзины")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="CartCountResponse",
 *     @OA\Property(property="selected_items_count", type="integer"),
 *     @OA\Property(property="selected_total_amount", type="number", format="float"),
 *     @OA\Property(property="selected_blago_total", type="number", format="float")
 * )
 *
 * @OA\Schema(
 *     schema="FavoriteProduct",
 *     description="Товар в избранном — полная карточка, как GET /api/catalog/product/{slug}",
 *     ref="#/components/schemas/CatalogProductDetail"
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
 *     @OA\Property(property="code", type="string", example="blago2563"),
 *     @OA\Property(property="expires_at", type="integer"),
 *     @OA\Property(property="blago_total", type="number", format="float")
 * )
 *
 * @OA\Schema(
 *     schema="OrderStatus",
 *     type="string",
 *     enum={"draft","awaiting_payment","processing","delivering","shipped","delivered","completed","cancelled","returned"},
 *     example="processing"
 * )
 *
 * @OA\Schema(
 *     schema="OrderTimelineStep",
 *     @OA\Property(property="key", type="string", enum={"ordered","assembly","delivery"}, example="ordered"),
 *     @OA\Property(property="label", type="string", example="дата заказа"),
 *     @OA\Property(property="date", type="string", nullable=true, description="ISO 8601 или строка даты доставки", example="2026-04-01T00:00:00+00:00"),
 *     @OA\Property(property="completed", type="boolean", example=true)
 * )
 *
 * @OA\Schema(
 *     schema="OrderProductImage",
 *     @OA\Property(property="url", type="string", example="https://e-wings.ru/uploads/product/1.jpg")
 * )
 *
 * @OA\Schema(
 *     schema="OrderProductInfo",
 *     @OA\Property(property="name", type="string", example="Худи Wings"),
 *     @OA\Property(property="brand", type="string", nullable=true, example="Wings"),
 *     @OA\Property(property="product_code", type="string", nullable=true, example="WH-001"),
 *     @OA\Property(
 *         property="images",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/OrderProductImage")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="OrderTracking",
 *     @OA\Property(property="provider", type="string", example="cdek"),
 *     @OA\Property(property="track_number", type="string", nullable=true, example="10123456789"),
 *     @OA\Property(property="current_status", type="string", nullable=true),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="current_city", type="string", nullable=true),
 *     @OA\Property(property="updated_at", type="string", nullable=true, format="date-time"),
 *     @OA\Property(property="expected_delivery", type="string", nullable=true, example="2026-04-02", description="Примерная дата доставки"),
 *     @OA\Property(property="delivery_date", type="string", nullable=true, example="2026-04-05", description="Фактическая дата вручения; заполняется после доставки")
 * )
 *
 * @OA\Schema(
 *     schema="OrderListItemBase",
 *     description="Общие поля карточки заказа в личном кабинете",
 *     @OA\Property(property="id", type="integer", example=123456789, description="Номер заказа"),
 *     @OA\Property(property="code", type="string", example="blago2563", description="Уникальный код заказа"),
 *     @OA\Property(property="status", ref="#/components/schemas/OrderStatus"),
 *     @OA\Property(property="status_label", type="string", example="В ОБРАБОТКЕ", description="Подпись статуса для бейджа"),
 *     @OA\Property(property="payment_status", type="string", example="pending"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Дата заказа"),
 *     @OA\Property(property="completed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="estimated_delivery", type="string", nullable=true, example="2026-04-02", description="Примерная дата доставки"),
 *     @OA\Property(property="delivery_address", type="string", nullable=true),
 *     @OA\Property(property="total_price", type="number", format="float", example=7000, description="Сумма заказа"),
 *     @OA\Property(property="blago_total", type="number", format="float", example=350, description="Сумма благо по товарам"),
 *     @OA\Property(property="items_count", type="integer", example=2, description="Общее количество товаров (состав / N шт.)"),
 *     @OA\Property(property="show_details", type="boolean", example=false, description="Показывать ссылку «ПОДРОБНЕЕ» (true для выполненных)"),
 *     @OA\Property(
 *         property="timeline_steps",
 *         type="array",
 *         description="Таймлайн: дата заказа → сборка → доставка",
 *         @OA\Items(ref="#/components/schemas/OrderTimelineStep")
 *     ),
 *     @OA\Property(property="tracking", ref="#/components/schemas/OrderTracking", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="OrderListItemPurchase",
 *     description="Карточка заказа в разделе «Мои заказы»",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/OrderListItemBase"),
 *         @OA\Schema(
 *             @OA\Property(
 *                 property="items",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="integer"),
 *                     @OA\Property(property="product_id", type="integer"),
 *                     @OA\Property(property="quantity", type="integer"),
 *                     @OA\Property(property="unit_price", type="number", format="float"),
 *                     @OA\Property(property="total_price", type="number", format="float"),
 *                     @OA\Property(property="product_info", ref="#/components/schemas/OrderProductInfo")
 *                 )
 *             )
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="OrderListItemDelivery",
 *     description="Карточка заказа в разделе доставки",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/OrderListItemBase"),
 *         @OA\Schema(
 *             @OA\Property(
 *                 property="items",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="integer"),
 *                     @OA\Property(property="product_id", type="integer"),
 *                     @OA\Property(property="product_info", ref="#/components/schemas/OrderProductInfo")
 *                 )
 *             )
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="OrderPurchasesResponse",
 *     description="Список заказов для раздела «Мои заказы»",
 *     @OA\Property(
 *         property="orders",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/OrderListItemPurchase")
 *     ),
 *     @OA\Property(
 *         property="available_filters",
 *         type="object",
 *         @OA\Property(
 *             property="filters",
 *             type="array",
 *             @OA\Items(type="object"),
 *             example={}
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="OrderDeliveriesResponse",
 *     description="Список заказов в доставке",
 *     @OA\Property(
 *         property="orders",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/OrderListItemDelivery")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="OrderDetailsItem",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="order_item_id", type="integer"),
 *     @OA\Property(property="product_id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="quantity", type="integer"),
 *     @OA\Property(property="unit_price", type="number", format="float"),
 *     @OA\Property(property="delivery_label", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="OrderDetailsResponse",
 *     description="Детальная информация о заказе (экран «ПОДРОБНЕЕ»)",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/OrderListItemBase"),
 *         @OA\Schema(
 *             @OA\Property(property="expires_at", type="integer", nullable=true),
 *             @OA\Property(property="delivery_provider", type="string", nullable=true),
 *             @OA\Property(property="delivery_method_code", type="string", nullable=true),
 *             @OA\Property(
 *                 property="items",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/OrderDetailsItem")
 *             )
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="CdekDeliveryOption",
 *     @OA\Property(property="id", type="integer", example=1, description="1 — ПВЗ, 2 — курьер"),
 *     @OA\Property(property="name", type="string", example="СДЭК до ПВЗ"),
 *     @OA\Property(property="code", type="string", example="cdek_pvz"),
 *     @OA\Property(property="is_pvz", type="boolean"),
 *     @OA\Property(property="price", type="number", format="float", example=350),
 *     @OA\Property(property="period_min", type="integer", example=2),
 *     @OA\Property(property="period_max", type="integer", example=4),
 *     @OA\Property(property="tariff_code", type="integer", example=136)
 * )
 *
 * @OA\Schema(
 *     schema="CdekPvzPoint",
 *     @OA\Property(property="code", type="string", example="MSK1"),
 *     @OA\Property(property="name", type="string", example="СДЭК ПВЗ Тверская"),
 *     @OA\Property(property="address", type="string"),
 *     @OA\Property(property="work_time", type="string"),
 *     @OA\Property(property="lat", type="number", format="float", nullable=true),
 *     @OA\Property(property="lon", type="number", format="float", nullable=true),
 *     @OA\Property(property="city_code", type="integer"),
 *     @OA\Property(property="distance_km", type="number", format="float", nullable=true, description="Расстояние от geo_lat/geo_lon в км (если координаты переданы)")
 * )
 *
 * @OA\Schema(
 *     schema="CdekPvzListMeta",
 *     @OA\Property(property="page", type="integer", example=1, description="Текущая страница"),
 *     @OA\Property(property="count", type="integer", example=10, description="Количество пунктов в data"),
 *     @OA\Property(property="has_more", type="boolean", example=true, description="Есть ли следующая страница — запросите page+1")
 * )
 *
 * @OA\Schema(
 *     schema="CdekPvzListResponse",
 *     @OA\Property(property="status", type="string", example="success"),
 *     @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CdekPvzPoint")),
 *     @OA\Property(property="meta", ref="#/components/schemas/CdekPvzListMeta")
 * )
 *
 * @OA\Schema(
 *     schema="DeliveryCalculateItem",
 *     @OA\Property(property="order_item_id", type="integer"),
 *     @OA\Property(property="product_id", type="integer"),
 *     @OA\Property(property="delivery_label", type="string", example="Доставка СДЭК 2-4 дн.")
 * )
 *
 * @OA\Schema(
 *     schema="OrderConfirmRequest",
 *     required={"city_fias_id","destination_id","destination_address"},
 *     @OA\Property(property="order_id", type="integer"),
 *     @OA\Property(property="delivery_method_id", type="integer", default=1, description="1 — ПВЗ, 2 — курьер"),
 *     @OA\Property(property="city_fias_id", type="string"),
 *     @OA\Property(property="destination_id", type="string", description="pvz_code для ПВЗ или id подсказки адреса"),
 *     @OA\Property(property="destination_address", type="string"),
 *     @OA\Property(property="pvz_code", type="string", nullable=true, description="Код ПВЗ при доставке до пункта выдачи"),
 *     @OA\Property(property="is_pvz", type="boolean", nullable=true),
 *     @OA\Property(property="payment_method", type="string", default="cash"),
 *     @OA\Property(property="payment_time", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="DeliveryCalculateRequest",
 *     required={"order_id","city_fias_id"},
 *     @OA\Property(property="order_id", type="integer"),
 *     @OA\Property(property="city_fias_id", type="string"),
 *     @OA\Property(property="delivery_method_id", type="integer", default=1, description="1 — ПВЗ, 2 — курьер")
 * )
 *
 * @OA\Schema(
 *     schema="DeliveryCalculateResponse",
 *     @OA\Property(property="provider", type="string", example="cdek"),
 *     @OA\Property(property="method_code", type="string", example="cdek_pvz"),
 *     @OA\Property(property="delivery_cost", type="number", format="float", example=350),
 *     @OA\Property(property="period_min", type="integer", example=2),
 *     @OA\Property(property="period_max", type="integer", example=4),
 *     @OA\Property(property="total_with_delivery", type="number", format="float", example=7350),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/DeliveryCalculateItem")
 *     )
 * )
 *
 * @OA\Examples(
 *     example="dadata-suggest-address-request",
 *     summary="Запрос подсказок адреса",
 *     value={"query": "Москва Тверская 7", "count": 3}
 * )
 *
 * @OA\Examples(
 *     example="dadata-suggest-address-response",
 *     summary="Подсказки адреса из DaData (production)",
 *     value={
 *         "status": "success",
 *         "data": {
 *             {
 *                 "value": "г Москва, ул Тверская, д 7",
 *                 "full_address": "125009, г Москва, Тверской р-н, ул Тверская, д 7",
 *                 "postal_code": "125009",
 *                 "city_name": "г Москва",
 *                 "data": {
 *                     "city_fias_id": "0c5b2444-70a0-4932-980c-b4dc0d3f02b5",
 *                     "address_fias_id": "cb983f95-4865-4320-bba0-ab6edc396ba5",
 *                     "house_fias_id": "cb983f95-4865-4320-bba0-ab6edc396ba5",
 *                     "geo_lat": "55.7579795",
 *                     "geo_lon": "37.611263"
 *                 },
 *                 "pvz_code": null
 *             },
 *             {
 *                 "value": "г Москва, ул 1-я Тверская-Ямская, д 7",
 *                 "full_address": "125047, г Москва, Тверской р-н, ул 1-я Тверская-Ямская, д 7",
 *                 "postal_code": "125047",
 *                 "city_name": "г Москва",
 *                 "data": {
 *                     "city_fias_id": "0c5b2444-70a0-4932-980c-b4dc0d3f02b5",
 *                     "address_fias_id": "044a7767-7764-45eb-ba30-1adc8c8f06ad",
 *                     "house_fias_id": "044a7767-7764-45eb-ba30-1adc8c8f06ad",
 *                     "geo_lat": "55.773444",
 *                     "geo_lon": "37.584725"
 *                 },
 *                 "pvz_code": null
 *             }
 *         }
 *     }
 * )
 *
 * @OA\Examples(
 *     example="cdek-calculate-mock",
 *     summary="Расчёт доставки СДЭК (mock до подключения ЛК)",
 *     value={
 *         "provider": "cdek",
 *         "method_code": "cdek_pvz",
 *         "delivery_cost": 350,
 *         "period_min": 2,
 *         "period_max": 4,
 *         "total_with_delivery": 7350,
 *         "items": {
 *             {"order_item_id": 1, "product_id": 10, "delivery_label": "Доставка СДЭК 2-4 дн."}
 *         }
 *     }
 * )
 *
 * @OA\Examples(
 *     example="cdek-pvz-page1",
 *     summary="ПВЗ: первая страница (только город)",
 *     value={
 *         "status": "success",
 *         "data": {
 *             {
 *                 "code": "MSK2",
 *                 "name": "MSK2, Москва, ул. Международная",
 *                 "address": "ул. Международная, 15",
 *                 "work_time": "Пн-Пт 10:00-20:00",
 *                 "lat": 55.7558,
 *                 "lon": 37.6173,
 *                 "city_code": 44
 *             }
 *         },
 *         "meta": {"page": 1, "count": 10, "has_more": true}
 *     }
 * )
 *
 * @OA\Examples(
 *     example="cdek-pvz-page2",
 *     summary="ПВЗ: следующая страница (page=2)",
 *     value={
 *         "status": "success",
 *         "data": {
 *             {
 *                 "code": "MSK29",
 *                 "name": "MSK29, Москва",
 *                 "address": "ул. Примерная, 1",
 *                 "work_time": "Ежедневно 09:00-21:00",
 *                 "lat": 55.75,
 *                 "lon": 37.62,
 *                 "city_code": 44
 *             }
 *         },
 *         "meta": {"page": 2, "count": 10, "has_more": true}
 *     }
 * )
 *
 * @OA\Examples(
 *     example="cdek-pvz-geo",
 *     summary="ПВЗ рядом с уточнённым адресом (geo_lat/geo_lon из DaData)",
 *     value={
 *         "status": "success",
 *         "data": {
 *             {
 *                 "code": "MSK11",
 *                 "name": "СДЭК ПВЗ Тверская",
 *                 "address": "г Москва, ул Тверская, д 7",
 *                 "work_time": "Пн-Пт 10:00-20:00",
 *                 "lat": 55.7641,
 *                 "lon": 37.6054,
 *                 "city_code": 44,
 *                 "distance_km": 0.8
 *             }
 *         },
 *         "meta": {"page": 1, "count": 10, "has_more": false}
 *     }
 * )
 *
 * @OA\Examples(
 *     example="cdek-pvz-mock",
 *     summary="Список ПВЗ СДЭК (устаревший формат, см. cdek-pvz-page1)",
 *     value={
 *         "status": "success",
 *         "data": {
 *             {
 *                 "code": "MSK1",
 *                 "name": "СДЭК ПВЗ Тверская",
 *                 "address": "г Москва, ул Тверская, д 7",
 *                 "work_time": "Пн-Пт 10:00-20:00, Сб-Вс 10:00-18:00",
 *                 "lat": 55.7641,
 *                 "lon": 37.6054,
 *                 "city_code": 44
 *             },
 *             {
 *                 "code": "MSK2",
 *                 "name": "СДЭК ПВЗ Арбат",
 *                 "address": "г Москва, ул Арбат, д 12",
 *                 "work_time": "Ежедневно 09:00-21:00",
 *                 "lat": 55.7520,
 *                 "lon": 37.5925,
 *                 "city_code": 44
 *             }
 *         },
 *         "meta": {"page": 1, "count": 2, "has_more": false}
 *     }
 * )
 *
 * @OA\Examples(
 *     example="cdek-delivery-options-mock",
 *     summary="Способы доставки СДЭК (mock до подключения ЛК)",
 *     value={
 *         {
 *             "id": 1,
 *             "name": "СДЭК до ПВЗ",
 *             "code": "cdek_pvz",
 *             "is_pvz": true,
 *             "price": 350,
 *             "period_min": 2,
 *             "period_max": 4,
 *             "tariff_code": 136
 *         },
 *         {
 *             "id": 2,
 *             "name": "СДЭК курьером",
 *             "code": "cdek_courier",
 *             "is_pvz": false,
 *             "price": 490,
 *             "period_min": 2,
 *             "period_max": 4,
 *             "tariff_code": 137
 *         }
 *     }
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
 * @OA\Examples(
 *     example="order-create-response",
 *     summary="Создание черновика заказа",
 *     value={
 *         "order_id": 42,
 *         "code": "blago2563",
 *         "expires_at": 1751800000,
 *         "status": "draft",
 *         "blago_total": 350
 *     }
 * )
 *
 * @OA\Examples(
 *     example="cart-list-with-blago",
 *     summary="Корзина с суммой благо",
 *     value={
 *         "cart_id": 7,
 *         "items": {
 *             {
 *                 "product_id": 10,
 *                 "size_value": "M",
 *                 "cart_id": 7,
 *                 "quantity": 2,
 *                 "unit_price": 3500,
 *                 "price_show": 3500,
 *                 "blago_amount": 350,
 *                 "product_info": {
 *                     "id": 10,
 *                     "slug": "oversize-hoodie-black",
 *                     "name": "Худи Wings",
 *                     "brand": "Wings",
 *                     "images": {{"url": "https://e-wings.ru/uploads/product/10.jpg"}}
 *                 }
 *             }
 *         },
 *         "summary": {
 *             "items_count": 2,
 *             "total_amount": 7000,
 *             "blago_total": 350
 *         }
 *     }
 * )
 *
 * @OA\Examples(
 *     example="order-processing-card",
 *     summary="Карточка заказа «В обработке»",
 *     value={
 *         "id": 123456789,
 *         "code": "blago2563",
 *         "status": "processing",
 *         "status_label": "В ОБРАБОТКЕ",
 *         "payment_status": "pending",
 *         "created_at": "2026-04-01T10:00:00+00:00",
 *         "completed_at": null,
 *         "estimated_delivery": "2026-04-02",
 *         "delivery_address": "г Москва, ул Тверская, д 7",
 *         "total_price": 7000,
 *         "blago_total": 350,
 *         "items_count": 2,
 *         "show_details": false,
 *         "timeline_steps": {
 *             {"key": "ordered", "label": "дата заказа", "date": "2026-04-01T10:00:00+00:00", "completed": true},
 *             {"key": "assembly", "label": "сборка", "date": null, "completed": false},
 *             {"key": "delivery", "label": "Примерная доставка", "date": "2026-04-02", "completed": false}
 *         },
 *         "items": {
 *             {
 *                 "id": 1,
 *                 "product_id": 10,
 *                 "quantity": 1,
 *                 "unit_price": 3500,
 *                 "total_price": 3500,
 *                 "product_info": {"name": "Худи Wings", "brand": "Wings", "product_code": "WH-001", "images": {{"url": "https://e-wings.ru/uploads/product/10.jpg"}}}
 *             },
 *             {
 *                 "id": 2,
 *                 "product_id": 10,
 *                 "quantity": 1,
 *                 "unit_price": 3500,
 *                 "total_price": 3500,
 *                 "product_info": {"name": "Худи Wings", "brand": "Wings", "product_code": "WH-001", "images": {{"url": "https://e-wings.ru/uploads/product/10.jpg"}}}
 *             }
 *         }
 *     }
 * )
 *
 * @OA\Examples(
 *     example="order-completed-card",
 *     summary="Карточка заказа «Выполнен»",
 *     value={
 *         "id": 123456789,
 *         "code": "blago4821",
 *         "status": "completed",
 *         "status_label": "ВЫПОЛНЕН",
 *         "payment_status": "paid",
 *         "created_at": "2026-04-01T10:00:00+00:00",
 *         "completed_at": "2026-04-02T14:00:00+00:00",
 *         "estimated_delivery": "2026-04-02",
 *         "delivery_address": "г Москва, ул Тверская, д 7",
 *         "total_price": 7000,
 *         "blago_total": 420,
 *         "items_count": 4,
 *         "show_details": true,
 *         "timeline_steps": {
 *             {"key": "ordered", "label": "дата заказа", "date": "2026-04-01T10:00:00+00:00", "completed": true},
 *             {"key": "assembly", "label": "сборка", "date": null, "completed": true},
 *             {"key": "delivery", "label": "Выполнен", "date": "2026-04-02T14:00:00+00:00", "completed": true}
 *         },
 *         "items": {
 *             {
 *                 "id": 1,
 *                 "product_id": 10,
 *                 "quantity": 2,
 *                 "unit_price": 1750,
 *                 "total_price": 3500,
 *                 "product_info": {"name": "Худи Wings", "brand": "Wings", "product_code": "WH-001", "images": {{"url": "https://e-wings.ru/uploads/product/10.jpg"}}}
 *             },
 *             {
 *                 "id": 2,
 *                 "product_id": 11,
 *                 "quantity": 2,
 *                 "unit_price": 1750,
 *                 "total_price": 3500,
 *                 "product_info": {"name": "Худи Wings Blue", "brand": "Wings", "product_code": "WH-002", "images": {{"url": "https://e-wings.ru/uploads/product/11.jpg"}}}
 *             }
 *         }
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="NewsArticleListResponse",
 *     description="Постраничный список опубликованных статей",
 *     @OA\Property(property="page", type="integer", example=1),
 *     @OA\Property(property="pages", type="integer", example=3),
 *     @OA\Property(property="total", type="integer", example=42),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/NewsArticleCard")
 *     ),
 *     example={
 *         "page": 1,
 *         "pages": 2,
 *         "total": 3,
 *         "items": {
 *             {
 *                 "id": 3,
 *                 "title": "ОТКРЫТИЕ НОВОГО МАГАЗИНА",
 *                 "slug": "otkrytie-novogo-magazina",
 *                 "image_url": "http://example.com/uploads/news/news_abc.png"
 *             }
 *         }
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="NewsArticleCard",
 *     description="Краткая карточка статьи (блок последних публикаций)",
 *     @OA\Property(property="id", type="integer", example=4),
 *     @OA\Property(property="title", type="string", example="Новая коллекция"),
 *     @OA\Property(property="slug", type="string", example="novaya-kollektsiya"),
 *     @OA\Property(
 *         property="image_url",
 *         type="string",
 *         example="http://example.com/uploads/news/news_abc.png",
 *         description="Абсолютный URL изображения; пустая строка, если фото не задано"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="NewsArticleDetail",
 *     description="Полная опубликованная статья",
 *     @OA\Property(property="id", type="integer", example=3),
 *     @OA\Property(property="title", type="string", example="ОТКРЫТИЕ НОВОГО МАГАЗИНА"),
 *     @OA\Property(property="slug", type="string", example="otkrytie-novogo-magazina"),
 *     @OA\Property(property="subtitle", type="string", nullable=true, example="Расширяем границы удобства"),
 *     @OA\Property(property="text", type="string", nullable=true, description="Текст статьи"),
 *     @OA\Property(
 *         property="image_url",
 *         type="string",
 *         example="http://example.com/uploads/news/news_abc.png",
 *         description="Абсолютный URL обложки; пустая строка, если фото не задано"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="integer",
 *         example=1782987000,
 *         description="Unix timestamp даты создания/публикации"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="NewsArticleResponse",
 *     description="Статья по slug и до 3 последних опубликованных статей (кроме текущей), отсортированных по дате создания",
 *     @OA\Property(property="article", ref="#/components/schemas/NewsArticleDetail"),
 *     @OA\Property(
 *         property="latest",
 *         type="array",
 *         maxItems=3,
 *         @OA\Items(ref="#/components/schemas/NewsArticleCard")
 *     ),
 *     example={
 *         "article": {
 *             "id": 3,
 *             "title": "ОТКРЫТИЕ НОВОГО МАГАЗИНА",
 *             "slug": "otkrytie-novogo-magazina",
 *             "subtitle": "Расширяем границы удобства",
 *             "text": "Текст статьи...",
 *             "image_url": "http://example.com/uploads/news/news_abc.png",
 *             "created_at": 1782987000
 *         },
 *         "latest": {
 *             {
 *                 "id": 4,
 *                 "title": "Новая коллекция",
 *                 "slug": "novaya-kollektsiya",
 *                 "image_url": "http://example.com/uploads/news/news_def.png"
 *             }
 *         }
 *     }
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
