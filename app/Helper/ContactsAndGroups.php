<?php

namespace App\Helper;

use App\Models\ContactGroup;


use Illuminate\Http\Request;
use App\Resources\ContactGroupResource;
use App\Http\Resources\ContactResource;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;

use Slim\Http\UploadedFile;

class ContactsAndGroups
{

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

    /**
     * File extention
     *
     * @var string
     */
    protected $extension;


    /**
     * Get contact group by clientid and groupid
     *
     * @param int $clientid
     * @param int $groupid
     * @return void
     */
    public static function getGroupByClientAndId($clientid, $groupid)
    {
        $groups = ContactGroup::where('user_id', $clientid)
                                    ->where('id',$groupid)
                                    ->where('status',1)
                                    ->get();
        return $groups;
    }


    /**
     * Upload client contacts in a group
     *
     * @param Request $request
     * @return void
     */
    public static function addContactFile($userid, $request)
    {

        if($request->getUploadedFiles()){

			//var_dump($request->getUploadedFiles('file')); die();
			$uploadedFiles = $request->getUploadedFiles();

			$uploadedFile = $uploadedFiles['file'];
			

			$extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);

            $filename = date("YmdHis").$userid.".".$extension;

            
            // $this->file = $filename;
            // $this->extension = $request->file->getClientOriginalExtension();

            if ($uploadedFile->getError() === UPLOAD_ERR_OK) {

	            $uploadedFile->moveTo( __DIR__ .'/../../public/contacts/' . DIRECTORY_SEPARATOR . $filename);
	        }

            // $request->file
            //         ->storeAs('public/contacts',$filename);

        }

        $data['filename'] = $filename;
        $data['extension'] = $extension;

        return $data;
    }


    /**
     * Get BD mobile number from xls or xlsx
     *
     * @return void
     */
    public static function getBDMobileNumberFromXlsOrXlsx($file)
    {
        if (! $file['extension'] == 'xls' || ! $file['extension'] == 'xlsx')
        {
            return false;
        }

        $mobileNumbers = array();

        $fileDir = __DIR__ .'/../../public/contacts/' . DIRECTORY_SEPARATOR .$file['filename'];
        
        if(file_exists($fileDir))
        {
            try {
                $objPHPExcel = IOFactory::load($fileDir);
            } catch(\Exception $e) {
                die('Error loading file "'.$fileDir.'": '.$e->getMessage());
            }
            
            $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
            
            if (count($sheetData) > 0)
            {
                foreach($sheetData as $key=>$xlsData){
                    
                    if($mobileNo = ContactsAndGroups::mobileNumber($xlsData['A'])){  
                        array_push($mobileNumbers, $mobileNo);
                    }
                }
            }
        }

        if (isset($mobileNumbers[0])){
            //sort the array in ascending order
            sort($mobileNumbers);
            return $mobileNumbers;
        }

        return false;
    }

    /**
     * Get BD Mobile number from CSV
     *
     * @return void
     */
    public static function getBdMobileNumberFromCSV($file)
    {
        if (! $file['extension'] === 'csv')
        {
            return false;
        }

        $numberArr = $nameArr = $emailArr = $genderArr= $dobArr = [];

        $fileDir = __DIR__ .'/../../public/contacts/' . DIRECTORY_SEPARATOR .$file['filename'];

        if(file_exists($fileDir))
        {
            $handle = fopen($fileDir, "r");
            $tokens = [];
            $pattern = '[[%s]]';
            $key = 0 ;
            
            $mobileNumbers = array();
            while (($CsvData = fgetcsv($handle, 1000, ",")) !== FALSE) 
            {
                if($mobileNo = ContactsAndGroups::mobileNumber($CsvData[0])){  
                    array_push($mobileNumbers, $mobileNo);
                }
            }
            fclose($handle);
            
            if (isset($mobileNumbers[0])){
                sort($mobileNumbers);
                return $mobileNumbers;
    
            }
            return false;
        }
    }

    /**
     * Get BD mobile number from text file
     *
     * @return void
     */
    public static function getBDMobileNumberFromTextFile($file)
    {
        if (! $file['extension'] === 'txt')
        {
            return false;
        }

        $fileDir = __DIR__ .'/../../public/contacts/' . DIRECTORY_SEPARATOR .$file['filename'];

        $contents = file_get_contents($fileDir);

        $contactlist = explode(",",str_replace("\n",",",$contents));

        //get integer format of number: 01723 -> 1723
        $mobileNumbers = array();
        foreach ($contactlist as $mobileNo) {
            
            if($mobileNo = ContactsAndGroups::mobileNumber($mobileNo)){  
                array_push($mobileNumbers, $mobileNo);
            }
        }

        if (isset($mobileNumbers[0])){
            //sort the array in ascending order
            sort($mobileNumbers);

            return $mobileNumbers;
        }

        return false;
        

    }

    public static function mobileNumber($mobileNo){
        
        $validprefix =  ["17","14","13","15","18","16","19"];
        //gp, blx, gpx, ttk, robi, airtel, bl 

        $mobileNo = str_replace('   '," ",$mobileNo);
        $mobileNo = str_replace('-',"",$mobileNo);
        $mobileNo = str_replace(' ',"",$mobileNo);
        $mobileNo = str_replace("\n","",$mobileNo);


        if ($mobileNo && trim($mobileNo)) {
            //remove newline for text files generated on mac
            $mobileNo = preg_replace('/\s+/', ' ', trim($mobileNo));
            $mobileNo = str_replace("o","0", $mobileNo); //replace o with 0

            //get last 10 digit of the number
            if (strlen($mobileNo)>10) {
                $mobileNo = substr($mobileNo, -10);
            }
            if(strlen($mobileNo)==10 && in_array(substr($mobileNo,0,2), $validprefix)){
                return $mobileNo;
            }
            return false;
        }
    }


    /**
     * Get a group by id
     *
     * @param int $groupid
     * @return void
     */
    public function getGroupById($groupid)
    {
        
        
        if (! ContactGroup::where('id', $groupid)->exists())
        {
            return response()->json(['errmsg' => 'Record Not Found'], 406);
        }

        $check = new ContactGroupResource(ContactGroup::where('id', $groupid)->first());
        
        return $check;
    }

    /**
     * Get contact group list by clientid
     *
     * @param int $clientid
     * @return void
     */
    public function getGroupsByClient($clientid)
    {
        $groups = ContactGroupResource::collection(
            ContactGroup::where('user_id', $clientid)
                        ->where('status',1)
                        ->get()
        );
        return $groups;
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


    

    

    

    


    /**
     * Get all contacts in a group
     *
     * @param int $contact_group_id
     * @return void
     */
    public function getContactGroupById($contact_group_id)
    {
        return ContactResource::collection(
            Contact::where('contact_group_id', $contact_group_id)->get()
        );
    }

    /**
     * Get contact by groupid and contactid
     *
     * @param int $contact_group_id
     * @param int $contactid
     * @return void
     */
    public function getContactByGroupAndContactId($contact_group_id, $contactid)
    {
        if (Contact::where('contact_group_id', $contact_group_id)
                    ->where('id',$contactid)
                    ->exists()
        )
        {
            return new ContactResource(Contact::where('contact_group_id', $contact_group_id)->where('id',$contactid)->first());
        }

        return false;
    }

    /**
     * Get contact by groupid and contactid
     *
     * @param int $contact_group_id
     * @param int $contactid
     * @return void
     */
    public function getContactByGroupAndContactNumber($contact_group_id, $contactnumber)
    {
        if (Contact::where('contact_group_id', $contact_group_id)
                    ->where('contact_number',$contactnumber)
                    ->exists()
        )
        {
            return new ContactResource(
                Contact::where('contact_group_id', $contact_group_id)
                        ->where('contact_number',$contactnumber)
                        ->first()
            );
        }

        return false;
    }

    /**
     * Update contact group
     *
     * @param array $data
     * @return void
     */
    public function updateContactGroup(array $data)
    {
        
        if (Contact::where('contact_group_id', $data['contact_group_id'])
                    ->where('id', $data['id'])
                    ->exists()
        )
        {
            $check = new ContactResource(
                Contact::where('contact_group_id', $data['contact_group_id'])
                        ->where('id', $data['id'])
                        ->first()
            );

            $check->update([
                'contact_name' => $data['contact_name'],
                'contact_number' => $data['contact_number'],
                'email' => $data['email'],
                'gender' => $data['gender'],
                'dob' => $data['dob'],
                'status' => $data['status']
            ]);

            return response()->json(['msg' => 'Contact updated successfully'], 200);
        }

        return response()->json(['errmsg' => 'Contact number not found'], 406);
    }

    /**
     * Update contact group
     *
     * @param array $data
     * @return void
     */
    public function updateContactGroupByContactNumber(array $data)
    {
        

        if (Contact::where('contact_group_id', $data['contact_group_id'])
                    ->where('contact_number', $data['contact_number'])
                    ->exists()
        )
        {
            $check = new ContactResource(
                Contact::where('contact_group_id', $data['contact_group_id'])
                        ->where('contact_number', $data['contact_number'])
                        ->first()
            );

            $check->update([
                'contact_name' => $data['contact_name'],
                'contact_group_id' => $data['contact_group_id'],
                'contact_number' => $data['contact_number'],
                'email' => $data['email'],
                'gender' => $data['gender'],
                'dob' => $data['dob'],
                'status' => $data['status']
            ]);

            return response()->json(['msg' => 'Contact updated successfully'], 200);
        }

        return response()->json(['errmsg' => 'Contact number not found'], 406);
    }

    /**
     * Delete all contacts in a group
     *
     * @param int $contact_group_id
     * @return void
     */
    public function deleteContactGroup($contactid)
    {

        if (Contact::where('id', $contactid)->exists())
        {
            $check = new ContactResource(Contact::where('id', $contactid)->first());

            $check->delete();

            return response()->json(['msg' => 'Contact deleted successfully'], 200);
        }

        return response()->json(['errmsg' => 'Contact not found'], 406);
    }
}