<?php

namespace App\Core\ContactsAndGroups;

use App\Contact;
use App\ContactGroup;
use App\Core\ContactsAndGroups\ContactsAndGroups;
use App\Datatables\DataTableClass;
use Illuminate\Http\Request;
use App\Http\Resources\ContactGroupResource;
use App\Http\Resources\ContactResource;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ContactsAndGroupsDetails implements ContactsAndGroups
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
    protected $extension;

    /**
     * Create a group
     *
     * @param array $data
     * @return void
     */
    public function createGroup(array $data)
    {
        if (! is_array($data))
        {
            return response()->json(['errmsg' => 'Data mustbe an array'],406);
        }

        
        if (ContactGroup::where('group_name', $data['group_name'])->exists())
        {
            return response()->json(['errmsg' => 'Group name already exists'], 406);
        }

        if(new ContactGroupResource(ContactGroup::create([
            'user_id' => $data['user_id'],
            'group_name' => $data['group_name'],
            'status' => $data['status']
        ])))
        {
            return response()->json(['msg' => 'Contact group created successfully'], 200);
        }

        return response()->json(['errmsg' => 'Can not create group, there is an error'], 406);
    }

    /**
     * Show all groups
     *
     * @return void
     */
    public function showGroups()
    {
        //return ContactGroupResource::collection(ContactGroup::all());
        // DB table to use
        $table = 'client_contact_groups';

        // Table's primary key
        $primaryKey = 'contactgroupid';

        $where = " user_id = '".Auth::guard('web')->user()->id."'";

        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        
        $columns = array(
            array( 'db' => 'contactgroupid', 'dt' => 0 ),
            array( 'db' => 'group_name', 'dt' => 1 ),
            array( 'db' => 'total_contacts',  'dt' => 2 ),
            array( 'db' => 'status',   'dt' => 3 ),
            array( 'db' => 'user_email',   'dt' => 4 ),
            array( 'db' => 'phone',   'dt' => 5 ),
        );

        // SQL server connection information
        $sql_details = array(
            'user' => env('DB_USERNAME'),
            'pass' => env('DB_PASSWORD'),
            'db'   => env('DB_DATABASE'),
            'host' => env('DB_HOST')
        );


        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
         * If you just want to use the basic configuration for DataTables with PHP
         * server-side, there is no need to edit below this line.
         */

        

        //echo json_encode(DataTableClass::simple( $_GET, $sql_details, $table, $primaryKey,$columns));
        echo json_encode(DataTableClass::complex( $_GET, $sql_details, $table, $primaryKey, $columns, null, $where));
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
     * Get contact group by clientid and groupid
     *
     * @param int $clientid
     * @param int $groupid
     * @return void
     */
    public function getGroupByClientAndId($clientid, $groupid)
    {
        $groups = ContactGroupResource::collection(
                        ContactGroup::where('user_id', $clientid)
                                    ->where('id',$groupid)
                                    ->where('status',1)
                                    ->get()
        );
        return $groups;
    }


    /**
     * Update a group
     *
     * @param array $data
     * @return void
     */
    public function updateGroup(array $data)
    {
        
        
        if (! ContactGroup::where('id', $data['id'])->exists())
        {
            return response()->json(['errmsg' => 'Record Not Found'], 406);
        }

        $check = $this->getGroupById($data['id']);

        $check->update([
            'group_name' => $data['group_name'],
            'status' => $data['status']
        ]);

        return response()->json(['msg' => 'Contact group updated successfully'], 200);
    }

    /**
     * Delete a group
     *
     * @param int $groupid
     * @return void
     */
    public function deleteGroup($groupid)
    {
        
        
        if (! ContactGroup::where('id', $groupid)->exists())
        {
            return response()->json(['errmsg' => 'Record Not Found'], 406);
        }

        $check = $this->getGroupById($groupid);

        if (! $check->contacts->isEmpty())
        {
            return response()->json(['errmsg' => 'Child record found, You can not delete parent record'], 406);
        }

        $check->delete();

        return response()->json(['msg' => 'Contact group deleted successfully'], 200);
    }

    /**
     * Add Contact group
     *
     * @param array $data
     * @return void
     */
    public function addContactGroup(array $data)
    {
        if (! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }


        if ($data['contactformtype'] == 'single')
        {
            $check = $this->getContactByGroupAndContactNumber($data['contact_group_id'], $data['contact_number']);

            if (! $check)
            {
                return new ContactResource(Contact::create([
                    'user_id' => $data['user_id'],
                    'contact_group_id' => $data['contact_group_id'],
                    'contact_name' => $data['contact_name'],
                    'contact_number' => $data['contact_number'],
                    'contact_file_address' => !empty($this->file) ? $this->file : $data['contact_file_address'],
                    'email' => $data['email'],
                    'gender' => $data['gender'],
                    'dob' => $data['dob'],
                    'status' => $data['status']
                ]));
            }

            return $this->updateContactGroupByContactNumber($data);
        } 

        if ($data['contactformtype'] == 'multiple')
        {
            
            $check = $this->getContactByGroupAndContactNumber($data['contact_group_id'], $data['contact_number']);

            if (! $check)
            {
                return new ContactResource(Contact::create([
                    'user_id' => $data['user_id'],
                    'contact_group_id' => $data['contact_group_id'],
                    'contact_name' => $data['contact_name'],
                    'contact_number' => $data['contact_number'],
                    'contact_file_address' => !empty($this->file) ? $this->file : $data['contact_file_address'],
                    'email' => $data['email'],
                    'gender' => $data['gender'],
                    'dob' => $data['dob'],
                    'status' => $data['status']
                ]));
            }

            return $this->updateContactGroupByContactNumber($data);
        } 
        

        return response()->json(['msg' => 'Contact successfully inserted'], 200);
    }

    /**
     * Upload client contacts in a group
     *
     * @param Request $request
     * @return void
     */
    public function addContactFile(Request $request)
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
                    ->storeAs('public/contacts',$filename);

        }

        return $this->file;
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
     * Get BD Mobile number from CSV
     *
     * @return void
     */
    public function getBdMobileNumberFromCSV()
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

    /**
     * Get BD mobile number from xls or xlsx
     *
     * @return void
     */
    public function getBDMobileNumberFromXlsOrXlsx()
    {
        if (! $this->extension == 'xls' || ! $this->extension == 'xlsx')
        {
            return response()->json(['errmsg' => 'File must be xls or xlsx type'], 406);
        }

        $tokens = [];

        $smsnumber = '';
        
        
        if(storage_path('app/public/contacts/'.$this->file))
        {
            try {
                $objPHPExcel = IOFactory::load(storage_path('app/public/contacts/'.$this->file));
            } catch(\Exception $e) {
                die('Error loading file "'.storage_path('app/public/contacts/'.$this->file).'": '.$e->getMessage());
            }
            
            $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);

            if (count($sheetData) > 0)
            {
                foreach($sheetData as $key=>$xlsData){
                
                    if($key!=1) { 
                
                        $number = substr(mobilenumb($xlsData['A']), 0, 11);	 
                    
                        if(strlen($number) >= 11 && is_numeric($number)){
                                    
                            $numberArr[]= $number; 
                        } 
                                        
                    }
                }
            }
        }
        if (! isset($numberArr)){

            return response()->json(['errmsg' => 'File Format is Wrong'], 406);

        }

        return $numberArr;

    }

    /**
     * Get BD mobile number from text file
     *
     * @return void
     */
    public function getBDMobileNumberFromTextFile()
    {
        if (! $this->extension === 'txt')
        {
            return response()->json(['errmsg' => 'File must be txt type'], 406);
        }

        $contents = file_get_contents(storage_path('app/public/contacts/'.$this->file));
        
        $arrfields = explode('    ', $contents); 
        if (! empty($contents))
        {
            foreach($arrfields as $field) {

                preg_match_all('!\d+!', $field, $matches);

                $numberStr = implode(',', $matches[0]);

                if(!empty($numberStr)){

                    $numbers = explode(',', $numberStr); 

                    foreach($numbers as $key=>$number){

                        if(strlen($number) >= 11  && is_numeric($number)){   

                            $numberArr[]= $number;
                        } 
                    }
                }
                    
            }

        }
        if (! isset($numberArr)){

            return response()->json(['errmsg' => 'File Format is Wrong'], 406);

        }
        return $numberArr;

    }

    /**
     * Show all contact in groups
     *
     * @return void
     */
    public function showContactGroups()
    {
        //return ContactResource::collection(Contact::all());
        //return ContactGroupResource::collection(ContactGroup::all());
        // DB table to use
        $table = 'contact_in_group';

        // Table's primary key
        $primaryKey = 'contactid';

        $where = " user_id = '".Auth::guard('web')->user()->id."'";
        
        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        
        $columns = array(
            array( 'db' => 'contactid', 'dt' => 0 ),
            array( 'db' => 'contact_number', 'dt' => 1 ),
            array( 'db' => 'group_name',  'dt' => 2 ),
            array( 'db' => 'contact_status',   'dt' => 3 ),
            array( 'db' => 'user_id',   'dt' => 4 ),
        );

        // SQL server connection information
        $sql_details = array(
            'user' => env('DB_USERNAME'),
            'pass' => env('DB_PASSWORD'),
            'db'   => env('DB_DATABASE'),
            'host' => env('DB_HOST')
        );


        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
         * If you just want to use the basic configuration for DataTables with PHP
         * server-side, there is no need to edit below this line.
         */

        

        echo json_encode(DataTableClass::complex( $_GET, $sql_details, $table, $primaryKey, $columns, null, $where));
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