<?php

namespace Increment\Marketplace\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class Product extends APIModel
{
    protected $table = 'products';
    protected $fillable = ['code', 'account_id', 'title',  'description', 'tags', 'price_settings', 'status'];


    public function setProductAttribute($value){
        return $this->attributes['details'] = json_endcode(array("solvent" => "", "safety" => "", "formulation" => "", "group"=> '', "active"=> array(), "safety_equipment" => array(), "mixing_order"=> array(), "files"=> array("url"=> '', "title"=> '')));
    }

    // protected $attributes = [
    //     'details' => '[
    //         {solvent: ""}
    //         {safety: ""}
    //         {formulation: ""}
    //         {group: ""}
    //         {active: ""}
    //         {safety_equipment: []}
    //         {mixing_order: []}
    //         {files: 
    //             [{url: ''},
    //             {title: ''}
    //             ]
    //         }
    //     ]',
    // ];

    // protected $casts = [
    //     'details' => 'array',
    // ];
    
}
