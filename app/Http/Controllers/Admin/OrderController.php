<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;

class OrderController extends Controller
{
  public function index()
  {
    $orders = Order::select('id', 'user_id','status', 'payment_method_id','status', 'order_code', 'name', 'email', 'phone', 'created_at')->where('status', '<>', 0)->with([
        'user' => function ($query) {
          $query->select('id', 'name');
        },
        'payment_method' => function ($query) {
          $query->select('id', 'name');
        }
      ])->latest()->get();
    return view('admin.order.index')->with('orders', $orders);
  }

  public function show($id)
  {
    $order = Order::select('id', 'user_id', 'payment_method_id', 'order_code', 'name', 'email', 'phone', 'address', 'created_at')->where([['status', '<>', 0], ['id', $id]])->with([
        'user' => function ($query) {
          $query->select('id', 'name', 'email', 'phone', 'address');
        },
        'payment_method' => function ($query) {
          $query->select('id', 'name', 'describe');
        },
        'order_details' => function($query) {
          $query->select('id', 'order_id', 'product_detail_id', 'quantity', 'price')
          ->with([
            'product_detail' => function ($query) {
              $query->select('id', 'product_id', 'color')
              ->with([
                'product' => function ($query) {
                  $query->select('id', 'name', 'image', 'sku_code');
                }
              ]);
            }
          ]);
        }
      ])->first();
    if(!$order) abort(404);
    return view('admin.order.show')->with('order', $order);
  }

  public function actionTransaction($action,$id){
    $orderAction = Order::find($id);
    if($orderAction){
      switch ($action) {
        case 'process':
          $orderAction->status= 2;
          break;
        case 'success':
          $orderAction->status= 3;
          break;
        case 'cancel':
          $orderAction->status= -1;
          break;
      }
      $orderAction->save();
    }
    return redirect()->back();
  }
}
