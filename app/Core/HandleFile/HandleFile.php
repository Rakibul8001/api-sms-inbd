<?php

namespace App\Core\HandleFile;

use Illuminate\Http\Request;

interface HandleFile {

    public function addFile(Request $request);

    public function readXlsFile();

    public function readCsvFile();

    public function readTextFile();

    public function getFileExtension();

    public function getFileName();

    public function readXlsFileFirstRow();
}