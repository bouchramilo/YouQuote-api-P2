<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorie extends Model
{
    use HasFactory;

    protected $table = 'favories';

    protected $fillable = [
        "user_id",
        "quote_id",
    ];

    // les relations ********************************************************

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

}
