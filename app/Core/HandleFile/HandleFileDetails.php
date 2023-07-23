<?php

namespace App\Core\HandleFile;

use App\Core\HandleFile\HandleFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;

class HandleFileDetails implements HandleFile {

    /**
     * Contact upload filename
     *
     * @var string
     */
    protected $file;

    /**
     * File extention
     *
     * @var string
     */
    protected $extension;


    public function addFile(Request $request)
    {
        if($request->hasFile('file')){

            //$request->nationalid->getClientOriginalName();

            if (empty($request->user))
            {
                $userid = Auth::guard('web')->user()->id;
            } else {
                $userid = $request->user->id;
            }

            $filename = date("YmdHis").$userid.".".$request->file->getClientOriginalExtension();

            $this->file = $filename;
            $this->extension = $request->file->getClientOriginalExtension();

            $request->file
                    ->storeAs('public/templateContent',$filename);

        }

        return $this->file;
    }

    public function readXlsFileFirstRow()
    {
        if (! $this->extension == 'xls' || ! $this->extension == 'xlsx')
        {
            return response()->json(['errmsg' => 'File must be xls or xlsx type'], 406);
        }

        $tokens = [];

        $smsnumber = '';
        
        $datarowcol = [];

        if(storage_path('app/public/templateContent/'.$this->file))
        {
            try {
                $objPHPExcel = IOFactory::load(storage_path('app/public/templateContent/'.$this->file));
            } catch(\Exception $e) {
                die('Error loading file "'.storage_path('app/public/templateContent/'.$this->file).'": '.$e->getMessage());
            }
            
            //$sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
            $sheetData = $objPHPExcel->getActiveSheet();

            $highestRow = $sheetData->getHighestRow();

            $highestColumn = $sheetData->getHighestColumn();

            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            $h = 0;
            
            for ($row = 1; $row <= $highestRow; ++$row) {
                
                if ($h == 0) {
                    for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                            
                            $datarowcol[] = $sheetData->getCellByColumnAndRow($col, $row)->getValue();

                    }
                    
                    $h = 1;
                }
            }
        }

        return $datarowcol;
    }

    public function readXlsFile()
    {
        if (! $this->extension == 'xls' || ! $this->extension == 'xlsx')
        {
            return response()->json(['errmsg' => 'File must be xls or xlsx type'], 406);
        }

        $tokens = [];

        $smsnumber = '';
        
        
        if(storage_path('app/public/templateContent/'.$this->file))
        {
            try {
                $objPHPExcel = IOFactory::load(storage_path('app/public/templateContent/'.$this->file));
            } catch(\Exception $e) {
                die('Error loading file "'.storage_path('app/public/templateContent/'.$this->file).'": '.$e->getMessage());
            }
            
            $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);

            $dataindex = [];

            if (count($sheetData) > 0)
            {
                return $sheetData;
                //foreach($sheetData as $key=>$xlsData){

                    //array_push($dataindex, $xlsData);

                    //if($key!=1) { 

                        //array_push($dataindex, $xlsData);

                        //$dataindex[] = $xlsData;
                
                        //$number = substr(mobilenumb($xlsData['A']), 0, 11);	 
                    
                        //if(strlen($number) >= 11 && is_numeric($number)){
                                    
                        //    $numberArr[]= $number; 
                        //} 
                                        
                    //}
                //}
            }
        }
        if (! isset($dataindex)){

            return response()->json(['errmsg' => 'File Format is Wrong'], 406);

        }

        return $dataindex;
    }

    public function readCsvFile()
    {
        if (! $this->extension === 'csv')
        {
            return response()->json(['errmsg' => 'File must be CSV type'], 406);
        }

        $numberArr = $nameArr = $emailArr = $genderArr= $dobArr = [];
        if(storage_path('app/public/contacts/'.$this->file))
        {
            $handle = fopen(storage_path('app/public/contacts/'.$this->file), "r");
            $tokens = [];
            $pattern = '[[%s]]';
            $key = 0 ;
            while (($CsvData = fgetcsv($handle, 1000, ",")) !== FALSE) 
            {
                $number = substr(mobilenumb($CsvData[0]), 0, 11) ;	

                if(strlen($number) >= 11 && is_numeric($number)){  
                    
                    $numberArr[]= $number;
                                    
                }
                $key++;
            }
            fclose($handle);
            
            if (! isset($numberArr)){

                return response()->json(['errmsg' => 'File Format is Wrong'], 406);
    
            }

            return $numberArr;
        }
    }

    public function readTextFile()
    {
        
    }

    /**
     * Get file extension
     *
     * @return void
     */
    public function getFileExtension(){
        return $this->extension;
    }

    /**
     * Get uploaded file name
     *
     * @return void
     */
    public function getFileName()
    {
        return $this->file;
    }
}