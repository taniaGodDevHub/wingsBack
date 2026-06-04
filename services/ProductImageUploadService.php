<?php

declare(strict_types=1);

namespace app\services;

use app\models\Product;
use app\models\ProductImage;
use Yii;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

final class ProductImageUploadService
{
    private const MAX_BYTES = 5_242_880;

    /** @var list<string> */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function uploadDirectory(): string
    {
        return Yii::getAlias('@uploads/products');
    }

    /**
     * @param UploadedFile[] $files
     * @return list<string> error messages
     */
    public function uploadMany(Product $product, array $files): array
    {
        $errors = [];
        $files = array_values(array_filter(
            $files,
            static fn ($file): bool => $file instanceof UploadedFile && $file->error !== UPLOAD_ERR_NO_FILE,
        ));

        if ($files === []) {
            return [];
        }

        FileHelper::createDirectory($this->uploadDirectory());

        $directoryError = $this->ensureUploadDirectoryWritable();
        if ($directoryError !== null) {
            return [$directoryError];
        }

        foreach ($files as $file) {
            $error = $this->uploadOne($product, $file);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    public function uploadOne(Product $product, UploadedFile $file): ?string
    {
        if ($file->hasError) {
            return Yii::t('app', 'Failed to upload file «{name}».', ['name' => $file->name]);
        }

        if ($file->size > self::MAX_BYTES) {
            return Yii::t('app', 'File «{name}» exceeds the maximum size of 5 MB.', ['name' => $file->name]);
        }

        $extension = strtolower((string) pathinfo($file->name, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return Yii::t('app', 'File «{name}» has an unsupported format.', ['name' => $file->name]);
        }

        $mime = (string) $file->type;
        if ($mime !== '' && !str_starts_with($mime, 'image/')) {
            return Yii::t('app', 'File «{name}» must be an image.', ['name' => $file->name]);
        }

        $filename = sprintf('%d_%s.%s', (int) $product->id, bin2hex(random_bytes(8)), $extension);
        $path = $this->uploadDirectory() . DIRECTORY_SEPARATOR . $filename;

        if (!$file->saveAs($path, false)) {
            return Yii::t('app', 'Failed to save file «{name}».', ['name' => $file->name]);
        }

        $record = new ProductImage();
        $record->product_id = (int) $product->id;
        $record->image_url = 'uploads/products/' . $filename;
        $record->sort_order = $this->nextSortOrder((int) $product->id);

        if (!$record->save()) {
            @unlink($path);

            return Yii::t('app', 'Failed to save image record for «{name}».', ['name' => $file->name]);
        }

        return null;
    }

    public function deleteImage(ProductImage $image): void
    {
        $this->removeLocalFileIfOwned($image->image_url);
        $image->delete();
    }

    public function findImageForProduct(int $productId, int $imageId): ?ProductImage
    {
        return ProductImage::findOne(['id' => $imageId, 'product_id' => $productId]);
    }

    private function ensureUploadDirectoryWritable(): ?string
    {
        $directory = $this->uploadDirectory();
        if (is_writable($directory)) {
            return null;
        }

        return Yii::t('app', 'Upload directory is not writable: {path}', ['path' => $directory]);
    }

    private function nextSortOrder(int $productId): int
    {
        $max = ProductImage::find()->where(['product_id' => $productId])->max('sort_order');

        return $max !== null ? (int) $max + 1 : 0;
    }

    private function removeLocalFileIfOwned(string $imageUrl): void
    {
        $path = parse_url($imageUrl, PHP_URL_PATH);
        if (!is_string($path) || !str_contains($path, '/uploads/products/')) {
            return;
        }

        $filename = basename($path);
        if ($filename === '' || $filename === '.' || $filename === '..') {
            return;
        }

        $fullPath = $this->uploadDirectory() . DIRECTORY_SEPARATOR . $filename;
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
