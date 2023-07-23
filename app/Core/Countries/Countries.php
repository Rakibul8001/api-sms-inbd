<?php 

namespace App\Core\Countries;

use App\Country;
use App\Http\Resources\CountryResource;
use App\Core\Countries\CountriesInterface;
use App\Http\Resources\CountryResourceCollection;

class Countries implements CountriesInterface
{
    public function show()
    {
        return new CountryResourceCollection(Country::all());
    }
}
