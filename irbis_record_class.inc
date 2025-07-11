<?php

/** вспомогательный класс записи ирбис */
class irbisRecord
{
    private $record = [];

    /** конструктор, принимает массив записи. если ничего не передать, то создаёт пустую запись
     * @param array $rec массив записи ирбис
     */
    function __construct($rec = [])
    {
        if (is_array($rec))
            if ($rec)
                $this->record = $rec;
            else
                $this->emptyRecord();
    }
    /** деструктор */
    function __destruct() {}

    /** возвращает мфн записи */
    public function getMFN(): int
    {
        return $this->record['mfn'];
    }
    /** достаём поле из записи
     * @param int $fieldNum номер поля
     * @param int $iter номер повторения
     * @param string $subField подполе, * - всё поле в виде строки
     */
    public function getField(int $fieldNum, int $iter = 1, string $subField = '*'): string
    {
        if (isset($this->record['fields'][$fieldNum][$iter][mb_strtoupper($subField)]))
            return $this->record['fields'][$fieldNum][$iter][mb_strtoupper($subField)];
        return '';
    }
    /** количество повторений поля
     * @param int $fieldNum номер поля
     */
    public function getFieldCount(int $fieldNum): int
    {
        if (isset($this->record['fields'][$fieldNum]))
            return count($this->record['fields'][$fieldNum]);
        return 0;
    }
    /** получить массив поля вида array[1]['A']
     * @param int $fieldNum номер поля
     */
    public function getFieldArray(int $fieldNum): array
    {
        if (isset($this->record['fields'][$fieldNum])) {
            $temp = $this->record['fields'][$fieldNum];
            foreach ($temp as &$tmp) {
                unset($tmp['*']);
                $tmp = array_filter($tmp, function ($value) {
                    return $value === '0' || $value === 0 || (bool)$value;
                });
            }
            return array_filter($temp);
        }
        return [];
    }
    /** установить значение поля или подполя
     * @param string $data данные
     * @param int $fieldNum номер поля
     * @param int $iter повторение поля
     * @param string $subField опционально, подполе
     */
    public function setField(string $data, int $fieldNum, int $iter, string $subField = '*'): void
    {
        if ($subField == '*') {
            if (isset($this->record['fields'][$fieldNum])) {
                unset($this->record['fields'][$fieldNum][$iter]);
                $this->record['fields'][$fieldNum] = array_filter($this->record['fields'][$fieldNum]);
            }
            $temp = [];
            $temp['*'] = $data;
            if (str_contains($data, '^')) {
                $matches = explode('^', $data);
                if (count($matches) == 1) {
                    $matches = explode("\x1f", $data);
                }
                foreach ($matches as $match) {
                    $temp[mb_strtoupper((string)substr($match, 0, 1))] = substr($match, 1);
                }
            }
            $this->record['fields'][$fieldNum][$iter] = $temp;
        } else {
            $this->record['fields'][$fieldNum][$iter][mb_strtoupper($subField)] = $data;
            $temp = $this->getFieldArray($fieldNum);
            $tmp = array_map(function ($key, $value) {
                return '^' . $key . $value;
            }, array_keys($temp[$iter]), $temp[$iter]);
            $this->record['fields'][$fieldNum][$iter]['*'] = implode('', $tmp);
        }
        //echo $this->record['fields'][$fieldNum][$iter]['*'];
    }
    /** добавить повторение поля
     * @param string $data данные в виде строки, подполя кодируются в виде ^Aданные^Bданные
     * @param int $fieldNum номер поля
     */
    public function addField(string $data, int $fieldNum): void
    {
        $temp = [];
        $temp['*'] = $data;
        if (str_contains($data, '^')) {
            $matches = explode('^', $data);
            if (count($matches) == 1) {
                $matches = explode("\x1f", $data);
            }
            foreach ($matches as $match) {
                $temp[mb_strtoupper((string)substr($match, 0, 1))] = substr($match, 1);
            }
        }
        $this->record['fields'][$fieldNum][$this->getFieldCount($fieldNum) + 1] = $temp;
        //echo $this->record['fields'][$fieldNum][$iter]['*'];
    }
    /** удалить поле
     * @param int $fieldNum номер поля
     * @param int $iter опционально, номер повторения
     * @param string $subField опционально, подполе
     */
    public function delField(int $fieldNum, int $iter = 0, string $subField = '*'): void
    {
        if (isset($this->record['fields'][$fieldNum]))
            if (!$iter) {
                unset($this->record['fields'][$fieldNum]);
                $this->record['fields'] = array_filter($this->record['fields']);
            } else if ($subField == '*') {
                unset($this->record['fields'][$fieldNum][$iter]);
                $this->record['fields'][$fieldNum] = array_filter($this->record['fields'][$fieldNum]);
                $temp = $this->record['fields'][$fieldNum];
                $this->record['fields'][$fieldNum] = [];
                $i = 1;
                foreach ($temp as $tmp) {
                    $this->record['fields'][$fieldNum][$i] = $tmp;
                    $i++;
                }
            } else {
                unset($this->record['fields'][$fieldNum][$iter][mb_strtoupper($subField)]);
                $this->record['fields'][$fieldNum][$iter] = array_filter($this->record['fields'][$fieldNum][$iter]);
                $temp = $this->getFieldArray($fieldNum);
                $tmp = array_map(function ($key, $value) {
                    return '^' . $key . $value;
                }, array_keys($temp[$iter]), $temp[$iter]);
                $this->record['fields'][$fieldNum][$iter]['*'] = implode('', $tmp);
            }
    }
    /** опустошение записи */
    public function emptyRecord(): void
    {
        $this->record = ['mfn' => 0, 'status' => 0, 'ver' => 0, 'fields' => []];
    }
    /** получение массива записи */
    public function getRecordArray(): array
    {
        // Filter out empty keys and values
        return $this->record;
    }
}
