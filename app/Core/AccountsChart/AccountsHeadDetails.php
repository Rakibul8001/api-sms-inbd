<?php

namespace App\Core\AccountsChart;

use App\AccountHead;
use App\Core\AccountsChart\AccountsHead;
use App\Http\Resources\AccountHeadResource;

class AccountsHeadDetails implements AccountsHead
{
    /**
     * Add accounts root head
     *
     * @param array $data
     * @return void
     */
    public function addAccountsHead(array $data)
    {
        if (! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }

        return new AccountHeadResource(AccountHead::create([
            'acc_head' => $data['acc_head'],
            'parent_id' => $data['parent_id'],
            'status' => $data['status'],
            'created_by' => $data['created_by'],
            'updated_by' => $data['updated_by'],
            'user_type' => $data['user_type'],
            'account_type' => $data['account_type'],
        ]));
    }

    /**
     * Edit accounts head by id
     *
     * @param int $id
     * @return void
     */
    public function editAccountsHead(array $data)
    {
        if (! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }

        if ($check = $this->getAccountsHeadById($data['id']))
        {
            $check->update([
                'acc_head' => $data['acc_head'],
                'status' => $data['status'],
                'updated_at' => $data['updated_at']
            ]);

            return response()->json(['msg' => 'Account\'s successfully updated'], 200);
        }

        return $this->getAccountsHeadById($data['id']);
    }

    /**
     * Get a accounts head by id
     *
     * @param int $id
     * @return void
     */
    public function getAccountsHeadById($id)
    {
        if (AccountHead::where('id',$id)->exists())
        {
            return new AccountHeadResource(AccountHead::where('id',$id)->first());
            
        }

        return response()->json(['errmsg' => 'ID Not Found'], 406);
    }

    /**
     * Get all accounts head list
     *
     * @return void
     */
    public function getAllRootAccountsHead()
    {
        $data = [];
        $accountsheads = AccountHeadResource::collection(AccountHead::where('account_type','parent')->get());

        foreach($accountsheads as $account)
        {
            $data['data'][] = [
                'id' => $account->id,
                'acc_head' => $account->acc_head,
                'parent' => $account->parent_id == 0 ? '' : $account->parent->acc_head,
                'created_by' => $account->createdby->name,
                'updated_by' => $account->updatedby->name,
                'status' => $account->status,
                'user_type' => $account->user_type,
            ];
        }

        return $data;
    }


    /**
     * Get all group accounts head
     *
     * @return void
     */
    public function getAllGroupAccountsHead()
    {
        $data = [];
        $accountsheads = AccountHeadResource::collection(AccountHead::where('account_type','group')->get());

        foreach($accountsheads as $account)
        {
            $data['data'][] = [
                'id' => $account->id,
                'acc_head' => $account->acc_head,
                'parent' => $account->parent_id == 0 ? '' : $account->parent->acc_head,
                'created_by' => $account->createdby->name,
                'updated_by' => $account->updatedby->name,
                'status' => $account->status,
                'user_type' => $account->user_type,
            ];
        }

        return $data;
    }
    /**
     * Get all account's head under a group
     *
     * @return void
     */
    public function getAllGroupAccountsHeadById($group_id)
    {
        $data = [];
        $accountsheads = AccountHeadResource::collection(AccountHead::where('account_type','transection')->where('parent_id',$group_id)->get());

        foreach($accountsheads as $account)
        {
            $data['data'][] = [
                'id' => $account->id,
                'acc_head' => $account->acc_head,
                'parent' => $account->parent_id == 0 ? '' : $account->parent->acc_head,
                'created_by' => $account->createdby->name,
                'updated_by' => $account->updatedby->name,
                'status' => $account->status,
                'user_type' => $account->user_type,
            ];
        }

        return $data;
    }

        /**
     * Get all transection accounts head
     *
     * @return void
     */
    public function getAllTransectionAccountsHead()
    {
        $data = [];
        $accountsheads = AccountHeadResource::collection(AccountHead::where('account_type','transection')->get());

        foreach($accountsheads as $account)
        {
            $data['data'][] = [
                'id' => $account->id,
                'acc_head' => $account->acc_head,
                'rootparent' => $account->parent_id == 0 ? '' : $account->parent->parent->acc_head,
                'parent' => $account->parent_id == 0 ? '' : $account->parent->acc_head,
                'created_by' => $account->createdby->name,
                'updated_by' => $account->updatedby->name,
                'status' => $account->status,
                'user_type' => $account->user_type,
            ];
        }

        return $data;
    }

    /**
     * Delete an account's head
     *
     * @param int $id
     * @return void
     */
    public function deleteAccountsHead($id)
    {
        if (! isset($id) || empty($id))
        {
            return response()->json(['errmsg' => 'Record Id Not Found'], 406);
        }

        if (! $check = $this->getAccountsHeadById($id))
        {
            return response()->json(['errmsg' => 'Record Not Found'], 406);
        }

        $check = $this->getAccountsHeadById($id);

        if ($check->child->isEmpty()) 
        {
            $check->delete();

            return response()->json(['msg' => 'Record Deleted Successfully'], 200);
        }

        return response()->json(['errmsg' => 'Child record found, You can\'t delete parent record'], 406);

    }
}