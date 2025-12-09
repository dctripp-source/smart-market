@extends('layouts.admin')

@section('title', 'Proizvodi')
@section('page-title', 'Proizvodi')

@section('content')
    <!-- Filters -->
    <div class="card">
        <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
            <div class="form-group" style="margin: 0; flex: 1; min-width: 200px;">
                <input type="text" 
                       id="searchInput" 
                       class="form-control" 
                       placeholder="🔍 Pretraži proizvode..." 
                       onkeyup="filterProducts()">
            </div>
            
            <select id="categoryFilter" class="form-control" style="width: 200px;" onchange="filterProducts()">
                <option value="">Sve kategorije</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                        {{ $category->icon }} {{ $category->name }}
                    </option>
                @endforeach
            </select>

            <select id="supplierFilter" class="form-control" style="width: 200px;" onchange="filterProducts()">
                <option value="">Svi dobavljači</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ request('supplier') == $supplier->id ? 'selected' : '' }}>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>

            <select id="statusFilter" class="form-control" style="width: 150px;" onchange="filterProducts()">
                <option value="">Svi statusi</option>
                <option value="active">✓ Aktivni</option>
                <option value="inactive">✗ Neaktivni</option>
            </select>

            <button class="btn-small" onclick="syncAll()" style="white-space: nowrap;">
                🔄 Sinhronizuj sve
            </button>
        </div>
    </div>

    <!-- Products by Category -->
    @if($products->isEmpty())
        <div class="card">
            <div style="text-align: center; padding: 60px 20px; color: var(--text-muted);">
                <div style="font-size: 64px; margin-bottom: 20px;">📦</div>
                <p style="font-size: 20px; margin-bottom: 15px; font-weight: 600;">Nema proizvoda</p>
                <p style="margin-bottom: 30px;">Dodaj dobavljače i pokreni sinhronizaciju da bi dobio proizvode.</p>
                <a href="{{ route('admin.suppliers') }}" class="btn-small">
                    + Dodaj web shop
                </a>
            </div>
        </div>
    @else
        <!-- Category Tabs -->
        <div class="card">
            <div style="display: flex; gap: 10px; flex-wrap: wrap; padding-bottom: 15px; border-bottom: 1px solid var(--border);">
                <button class="category-tab active" data-category="all" onclick="switchCategory('all')">
                    🛍️ Svi proizvodi ({{ $totalCount }})
                </button>
                @foreach($categories as $category)
                    @if($category->products_count > 0)
                        <button class="category-tab" data-category="{{ $category->id }}" onclick="switchCategory({{ $category->id }})">
                            {{ $category->icon }} {{ $category->name }} ({{ $category->products_count }})
                        </button>
                    @endif
                @endforeach
            </div>

            <!-- Products Grid -->
            <div id="productsContainer" style="margin-top: 25px;">
                @foreach($productsByCategory as $categoryName => $categoryProducts)
                    <div class="category-section" data-category-id="{{ $categoryProducts->first()->category_id ?? 'uncategorized' }}">
                        <h3 style="margin-bottom: 20px; color: var(--accent); font-size: 20px;">
                            {{ $categoryName }} ({{ $categoryProducts->count() }})
                        </h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-bottom: 40px;">
                            @foreach($categoryProducts as $product)
                                <div class="product-card" 
                                     data-product-id="{{ $product->id }}"
                                     data-category="{{ $product->category_id }}"
                                     data-supplier="{{ $product->supplier_id }}"
                                     data-status="{{ $product->is_active ? 'active' : 'inactive' }}"
                                     data-name="{{ strtolower($product->name) }}">
                                    
                                    <div style="position: relative;">
                                        @if($product->image_url)
                                            <img src="{{ $product->image_url }}" 
                                                 alt="{{ $product->name }}"
                                                 style="width: 100%; height: 180px; object-fit: cover; border-radius: 10px; margin-bottom: 12px;"
                                                 onerror="this.src='https://via.placeholder.com/280x180?text=No+Image'">
                                        @else
                                            <div style="width: 100%; height: 180px; background: var(--bg-dark); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; font-size: 48px;">
                                                📦
                                            </div>
                                        @endif
                                        
                                        <!-- Toggle Switch on Image -->
                                        <div style="position: absolute; top: 10px; right: 10px;">
                                            <label class="toggle-switch">
                                                <input type="checkbox" 
                                                       {{ $product->is_active ? 'checked' : '' }}
                                                       onchange="toggleProduct({{ $product->id }}, this)">
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </div>
                                    </div>

                                    <div style="padding: 0 5px;">
                                        <h4 style="font-size: 15px; font-weight: 600; margin-bottom: 8px; line-height: 1.4; min-height: 42px;">
                                            {{ Str::limit($product->name, 60) }}
                                        </h4>

                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                            <div style="font-size: 20px; font-weight: 700; color: var(--accent);">
                                                {{ $product->formatted_price }}
                                            </div>
                                            @if($product->is_active)
                                                <span style="padding: 4px 8px; background: rgba(74, 222, 128, 0.2); border-radius: 5px; font-size: 11px; color: var(--success); font-weight: 600;">
                                                    ✓ Prikazan
                                                </span>
                                            @else
                                                <span style="padding: 4px 8px; background: rgba(239, 68, 68, 0.2); border-radius: 5px; font-size: 11px; color: var(--danger); font-weight: 600;">
                                                    ✗ Sakriven
                                                </span>
                                            @endif
                                        </div>

                                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 8px;">
                                            🏪 {{ $product->supplier->name ?? 'N/A' }}
                                        </div>

                                        @if($product->category)
                                            <div style="display: inline-block; padding: 3px 8px; background: rgba(53, 116, 156, 0.2); border-radius: 5px; font-size: 11px; color: var(--accent);">
                                                {{ $product->category->icon }} {{ $product->category->name }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Pagination -->
        <div style="margin-top: 20px;">
            {{ $products->links() }}
        </div>
    @endif
@endsection

@push('styles')
<style>
.product-card {
    background: var(--bg-dark);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 15px;
    transition: all 0.3s ease;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    border-color: var(--primary);
}

.category-tab {
    padding: 10px 18px;
    background: var(--bg-dark);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
    font-weight: 500;
}

.category-tab:hover {
    border-color: var(--primary);
    color: var(--text-light);
}

.category-tab.active {
    background: var(--primary);
    border-color: var(--primary);
    color: white;
}

.category-section {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
@endpush

@push('scripts')
<script>
let currentCategory = 'all';

function switchCategory(categoryId) {
    currentCategory = categoryId;
    
    // Update tab styles
    document.querySelectorAll('.category-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`[data-category="${categoryId}"]`).classList.add('active');
    
    // Filter products
    filterProducts();
}

function filterProducts() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const categoryFilter = document.getElementById('categoryFilter').value;
    const supplierFilter = document.getElementById('supplierFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;

    document.querySelectorAll('.product-card').forEach(card => {
        const name = card.dataset.name;
        const category = card.dataset.category;
        const supplier = card.dataset.supplier;
        const status = card.dataset.status;

        let show = true;

        // Search filter
        if (searchTerm && !name.includes(searchTerm)) {
            show = false;
        }

        // Category tab filter
        if (currentCategory !== 'all' && category != currentCategory) {
            show = false;
        }

        // Category dropdown filter
        if (categoryFilter && category != categoryFilter) {
            show = false;
        }

        // Supplier filter
        if (supplierFilter && supplier != supplierFilter) {
            show = false;
        }

        // Status filter
        if (statusFilter && status !== statusFilter) {
            show = false;
        }

        card.style.display = show ? 'block' : 'none';
    });

    // Hide empty category sections
    document.querySelectorAll('.category-section').forEach(section => {
        const visibleProducts = section.querySelectorAll('.product-card[style*="display: block"]').length;
        section.style.display = visibleProducts > 0 ? 'block' : 'none';
    });
}

function toggleProduct(id, checkbox) {
    fetch(`/admin/products/${id}/toggle`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const card = checkbox.closest('.product-card');
            card.dataset.status = data.is_active ? 'active' : 'inactive';
            
            const badge = card.querySelector('span[style*="padding: 4px 8px"]');
            if (data.is_active) {
                badge.style.background = 'rgba(74, 222, 128, 0.2)';
                badge.style.color = 'var(--success)';
                badge.innerHTML = '✓ Prikazan';
            } else {
                badge.style.background = 'rgba(239, 68, 68, 0.2)';
                badge.style.color = 'var(--danger)';
                badge.innerHTML = '✗ Sakriven';
            }
            
            showNotification(`✓ Proizvod ${data.is_active ? 'prikazan' : 'sakriven'}!`, 'success');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        checkbox.checked = !checkbox.checked;
        showNotification('✗ Došlo je do greške.', 'error');
    });
}

function syncAll() {
    if (confirm('🔄 Pokrenuti sinhronizaciju svih dobavljača?')) {
        showNotification('⏳ Sinhronizacija u toku...', 'info');
        
        fetch('{{ route("admin.suppliers.sync-all") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(`✓ ${data.message}`, 'success');
                setTimeout(() => window.location.reload(), 3000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('✗ Došlo je do greške.', 'error');
        });
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.textContent = message;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
</script>
@endpush