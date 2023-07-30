<?php

namespace App\Http\Controllers;

use File;
use Illuminate\Http\Request;
use App\Models\Blog;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Validator;

class BlogController extends Controller
{
    public function index()
    {

        $blogs = Blog::all();

        return response()->json([
            'success' => true,
            'message' => "Blogs List",
            'data' => $blogs
        ]);
    }
    public function store(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'title' => 'required',
            'content' => 'required',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,bmp,gif,svg|max:2048',
            'tags' => 'nullable'
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $blog = new Blog;
        $blog->blog_id = 'blog-id-' . date('Ymd') . Str::random(16) . date('His');
        $blog->user_id = Auth::user()->id;
        $blog->title = $request->input('title');
        $blog->content = $request->input('content');
        $blog->tags = $request->input('tags') ?? '';
        $blog->deleted_at = $request->input('deleted_at') ?? '';

        if ($request->has('photo')) {
            $photoName = uniqid() . '-' . $request->title . '.' . $request->photo->getClientOriginalExtension();
            $blog->photo = $photoName;
            Storage::disk('public')->put($photoName, file_get_contents($request->photo));
        }

        //save all data
        $blog->save();

        return response()->json([
            'success' => true,
            'message' => "Blogs List",
            'data' => $blog
        ]);
    }

    public function show($blogId)
    {

        $blog = Blog::find($blogId);

        if (is_null($blog)) {
            return $this->sendError('Blog not found.');
        }
        return response()->json([
            'success' => true,
            'message' => "Showing Blog Successfully",
            'data' => $blog
        ]);

    }
    public function update(Request $request, $blogId)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'title' => 'required',
            'content' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $blog = Blog::find($blogId);
        $blog->title = $request->input('title') ?? $blog->title;
        $blog->content = $request->input('content') ?? $blog->content;
        $blog->tags = $request->input('tags') ?? $blog->tags;
        $blog->deleted_at = $request->input('deleted_at') ?? $blog->deleted_at;
        if ($request->has('photo')) {
            $photoName = uniqid() . '-' . $request->title . '.' . $request->photo->getClientOriginalExtension();
            $blog->photo = $photoName;
            Storage::disk('public')->put($photoName, file_get_contents($request->photo));
        }
        $blog->photo = $blog->photo;
        $blog->save();

        return response()->json([
            'success' => true,
            'message' => "Blog Updated Successfully.",
            'data' => $blog
        ]);

    }

    public function delete($blogId)
    {
        $blog = Blog::find($blogId);

        //image delete from storage
        if ($blog->photo != null) {
            if (File::exists(public_path('/images/news/' . $blog->photo))) {
                unlink(public_path('/images/news/' . $blog->photo));
            }

        }


        $blog->delete();

        return response()->json([
            'success' => true,
            'message' => "Deleted Blog Successfully"
        ]);
    }
}