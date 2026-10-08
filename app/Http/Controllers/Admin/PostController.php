<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

use App\Models\Post;

class PostController extends Controller
{
  public function index(Request $request)
  {
    $posts = Post::select('id', 'title', 'image', 'created_at')->latest()->get();

    return view('admin.post.index')->with('posts', $posts);
  }

  public function new(Request $request)
  {
    return view('admin.post.new');
  }

  public function save(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'title' => 'required',
      'content' => 'required',
      'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
    ], [
      'title.required' => 'Tiêu đề bài viết không được để trống!',
      'content.required' => 'Nội dung bài viết không được để trống!',
      'image.required' => 'Hình ảnh hiển thị bài viết phải được tải lên!',
      'image.image' => 'File tải lên phải là hình ảnh!',
      'image.mimes' => 'Hình ảnh phải có định dạng jpg, jpeg, png hoặc webp!',
      'image.max' => 'Dung lượng hình ảnh không được vượt quá 5MB!',
    ]);

    if ($validator->fails()) {
      return back()
        ->withErrors($validator)
        ->withInput();
    }

    $post = new Post;
    $post->title = $request->title;
    $post->content = $this->handleEditorContentImages($request->content);
    $post->user_id = Auth::user()->id;

    if ($request->hasFile('image')) {
      $image = $request->file('image');
      $image_name = time() . '_' . Str::random(8) . '_' . $image->getClientOriginalName();

      $image->storeAs('images/posts', $image_name, 'public');

      $post->image = $image_name;
    }

    $post->save();

    return redirect()->route('admin.post.index')->with(['alert' => [
      'type' => 'success',
      'title' => 'Thành Công',
      'content' => 'Bài viết của bạn đã được tạo thành công.'
    ]]);
  }

  public function delete(Request $request)
  {
    $post = Post::where('id', $request->post_id)->first();

    if (!$post) {
      $data['type'] = 'error';
      $data['title'] = 'Thất Bại';
      $data['content'] = 'Bạn không thể xóa bài viết không tồn tại!';
    } else {
      Storage::disk('public')->delete('images/posts/' . $post->image);

      if ($post->content != null) {
        $dom = $this->loadEditorHtml($post->content);

        $images = $dom->getElementsByTagName('img');

        foreach ($images as $img) {
          $src = $img->getAttribute('src');

          if (Str::startsWith($src, '/storage/images/posts/')) {
            $src = str_replace('/storage/', '', $src);

            Storage::disk('public')->delete($src);
          }
        }
      }

      $post->delete();

      $data['type'] = 'success';
      $data['title'] = 'Thành Công';
      $data['content'] = 'Xóa bài viết thành công!';
    }

    return response()->json($data, 200);
  }

  public function edit($id)
  {
    $post = Post::where('id', $id)->first();

    if (!$post) abort(404);

    return view('admin.post.edit')->with('post', $post);
  }

  public function update(Request $request, $id)
  {
    $validator = Validator::make($request->all(), [
      'title' => 'required',
      'content' => 'required',
      'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
    ], [
      'title.required' => 'Tiêu đề bài viết không được để trống!',
      'content.required' => 'Nội dung bài viết không được để trống!',
      'image.image' => 'File tải lên phải là hình ảnh!',
      'image.mimes' => 'Hình ảnh phải có định dạng jpg, jpeg, png hoặc webp!',
      'image.max' => 'Dung lượng hình ảnh không được vượt quá 5MB!',
    ]);

    if ($validator->fails()) {
      return back()
        ->withErrors($validator)
        ->withInput();
    }

    $post = Post::where('id', $id)->first();

    if (!$post) abort(404);

    $post->title = $request->title;
    $post->content = $this->handleEditorContentImages($request->content);

    if ($request->hasFile('image')) {
      $image = $request->file('image');
      $image_name = time() . '_' . Str::random(8) . '_' . $image->getClientOriginalName();

      $image->storeAs('images/posts', $image_name, 'public');

      if ($post->image != null) {
        Storage::disk('public')->delete('images/posts/' . $post->image);
      }

      $post->image = $image_name;
    }

    $post->save();

    return redirect()->route('admin.post.index')->with(['alert' => [
      'type' => 'success',
      'title' => 'Thành Công',
      'content' => 'Chỉnh sửa bài viết thành công.'
    ]]);
  }

  private function handleEditorContentImages($html)
  {
    $dom = $this->loadEditorHtml($html);

    $images = $dom->getElementsByTagName('img');

    foreach ($images as $k => $img) {
      $data = $img->getAttribute('src');

      if (Str::containsAll($data, ['data:image', 'base64'])) {
        if (!preg_match('/^data:image\/(\w+);base64,/', $data, $matches)) {
          continue;
        }

        $type = strtolower($matches[1]);

        if ($type === 'jpeg') {
          $type = 'jpg';
        }

        $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $data);
        $base64Data = str_replace(' ', '+', $base64Data);

        $imageData = base64_decode($base64Data);

        if ($imageData === false) {
          continue;
        }

        $image_name = time() . $k . '_' . Str::random(8) . '.' . $type;

        Storage::disk('public')->put('images/posts/' . $image_name, $imageData);

        $img->removeAttribute('src');
        $img->setAttribute('src', '/storage/images/posts/' . $image_name);
      }
    }

    return $this->getBodyContent($dom);
  }

  private function loadEditorHtml($html)
  {
    $dom = new \DOMDocument('1.0', 'UTF-8');

    libxml_use_internal_errors(true);

    $html = '<?xml encoding="UTF-8">' .
      '<!DOCTYPE html>' .
      '<html>' .
      '<head><meta charset="UTF-8"></head>' .
      '<body>' . $html . '</body>' .
      '</html>';

    $dom->loadHTML(
      $html,
      LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
    );

    libxml_clear_errors();

    return $dom;
  }

  private function getBodyContent($dom)
  {
    $body = $dom->getElementsByTagName('body')->item(0);

    $content = '';

    if ($body) {
      foreach ($body->childNodes as $child) {
        $content .= $dom->saveHTML($child);
      }
    }

    return $content;
  }
}