<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Category;
use App\Services\ProductScraperService;

class AdminController extends Controller
{
    protected $scraperService;

    public function __construct(ProductScraperService $scraperService)
    {
        $this->scraperService = $scraperService;
    }

    public function showLogin()
    {
        if (session('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($credentials['username'] === 'smart' && $credentials['password'] === 'smart') {
            session(['admin_logged_in' => true]);
            session(['admin_user' => 'Smart Market Admin']);
            
            return redirect()->route('admin.dashboard')
                ->with('success', 'Uspješno ste se prijavili!');
        }

        return back()->withErrors([
            'username' => 'Pogrešno korisničko ime ili lozinka.',
        ])->withInput($request->only('username'));
    }

    public function logout()
    {
        session()->flush();
        return redirect()->route('admin.login')
            ->with('success', 'Uspješno ste se odjavili!');
    }

    public function dashboard()
    {
        $stats = [
            'total_products' => Product::count(),
            'active_suppliers' => Supplier::where('is_active', true)->count(),
            'total_categories' => Category::count(),
            'last_sync' => Supplier::whereNotNull('last_synced_at')
                ->orderBy('last_synced_at', 'desc')
                ->first()?->last_synced_at,
        ];

        return view('admin.dashboard', compact('stats'));
    }

    public function suppliers()
    {
        $suppliers = Supplier::withCount('products')->get();
        return view('admin.suppliers', compact('suppliers'));
    }

    public function storeSupplier(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'import_method' => 'required|in:scraping,xml',
            'url' => 'nullable|url',
            'xml_file' => 'nullable|file|mimes:xml',
            'css_selector' => 'nullable|string',
        ]);

        $supplier = new Supplier();
        $supplier->name = $validated['name'];
        $supplier->import_method = $validated['import_method'];
        $supplier->url = $validated['url'] ?? null;
        $supplier->css_selector = $validated['css_selector'] ?? null;
        $supplier->is_active = true;

        if ($request->hasFile('xml_file')) {
            $path = $request->file('xml_file')->store('suppliers/xml', 'public');
            $supplier->xml_file_path = $path;
        }

        $supplier->save();

        return redirect()->route('admin.suppliers')
            ->with('success', 'Dobavljač uspješno dodat!');
    }

    public function toggleSupplier($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->is_active = !$supplier->is_active;
        $supplier->save();

        return response()->json([
            'success' => true,
            'is_active' => $supplier->is_active,
        ]);
    }

    public function syncSupplier($id)
    {
        $supplier = Supplier::findOrFail($id);
        $result = $this->scraperService->scrapeSupplier($supplier);
        return response()->json($result);
    }

    public function syncAll()
    {
        $suppliers = Supplier::where('is_active', true)->get();
        
        if ($suppliers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Nema aktivnih dobavljača.',
            ]);
        }

        $totalAdded = 0;
        $totalUpdated = 0;
        $errors = [];

        foreach ($suppliers as $supplier) {
            $result = $this->scraperService->scrapeSupplier($supplier);
            
            if ($result['success']) {
                $totalAdded += $result['added'] ?? 0;
                $totalUpdated += $result['updated'] ?? 0;
            } else {
                $errors[] = "{$supplier->name}: {$result['message']}";
            }
        }

        $message = "Dodano: {$totalAdded}, Ažurirano: {$totalUpdated}";
        
        if (!empty($errors)) {
            $message .= " | Greške: " . implode(', ', $errors);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'added' => $totalAdded,
            'updated' => $totalUpdated,
        ]);
    }

    public function products(Request $request)
    {
        $query = Product::with(['supplier', 'category']);

        // Filter by supplier
        if ($request->has('supplier')) {
            $query->where('supplier_id', $request->supplier);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        // Get all products
        $products = $query->orderBy('created_at', 'desc')->paginate(100);

        // Group by category for display
        $productsByCategory = Product::with(['supplier', 'category'])
            ->get()
            ->groupBy(function($product) {
                return $product->category ? $product->category->name : 'Bez kategorije';
            });

        $categories = Category::withCount('products')->get();
        $suppliers = Supplier::all();
        $totalCount = Product::count();

        return view('admin.products', compact(
            'products', 
            'productsByCategory', 
            'categories', 
            'suppliers',
            'totalCount'
        ));
    }

    /**
     * Toggle product visibility (NEW)
     */
    public function toggleProduct($id)
    {
        $product = Product::findOrFail($id);
        $product->is_active = !$product->is_active;
        $product->save();

        return response()->json([
            'success' => true,
            'is_active' => $product->is_active,
        ]);
    }

    public function categories()
    {
        $categories = Category::withCount('products')->get();
        return view('admin.categories', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories',
            'icon' => 'nullable|string',
        ]);

        Category::create($validated);

        return redirect()->route('admin.categories')
            ->with('success', 'Kategorija uspješno dodana!');
    }

    public function settings()
    {
        return view('admin.settings');
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'shop_name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string',
            'address' => 'required|string',
        ]);

        config(['app.shop_name' => $validated['shop_name']]);
        
        return redirect()->route('admin.settings')
            ->with('success', 'Podešavanja uspješno sačuvana!');
    }
}