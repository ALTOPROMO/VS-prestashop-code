<?php

require_once _PS_MODULE_DIR_.'partnerfeed/common/Adapter.php';
require_once _PS_MODULE_DIR_.'partnerfeed/common/ProductHelper.php';
require_once _PS_MODULE_DIR_.'partnerfeed/libs/PHPExcel/Classes/PHPExcel.php';
require_once _PS_MODULE_DIR_.'partnerfeed/classes/PartnerFeedClass.php';

/**
 * Фронт-контроллер модуля PartnerFeed для генерирования файлов с фидом
 * 
 * @author Vladimir Skih <skih.vladimir@gmail.com>
 */
class PartnerFeedGenerateModuleFrontController extends ModuleFrontController
{
    /**
     * @var int $partner_id ID партнёра
     */
    private $partner_id;

    /**
     * @var PartnerFeedClass $partner Партнёр
     */
    private $partner = null;

    /**
     * @var int $weight Вес экспортируемого файла
     */
    private $weight;

    /**
     * @var int $id_lang ID языка
     */
    private $id_lang;

    /**
     * @var array $file_stucture_heavy Структура тяжёлого файла
     */
    private $file_structure_heavy;

    /**
     * @var array $file_stucture_light Структура лёгкого файла
     */
    private $file_structure_light;

    /**
     * @var ProductHelper $product_helper Хелпер для работы с товарами
     */
    private $product_helper;

    /**
     * @inheritdoc
     */
    public function __construct()
    {
        $this->partner_id = (int)Tools::getValue('partner_id');
        $this->weight     = Tools::getValue('weight');
        $this->id_lang    = $this->context->language->id;
        
        if ($this->id_lang === null) {
            $this->id_lang = Configuration::get('PS_LANG_DEFAULT');
        }

        parent::__construct();


        $this->product_helper = new ProductHelper($this->module, $this->id_lang);
    }

    /**
     * @inheritdoc
     */
    public function initContent()
    {
        try {
            $this->checkPartnerById($this->partner_id);
            $this->setFilesStructures($this->partner);
            $this->createFileByWeight($this->weight);
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    } 

    /**
     * Проверка партнёра на существование в БД
     * 
     * @param int $partner_id ID партнёра, которого проверяем
     * 
     * @throws Exception
     * 
     * @return void
     */
    private function checkPartnerById($partner_id)
    {
        $is_exist = PartnerFeedClass::checkPartnerById($partner_id);

        if (!$is_exist) {
            throw new Exception($this->module->l('Данного партнёра не существует.'));
        }
        else {
            $this->partner = new PartnerFeedClass($this->partner_id);
        }
    }

    /**
     * Установка структур для файлов
     * 
     * @return void
     */
    private function setFilesStructures($partner)
    {
        $this->file_structure_heavy = array(
            'sections' => array(
                'categories' => array(
                    'columns' => array(
                        array(
                            'export_data_field' => 'id',
                            'title'             => $this->module->l('ID категории'),
                        ),
                        array(
                            'export_data_field' => 'name',
                            'title'             => $this->module->l('Наименование'),
                        ),
                        array(
                            'export_data_field' => 'description',
                            'title'             => $this->module->l('Описание'),
                            'use_formatted'     => $partner->formatted_desc,
                        ),
                    ),
                    'show_title' => true,
                    'title'      => $this->module->l('Категории'),
                    'item_title' => 'category'
                ),
                'brands' => array(
                    'columns' => array(
                        array(
                            'export_data_field' => 'id',
                            'title'             => $this->module->l('ID бренда'),
                        ),
                        array(
                            'export_data_field' => 'name',
                            'title'             => $this->module->l('Наименование'),
                        ),
                        array(
                            'export_data_field' => 'description',
                            'title'             => $this->module->l('Описание'),
                            'use_formatted'     => $partner->formatted_desc,
                        ),
                    ),
                    'show_title' => true,
                    'title'      => $this->module->l('Бренды'),
                    'item_title' => 'brand'
                ),
                'products' => array(
                    'columns' => array(
                        array(
                            'export_data_field' => 'id',
                            'title'             => $this->module->l('ID товара'),
                        ),
                        array(
                            'export_data_field' => 'name',
                            'title'             => $this->module->l('Наименование'),
                        ),
                        array(
                            'export_data_field' => 'weight',
                            'title'             => $this->module->l('Вес'),
                        ),
                        array(
                            'export_data_field' => 'volume',
                            'title'             => $this->module->l('Объём'),
                        ),
                        array(
                            'export_data_field' => 'images',
                            'title'             => $this->module->l('Изображения'),
                            'special_type'      => true,
                            'callback_excel'    => 'setProductImagesToExcel',
                            'callback_xml'      => 'setProductImagesToXml'
                        ),
                        array(
                            'export_data_field' => 'description',
                            'title'             => $this->module->l('Описание'),
                            'use_formatted'     => $partner->formatted_desc,
                        ),
                        array(
                            'export_data_field' => 'price',
                            'title'             => $this->module->l('Цена'),
                        ),
                        array(
                            'export_data_field' => 'availability',
                            'title'             => $this->module->l('Наличие'),
                        ),
                        array(
                            'export_data_field' => 'quantity',
                            'title'             => $this->module->l('Доступное количество'),
                        ),
                    ),
                    'show_title' => true,
                    'title'      => $this->module->l('Товары'),
                    'item_title' => 'product'
                )
            )
        );

        $this->file_structure_light = array(
            'sections' => array(
                'products' => array(
                    'columns' => array(
                        array(
                            'export_data_field' => 'id',
                            'title'             => $this->module->l('ID товара'),
                        ),
                        array(
                            'export_data_field' => 'name',
                            'title'             => $this->module->l('Наименование'),
                        ),
                        array(
                            'export_data_field' => 'price',
                            'title'             => $this->module->l('Цена'),
                        ),
                        array(
                            'export_data_field' => 'quantity',
                            'title'             => $this->module->l('Доступное количество'),
                        ),
                    ),
                    'show_title' => false,
                    'title'      => $this->module->l('Товары'),
                    'item_title' => 'product'
                )
            )
        );
    }

    /**
     * Создание файла на основе веса
     * 
     * @param string $weight Вес файла
     * 
     * @throws Exception
     */
    private function createFileByWeight($weight)
    {
        if (!$weight || $weight === '') {
            throw new Exception($this->module->l('Не указан вес экспортируемого файла.'));
        }
        else if ($weight !== 'heavy' && $weight !== 'light') {
            throw new Exception($this->module->l('Указан неверный вес файла.'));
        }

        $call_method = 'generate'.Tools::toCamelCase(PartnerFeedClass::getFileTypeName($this->partner->file_export_type), 1);

        // ----- Если метод $call_method существует - то запускаем его, иначе генерируем исключение
        if (method_exists($this, $call_method))
        {
            call_user_func_array(array($this, $call_method), array($weight, $this->partner));
        }
        else {
            throw new Exception($this->module->l("Метода {$call_method} не существует."));
        }
    }

    /**
     * Генерирование XML на основе веса
     * 
     * Вызывается из метода createFileByWeight через call_user_func_array()
     * 
     * @param string               $weight  Вес файла
     * @param PartnerFeedClass $partner Партнёр
     * 
     * @throws Exception
     */
    private function generateXml($weight, $partner)
    {
        $export_data = $this->getFeedDataByPartner($partner);
        
        // ----- Создание XML файла
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<catalog date="' . date('Y-m-d').' '.date('H:i').'">'."\n";

        $file_name = $this->createFileName($weight, $partner, 'xml');
        $file_path = $this->createFilePath($file_name);

        if (file_exists($file_path)) {
            unlink($file_path);
        }

        $xmlObject = fopen($file_path, 'w+');
        @chmod($xmlObject, 0777);

        fwrite($xmlObject, pack("CCC", 0xef, 0xbb, 0xbf));
        fwrite($xmlObject, $xml);

        // ----- Заполнение файла
        $use_file_structure_var = "file_structure_{$weight}";
        
        foreach ($this->$use_file_structure_var['sections'] as $section_name => $section) {

            if (!$section['item_title']) {
                throw new Exception($this->module->l("Структура файла типа {$weight} задана неверно: необходимо заполнить поле 'item_title' для секции {$section_name}!"));
            }

            // ----- Если в подготовленных данных нет нужной секции - то пропускаем итерацию
            if ($export_data[$section_name] === null) {
                echo $this->module->l("Внимание! В подготовленных данных export_data отсутствует секция {$section_name}.<br/>");
                continue;
            }

            $xml = "<{$section_name}>\n";

            foreach ($export_data[$section_name] as $item) {

                $xml .= "<{$section['item_title']}>\n";

                for ($column = 0; $column < count($section['columns']) ; $column++) { 
                    $field_name = $section['columns'][$column]['export_data_field'];

                    $xml .= "<{$field_name}>\n";
                    
                    //----- Если у поля специальный тип, то запускаем callback функцию и передаем в нее необходимые параметры
                    $callback_key = 'callback_'.mb_strtolower(PartnerFeedClass::getFileTypeName($partner->file_export_type));

                    if ($section['columns'][$column]['special_type'] && method_exists($this, $section['columns'][$column][$callback_key])) {
                        fwrite($xmlObject, $xml);
                        $xml = '';
                        
                        $call_method = $section['columns'][$column][$callback_key];
                        $this->$call_method($item[$field_name], $xmlObject);
                    }
                    else {
                        if (isset($section['columns'][$column]['use_formatted']) 
                            && !$section['columns'][$column]['use_formatted']
                        ) {
                            $xml .= "<![CDATA[{$item[$field_name]}]]>\n";
                        }
                        else {
                            $xml .= $item[$field_name]."\n";
                        }
                    }
                    $xml .= "</{$field_name}>\n";
                }
                $xml .= "</{$section['item_title']}>\n";
            }
            $xml .= "</{$section_name}>\n";
            fwrite($xmlObject, $xml);
        }

        $xml_close = "</catalog>\n";
        fwrite($xmlObject, $xml_close);
        fclose($xmlObject);

        $this->downloadFile($file_name, $file_path);
    }

    /**
     * Генерирование Excel на основе веса
     * 
     * Вызывается из метода createFileByWeight через call_user_func_array()
     * 
     * @param string               $weight  Вес файла
     * @param PartnerFeedClass $partner Партнёр
     * 
     * @throws Exception
     */
    private function generateExcel($weight, $partner)
    {
        $export_data = $this->getFeedDataByPartner($partner);

        // ----- Сохдание Excel файла
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator(Configuration::get('PS_SHOP_DOMAIN'));
        $objPHPExcel->getActiveSheet()->setTitle($this->module->l("Каталог"));

        // ----- Заполнение файла
        $use_file_structure_var = "file_structure_{$weight}";
        
        $row = 1;

        foreach ($this->$use_file_structure_var['sections'] as $section_name => $section) {

            $row_section_start = $row;

            // ----- Если в подготовленных данных нет нужной секции - то пропускаем итерацию
            if ($export_data[$section_name] === null) {
                echo $this->module->l("Внимание! В подготовленных данных export_data отсутствует секция {$section_name}.<br/>");
                continue;
            }

            if ($section['show_title']) {
                // ----- Указываем название секции и ставим стили
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, $row, $section['title']);
                $objPHPExcel->getActiveSheet()->getStyle($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(0, $row)->getCoordinate())->getFont()->setBold(true);

                $row += 2;
            }

            // ----- Указываем наменования колонок для секции
            $columns_count = count($section['columns']);
            for ($column = 0; $column < $columns_count; $column++) {
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($column, $row, $section['columns'][$column]['title']);
                $objPHPExcel->getActiveSheet()->getStyle($objPHPExcel->getActiveSheet()->getCellByColumnAndRow($column, $row)->getCoordinate())->getFont()->setBold(true);
            }
            $row++;

            // ----- Заполнение документа данными секции
            foreach ($export_data[$section_name] as $item) {
                for ($column = 0; $column < count($section['columns']) ; $column++) { 
                    $field_name = $section['columns'][$column]['export_data_field'];
                    
                    //----- Если у поля специальный тип, то запускаем callback функцию и передаем в нее необходимые параметры
                    $callback_key = 'callback_'.mb_strtolower(PartnerFeedClass::getFileTypeName($partner->file_export_type));

                    if ($section['columns'][$column]['special_type'] && method_exists($this, $section['columns'][$column][$callback_key])) {
                        $call_method = $section['columns'][$column][$callback_key];
                        $this->$call_method($item[$field_name], $objPHPExcel, $row, $column);
                    }
                    else {
                        if (isset($section['columns'][$column]['use_formatted']) 
                            && !$section['columns'][$column]['use_formatted']
                        ) {
                            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($column, $row, strip_tags($item[$field_name]));
                        }
                        else {
                            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($column, $row, $item[$field_name]);
                        }
                        $objPHPExcel->getActiveSheet()->getStyle($objPHPExcel->getActiveSheet()->getCellByColumnAndRow($column, $row)->getCoordinate())->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                    }
                }
                $row++;
            }        
            
            if ($section['show_title']) {
                // ----- Стили для заголовка
                $objPHPExcel->getActiveSheet()->mergeCells('A'.$row_section_start.':I'.$row_section_start);
                $objPHPExcel->getActiveSheet()->getStyle('A'.$row_section_start)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('A'.$row_section_start)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()
                            ->getStyle('A'.$row_section_start)
                            ->getFill()
                            ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setRGB('D4E4FF');
            }

            $row++;
        }

        foreach(range('A','I') as $id_column) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($id_column)->setWidth(35);
        }

        $file_name = $this->createFileName($weight, $partner, 'xls');
        $file_path = $this->createFilePath($file_name);

        if (file_exists($file_path)) {
            unlink($file_path);
        }

        $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
        $objWriter->save($file_path);

        $this->downloadFile($file_name, $file_path);
    }

    /**
     * Подготовка данных для экспорта
     * 
     * @param PartnerFeedClass $partner Партнёр
     * 
     * @return array $export_data Данные для экспорта
     */
    private function getFeedDataByPartner($partner): array
    {
        $categories = $this->partner->getPartnerCategories($this->id_lang);
        $brands     = $this->partner->getPartnerBrands();

        if (empty($categories)) {
            throw new Exception($this->module->l('Категории для экспорта не указаны.'));
        }

        if (empty($brands)) {
            throw new Exception($this->module->l('Бренды для экспорта не указаны.'));
        }

        $categories_products = $this->product_helper->getProductsIdsByCategories($categories);
        $brands_products     = $this->product_helper->getProductsIdsByBrands($brands);

        // ----- Поиск совпадающих значений
        if (!$partner->use_stocks) {
            $equal_products = array_intersect($categories_products, $brands_products);
        }
        else {
            $stocks          = $this->partner->getPartnerStocks();
            $stocks_products = $this->product_helper->getProductsIdsByStocks($stocks);

            // ----- Немного костылей никому не мешало :)
            $equal_products  = array_intersect($stocks_products, array_intersect($categories_products, $brands_products));
        }

        if (empty($equal_products)) {
            throw new Exception($this->module->l('Товары, подходящие по параметрам, не найдены. Возможно, следует указать другие бренды или категории.'));
        }

        // ----- Подготовка массивов с данными о категориях, брендах и товарах
        foreach ($categories as $key => $id_category) {
            $category = new Category($id_category, $this->id_lang);
            if ($this->partner->checkPartnerExportHidden($category)) {
                $transform_structure = array(
                    'id'          => 'id',
                    'name'        => 'name',
                    'description' => 'description'
                );

                $category_bridge  = new Adapter($category, $transform_structure);
                $categories[$key] = $category_bridge->convertToArray()->getDataOut();
            }
        }

        foreach ($brands as $key => $id_brand) {
            $brand = new Manufacturer($id_brand, $this->id_lang);
            if ($this->partner->checkPartnerExportHidden($brand)) {
                $transform_structure = array(
                    'id'          => 'id',
                    'name'        => 'name',
                    'description' => 'description'
                );

                $brand_bridge = new Adapter($brand, $transform_structure);
                $brands[$key] = $brand_bridge->convertToArray()->getDataOut();
            }
        }

        $products = array();
        foreach ($equal_products as $product_row) {
            $product = new Product($product_row['id_product'], false, $this->id_lang);

            // ----- Проверяем на соответствие нужным статусам товаров и на выполнение условия со складами 
            if ($this->product_helper->checkProductStatus($product, $partner) 
                && $this->product_helper->checkProductUseStocks($product, $partner)
            ) {
                $transform_structure = array(
                    'id'          => 'id',
                    'name'        => 'name',
                    'description' => "description"
                );
                 
                $combinations = $product->getAttributeCombinations($this->id_lang);

                // ----- Заполнение массива $products данными
                if (empty($combinations)) {
                    $product_bridge = new Adapter($product, $transform_structure);

                    $product_bridge->convertToArray()
                        ->setDataOutField(
                            'price',
                            $this->product_helper->getProductPrice($product)
                        )->setDataOutField(
                            'quantity',
                            $this->product_helper->getProductQuantity($product)
                        )->setDataOutField(
                            'volume',
                            $this->product_helper->getProductVolume($product)
                        )->setDataOutField(
                            'weight',
                            $this->product_helper->getProductWieght($product)
                        )->setDataOutField(
                            'availability',
                            $this->product_helper->getProductAvailability($product)
                        )->setDataOutField(
                            'images',
                            $this->product_helper->getProductImages($product)
                        );

                    $products[] = $product_bridge->getDataOut();
                }
                else {
                    $combinations = $this->product_helper->prepareCombinations($combinations);
                    foreach ($combinations as $id_product_attribute => $combination) {
                        $product_bridge = new Adapter($product, $transform_structure);

                        $product_bridge->convertToArray()
                            ->setDataOutField(
                                'name', 
                                $this->product_helper->getProductName($product, $combination)
                            )->setDataOutField(
                                'price',
                                $this->product_helper->getProductPrice($product, $id_product_attribute)
                            )->setDataOutField(
                                'quantity',
                                $this->product_helper->getProductQuantity($product, $id_product_attribute)
                            )->setDataOutField(
                                'volume',
                                $this->product_helper->getProductVolume($product)
                            )->setDataOutField(
                                'weight',
                                $this->product_helper->getProductWieght($product, $combination)
                            )->setDataOutField(
                                'availability',
                                $this->product_helper->getProductAvailability($product)
                            )->setDataOutField(
                                'images',
                                $this->product_helper->getProductImages($product, $id_product_attribute)
                            );

                        $products[] = $product_bridge->getDataOut();
                    }
                }
            }
        }

        if (empty($products)) {
            throw new Exception($this->module->l('Товары, подходящие по параметрам, не найдены. Возможно, следует указать другие бренды или категории.'));
        }

        $export_data = array(
            'categories' => $categories,
            'brands'     => $brands,
            'products'   => $products
        );

        return $export_data;
    }

    /**
     * Создание имени файла
     * 
     * @param string               $weight   Вес файла
     * @param PartnerFeedClass $partner  Партнёр
     * @param string               $file_ext Расширение файла
     * 
     * @return string $file_name Имя файла
     */
    private function createFileName($weight, $partner, $file_ext): string
    {
        $today       = date("d.m.y");
        $shop_domain = Configuration::get('PS_SHOP_DOMAIN');
        $file_name   = "{$shop_domain}_partner_{$partner->id}_{$weight}_{$today}.${file_ext}";

        return $file_name;
    }

    /**
     * Создание пути до файла
     * 
     * @param string $file_name Имя файла
     * 
     * @return string $file_path Путь до файла
     */
    private function createFilePath($file_name): string
    {
        $file_path =  _PS_MODULE_DIR_.'partnerfeed/tmp/'.$file_name;

        return $file_path;
    }
    
    private function downloadFile($file_name, $file_path)
    {
        if(file_exists($file_path))
        {
            header('Content-Disposition: attachment; filename=' . $file_name);  
            readfile($file_path);
            unlink($file_path);
            exit;
        }
    }

    /**
     * Вставка изображений в Excel
     * 
     * @param array    $images      Массив изображений
     * @param PHPExcel $objPHPExcel Excel таблица
     * @param int      $row         Индекс строки
     * @param int      $column      Индекс колонки
     */
    public function setProductImagesToExcel($images, &$objPHPExcel, $row = 0, $column = 0)
    {
        $offsetX = 0;
        $offsetY = 0;

        foreach($images as $image) {
            $path = str_split($image['id_image']);
            $uri = '.' . _PS_IMG_ . 'p/';
            foreach($path as $item) {
                $uri .= $item . '/';
            }
            $uri .= $image['id_image'] . '.jpg';

            if (file_exists($uri)) {
                $objDrawing = new PHPExcel_Worksheet_Drawing();
                $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
                $objDrawing->setName($image['id_image'].'.jpg');
                $objDrawing->setDescription($image['legend']);
                $objDrawing->setPath($uri);
                $objDrawing->setCoordinates($objPHPExcel->getActiveSheet()->getCellByColumnAndRow($column, $row)->getCoordinate());
                $objDrawing->setOffsetX($offsetX);
                $objDrawing->setOffsetY($offsetY); 
                $objDrawing->getShadow()->setVisible(true);

                $offsetX += 25;
                $offsetY += 25;
            }
        }
    }

    /**
     * Вставка изображений в XML
     * 
     * @param array  $images    Массив изображений
     * @param object $xmlObject XML файл
     */
    public function setProductImagesToXml($images, &$xmlObject)
    {
        foreach($images as $image) {
            $path = str_split($image['id_image']);
            $uri = '.' . _PS_IMG_ . 'p/';
            foreach($path as $item) {
                $uri .= $item . '/';
            }
            $uri .= $image['id_image'] . '.jpg';

            if (file_exists($uri)) {
                $xml = "<image>\n";
    
                $image_url = $this->product_helper->getProductImageUrl($image, 'Niara_cart', $this->id_lang);
    
                $xml .= "<img alt='{$image['legend']}' src='{$image_url}'/>\n";
                $xml .= "</image>\n";
                fwrite($xmlObject, $xml);
            }
        }
    }
}