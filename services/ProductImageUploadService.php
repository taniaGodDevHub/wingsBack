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

    public function copyImagesFromProduct(Product $source, Product $target): void
    {
        foreach ($source->images as $image) {
            $this->copyImage($target, $image);
        }
    }

    private function copyImage(Product $product, ProductImage $sourceImage): void
    {
        $sourcePath = $this->resolveLocalFilePath((string) $sourceImage->image_url);
        $imageUrl = (string) $sourceImage->image_url;

        if ($sourcePath !== null) {
            $extension = strtolower((string) pathinfo($sourcePath, PATHINFO_EXTENSION));
            if ($extension === '') {
                $extension = 'jpg';
            }

            FileHelper::createDirectory($this->uploadDirectory());
            $filename = sprintf('%d_%s.%s', (int) $product->id, bin2hex(random_bytes(8)), $extension);
            $targetPath = $this->uploadDirectory() . DIRECTORY_SEPARATOR . $filename;

            if (!@copy($sourcePath, $targetPath)) {
                throw new \RuntimeException('Failed to copy product image file.');
            }

            $imageUrl = 'uploads/products/' . $filename;
        }

        $record = new ProductImage();
        $record->product_id = (int) $product->id;
        $record->image_url = $imageUrl;
        $record->sort_order = (int) $sourceImage->sort_order;

        if (!$record->save()) {
            if ($sourcePath !== null && isset($targetPath) && is_file($targetPath)) {
                @unlink($targetPath);
            }

            throw new \RuntimeException('Failed to save copied product image record.');
        }
    }

    public function findImageForProduct(int $productId, int $imageId): ?ProductImage
    {
        return ProductImage::findOne(['id' => $imageId, 'product_id' => $productId]);
    }

    /**
     * @param int[] $imageIds
     */
    public function reorderImages(int $productId, array $imageIds): ?string
    {
        $imageIds = array_values(array_unique(array_map(static fn ($id): int => (int) $id, $imageIds)));
        $imageIds = array_values(array_filter($imageIds, static fn (int $id): bool => $id > 0));

        $existing = ProductImage::find()
            ->where(['product_id' => $productId])
            ->indexBy('id')
            ->all();

        if ($existing === []) {
            return null;
        }

        if (count($imageIds) !== count($existing)) {
            return Yii::t('app', 'Invalid image order.');
        }

        foreach ($imageIds as $imageId) {
            if (!isset($existing[$imageId])) {
                return Yii::t('app', 'Image not found.');
            }
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            foreach ($imageIds as $position => $imageId) {
                $image = $existing[$imageId];
                $image->sort_order = $position;
                $image->update(false, ['sort_order']);
            }

            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            Yii::error($exception->getMessage(), __METHOD__);

            return Yii::t('app', 'Something went wrong. Please try again.');
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

    private function nextSortOrder(int $productId): int
    {
        $max = ProductImage::find()->where(['product_id' => $productId])->max('sort_order');

        return $max !== null ? (int) $max + 1 : 0;
    }

    private function removeLocalFileIfOwned(string $imageUrl): void
    {
        $fullPath = $this->resolveLocalFilePath($imageUrl);
        if ($fullPath !== null && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    private function resolveLocalFilePath(string $imageUrl): ?string
    {
        if (str_contains($imageUrl, 'uploads/products/')) {
            $filename = basename($imageUrl);
        } else {
            $path = parse_url($imageUrl, PHP_URL_PATH);
            if (!is_string($path) || !str_contains($path, '/uploads/products/')) {
                return null;
            }

            $filename = basename($path);
        }

        if ($filename === '' || $filename === '.' || $filename === '..') {
            return null;
        }

        $fullPath = $this->uploadDirectory() . DIRECTORY_SEPARATOR . $filename;

        return is_file($fullPath) ? $fullPath : null;
    }
}
