<?php

if (!defined('_PS_VERSION_'))
    exit;

require_once dirname(__FILE__).'/classes/PartnerFeedClass.php';

/**
 * Модуль PartnerFeed
 * 
 * @author Vladimir Skih <skih.vladimir@gmail.com>
 */
class PartnerFeed extends Module
{
    /**
     * @inheritdoc
     */
    public function __construct()
    {
        $this->name          = strtolower(get_class());
        $this->tab           = 'other';
        $this->version       = 1.0;
        $this->author        = 'PrestaWeb.ru';
        $this->need_instance = 0;
        $this->bootstrap     = true;

        parent::__construct();

        $this->controllers = array('AdminPartnerFeed', 'Generate');
        $this->displayName = $this->l("Экспорт каталога для партнёров");
        $this->description = $this->l("Позволяет создавать партнёров и задавать для них настраиваемые тяжелый и легкий файлы экспорта каталога");
        
        $this->ps_versions_compliancy = array('min' => '1.6.0.0', 'max' => _PS_VERSION_);
    }

    /**
     * @inheritdoc
     */
    public function install()
    {
        if ( !parent::install() 
            || !$this->installDB($this->name)
            || !$this->installModuleTab()
        ) return false;

        return true;
    }

    /**
     * @inheritdoc
     */
    public function uninstall()
    {
        if (!$this->uninstallDB($this->name) || !$this->uninstallModuleTab() || !parent::uninstall()) {
            return false;
        }
        return true;
    }

    /**
     * Установка табицы в БД
     * 
     * @param string $name Название таблицы
     * 
     * @return boolean Результат установки таблицы
     */
    private function installDB($name): bool
    {
        $query   = array();
        $query[] = 'DROP TABLE IF EXISTS '._DB_PREFIX_.$name.';';
        $query[] = 'CREATE TABLE '._DB_PREFIX_.$name.' (
		  `id_partnerfeed` int(10) NOT NULL AUTO_INCREMENT,
          `name` text(255) NOT NULL,
          `use_stocks` boolean,
          `stocks` text(255),
		  `categories_export_type` int(2),
		  `categories` text(1000),
          `brands_export_type` int(2),
		  `brands` text(1000),
          `products_status` boolean,
          `formatted_desc` boolean,
          `export_hidden` boolean,
          `file_export_type` int(2),
          `link_heavy` text(255),
          `link_light` text(255),
		  PRIMARY KEY (`id_partnerfeed`)
        ) DEFAULT CHARSET=utf8;';

        foreach ($query as $q) 
            if (!Db::getInstance()->Execute($q))
                return false;

        return true;
    }

    /**
     * Удаление таблицы в БД
     * 
     * @param string $name Название таблицы
     * 
     * @return boolean Результат удаления таблицы
     */
    private function uninstallDB($name): bool
    {
        $query   = array();
        $query[] = 'DROP TABLE IF EXISTS '._DB_PREFIX_.$name.';';
        foreach ($query as $q)
            if (!Db::getInstance()->Execute($q))
                return false;

        return true;
    }  

    /**
     * Установка Таба с Админ контроллерами
     * 
     * @return boolean $response Результат установки Таба
     */
    private function installModuleTab(): bool
    {
        $response    = true;
        $parentTabID = Tab::getIdFromClassName('AdminPartnerFeed');
        $langs       = Language::getLanguages();

        if ($parentTabID) {
            $parentTab = new Tab($parentTabID);
        } 
        else {
            $parentTab = new Tab();

            $parentTab->active     = 1;
            $parentTab->name       = array();
            $parentTab->class_name = "AdminPartnerFeed";

            foreach ($langs as $lang) {
                $parentTab->name[$lang['id_lang']] = $this->l('Экспорт для партнёров');
            }

            $parentTab->id_parent = 0;
            $parentTab->module    = $this->name;

            if (version_compare(_PS_VERSION_, '1.7.0', '>=') === true) {
                $response &= $parentTab->save();
            }
            else {
                $response &= $parentTab->add();
            }

        }

        if (version_compare(_PS_VERSION_, '1.7.0', '>=') === true) {
            if($response) {
                $valTab = new Tab();

                $valTab->class_name = 'AdminPartnerFeedItem';

                $valTab->module    = $this->name;
                $valTab->id_parent = $parentTab->id;

                foreach ($langs as $l)
                    $valTab->name[$l['id_lang']] = $this->l('Партнёры');

                if (version_compare(_PS_VERSION_, '1.7.0', '>=') === true) {
                    $response &= $valTab->save();
                }
                else {
                    $response &= $valTab->add();
                }
            }
        }

        return $response;
    }

    /**
     * Удаление Таба с Админ контроллерами
     * 
     * @return boolean $response Результат удаления Таба
     */
    private function uninstallModuleTab(): bool
    {
        $response = true;
        $idTab    = Tab::getIdFromClassName('AdminPartnerFeed');

        if ($idTab != 0) {
            $tab       = new Tab($idTab);
            $response &= $tab->delete();
        }

        if (version_compare(_PS_VERSION_, '1.7.0', '>=') === true) {
            $idTabChild = Tab::getIdFromClassName('AdminPartnerFeedItem');

            if ($idTabChild != 0) {
                $tabChild  = new Tab($idTabChild);
                $response &= $tabChild->delete();
            }

            $response &= $tabChild->delete();
        }

        return $response;
    }
}


