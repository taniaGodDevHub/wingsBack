<?php

/** @var yii\web\View $this */
/** @var app\models\Product $model */
/** @var app\models\Size[] $catalogSizes */

use yii\helpers\Html;

$model->ensureSizeChartInitialized();

$this->registerCss(<<<'CSS'
.product-size-chart {
    margin-top: 1.5rem;
}

.product-size-chart__label {
    display: block;
    font-weight: 700;
    letter-spacing: 0.04em;
    margin-bottom: 0.75rem;
    text-transform: uppercase;
}

.product-size-chart__table {
    background: #fff;
}

.product-size-chart__table th,
.product-size-chart__table td {
    background: #fff;
    vertical-align: middle;
}

.product-size-chart__table th {
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    white-space: nowrap;
}

.product-size-btn {
    align-items: center;
    background: #fff;
    border: 1px solid #d9d9d9;
    color: #9a9a9a;
    cursor: pointer;
    display: inline-flex;
    font-size: 0.95rem;
    font-weight: 600;
    height: 3rem;
    justify-content: center;
    min-width: 3rem;
    overflow: hidden;
    padding: 0 0.75rem;
    position: relative;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    user-select: none;
}

.product-size-btn:not(.product-size-btn--in-stock)::after {
    background-color: #9a9a9a;
    content: '';
    height: 1px;
    left: 50%;
    pointer-events: none;
    position: absolute;
    top: 50%;
    transform: translate(-50%, -50%) rotate(-45deg);
    width: 140%;
}

.product-size-btn:hover {
    border-color: #bdbdbd;
}

.product-size-btn--in-stock {
    background: #1a1a1a;
    border-color: #1a1a1a;
    color: #fff;
}

.product-size-btn--in-stock:hover {
    background: #333;
    border-color: #333;
    color: #fff;
}
CSS);
?>
<div class="mb-3 product-size-chart" id="product-size-chart">
    <span class="product-size-chart__label"><?= Html::encode($model->getAttributeLabel('sizeChartBySizeId')) ?></span>
    <div class="table-responsive">
        <table class="table table-bordered product-size-chart__table mb-0">
            <thead>
                <tr>
                    <th scope="col"><?= Html::encode(Yii::t('app', 'RUS size')) ?></th>
                    <th scope="col"><?= Html::encode(Yii::t('app', 'INT size')) ?></th>
                    <th scope="col"><?= Html::encode(Yii::t('app', 'Chest circumference, cm')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($catalogSizes as $size): ?>
                    <?php
                    $sizeId = (int) $size->id;
                    $row = $model->sizeChartBySizeId[$sizeId] ?? [
                        'chest_circumference' => (string) $size->default_chest_circumference,
                        'is_in_stock' => false,
                    ];
                    $isInStock = !empty($row['is_in_stock']);
                    $chestName = Html::getInputName($model, "sizeChartBySizeId[{$sizeId}][chest_circumference]");
                    $stockName = Html::getInputName($model, "sizeChartBySizeId[{$sizeId}][is_in_stock]");
                    $buttonClass = 'product-size-btn' . ($isInStock ? ' product-size-btn--in-stock' : '');
                    ?>
                    <tr data-size-row="<?= $sizeId ?>">
                        <td><?= Html::encode($size->rus_label) ?></td>
                        <td>
                            <input
                                type="hidden"
                                name="<?= Html::encode($stockName) ?>"
                                value="<?= $isInStock ? '1' : '0' ?>"
                                data-size-stock-input
                            >
                            <button
                                type="button"
                                class="<?= $buttonClass ?>"
                                data-size-id="<?= $sizeId ?>"
                                aria-pressed="<?= $isInStock ? 'true' : 'false' ?>"
                                aria-label="<?= Html::encode(Yii::t('app', 'Sizes in stock')) ?>: <?= Html::encode($size->size_value) ?>"
                            ><?= Html::encode($size->size_value) ?></button>
                        </td>
                        <td>
                            <input
                                type="text"
                                class="form-control form-control-sm"
                                name="<?= Html::encode($chestName) ?>"
                                value="<?= Html::encode((string) ($row['chest_circumference'] ?? '')) ?>"
                                maxlength="16"
                            >
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
