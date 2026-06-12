<?php

declare(strict_types=1);

namespace app\services;

use app\models\HomeBanner;
use Yii;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

final class HomeBannerUploadService
{
    private const MAX_BYTES = 5_242_880;

    /** @var list<string> */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function uploadDirectory(): string
    {
        return Yii::getAlias('@uploads/banners');
    }

    public function upload(HomeBanner $banner, UploadedFile $file): ?string
    {
        if ($file->hasError) {
            return Yii::t('app', 'Failed to upload file «{name}».', ['name' => $file->name]);
        }

        if ($file->size > self::MAX_BYTES) {
            return Yii::t('app', 'File «{name}» exceeds the maximum size of 5 MB.', ['name' => $file->name]);
        }

        $extension = $this->resolveExtension($file);
        if ($extension === null) {
            return Yii::t('app', 'File «{name}» has an unsupported format.', ['name' => $file->name]);
        }

        $mime = (string) $file->type;
        if ($mime !== '' && !str_starts_with($mime, 'image/')) {
            return Yii::t('app', 'File «{name}» must be an image.', ['name' => $file->name]);
        }

        FileHelper::createDirectory($this->uploadDirectory());
        $writableError = $this->ensureUploadDirectoryWritable();
        if ($writableError !== null) {
            return $writableError;
        }

        $filename = sprintf('banner_%s.%s', bin2hex(random_bytes(8)), $extension);
        $path = $this->uploadDirectory() . DIRECTORY_SEPARATOR . $filename;

        if (!$file->saveAs($path, false)) {
            return Yii::t('app', 'Failed to save file «{name}».', ['name' => $file->name]);
        }

        $this->removeLocalFileIfOwned($banner->image_url);
        $banner->image_url = 'uploads/banners/' . $filename;

        return null;
    }

    public function removeLocalFileIfOwned(?string $imageUrl): void
    {
        if ($imageUrl === null || $imageUrl === '') {
            return;
        }

        $prefix = 'uploads/banners/';
        if (!str_starts_with($imageUrl, $prefix)) {
            return;
        }

        $path = Yii::getAlias('@webroot/' . $imageUrl);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function resolveExtension(UploadedFile $file): ?string
    {
        $extension = strtolower((string) pathinfo($file->name, PATHINFO_EXTENSION));
        if (in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return $extension;
        }

        $mime = strtolower((string) $file->type);
        $byMime = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/x-webp' => 'webp',
        ];
        if (isset($byMime[$mime])) {
            return $byMime[$mime];
        }

        $detectedMime = FileHelper::getMimeType($file->tempName, null, false);
        if (is_string($detectedMime) && isset($byMime[strtolower($detectedMime)])) {
            return $byMime[strtolower($detectedMime)];
        }

        return null;
    }

    private function ensureUploadDirectoryWritable(): ?string
    {
        $directory = $this->uploadDirectory();
        if (is_writable($directory)) {
            return null;
        }

        return Yii::t('app', 'Upload directory is not writable: {path}', ['path' => $directory]);
    }
}
