<?php

declare(strict_types=1);

namespace app\services;

use app\models\News;
use Yii;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

final class NewsImageUploadService
{
    public const MAX_BYTES = 15_728_640;

    public const MAX_MEGABYTES = 15;

    /** @var list<string> */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function uploadDirectory(): string
    {
        return Yii::getAlias('@uploads/news');
    }

    public function upload(News $news, UploadedFile $file): ?string
    {
        if ($file->hasError) {
            return Yii::t('app', 'Failed to upload file «{name}».', ['name' => $file->name]);
        }

        if ($file->size > self::MAX_BYTES) {
            return Yii::t('app', 'File «{name}» exceeds the maximum size of {max} MB.', [
                'name' => $file->name,
                'max' => self::MAX_MEGABYTES,
            ]);
        }

        $extension = strtolower((string) pathinfo($file->name, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return Yii::t('app', 'File «{name}» has an unsupported format.', ['name' => $file->name]);
        }

        $mime = (string) $file->type;
        if ($mime !== '' && !str_starts_with($mime, 'image/')) {
            return Yii::t('app', 'File «{name}» must be an image.', ['name' => $file->name]);
        }

        FileHelper::createDirectory($this->uploadDirectory());
        $directoryError = $this->ensureUploadDirectoryWritable();
        if ($directoryError !== null) {
            return $directoryError;
        }

        $filename = sprintf('news_%s.%s', bin2hex(random_bytes(8)), $extension);
        $path = $this->uploadDirectory() . DIRECTORY_SEPARATOR . $filename;
        if (!$file->saveAs($path, false)) {
            return Yii::t('app', 'Failed to save file «{name}».', ['name' => $file->name]);
        }

        $this->removeLocalFileIfOwned($news->image_url);
        $news->image_url = 'uploads/news/' . $filename;

        return null;
    }

    public function removeLocalFileIfOwned(?string $imageUrl): void
    {
        if ($imageUrl === null || $imageUrl === '' || !str_starts_with($imageUrl, 'uploads/news/')) {
            return;
        }

        $path = Yii::getAlias('@webroot/' . $imageUrl);
        if (is_file($path)) {
            @unlink($path);
        }
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
