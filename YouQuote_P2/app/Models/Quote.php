<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'quotes';

    protected $fillable = [
        "content",
        "user_id",
        "nbr_mots",
    ];

    // les relations ********************************************************

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_quote');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'quote_tag');
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorie::class);
    }

}
