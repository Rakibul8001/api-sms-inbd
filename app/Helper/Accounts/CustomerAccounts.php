<?php

namespace App\Helper\Accounts;

use App\Models\Customers;
use App\Models\Accounts\AccountCustomer;





class CustomerAccounts
{

	public static function updateCustomerBalance($customerID, $item, $amountToAdjust)
	{
		//item is the row id of the account_customer table

		//this will check if any previous account data is available or not. if available then last balance will remain there. if not found any data then the new amountToAdjust will be set as the balance of current data.

		$customerAccData = AccountCustomer::where('customer', $customerID)->orderBy('id', 'desc')->get();


		if (count($customerAccData)>1) {
			
			$customerAccount = AccountCustomer::find($item);

			//add the new amount with last record's amount and this becomes the new balance.

			$customerAccount->balance = $customerAccData[1]->balance + $amountToAdjust;

			$customerAccount->save();

			

			$customer =  Customers::find($customerAccount->customer);

			$customer->balance = $customerAccount->balance;

			
			$customer->save();

			return true;



		} else {

			$customerAccount = AccountCustomer::find($item);

			$customerAccount->balance = $amountToAdjust;

			$customerAccount->save();


			$customer =  Customers::find($customerAccount->customer);

			$customer->balance = $customerAccount->balance;

			$customer->save();

			return true;
		}
		
	}	
	
}



