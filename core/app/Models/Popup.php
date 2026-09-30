<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Popup extends Model
{
    use HasFactory;

    public function scopeActive($query)
    {
        $today = Carbon::today()->toDateString();

        return $query->where("status", 1)
            ->where(function ($query) use ($today) {
                $query->whereNull("start_date")
                    ->orWhere("start_date", "<=", $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull("end_date")
                    ->orWhere("end_date", ">=", $today);
            });
    }

    public function form()
    {
        return $this->belongsTo('App\Models\WebmasterSection', 'form_id');
    }
}
