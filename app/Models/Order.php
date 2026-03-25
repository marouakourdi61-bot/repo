<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    // Allow these fields to be saved to the 'orders' table
    protected $fillable = ['user_id', 'processed_at'];

    // Converts the database string 'processed_at' into a Carbon date 
    protected $casts = [
        
        'processed_at' => 'datetime',
    ];

    /**
     * THE RELATIONSHIP: An Order belongs to many Products.
     * This looks for the 'order_product' pivot table automatically.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class)
                    ->withPivot('quantity', 'price') // Grabs the extra columns from the middle table
                    ->withTimestamps();
    }

    
    public function markAsProcessed()
    {
        return $this->update(['processed_at' => now()]);
    }
}