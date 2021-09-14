<?php

require_once _PS_MODULE_DIR_.'partnerfeed/classes/PartnerFeedClass.php';

/**
 * Админ контроллер модуля PartnerFeed
 * 
 * @author Vladimir Skih <skih.vladimir@gmail.com>
 */
class AdminPartnerFeedController extends ModuleAdminController
{
    /**
     * @inheritdoc
     */
    public function __construct()
    {
        $this->table      = 'partnerfeed';
        $this->className  = 'PartnerFeedClass';
        $this->module     = 'partnerfeed';
        $this->bootstrap  = true;
        $this->context    = Context::getContext();
        
        parent::__construct();

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->show_toolbar = true;
        $this->token        = Tools::getAdminTokenLite('AdminPartnerFeed');
        $this->fields_list  = array(
            'id_partnerfeed' => array(
                'title' => $this->l('ID'),
                'width' => 100,
                'type'  => 'text',
            ),
            'name' => array(
                'title' => $this->l('Наименование партнёра'),
                'type'  => 'text',
            ),
            'file_export_type' => array(
                'title' => $this->l('Тип файла'),
                'type'  => 'int',
                'callback' => 'getFileTypeName',
            ),
            'link_heavy' => array(
                'title' => $this->l('Тяжелый файл'),
                'type'  => 'text',
            ),
            'link_light' => array(
                'title' => $this->l('Легкий файл'),
                'type'  => 'text',
            ),
        );
    }

    /**
     * Получение наименования типа экспортируемого файла
     * 
     * @param int $file_export_type ID типа файла
     * 
     * @return string $file_type_name Наименование типа экспортируемого файла
     */
    public function getFileTypeName($file_export_type): string
    {
        $file_type_name = PartnerFeedClass::getFileTypeName($file_export_type);

        return $file_type_name;
    }

    /**
     * @inheritdoc
     */
    public function renderForm()
    {
        if (!($partnerfeed = $this->loadObject(true))) {
            return;
        }

        // ----- Создаем список складов
        $warehouses     = Warehouse::getWarehouses();
        $stocks_options = array();

        foreach ($warehouses as $warehouse) {
            $stocks_options[] = array(
                'id_stock' => $warehouse['id_warehouse'],
                'name'     => $warehouse['name'],
            );
        }

        // ----- Строим дерево категорий
        $selected_categories = explode(',', $partnerfeed->categories);
        $categories          = new HelperTreeCategories($partnerfeed->id ? $partnerfeed->id : 0, $this->l('Категории'));

        if ($this->display == 'edit') {
            $categories->setSelectedCategories($selected_categories);
        }
        
        $categories->setUseSearch(true);
        $categories->setInputName('categories');
        $categories->setUseCheckBox(true);

        $tree = new HelperTreeCategories('categories-tree');
        $this->tpl_form_vars['categoryTreeView'] = $tree->setRootCategory((int)Category::getRootCategory()->id)->render();

        // ----- Создаем список доступных брендов
        $all_brands     = Manufacturer::getManufacturers();
        $brands_options = array();

        foreach ($all_brands as $brand) {
            $brands_options[] = array(
                'id_brand' => $brand['id_manufacturer'],
                'name'     => $brand['name'],
            );
        }
    
        $this->fields_form = array(
            'input' => array(
                array(
                    'type'     => 'text',
                    'label'    => $this->l('Наименование партнёра'),
                    'required' => true,
                    'name'     => 'name',
                ),
                array(
                    'type'  => 'radio',
                    'label' => $this->l('Использование складов'),
                    'name'  => 'use_stocks',
                    'desc'  => $this->l('Для использования складов необходимо включить расширенное управление запасами'),
                    'values' => array(
                        array(
                            'id'    => 'active',
                            'value' => 1,
                            'label' => $this->l('Да')
                        ),
                        array(
                            'id'    => 'not_active',
                            'value' => 0,
                            'label' => $this->l('Нет')
                        ),
                    ),
                    'is_bool' => true,
                ),
                array(
                    'type'     => 'select',
                    'name'     => 'stocks',
                    'multiple' => true,
                    'label'    => $this->l('Склады'),
                    'options'  => array(
                        'query' => $stocks_options,
                        'id'    => 'id_stock',
                        'name'  => 'name'
                    ),
                ),
                array(
                    'type'   => 'radio',
                    'label'  => $this->l('Тип экспорта категорий'),
                    'name'   => 'categories_export_type',
                    'values' => array(
                        array(
                            'id'    => 'export_all',
                            'value' => 0,
                            'label' => $this->l('Все')
                        ),
                        array(
                            'id'    => 'export_selected',
                            'value' => 1,
                            'label' => $this->l('Только выбранные')
                        ),
                        array(
                            'id'    => 'export_not_selected',
                            'value' => 2,
                            'label' => $this->l('Только не выбранные')
                        )
                    ),
                ),
                array(
                    'type'          => 'categories_select',
                    'name'          => 'categories',
                    'label'         => $this->l('Категории'),
                    'category_tree' => $categories,
                    'class'         => 'categories_block'
                ),
                array(
                    'type'   => 'radio',
                    'label'  => $this->l('Тип экспорта брендов'),
                    'name'   => 'brands_export_type',
                    'values' => array(
                        array(
                            'id'    => 'export_all',
                            'value' => 0,
                            'label' => $this->l('Все')
                        ),
                        array(
                            'id'    => 'export_selected',
                            'value' => 1,
                            'label' => $this->l('Только выбранные')
                        ),
                        array(
                            'id'    => 'export_not_selected',
                            'value' => 2,
                            'label' => $this->l('Только не выбранные')
                        )
                    ),
                ),
                array(
                    'type'     => 'select',
                    'name'     => 'brands',
                    'multiple' => true,
                    'label'    => $this->l('Бренды'),
                    'options'  => array(
                        'query' => $brands_options,
                        'id'    => 'id_brand',
                        'name'  => 'name'
                    ),
                ),
                array(
                    'type'   => 'radio',
                    'label'  => $this->l('Статус товаров'),
                    'name'   => 'products_status',
                    'values' => array(
                        array(
                            'id'    => 'active',
                            'value' => 1,
                            'label' => $this->l('Включенные')
                        ),
                        array(
                            'id'    => 'not_active',
                            'value' => 0,
                            'label' => $this->l('Не включенные')
                        ),
                    ),
                    'is_bool' => true,
                ),
                array(
                    'type'   => 'radio',
                    'label'  => $this->l('Форматированное описание'),
                    'name'   => 'formatted_desc',
                    'values' => array(
                        array(
                            'id'    => 'formatted',
                            'value' => 1,
                            'label' => $this->l('С форматирование')
                        ),
                        array(
                            'id'    => 'not_formatted',
                            'value' => 0,
                            'label' => $this->l('Без форматирования')
                        ),
                    ),
                    'is_bool' => true,
                    'desc'    => $this->l('Нужно ли использовать форматированное описание товаров для экспорта')
                ),
                array(
                    'type'   => 'radio',
                    'label'  => $this->l('Экспорт скрытых'),
                    'name'   => 'export_hidden',
                    'values' => array(
                        array(
                            'id'    => 'include_hidden',
                            'value' => 0,
                            'label' => $this->l('Включить скрытые')
                        ),
                        array(
                            'id'    => 'exclude_hidden',
                            'value' => 1,
                            'label' => $this->l('Не включать скрытые')
                        ),
                    ),
                    'is_bool' => true,
                    'desc'    => $this->l('Нужно ли экспортировать скрытые товары, категории и бренды')
                ),
                array(
                    'type'   => 'radio',
                    'label'  => $this->l('Тип экспортируемого файла'),
                    'name'   => 'file_export_type',
                    'values' => $this->getFileTypesFormValues(),
                ),
                array(
                    'type'     => 'text',
                    'label'    => $this->l('Тяжелый файл'),
                    'name'     => 'link_heavy',
                    'disabled' => true
                ),
                array(
                    'type'     => 'text',
                    'label'    => $this->l('Легкий файл'),
                    'name'     => 'link_light',
                    'disabled' => true
                ),
            ),
            'submit' => array(
                'title' => $this->l('Сохранить'),
                'class' => 'btn btn-default pull-right'   
            )
        );

        $this->fields_value = $this->getFormFieldValues($partnerfeed);

        return parent::renderForm();
    }

    /**
     * Получение значений для поля file_export_type в форме
     * 
     * @return array $values Значения с типами экспортируемых файлов
     */
    private function getFileTypesFormValues(): array
    {
        $values = array();

        foreach (PartnerFeedClass::$file_types as $id => $file_type) {
            $values[] = array(
                'id'    => $file_type.'_file_type',
                'value' => $id,
                'label' => $file_type
            );
        }

        return $values;
    }

    /**
     * Заполнение данных формы
     * 
     * @param PartnerFeedClass $partnerfeed Партнёр
     * 
     * @return array $fields_values   Заполненные поля
     */
    private function getFormFieldValues($partnerfeed): array
    {
        $fields_values = array();

        $fields_values['name']                   = $partnerfeed->name;
        $fields_values['use_stocks']             = $partnerfeed->use_stocks;
        $fields_values['categories_export_type'] = (int)$partnerfeed->categories_export_type;
        $fields_values['brands_export_type']     = (int)$partnerfeed->brands_export_type;
        $fields_values['products_status']        = (int)$partnerfeed->products_status;
        $fields_values['formatted_desc']         = (int)$partnerfeed->formatted_desc;
        $fields_values['export_hidden']          = (int)$partnerfeed->export_hidden;
        $fields_values['file_export_type']       = (int)$partnerfeed->file_export_type;
        $fields_values['link_heavy']             = $partnerfeed->link_heavy;
        $fields_values['link_light']             = $partnerfeed->link_light;

        // ----- Заполняем бренды
        if ($partnerfeed->brands !== '') {
            $selected_ids_brands = explode(',', $partnerfeed->brands);
        }
        else {
            $selected_ids_brands = array();
        }
        $fields_values['brands[]'] = $selected_ids_brands;

        // ----- Заполняем склады
        if ($partnerfeed->stocks !== '') {
            $selected_ids_stocks = explode(',', $partnerfeed->stocks);
        }
        else {
            $selected_ids_stocks = array();
        }
        $fields_values['stocks[]'] = $selected_ids_stocks;

        return $fields_values;
    }

    /**
     * @inheritdoc
     */
    public function setMedia()
    {
        parent::setMedia();
        
        $new_partner_id         = PartnerFeedClass::getNewPartnerId();
        $stocks_feature_enabled = (bool)Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT');
        $stocks                 = Warehouse::getWarehouses();
        $stock_available_error  = count($stocks) === 0 ? $this->l('Отстутствуют склады') : false;

        // ----- Определение основных JS переменных
        Media::addJsDef(array(
            'new_partner_id'          => $new_partner_id,
            'stocks_feature_enabled'  => $stocks_feature_enabled,
            'stock_available_error'   => $stock_available_error,
            'generate_controller_url' => Context::getContext()->link->getModuleLink($this->module->name, 'generate', array()),
        ));

        $this->addJS(_PS_MODULE_DIR_.'partnerfeed/views/js/partnerfeed.js');
    }

    /**
     * @inheritdoc
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_partnerfeed'] = array(
                'href' => self::$currentIndex.'&addpartnerfeed&token='.$this->token,
                'desc' => $this->l('Добавить партнёра', null, null, false),
                'icon' => 'process-icon-new'
            );
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * @inheritdoc
     */
    public function processSave()
    { 
        // ----- Обработка ошибок

        // ----- Проверка на пустоту наименования партнёра
        if ($_POST['name'] === '') { 
            $this->errors[] = $this->l('Укажите наименование партнёра');
        }

        // ----- Проверка на выбранные склады
        if ((int)$_POST['use_stocks'] === 1 && empty($_POST['stocks'])) {
            $this->errors[] = $this->l('Ни один склад не выбран');
        }
        else if ((int)$_POST['use_stocks'] === 1 && !empty($_POST['stocks'])) {
            $_POST['stocks'] = implode(',', $_POST['stocks']);
        }
        else if ((int)$_POST['use_stocks'] === 0) {
            $_POST['stocks'] = '';
        }

        // ----- Проверка на выбранные категории, если экспортируются не все
        if (((int)$_POST['categories_export_type'] === 1 
            || (int)$_POST['categories_export_type'] === 2
            ) && empty($_POST['categories'])
        ) {
            $this->errors[] = $this->l('Выберете категории');
        }
        else if (((int)$_POST['categories_export_type'] === 1 
                || (int)$_POST['categories_export_type'] === 2
                ) && !empty($_POST['categories'])
        ) {
            $_POST['categories'] = implode(',', $_POST['categories']);
        }
        else {
            $all_categories     = Category::getSimpleCategories($this->context->language->id);
            $categories_options = array();
            $categories         = '';

            foreach ($all_categories as $category) {
                $categories_options[] = $category['id_category'];
            }

            $categories          = implode(',', $categories_options);
            $_POST['categories'] = $categories;
        }

        // ----- Проверка на выбранные бренды, если экспортируются не все
        if (((int)$_POST['brands_export_type'] === 1 || 
            (int)$_POST['brands_export_type'] === 2
            ) && empty($_POST['brands'])
        ) {
            $this->errors[] = $this->l('Выберете бренды');
        }
        else if (((int)$_POST['brands_export_type'] === 1 || 
            (int)$_POST['brands_export_type'] === 2
            ) && !empty($_POST['brands'])
        ) {
            $_POST['brands'] = implode(',', $_POST['brands']);
        }
        else {
            $all_brands     = Manufacturer::getManufacturers();
            $brands_options = array();
            $brands         = '';

            foreach ($all_brands as $brand) {
                $brands_options[] = $brand['id_manufacturer'];
            }

            $brands          = implode(',', $brands_options);
            $_POST['brands'] = $brands;
        }

        return parent::processSave();
    }
}