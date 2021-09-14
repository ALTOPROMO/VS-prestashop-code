<?php

require_once _PS_MODULE_DIR_.'partnerfeed/common/ArrayHelper.php';

/**
 * Модель партнёра
 * 
 * @author Vladimir Skih <skih.vladimir@gmail.com>
 */
class PartnerFeedClass extends ObjectModel
{
    /**
     * @var int $id ID партнёра
     */
    public $id;

    /**
     * @var string $name Название пратнера
     */
    public $name;

    /**
     * @var boolean $use_stocks Использование складов
     */
    public $use_stocks;

    /**
     * @var string $stocks Список складов
     */
    public $stocks;

    /**
     * @var int $categories_export_type Тип экспорта категорий
     */
    public $categories_export_type;

    /**
     * @var string $categories Список категорий
     */
    public $categories;

    /**
     * @var int $categories_export_type Тип экспорта брендов
     */
    public $brands_export_type;

    /**
     * @var string $brands Список брендов
     */
    public $brands;

    /**
     * @var boolean $products_status Статус экспортируемых товаров
     */
    public $products_status;

    /**
     * @var boolean $formatted_desc Формат описания CDATA
     */
    public $formatted_desc;

    /**
     * @var boolean $export_hidden Экспорт скрытых (категорий, брендов, товаров)
     */
    public $export_hidden;

    /**
     * @var int $file_export_type Тип экспортируемого файла (XML, Excel)
     */
    public $file_export_type;

    /**
     * @var string $link_heavy Ссылка для скачивания тяжелого файла
     */
    public $link_heavy;

    /**
     * @var string $link_light Ссылка для скачивания легкого файла
     */
    public $link_light;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table'     => 'partnerfeed',
        'primary'   => 'id_partnerfeed',
        'fields'    => array (
            'name'                   => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255, 'required' => true),
            'use_stocks'             => array('type' => self::TYPE_BOOL),
            'stocks'                 => array('type' => self::TYPE_STRING, 'size' => 255),
            'categories_export_type' => array('type' => self::TYPE_INT, 'size' => 2),
            'categories'             => array('type' => self::TYPE_STRING, 'size' => 1000),
            'brands_export_type'     => array('type' => self::TYPE_INT, 'size' => 2),
            'brands'                 => array('type' => self::TYPE_STRING, 'size' => 1000),
            'products_status'        => array('type' => self::TYPE_BOOL),
            'formatted_desc'         => array('type' => self::TYPE_BOOL),
            'export_hidden'          => array('type' => self::TYPE_BOOL),
            'file_export_type'       => array('type' => self::TYPE_INT, 'size' => 2),
            'link_heavy'             => array('type' => self::TYPE_STRING, 'size' => 1000),
            'link_light'             => array('type' => self::TYPE_STRING, 'size' => 1000),
        ),
    );

    /**
     * @var array $file_types Возможные типы экспортируемых файлов
     */
    public static $file_types = array('XML', 'Excel');

    /**
     * Получение нового ID партнёра до сохранения
     * 
     * @return int $new_partner_id ID нового партнёра
     */
    public static function getNewPartnerId(): int
    {
        $table_info = Db::getInstance()->executeS('
            SELECT AUTO_INCREMENT FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = "'._DB_NAME_.'"
            AND TABLE_NAME = "'._DB_PREFIX_.self::$definition['table'].'"'
        );

        $new_partner_id = (int)$table_info[0]['AUTO_INCREMENT'];

        return $new_partner_id;
    } 

    /**
     * Проверка партнёра на существование в БД
     * 
     * @param int $id_partnerfeed ID партнёра, которого проверяем
     * 
     * @return bool $is_exist Существует ли партнёр в БД
     */
    public static function checkPartnerById($id_partnerfeed): bool
    {
        $is_exist = Db::getInstance()->getValue('
            SELECT id_partnerfeed
            FROM '._DB_PREFIX_.self::$definition['table'].'
            WHERE id_partnerfeed = "'.$id_partnerfeed.'"
        ');

        return $is_exist;
    } 

    /**
     * Проверка на экспорт скрытых элементов
     * 
     * True в случае, если экспортируем скрытые или, если мы не экспортируем скрытые, и элемент активен
     * 
     * @param object $item Объект, который проверяется на активность
     * 
     * @return boolean $check_hidden Подходял ли условия для экспорта
     */
    public function checkPartnerExportHidden($item): bool
    {
        $check_hidden = $this->export_hidden || (!$this->export_hidden && $item->active);

        return $check_hidden;
    }

    /**
     * Получение наименования типа экспортируемого файла
     * 
     * @param int $file_export_type ID типа файла
     * 
     * @return string $file_type_name Наименование типа экспортируемого файла
     */
    public static function getFileTypeName($file_export_type): string
    {
        $file_type_name = self::$file_types[$file_export_type];

        return $file_type_name;
    }

    /**
     * Получение списка складов, которые надо имопртировать для партнёра
     * 
     * @return array $stocks Список ID складов
     */
    public function getPartnerStocks(): array
    {
        $stocks = explode(',', $this->stocks);

        return $stocks;
    }

    /**
     * Получение списка категорий, которые надо имопртировать для партнёра
     * 
     * @param PartnerFeedClass $partner Партнёр
     * 
     * @return array $categories Список ID категорий
     */
    public function getPartnerCategories($id_lang): array
    {
        $categories = explode(',', $this->categories);

        if ((int)$this->categories_export_type === 2) {
            $all_categories = Category::getSimpleCategories($id_lang);
            $categories     = array_filter($all_categories, array(new ArrayHelper($categories, 'id_category'), 'filter'));

            foreach ($categories as $key => $category) {
                $categories[$key] = $category['id_category'];
            }
        }

        return $categories;
    }

    /**
     * Получение списка брендов, которые надо имопртировать для партнёра
     * 
     * @param PartnerFeedClass $partner Партнёр
     * 
     * @return array $brands Список ID брендов
     */
    public function getPartnerBrands(): array
    {
        $brands = explode(',', $this->brands);

        if ((int)$this->brands_export_type === 2) {
            $all_brands = Manufacturer::getManufacturers();
            $brands     = array_filter($all_brands, array(new ArrayHelper($brands, 'id_manufacturer'), 'filter'));

            foreach ($brands as $key => $brand) {
                $brands[$key] = $brand['id_manufacturer'];
            }
        }

        return $brands;
    }
}