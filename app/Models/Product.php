<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Product extends Model
{
    use HasFactory;

    // This allows you to use Product::create([...]) safely
    protected $fillable = ['name', 'price', 'stock'];

    // Ensures the database numbers stay as integers in PHP
    protected $casts = [
        'price' => 'integer',
        'stock' => 'integer',
    ];
}
