<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Producer;

class ProducerController extends Controller
{
    // Danh sách hãng
    public function index()
    {
        $producers = Producer::orderBy('name', 'asc')->get();

        return view('admin.producer.index')->with('producers', $producers);
    }

    // Form thêm hãng
    public function new()
    {
        return view('admin.producer.new');
    }

    // Lưu hãng mới
    public function save(Request $request)
    {
        $request->validate([
            'name' => 'required|max:191|unique:producers,name',
        ], [
            'name.required' => 'Vui lòng nhập tên hãng điện thoại.',
            'name.max' => 'Tên hãng không được vượt quá 191 ký tự.',
            'name.unique' => 'Hãng điện thoại này đã tồn tại.',
        ]);

        $producer = new Producer();
        $producer->name = $request->name;
        $producer->save();

        return redirect()
            ->route('admin.producer.index')
            ->with('success', 'Thêm hãng điện thoại thành công!');
    }

    // Form sửa hãng
    public function edit($id)
    {
        $producer = Producer::find($id);

        if (!$producer) {
            return redirect()
                ->route('admin.producer.index')
                ->with('error', 'Không tìm thấy hãng điện thoại.');
        }

        return view('admin.producer.edit')->with('producer', $producer);
    }

    // Cập nhật hãng
    public function update(Request $request, $id)
    {
        $producer = Producer::find($id);

        if (!$producer) {
            return redirect()
                ->route('admin.producer.index')
                ->with('error', 'Không tìm thấy hãng điện thoại.');
        }

        $request->validate([
            'name' => 'required|max:191|unique:producers,name,' . $id,
        ], [
            'name.required' => 'Vui lòng nhập tên hãng điện thoại.',
            'name.max' => 'Tên hãng không được vượt quá 191 ký tự.',
            'name.unique' => 'Hãng điện thoại này đã tồn tại.',
        ]);

        $producer->name = $request->name;
        $producer->save();

        return redirect()
            ->route('admin.producer.index')
            ->with('success', 'Cập nhật hãng điện thoại thành công!');
    }

    // Xóa hãng
    public function delete(Request $request)
    {
        $producer = Producer::find($request->producer_id);

        if (!$producer) {
            return response()->json([
                'type' => 'error',
                'title' => 'Thất bại',
                'content' => 'Không tìm thấy hãng điện thoại!'
            ]);
        }

        // Kiểm tra hãng có sản phẩm hay chưa
        if ($producer->products()->count() > 0) {
            return response()->json([
                'type' => 'error',
                'title' => 'Không thể xóa',
                'content' => 'Hãng này đang có sản phẩm, không thể xóa!'
            ]);
        }

        $producer->delete();

        return response()->json([
            'type' => 'success',
            'title' => 'Thành công',
            'content' => 'Xóa hãng điện thoại thành công!'
        ]);
    }
}