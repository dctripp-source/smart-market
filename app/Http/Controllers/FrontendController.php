<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;

class FrontendController extends Controller
{
    /**
     * Show homepage
     */
    public function index()
    {
        $featuredProducts = Product::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $categories = Category::all();

        return view('frontend.index', compact('featuredProducts', 'categories'));
    }

    /**
     * Show shop page
     */
    public function shop(Request $request)
    {
        $query = Product::where('is_active', true);

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->paginate(12);
        $categories = Category::all();

        return view('frontend.shop', compact('products', 'categories'));
    }

    /**
     * Show category page
     */
    public function category($slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $products = Product::where('category_id', $category->id)
            ->where('is_active', true)
            ->paginate(12);

        return view('frontend.category', compact('category', 'products'));
    }

    /**
     * Show product details
     */
    public function product($slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->limit(4)
            ->get();

        return view('frontend.product', compact('product', 'relatedProducts'));
    }

    /**
     * Show about page
     */
    public function about()
    {
        return view('frontend.about');
    }

    /**
     * Show contact page
     */
    public function contact()
    {
        return view('frontend.contact');
    }
}