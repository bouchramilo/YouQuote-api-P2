<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
      use HasFactory;

      protected $table = 'tags';

      protected $fillable = [
          "name",
      ];


      // Les relations **************************************************************************************************************
    public function quotes()
    {
        return $this->belongsToMany(Quote::class, 'quote_tag');
    }
}
