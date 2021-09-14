<?php 

/**
 * Хелпер для работы с товарами
 * 
 * @author Vladimir Skih <skih.vladimir@gmail.com>
 */
class ProductHelper
{
    /**
     * @var $int $id_lang ID языка
     */
    private $id_lang;

    /**
     * @var Module $module Экземпляр класса Module
     */
    private $module;

    /**
     * Конструктор
     * 
     * @param Module $module  Экземпляр класса Module
     * @param int    $id_lang ID языка
     */
    public function __construct($module, $id_lang)
    {
        $this->id_lang = $id_lang;
        $this->module  = $module;
    }

    /**
     * Получение ID товаров, исходя из массива категорий
     * 
     * @param array $categories Массив, содержащий ID категорий
     * 
     * @return array $products_ids Массив, содержащий ID товаров
     */
    public function getProductsIdsByCategories($categories): array 
    {
        $sql_get_products_ids = '
            SELECT DISTINCT id_product
            FROM '._DB_PREFIX_.'category_product
            WHERE id_category IN ('.implode(',', $categories).')
            ORDER BY id_product ASC
        ';

        $products_ids = Db::getInstance()->executeS($sql_get_products_ids);

        return $products_ids;
    }

    /**
     * Получение ID товаров, исходя из массива брендов
     * 
     * @param array $brands Массив, содержащий ID брендов
     * 
     * @return array $products_ids Массив, содержащий ID товаров
     */
    public function getProductsIdsByBrands($brands): array
    {
        $sql_get_products_ids = '
            SELECT id_product
            FROM '._DB_PREFIX_.'product
            WHERE id_manufacturer IN ('.implode(',', $brands).')
            ORDER BY id_product ASC
        ';

        $products_ids = Db::getInstance()->executeS($sql_get_products_ids);

        return $products_ids;
    }

    /**
     * Получение ID товаров, исходя из массива складов
     * 
     * @param array $stocks Массив, содержащий ID складов
     * 
     * @return array $products_ids Массив, содержащий ID товаров
     */
    public function getProductsIdsByStocks($stocks): array
    {
        $sql_get_products_ids = '
            SELECT DISTINCT id_product
            FROM '._DB_PREFIX_.'stock
            WHERE id_warehouse IN ('.implode(',', $stocks).')
            ORDER BY id_product ASC
        ';

        $products_ids = Db::getInstance()->executeS($sql_get_products_ids);

        return $products_ids;
    }

    /**
     * Проверка на статус товара
     * 
     * True в случае, если экспортируем включенные товары и сам товар включен или, 
     * если мы экспортируем выключенные товары, и сам товар выключен
     * 
     * @param Product              $product Товар
     * @param PartnerExportClass $partner Партнёр
     * 
     * @return boolean $check_hidden Подходял ли условия для экспорта
     */
    public function checkProductStatus($product, $partner): bool
    {
        $check_hidden = ($partner->products_status && $product->active) 
                        || (!$partner->products_status && !$product->active);

        return $check_hidden;
    }

    /**
     * Проверка на использование складов товаром
     * 
     * True в случае, если мы не используем склады, и товар не подключен к конкретному складу или, если мы используем
     * склады при экспорте, и товар прикреплен к конкретному складу
     * 
     * @param Product            $product Товар
     * @param PartnerExportClass $partner Партнёр
     * 
     * @return boolean $check_product_stocks Используется ли склады, и подходит ли товар
     */
    public function checkProductUseStocks($product, $partner): bool
    {
        $product->loadStockData();

        $check_product_stocks = (!$partner->use_stocks) || ($partner->use_stocks && $product->advanced_stock_management);

        return $check_product_stocks;
    }

    /**
     * Получение названия для комбинации
     * 
     * @param array $combinations Массив комбинаций
     * 
     * @return array $prepared_combinations Отформатированный массив комбинаций с именами
     */
    public function prepareCombinations($combinations): array
    {
        $prepared_combinations = array();

        foreach ($combinations as $combination) {
            $prepared_combinations[$combination['id_product_attribute']]['name'] .= " {$combination['group_name']} - {$combination['attribute_name']} ";

            $prepared_combinations[$combination['id_product_attribute']]['name'] = trim($prepared_combinations[$combination['id_product_attribute']]['name']);

            $prepared_combinations[$combination['id_product_attribute']]['weight'] = $combination['weight'];
        }

        return $prepared_combinations;
    }

    /**
     * Получение наименования товара
     * 
     * @param Product $product     Товар
     * @param array   $combination Комбинация
     * 
     * @return string $name Наименование товара
     */
    public function getProductName($product, $combination = array()): string
    {
        $name = empty($combination) ? $product->name : $product->name.': '.$combination['name'];

        return $name;
    }

    /**
     * Получение цены товара
     * 
     * @param Product $product              Товар
     * @param int     $id_product_attribute ID атрибута товара
     * @param int     $decimals             Количество знаков после запятой
     * 
     * @return float $price Цена товара
     */
    public function getProductPrice($product, $id_product_attribute = null, $decimals = 2): float
    {
        $price = $product->getPrice(true, $id_product_attribute, $decimals);

        return $price;
    }

    /**
     * Получение объёма для товара
     * 
     * @param Product $product Товар
     * 
     * @return float|string $volume Объём
     */
    public function getProductVolume($product)
    {
        $features = $product->getFrontFeatures($this->id_lang);

        $volume = $this->getFeatureValueByName($features, 'Объём');
        if (!$volume) {
            $width  = $this->getFeatureValueByName($features, 'Width');
            $height = $this->getFeatureValueByName($features, 'Height');
            $depth  = $this->getFeatureValueByName($features, 'Depth');

            $calculate_width  = $width  ? (float)$width  : $product->width;
            $calculate_height = $height ? (float)$height : $product->height;
            $calculate_depth  = $depth  ? (float)$depth  : $product->depth;
            
            $volume = round($calculate_width * $calculate_height * $calculate_depth, 2);
        }

        return $volume !== false || $volume > 0 ? $volume : '';
    }

    /**
     * Получение веса для товара
     * 
     * @param Product $product     Товар
     * @param array   $combination Комбинация товара
     * 
     * @return float|string $weight Вес
     */
    public function getProductWieght($product, $combination = array())
    {
        $features = $product->getFrontFeatures($this->id_lang);

        $weight = $this->getFeatureValueByName($features, 'Weight');
        if (!$weight) {
            $weight = $combination['weight'] && $combination['weight'] > 0 
                    ? $product->weight + $combination['weight'] 
                    : $product->weight;
        }

        return $weight !== false || $weight > 0 ? $weight : '';
    }
    
    /**
     * Получение массива изображений для товара
     * 
     * @param Product $product              Товар
     * @param int     $id_product_attribute ID атрибута для товара
     * 
     * @return array $images Массив изображений
     */
    public function getProductImages($product, $id_product_attribute = null): array
    {
        $images = Image::getImages($this->id_lang, $product->id, $id_product_attribute);

        return $images;
    }

    /**
     * Получение строки с наличием товара
     * 
     * @param Product $product Товар
     * 
     * @return string $availability Наличие
     */
    public function getProductAvailability($product): string
    {
        $availability = Product::getQuantity($product->id) > 0 ? $this->module->l('В наличии') : $this->module->l('Отсутствует');

        return $availability;
    }

    /**
     * Получение строки с наличием товара
     * 
     * @param Product $product Товар
     * 
     * @return int $qty Доступное количество
     */
    public function getProductQuantity($product, $id_product_attribute = null): string
    {
        $qty = Product::getQuantity($product->id, $id_product_attribute);

        return $qty;
    }

    /**
     * Получение URL изображения товара
     * 
     * @param array  $image      Массив с данными об изображении
     * @param string $image_type Необходимый тип изображения
     * @param int    $id_lang    ID языка
     * 
     * @return string $image_url URL изображения товара
     */
    public function getProductImageUrl($image, $image_type, $id_lang)
    {
        $product   = new Product((int)$image['id_product'], false, $id_lang);
        $image_url = Context::getContext()->link->getImageLink(isset($product->link_rewrite) ? $product->link_rewrite : $product->name, (int)$image['id_image'], $image_type);

        return $image_url;
    }

    /**
     * Поиск значения характеристики в массиве характеристик
     * 
     * @param array  $features     Массив характеристик
     * @param string $feature_name Название характеристики
     * 
     * @return string $value Значение характеристики
     */
    private function getFeatureValueByName($features, $feature_name): string
    {
        foreach ($features as $feature) {
            if ($feature['name'] === $feature_name) {
                $value = $feature['value'];

                return $value;
            }
        }

        return false;
    }
}