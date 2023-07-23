<?php 
namespace App\Core\OperatorsGateways;

use App\Gateway;
use App\Datatables\DataTableClass;
use App\Http\Resources\GateWayResource;
use App\Core\OperatorsGateways\OperatorsApi;
use App\Http\Resources\GateWayResourceCollection;

class OperatorsApiDetails implements OperatorsApi
{
    /**
     * Add Gateway API
     *
     * @param array $data
     * @return void
     */
    public function addGateWayApi(array $data)
    {
        if(! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'],406);
        }

        if (! empty($data['gateway_id']))
        {
            return $this->updateGatewayApi($data);
        }

        $gatewayapi = new GateWayResource(Gateway::create([
            'gateway_name' => $data['gateway_name'],
            'user' => $data['user'],
            'password' => $data['password'],
            'api_url' => $data['api_url'],
            'status' => $data['status'],
            'created_by' => $data['created_by'],
            'updated_by' => $data['updated_by']
        ]));

        return 'API gateway created successfully';
    }

    /**
     * Update Gateway API
     *
     * @param int $id
     * @return void
     */
    public function updateGatewayApi(array $data)
    {
        if(! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'],406);
        }

        $gatewayapi = new GateWayResource(Gateway::where('id', $data['gateway_id'])->update([
            'gateway_name' => $data['gateway_name'],
            'user' => $data['user'],
            'password' => $data['password'],
            'api_url' => $data['api_url'],
            'status' => $data['status'],
            'created_by' => $data['created_by'],
            'updated_by' => $data['updated_by']
        ]));

        return 'API gateway updated successfully';
    }

    /**
     * Get an api details
     *
     * @param int $id
     * @return void
     */
    public function getGatewayApi($id)
    {
        if (! isset($id) || !empty($id))
        {
            return response()->json(['errmsg'=> 'ID not provided'], 406);
        }

        return new GateWayResource(Gateway::where('id', $id)->first());
    }

    /**
     * Show all Api Gateways
     *
     * @return void
     */
    public function showApiGateways()
    {
        //return new GateWayResourceCollection(Gateway::all());

        // DB table to use
        $table = 'gateways';

        // Table's primary key
        $primaryKey = 'id';

        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        
        $columns = array(
            array( 'db' => 'id', 'dt' => 0 ),
            array( 'db' => 'gateway_name', 'dt' => 1 ),
            array( 'db' => 'user',  'dt' => 2 ),
            array( 'db' => 'password',   'dt' => 3 ),
            array( 'db' => 'api_url',   'dt' => 4 ),
            array( 'db' => 'status',   'dt' => 5 ),
        );

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

        

        echo json_encode(DataTableClass::simple( $_GET, $sql_details, $table, $primaryKey,$columns));
    }

    public function getGateways()
    {
        return new GateWayResourceCollection(Gateway::where('status','y')->get());
    }
}