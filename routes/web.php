<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Nạp Controller Auth
use App\Http\Controllers\Auth\RegisterController;

// Nạp Controller Admin (dùng bí danh Admin... để không đụng tên)
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\AdvertiseController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProducerController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\StatisticController;
use App\Http\Controllers\Admin\WarehouseController;

// Nạp Controller Client (Pages)
use App\Http\Controllers\Pages\HomePage;
use App\Http\Controllers\Pages\AboutPage;
use App\Http\Controllers\Pages\ContactPage;
use App\Http\Controllers\Pages\SearchController;
use App\Http\Controllers\Pages\PostController;
use App\Http\Controllers\Pages\OrderController;
use App\Http\Controllers\Pages\UserController;
use App\Http\Controllers\Pages\ProductsController;
use App\Http\Controllers\Pages\CartController;

/*
|--------------------------------------------------------------------------
| XÁC THỰC (AUTHENTICATION)
|--------------------------------------------------------------------------
*/
Auth::routes(); // Tự động tạo route: login, register, logout, password/reset
Route::get('active/{token}', [RegisterController::class, 'activation'])->name('active_account'); // Kích hoạt email

/*
|--------------------------------------------------------------------------
| KHU VỰC QUẢN TRỊ (ADMIN) - Bắt buộc quyền Admin
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Quản lý Users
    Route::get('users', [AdminUserController::class, 'index'])->name('users');
    Route::post('user/new', [AdminUserController::class, 'new'])->name('user_new');
    Route::post('user/delete', [AdminUserController::class, 'delete'])->name('user_delete');
    Route::get('user/{id}/show', [AdminUserController::class, 'show'])->name('user_show');
    Route::get('user/{id}/send', [AdminUserController::class, 'send'])->name('user_send');

    // Quản lý Bài viết (Tin tức)
    Route::get('posts', [AdminPostController::class, 'index'])->name('post.index');
    Route::get('post/new', [AdminPostController::class, 'new'])->name('post.new');
    Route::post('post/save', [AdminPostController::class, 'save'])->name('post.save');
    Route::post('post/delete', [AdminPostController::class, 'delete'])->name('post.delete');
    Route::get('post/{id}/edit', [AdminPostController::class, 'edit'])->name('post.edit');
    Route::post('post/{id}/update', [AdminPostController::class, 'update'])->name('post.update');

    // Quản lý Banner Quảng cáo
    Route::get('advertises', [AdvertiseController::class, 'index'])->name('advertise.index');
    Route::get('advertise/new', [AdvertiseController::class, 'new'])->name('advertise.new');
    Route::post('advertise/save', [AdvertiseController::class, 'save'])->name('advertise.save');
    Route::post('advertise/delete', [AdvertiseController::class, 'delete'])->name('advertise.delete');
    Route::get('advertise/{id}/edit', [AdvertiseController::class, 'edit'])->name('advertise.edit');
    Route::post('advertise/{id}/update', [AdvertiseController::class, 'update'])->name('advertise.update');

    // Quản lý Sản phẩm (CRUD, khuyến mãi, biến thể, ảnh)
    Route::get('products', [AdminProductController::class, 'index'])->name('product.index');
    Route::get('product/new', [AdminProductController::class, 'new'])->name('product.new');
    Route::post('product/save', [AdminProductController::class, 'save'])->name('product.save');
    Route::post('product/delete', [AdminProductController::class, 'delete'])->name('product.delete');
    Route::get('product/{id}/edit', [AdminProductController::class, 'edit'])->name('product.edit');
    Route::post('product/{id}/update', [AdminProductController::class, 'update'])->name('product.update');
    Route::post('promotion/delete', [AdminProductController::class, 'delete_promotion'])->name('product.delete_promotion');
    Route::post('product_detail/delete', [AdminProductController::class, 'delete_product_detail'])->name('product.delete_product_detail');
    Route::post('product/image/delete', [AdminProductController::class, 'delete_image'])->name('product.delete_image');

    // Quản lý Nhà sản xuất / Thương hiệu
    Route::get('producers', [ProducerController::class, 'index'])->name('producer.index');
    Route::get('producer/new', [ProducerController::class, 'new'])->name('producer.new');
    Route::post('producer/save', [ProducerController::class, 'save'])->name('producer.save');
    Route::post('producer/delete', [ProducerController::class, 'delete'])->name('producer.delete');
    Route::get('producer/{id}/edit', [ProducerController::class, 'edit'])->name('producer.edit');
    Route::post('producer/{id}/update', [ProducerController::class, 'update'])->name('producer.update');

    // Quản lý Đơn hàng & Duyệt đơn
    Route::get('orders', [AdminOrderController::class, 'index'])->name('order.index');
    Route::get('order/{id}/show', [AdminOrderController::class, 'show'])->name('order.show');
    Route::get('active/{action}/{id}', [AdminOrderController::class, 'actionTransaction'])->name('orderTransaction');

    // Báo cáo Thống kê doanh thu
    Route::get('statistic', [StatisticController::class, 'index'])->name('statistic');
    Route::post('statistic/change', [StatisticController::class, 'edit'])->name('statistic.edit');

    // Quản lý Kho hàng & Xuất nhập
    Route::get('warehouse', [WarehouseController::class, 'index'])->name('warehouse');
    Route::get('orderDetails', [WarehouseController::class, 'orderDetails'])->name('orderDetails');
});

/*
|--------------------------------------------------------------------------
| KHU VỰC KHÁCH HÀNG (CLIENT)
|--------------------------------------------------------------------------
*/
Route::group([], function () {
    // Thông tin chung & Tìm kiếm
    Route::get('/', HomePage::class)->name('home_page');
    Route::get('about', AboutPage::class)->name('about_page');
    Route::get('contact', ContactPage::class)->name('contact_page');
    Route::get('search', SearchController::class)->name('search');

    // Tin tức
    Route::get('posts', [PostController::class, 'index'])->name('posts_page');
    Route::get('post/{id}', [PostController::class, 'show'])->name('post_page');

    // Lịch sử đơn mua của khách
    Route::get('orders', [OrderController::class, 'index'])->name('orders_page');
    Route::get('order/{id}', [OrderController::class, 'show'])->name('order_page');

    // Thông tin tài khoản cá nhân
    Route::get('user/profile', [UserController::class, 'show'])->name('show_user');
    Route::get('user/edit', [UserController::class, 'edit'])->name('edit_user');
    Route::post('user/save', [UserController::class, 'save'])->name('save_user');

    // Xem sản phẩm & Đánh giá
    Route::get('products', [ProductsController::class, 'index'])->name('products_page');
    Route::get('producer/{id}', [ProductsController::class, 'getProducer'])->name('producer_page');
    Route::get('product/{id}', [ProductsController::class, 'getProduct'])->name('product_page');
    Route::post('vote', [ProductsController::class, 'addVote'])->name('add_vote');

    // Giỏ hàng (Thêm, sửa, xóa, hiển thị)
    Route::post('cart/add', [CartController::class, 'addCart'])->name('add_cart');
    Route::post('cart/remove', [CartController::class, 'removeCart'])->name('remove_cart');
    Route::post('minicart/update', [CartController::class, 'updateMiniCart'])->name('update_minicart');
    Route::post('cart/update', [CartController::class, 'updateCart'])->name('update_cart');
    Route::get('cart', [CartController::class, 'showCart'])->name('show_cart');

    // Đặt hàng & Cổng thanh toán (VNPAY)
    Route::post('checkout', [CartController::class, 'showCheckout'])->name('show_checkout');
    Route::post('payment', [CartController::class, 'payment'])->name('payment');
    Route::get('payment/response', [CartController::class, 'responsePayment'])->name('payment_response');
    Route::get('vnpay/return', [CartController::class, 'vnpayReturn'])->name('vnpay_return');
});