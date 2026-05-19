<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class Order extends Model
{
  public function user() {
    return $this->belongsTo('App\Models\User');
  }
  public function payment_method() {
    return $this->belongsTo('App\Models\PaymentMethod');
  }
  public function order_details() {
    return $this->hasMany('App\Models\OrderDetail');
  }
  protected $guarded = [''];

  protected $status = [
    '1' => [
        'class' => 'default',
        'name'  => 'Đang Xử Lý'
    ],

    '2' => [
      'class' => 'info',
      'name'  => 'Đang Vận Chuyển'
    ],

    '3' => [
      'class' => 'success',
      'name'  => 'Đã Giao Hàng'
    ],

    '-1' => [
      'class' => 'danger',
      'name'  => 'Hủy'
    ],

  ];

  public function getStatus(){
    return Arr::get($this->status,"[N\A]");
  }
  
}
