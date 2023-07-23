<?php

namespace App\Core\OperatorsGateways;

use App\Operator;
use App\Datatables\DataTableClass;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\OperatorResource;
use App\Core\OperatorsGateways\Operators;
use Illuminate\Http\Request;

class OperatorsDetails implements Operators
{
    /**
     * Add new operator
     *
     * @param array $data
     * @return void
     */
    public function addOperator(array $data)
    {
        if (! is_array($data)) {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }

        return new OperatorResource(Operator::create([
            'operator_name' => $data['operator_name'],
            'operator_prefix' => $data['operator_prefix'],
            'status' => $data['status'],
            'created_by' => $data['created_by']
        ]));
    }


    /**
     * SMS operators list
     *
     * @return void
     */
    public function showOperators()
    {
       
        // DB table to use
            $table = 'rootoperators';

            // Table's primary key
            $primaryKey = 'operator_id';

            // Array of database columns which should be read and sent back to DataTables.
            // The `db` parameter represents the column name in the database, while the `dt`
            // parameter represents the DataTables column identifier. In this case simple
            // indexes
            
            $columns = array(
                array( 'db' => 'operator_id', 'dt' => 0 ),
                array( 'db' => 'operator_name', 'dt' => 1 ),
                array( 'db' => 'operator_prefix',  'dt' => 2 ),
                array( 'db' => 'status',   'dt' => 3 ),
                array( 'db' => 'name',   'dt' => 4 ),
                array( 'db' => 'root_user_id',   'dt' => 5 ),
                array( 'db' => 'gateway_user',   'dt' => 6 ),
                array( 'db' => 'gateway_password',   'dt' => 7 ),
                array( 'db' => 'api_url',   'dt' => 8 ),
                array( 'db' => 'gateway_status',   'dt' => 9 ),
                array( 'db' => 'gateway_created_by',   'dt' => 10 ),
                array( 'db' => 'gateway_updated_by',   'dt' => 11 ),
                array( 'db' => 'gateway_id',   'dt' => 12 ),
            );

            /*$data = [];
            $operatorgateways = Operator::with('rootUser')->with('gateways')->get();
            foreach($operatorgateways as $operatorgateway) {
                foreach($operatorgateway->gateways as $gateway) {
                    $data['data'][] = [
                        'operator_id' => $operatorgateway->id,
                        'operator_name' => $operatorgateway->operator_name,
                        'operator_prefix' => $operatorgateway->operator_prefix,
                        'status' => $operatorgateway->status,
                        'name' => $operatorgateway->rootUser->name,
                        'root_user_id' => $operatorgateway->rootUser->id,
                        'gateway_user' => $gateway->user,
                        'gateway_password' => $gateway->password,
                        'api_url' => $gateway->api_url,
                        'gateway_status' => $gateway->status,
                        'gateway_created_by' => $gateway->created_by,
                        'gateway_updated_by' => $gateway->updated_by,
                        'gateway_created_name' => $operatorgateway->rootUser->name,
                        'gateway_updated_name' => $operatorgateway->rootUser->name,
                    ];
                }
            }

            return $data;
            */
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

            

            echo json_encode(DataTableClass::simple( $_GET, $sql_details, $table, $primaryKey,$columns));
    }

    

    /**
     * Update sms operator
     *
     * @param int $id
     * @return void
     */
    public function updateOperator($data)
    {
        $operator = new OperatorResource(Operator::where('id',$data['id'])->first());

        if ($operator) {
            return new OperatorResource($operator->update([
                //'operator_name' => $data['operatorname'],
                'operator_prefix' => $data['operatorprefix'],
                'status' => $data['status'],
                'created_by' => $data['createdby']
            ]));
        }

        return response()->json(['errmsg' => 'Operator Not Found'], 406);
    }

    public function deleteOperator($id)
    {
        if (! isset($id) || empty($id))
        {
            return response()->json(['errmsg' => 'Operator ID missing']);
        }

        $checkOperator = new OperatorResource(Operator::where('id', $id)->with('gateways')->first());

        if (! $checkOperator->gateways->isEmpty())
        {
            return response()->json(['errmsg' => 'Child record found, You can not delete parent record!'], 406);
        }
        new OperatorResource(Operator::where('id', $id)->delete());

        return response()->json(['msg' => 'Operator deleted successfully']);
    }

    public function getOperators()
    {
        return OperatorResource::collection(Operator::where('status','y')->get());
    }
}