<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'user_id' => $this->user_id,
            'contact_group_id' => $this->contact_group_id,
            'contact_name' => $this->contact_name,
            'contact_number' => $this->contact_number,
            'contact_file_address' => $this->contact_file_address,
            'email' => $this->email,
            'gender' => $this->gender,
            'dob' => $this->dob,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'contactGroup' => $this->contactGroup
        ];
    }
}
