<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Collection;

use TablePress\PhpOffice\PhpSpreadsheet\Settings;
use TablePress\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class CellsFactory
{
    /**
     * Initialise the cache storage.
     *
     * @param Worksheet $worksheet Enable cell caching for this worksheet
     *
     * @return Cells
     * */
    public static function getInstance(Worksheet $worksheet)
    {
        return new Cells($worksheet, Settings::getCache());
    }
}
