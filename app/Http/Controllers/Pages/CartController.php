<?php

namespace App\Http\Controllers\Pages;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\OrderSuccessMail;

use App\Models\ProductDetail;
use App\Models\Cart;
use App\Models\Advertise;
use App\Models\PaymentMethod;
use App\Models\Order;
use App\Models\OrderDetail;


class CartController extends Controller
{
  public function addCart(Request $request) {

    $product = ProductDetail::where('id',$request->id)
    ->with(['product' => function($query) {
      $query->select('id', 'name', 'image', 'sku_code', 'RAM', 'ROM');
    }])->select('id', 'product_id', 'color', 'quantity', 'sale_price', 'promotion_price', 'promotion_start_date', 'promotion_end_date')->first();

    if(!$product) {
      $data['msg'] = 'Product Not Found!';
      return response()->json($data, 404);
    }

    $oldCart = Session::has('cart') ? Session::get('cart') : NULL;
    $cart = new Cart($oldCart);
    if(!$cart->add($product, $product->id, $request->qty)) {
      $data['msg'] = 'Số lượng sản phẩm trong giỏ vượt quá số lượng sản phẩm trong kho!';
      return response()->json($data, 412);
    }
    Session::put('cart', $cart);

    $data['msg'] = "Thêm giỏ hàng thành công";
    $data['url'] = route('home_page');
    $data['response'] = Session::get('cart');

    return response()->json($data, 200);
  }

  public function removeCart(Request $request) {

    $oldCart = Session::has('cart') ? Session::get('cart') : NULL;
    $cart = new Cart($oldCart);

    if(!$cart->remove($request->id)) {
      $data['msg'] = 'Sản Phẩm không tồn tại!';
      return response()->json($data, 404);
    } else {
      Session::put('cart', $cart);

      $data['msg'] = "Xóa sản phẩm thành công";
      $data['url'] = route('home_page');
      $data['response'] = Session::get('cart');

      return response()->json($data, 200);
    }
  }

  public function updateCart(Request $request) {
    $oldCart = Session::has('cart') ? Session::get('cart') : NULL;
    $cart = new Cart($oldCart);
    if(!$cart->updateItem($request->id, $request->qty)) {
      $data['msg'] = 'Số lượng sản phẩm trong giỏ vượt quá số lượng sản phẩm trong kho!';
      return response()->json($data, 412);
    }
    Session::put('cart', $cart);

    $response = array(
      'id' => $request->id,
      'qty' => $cart->items[$request->id]['qty'],
      'price' => $cart->items[$request->id]['price'],
      'salePrice' => $cart->items[$request->id]['item']->sale_price,
      'totalPrice' => $cart->totalPrice,
      'totalQty' => $cart->totalQty,
      'maxQty'  =>  $cart->items[$request->id]['item']->quantity
    );
    $data['response'] = $response;
    return response()->json($data, 200);
  }

  public function updateMiniCart(Request $request) {
    $oldCart = Session::has('cart') ? Session::get('cart') : NULL;
    $cart = new Cart($oldCart);
    if(!$cart->updateItem($request->id, $request->qty)) {
      $data['msg'] = 'Số lượng sản phẩm trong giỏ vượt quá số lượng sản phẩm trong kho!';
      return response()->json($data, 412);
    }
    Session::put('cart', $cart);

    $response = array(
      'id' => $request->id,
      'qty' => $cart->items[$request->id]['qty'],
      'price' => $cart->items[$request->id]['price'],
      'totalPrice' => $cart->totalPrice,
      'totalQty' => $cart->totalQty,
      'maxQty'  =>  $cart->items[$request->id]['item']->quantity
    );
    $data['response'] = $response;
    return response()->json($data, 200);
  }

  public function showCart() {

    $advertises = Advertise::where([
      ['start_date', '<=', date('Y-m-d')],
      ['end_date', '>=', date('Y-m-d')],
      ['at_home_page', '=', false]
    ])->latest()->limit(5)->get(['product_id', 'title', 'image']);

    $oldCart = Session::has('cart') ? Session::get('cart') : NULL;
    $cart = new Cart($oldCart);

    return view('pages.cart')->with(['cart' => $cart, 'advertises' => $advertises]);
  }

  public function showCheckout(Request $request)
  {
    if(Auth::check() && !Auth::user()->admin) {
      if($request->has('type') && $request->type == 'buy_now') {
        $payment_methods = PaymentMethod::select('id', 'name', 'describe')->get();
        $product = ProductDetail::where('id',$request->id)
          ->with(['product' => function($query) {
            $query->select('id', 'name', 'image', 'sku_code', 'RAM', 'ROM');
          }])->select('id', 'product_id', 'color', 'quantity', 'sale_price', 'promotion_price', 'promotion_start_date', 'promotion_end_date')->first();
        $cart = new Cart(NULL);
        if(!$cart->add($product, $product->id, $request->qty)) {
          return back()->with(['alert' => [
              'type' => 'warning',
              'title' => 'Thông Báo',
              'content' => 'Số lượng sản phẩm trong giỏ vượt quá số lượng sản phẩm trong kho!'
          ]]);
        }
        return view('pages.checkout')->with(['cart' => $cart, 'payment_methods' => $payment_methods, 'buy_method' =>$request->type]);
      } elseif($request->has('type') && $request->type == 'buy_cart') {

        $payment_methods = PaymentMethod::select('id', 'name', 'describe')->get();
        $oldCart = Session::has('cart') ? Session::get('cart') : NULL;
        $cart = new Cart($oldCart);
        $cart->update();
        Session::put('cart', $cart);
        return view('pages.checkout')->with(['cart' => $cart, 'payment_methods' => $payment_methods, 'buy_method' =>$request->type]);
      }
    } elseif(Auth::check() && Auth::user()->admin) {
      return redirect()->route('home_page')->with(['alert' => [
        'type' => 'error',
        'title' => 'Thông Báo',
        'content' => 'Bạn không có quyền truy cập vào trang này!'
      ]]);
    } else {
      return redirect()->route('login')->with(['alert' => [
        'type' => 'info',
        'title' => 'Thông Báo',
        'content' => 'Bạn hãy đăng nhập để mua hàng!'
      ]]);
    }
  }

  public function payment(Request $request) {
  $payment_method = PaymentMethod::select('id', 'name')->where('id', $request->payment_method)->first();

  if(!$payment_method) {
    return redirect()->route('home_page')->with(['alert' => [
      'type' => 'error',
      'title' => 'Thông Báo',
      'content' => 'Phương thức thanh toán không hợp lệ.'
    ]]);
  }

  if(Str::contains($payment_method->name, 'COD')) {

    if($request->buy_method == 'buy_now'){

      $order = new Order;
      $order->user_id = Auth::user()->id;
      $order->payment_method_id = $request->payment_method;
      $order->order_code = 'PSO'.str_pad(rand(0, pow(10, 5) - 1), 5, '0', STR_PAD_LEFT);
      $order->name = $request->name;
      $order->email = $request->email;
      $order->phone = $request->phone;
      $order->address = $request->address;
      $order->status = 1;
      $order->save();

      $order_details = new OrderDetail;
      $order_details->order_id = $order->id;
      $order_details->product_detail_id = $request->product_id;
      $order_details->quantity = $request->totalQty;
      $order_details->price = $request->price;
      $order_details->save();

      $this->sendOrderSuccessMail($order);

      $product = ProductDetail::find($request->product_id);
      $product->quantity = $product->quantity - $request->totalQty;
      $product->save();

      return redirect()->route('home_page')->with(['alert' => [
        'type' => 'success',
        'title' => 'Mua hàng thành công',
        'content' => 'Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ của chúng tôi. Sản phẩm của bạn sẽ được chuyển đến trong thời gian sớm nhất.'
      ]]);

    } elseif ($request->buy_method == 'buy_cart') {

      $cart = Session::get('cart');

      $order = new Order;
      $order->user_id = Auth::user()->id;
      $order->payment_method_id = $request->payment_method;
      $order->order_code = 'PSO'.str_pad(rand(0, pow(10, 5) - 1), 5, '0', STR_PAD_LEFT);
      $order->name = $request->name;
      $order->email = $request->email;
      $order->phone = $request->phone;
      $order->address = $request->address;
      $order->status = 1;
      $order->save();

      foreach ($cart->items as $key => $item) {
        $order_details = new OrderDetail;
        $order_details->order_id = $order->id;
        $order_details->product_detail_id = $item['item']->id;
        $order_details->quantity = $item['qty'];
        $order_details->price = $item['price'];
        $order_details->save();

        $product = ProductDetail::find($item['item']->id);
        $product->quantity = $product->quantity - $item['qty'];
        $product->save();
      }

      Session::forget('cart');

      $this->sendOrderSuccessMail($order);

      return redirect()->route('home_page')->with(['alert' => [
        'type' => 'success',
        'title' => 'Mua hàng thành công',
        'content' => 'Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ của chúng tôi. Sản phẩm của bạn sẽ được chuyển đến trong thời gian sớm nhất.'
      ]]);
    }

  } elseif(Str::contains($payment_method->name, 'VNPAY') || Str::contains($payment_method->name, 'Online')) {

    if($request->buy_method == 'buy_now'){

      $order = new Order;
      $order->user_id = Auth::user()->id;
      $order->payment_method_id = $request->payment_method;
      $order->order_code = 'PSO'.str_pad(rand(0, pow(10, 5) - 1), 5, '0', STR_PAD_LEFT);
      $order->name = $request->name;
      $order->email = $request->email;
      $order->phone = $request->phone;
      $order->address = $request->address;

      // status = 0: đơn đã tạo nhưng chưa thanh toán thành công
      $order->status = 0;
      $order->save();

      $order_details = new OrderDetail;
      $order_details->order_id = $order->id;
      $order_details->product_detail_id = $request->product_id;
      $order_details->quantity = $request->totalQty;
      $order_details->price = $request->price;
      $order_details->save();

      $totalAmount = $order_details->price * $order_details->quantity;

      return $this->redirectToVnpay($request, $order, $totalAmount);

    } elseif ($request->buy_method == 'buy_cart') {

      $cart = Session::get('cart');

      $order = new Order;
      $order->user_id = Auth::user()->id;
      $order->payment_method_id = $request->payment_method;
      $order->order_code = 'PSO'.str_pad(rand(0, pow(10, 5) - 1), 5, '0', STR_PAD_LEFT);
      $order->name = $request->name;
      $order->email = $request->email;
      $order->phone = $request->phone;
      $order->address = $request->address;

      // status = 0: đơn đã tạo nhưng chưa thanh toán thành công
      $order->status = 0;
      $order->save();

      foreach ($cart->items as $key => $item) {
        $order_details = new OrderDetail;
        $order_details->order_id = $order->id;
        $order_details->product_detail_id = $item['item']->id;
        $order_details->quantity = $item['qty'];
        $order_details->price = $item['price'];
        $order_details->save();
      }

      $totalAmount = $cart->totalPrice;

      Session::forget('cart');

      return $this->redirectToVnpay($request, $order, $totalAmount);
    }
  }

  return redirect()->route('home_page')->with(['alert' => [
    'type' => 'error',
    'title' => 'Thông Báo',
    'content' => 'Không thể xử lý thanh toán.'
  ]]);
}

private function redirectToVnpay(Request $request, Order $order, $totalAmount)
{
    $vnp_Url = trim(env('VNPAY_URL'));
    $vnp_Returnurl = trim(env('VNPAY_RETURN_URL'));
    $vnp_TmnCode = trim(env('VNPAY_TMN_CODE'));
    $vnp_HashSecret = trim(env('VNPAY_HASH_SECRET'));

    $vnp_TxnRef = $order->order_code;
    $vnp_OrderInfo = 'Thanh toan don hang ' . $order->order_code;
    $vnp_OrderType = 'billpayment';
    $vnp_Amount = (int)$totalAmount * 100;
    $vnp_Locale = 'vn';
    $vnp_IpAddr = $request->ip();

    $inputData = [
        "vnp_Version" => "2.1.0",
        "vnp_TmnCode" => $vnp_TmnCode,
        "vnp_Amount" => $vnp_Amount,
        "vnp_Command" => "pay",
        "vnp_CreateDate" => date('YmdHis'),
        "vnp_CurrCode" => "VND",
        "vnp_IpAddr" => $vnp_IpAddr,
        "vnp_Locale" => $vnp_Locale,
        "vnp_OrderInfo" => $vnp_OrderInfo,
        "vnp_OrderType" => $vnp_OrderType,
        "vnp_ReturnUrl" => $vnp_Returnurl,
        "vnp_TxnRef" => $vnp_TxnRef,
    ];

    ksort($inputData);

    $query = "";
    $hashdata = "";
    $i = 0;

    foreach ($inputData as $key => $value) {
        if ($i == 1) {
            $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashdata .= urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }

        $query .= urlencode($key) . "=" . urlencode($value) . '&';
    }

    $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);

    $vnp_Url = $vnp_Url . "?" . $query . 'vnp_SecureHash=' . $vnpSecureHash;

    return redirect()->away($vnp_Url);
}

public function vnpayReturn(Request $request)
{
  $vnp_HashSecret = env('VNPAY_HASH_SECRET');
  $inputData = $request->all();

  $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';

  unset($inputData['vnp_SecureHash']);
  unset($inputData['vnp_SecureHashType']);

  ksort($inputData);

  $hashData = '';
  foreach ($inputData as $key => $value) {
    $hashData .= ($hashData ? '&' : '') . urlencode($key) . '=' . urlencode($value);
  }

  $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

  if ($secureHash == $vnp_SecureHash) {

    if ($request->vnp_ResponseCode == '00' && $request->vnp_TransactionStatus == '00') {

      $order = Order::where('order_code', $request->vnp_TxnRef)->first();

      if ($order) {

        // Chỉ trừ kho 1 lần, tránh F5 trang return bị trừ tồn kho nhiều lần
        if ($order->status != 1) {
          $order->status = 1;
          $order->save();

          foreach ($order->order_details as $order_detail) {
            $product_detail = ProductDetail::where('id', $order_detail->product_detail_id)->first();

            if ($product_detail) {
              $product_detail->quantity = $product_detail->quantity - $order_detail->quantity;
              $product_detail->save();
            }
          }
          $this->sendOrderSuccessMail($order);
        }

        return redirect()->route('home_page')->with(['alert' => [
          'type' => 'success',
          'title' => 'Thanh toán thành công',
          'content' => 'Đơn hàng của bạn đã được thanh toán thành công qua VNPAY.'
        ]]);
      }

      return redirect()->route('home_page')->with(['alert' => [
        'type' => 'error',
        'title' => 'Không tìm thấy đơn hàng',
        'content' => 'Không tìm thấy đơn hàng tương ứng với giao dịch VNPAY.'
      ]]);

    } else {

      return redirect()->route('home_page')->with(['alert' => [
        'type' => 'error',
        'title' => 'Thanh toán thất bại',
        'content' => 'Giao dịch VNPAY không thành công hoặc đã bị hủy.'
      ]]);
    }
  }

  return redirect()->route('home_page')->with(['alert' => [
    'type' => 'error',
    'title' => 'Lỗi xác thực thanh toán',
    'content' => 'Chữ ký thanh toán VNPAY không hợp lệ.'
  ]]);
}
private function sendOrderSuccessMail(Order $order)
{
    $order->load('order_details.product_detail.product', 'payment_method');

    try {
        Mail::to($order->email)->send(new OrderSuccessMail($order));
    } catch (\Exception $e) {
        Log::error('Lỗi gửi email đơn hàng: ' . $e->getMessage());
    }
}
  public function responsePayment(Request $request) {
  return redirect()->route('home_page')->with(['alert' => [
    'type' => 'info',
    'title' => 'Thông Báo',
    'content' => 'Chức năng thanh toán Ngân Lượng đã được thay bằng VNPAY.'
  ]]);
}
}
