<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContactGroupResource extends JsonResource
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
            'group_name' => $this->group_name,
            'status' => $this->status,
            'contacts' => ContactResource::collection($this->contacts),
            'totalContacts' => $this->totalContacts()  
        ];
    }
}
