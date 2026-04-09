<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToArray;
class StudentCodeImport implements ToArray
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        //
    }
    public array $rows = [];

    public function array(array $array): void
    {
       foreach ($array as $row) {
            // lấy value đầu tiên bất kể key là gì
            $code = trim((string) array_values($row)[0] ?? '');
            if ($code !== '') {
                $this->rows[] = [$code]; // chuẩn hóa lại thành [0 => code]
            }
        }
    }
}
