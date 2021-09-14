<?php 

/**
 * Хелпер для перевода одной структуры данных в другую
 * 
 * @author Vladimir Skih <skih.vladimir@gmail.com>
 */
class Adapter
{
    /**
     * @var array|object $data_in Входные данные
     */
    private $data_in;

    /**
     * @var array $data_out Выходные данные
     */
    private $data_out;

    /**
     * @var array $transform_structure Соответствие полей во входных и выходных данных ('data_out_field' => 'data_in_field')
     */
    private $transform_structure;

    /**
     * @var array $is_correct_type Верен ли тип входных данных
     */
    private $is_correct_type;

    /**
     * Конструктор
     * 
     * @param array|object $data_in             Входные данные
     * @param array        $transform_structure Соответствие полей во входных и выходных данных ('data_out_field' => 'data_in_field')
     */
    public function __construct($data_in, $transform_structure = array())
    {
        $this->data_in             = $data_in;
        $this->is_correct_type     = gettype($data_in) === 'array' || gettype($data_in) === 'object';
        $this->transform_structure = $transform_structure;
    }

    /**
     * Конвертация входных данных в массив выходных данных на основе соответствия полей
     * 
     * @return Adapter $this Экземпляр класса
     */
    public function convertToArray()
    {
        if (!$this->is_correct_type) {
            throw new Exception("data_in Type error! It has to be Object or Array.");
        }

        foreach ($this->transform_structure as $data_out_field => $data_in_field) {
            if (gettype($this->data_in) === 'object') {
                $this->data_out[$data_out_field] = $this->data_in->$data_in_field;
            }
            else if (gettype($this->data_in) === 'array') {
                $this->data_out[$data_out_field] = $this->data_in[$data_in_field];
            }
        }

        return $this;
    }

    /**
     * Геттер для получения выходных данных
     * 
     * @return array $data_out Выходные данные
     */
    public function getDataOut(): array
    {
        return $this->data_out;
    }

    /**
     * Сеттер для ручной установки поля в массив выходных данных
     * 
     * @param string $name  Имя поля 
     * @param mixed  $value Значение
     * 
     * @return Adapter $this 
     */
    public function setDataOutField($name, $value)
    {
        $this->data_out[$name] = $value;

        return $this;
    }
}