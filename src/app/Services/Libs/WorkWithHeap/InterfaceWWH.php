<?php

namespace App\Services\Libs\WorkWithHeap;

interface InterfaceWWH
{
    public function initID();       // 1. создание идентификатора - initID
    public function isID();         // 2. проверка на существование идентификатора в куче - isID
    public function sizeBlock();    // 3. размер блока в куче - sizeBlock
    public function createBlock();  // 4. выделение блока в куче - createBlock
    public function ckearBlock();   // 5. очистка блока в куче - ckearBlock
    public function writeBlock();   // 6. запись блока в куче - writeBlock
    public function readBlock();    // 7. чтение блока в куче - readBlock
    public function deleteBlock();  // 8. удаление блока в куче - deleteBlock
}
