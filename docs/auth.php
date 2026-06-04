<?php

declare(strict_types=1);

namespace app\docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Auth", description="Registration and login")
 *
 * @OA\Schema(
 *     schema="CheckUserRequest",
 *     required={"is_email"},
 *     @OA\Property(property="phone_number", type="string", example="+79991234567"),
 *     @OA\Property(property="email", type="string", example="user@example.com"),
 *     @OA\Property(property="is_email", type="boolean")
 * )
 * @OA\Schema(
 *     schema="CheckUserResponse",
 *     @OA\Property(property="register", type="boolean"),
 *     @OA\Property(property="command", type="string")
 * )
 * @OA\Schema(
 *     schema="ChallengeOkResponse",
 *     @OA\Property(property="ok", type="boolean", example=true),
 *     @OA\Property(property="record_id", type="string", format="uuid"),
 *     @OA\Property(property="activation_code", type="string", example="123456")
 * )
 * @OA\Schema(
 *     schema="EmailLoginCodeResponse",
 *     @OA\Property(property="record_id", type="string", format="uuid"),
 *     @OA\Property(property="code", type="string", example="123456")
 * )
 * @OA\Schema(
 *     schema="TokenResponse",
 *     @OA\Property(property="access_token", type="string"),
 *     @OA\Property(property="refresh_token", type="string"),
 *     @OA\Property(property="token_type", type="string", example="bearer")
 * )
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
 * @OA\Post(
 *     path="/api/auth/check_user",
 *     tags={"Auth"},
 *     summary="Check if user exists",
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CheckUserRequest")),
 *     @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/CheckUserResponse"))
 * )
 * @OA\Post(
 *     path="/api/auth/phone_registration_confirmed",
 *     tags={"Auth"},
 *     summary="Start phone registration",
 *     @OA\Parameter(name="phone_number", in="query", required=true, @OA\Schema(type="string")),
 *     @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/ChallengeOkResponse"))
 * )
 * @OA\Post(
 *     path="/api/auth/phone_login_get_code",
 *     tags={"Auth"},
 *     summary="Request phone login code",
 *     @OA\Parameter(name="phone_number", in="query", required=true, @OA\Schema(type="string")),
 *     @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/ChallengeOkResponse"))
 * )
 * @OA\Post(
 *     path="/api/auth/email_registration_confirmed",
 *     tags={"Auth"},
 *     summary="Start email registration",
 *     @OA\Parameter(name="email", in="query", required=true, @OA\Schema(type="string")),
 *     @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/ChallengeOkResponse"))
 * )
 * @OA\Post(
 *     path="/api/auth/email_login_get_code",
 *     tags={"Auth"},
 *     summary="Request email login code",
 *     @OA\Parameter(name="email", in="query", @OA\Schema(type="string")),
 *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="email", type="string"))),
 *     @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/EmailLoginCodeResponse"))
 * )
 * @OA\Post(
 *     path="/api/auth/verify_phone_registration",
 *     tags={"Auth"},
 *     summary="Verify phone registration",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"phone_number","code","record_id"},
 *             @OA\Property(property="phone_number", type="string"),
 *             @OA\Property(property="code", type="string"),
 *             @OA\Property(property="record_id", type="string")
 *         )
 *     ),
 *     @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/TokenResponse"))
 * )
 * @OA\Post(
 *     path="/api/auth/verify_email_registration",
 *     tags={"Auth"},
 *     summary="Verify email registration",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email","code","record_id"},
 *             @OA\Property(property="email", type="string"),
 *             @OA\Property(property="code", type="string"),
 *             @OA\Property(property="record_id", type="string")
 *         )
 *     ),
 *     @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/TokenResponse"))
 * )
 * @OA\Post(
 *     path="/api/auth/login_phone_with_code",
 *     tags={"Auth"},
 *     summary="Login by phone code",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"code","record_id"},
 *             @OA\Property(property="code", type="string"),
 *             @OA\Property(property="record_id", type="string")
 *         )
 *     ),
 *     @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/TokenResponse"))
 * )
 * @OA\Post(
 *     path="/api/auth/login_email_with_code",
 *     tags={"Auth"},
 *     summary="Login by email code",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"code","record_id"},
 *             @OA\Property(property="code", type="string"),
 *             @OA\Property(property="record_id", type="string")
 *         )
 *     ),
 *     @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/TokenResponse"))
 * )
 * @OA\Post(
 *     path="/api/auth/refresh_token",
 *     tags={"Auth"},
 *     summary="Refresh JWT tokens",
 *     security={{"refreshToken":{}}},
 *     @OA\RequestBody(
 *         @OA\JsonContent(@OA\Property(property="refresh_token", type="string"))
 *     ),
 *     @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/TokenResponse"))
 * )
 * @OA\Get(
 *     path="/api/auth/my",
 *     tags={"Auth"},
 *     summary="Current user profile",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/UserProfileResponse"))
 * )
 */
class AuthApiDoc
{
}
