<?php 

/**
 * Хелпер для работы с массивами
 * 
 * @author Vladimir Skih <skih.vladimir@gmail.com>
 */
class ArrayHelper
{
    /**
     * @var array $exclude_values_array Массив, содержащий значения, которое надо удалить
     */
    private $exclude_values_array;

    /**
     * @var string $key Ключ, по которому получаем значение
     */
    private $key;

    /**
     * Конструктор
     * 
     * @param array $exclude_values_array  Массив, содержащий значения, которое надо удалить
     * @param int   $key                   Ключ, по которому получаем значение
     */
    public function __construct($exclude_values_array, $key) 
    {
        $this->exclude_values_array = $exclude_values_array;
        $this->key                  = $key;
    }

    /**
     * Проверка на наличие в массиве $exclude_values_array значения $item[$key]
     * 
     * @param object|array $item Проверяемый элемент
     */
    public function filter($item): bool
    {
        return !in_array($item[$this->key], $this->exclude_values_array);
    } 
}