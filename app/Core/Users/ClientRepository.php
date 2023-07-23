<?php

namespace App\Core\Users;

use App\User;
use App\Core\Users\ClientInterface;
use App\Http\Resources\UserResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ClientRepository implements ClientInterface
{

    /**
     * Add new client
     *
     * @param array $data
     * @return void
     */
    public function addClient(array $data)
    {
        if(! is_array($data)) {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }
        
        $check = User::where('email', $data['email'])->first();
        if ($check) {
            return response()->json(['errmsg' => 'Client already exist'], 406);
        }
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'company' => $data['company'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'country' => $data['country'],
            'city' => $data['city'],
            'state' => $data['city'],
            'root_user_id' => $data['root_user_id'],
            'manager_id' => $data['manager_id'],
            'reseller_id' => $data['reseller_id'],
            'created_from' => $data['created_from'],
            'created_by' => $data['created_by'],
            'status' => $data['status'],
            'security_code' => $data['security_code'],
            'phone_verified' => $data['phone_verified'],
            'verified' => $data['verified'],
            'api_token' => $data['api_token']
        ]);

        return $user;
    }


    /**
     * Update client
     *
     * @param array $data
     * @return void
     */
    public function clientUpdate($data)
    {
        //return $data;
        if(! is_array($data)) {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }
        
        $check = User::where('id', $data['id'])->first();

        if (! $check) {
            return back()->withInput();//response()->json(['errmsg' => 'Root already exist'], 406);
        } 

        if ($check) {
            if (! empty($data['password'])) {
                $user = User::where('id',$data['id'])->update([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'password' => Hash::make($data['password']),
                    'company' => $data['company'],
                    'address' => $data['address'],
                    'country' => $data['country'],
                    'city' => $data['city'],
                    'state' => $data['city'],
                    'status' => $data['status'],
                    //'api_token' => Hash::make($data['api_token'])
                ]);
            } else {
                $user = User::where('id',$data['id'])->update([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'company' => $data['company'],
                    'address' => $data['address'],
                    'country' => $data['country'],
                    'city' => $data['city'],
                    'state' => $data['city'],
                    'status' => $data['status'],
                    //'api_token' => Hash::make($data['api_token'])
                ]);
            }
        }

        return $user;
    }

    /**
     * Active client list
     *
     * @return void
     */
    public function activeClients()
    {
        return UserResource::collection(User::where('status','y')->get());
    }

    /**
     * Active reseller client list
     *
     * @return void
     */
    public function activeResellerClients($resellerid)
    {
        return UserResource::collection(User::where('status','y')->where('reseller_id',$resellerid)->get());
    }

    /**
     * Get active user by id
     *
     * @param int $userid
     * @return void
     */
    public function getUserById($userid)
    {
        if (User::where('id', $userid)->where('status','y')->exists())
        {
            return new UserResource(User::where('id', $userid)->where('status','y')->first());
        }

        return false;
    }


    /**
     * Get total users
     *
     * @return void
     */
    public function totalUsers()
    {
        return User::count();
    }

    /**
     * Get total users
     *
     * @return void
     */
    public function resellerTotalUsers($data)
    {
        return User::where('created_by','reseller')->where('reseller_id',$data['reseller_id'])->count();
    }


    /**
     * Get total users under support manager
     *
     * @return void
     */
    public function totalUsersBySupportManager()
    {
        return User::where('manager_id', Auth::guard('manager')->user()->id)
                    ->count();
    }

    /**
     * Get total users under reseller
     *
     * @return void
     */
    public function totalUsersByReseller()
    {
        return User::where('reseller_id', Auth::guard('reseller')->user()->id)
                    ->count();
    }

    /**
     * Toal enroll client in current day
     *
     * @return void
     */
    public function todaysEnrollClientForRoot()
    {
        return User::whereDate('created_at', Carbon::today())
                    ->count();
    }

    /**
     * Toal enroll client in current day
     *
     * @return void
     */
    public function todaysResellerEnrollClientForRoot($data)
    {
        return User::whereDate('created_at', Carbon::today())
                    ->where('created_by','reseller')
                    ->where('reseller_id',$data['reseller_id'])
                    ->count();
    }

    /**
     * Toal enroll client in current month
     *
     * @return void
     */
    public function monthlyEnrollClientForRoot()
    {
        return User::whereMonth('created_at', Carbon::now()->format('m'))
                    ->count();
    }

    /**
     * Toal enroll client in current month
     *
     * @return void
     */
    public function resellerMonthlyEnrollClientForRoot($data)
    {
        return User::whereMonth('created_at', Carbon::now()->format('m'))
                    ->where('created_by','reseller')
                    ->where('reseller_id',$data['reseller_id'])
                    ->count();
    }
}