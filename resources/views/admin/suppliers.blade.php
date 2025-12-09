@extends('layouts.admin')

@section('title', 'Dobavljači')
@section('page-title', 'Dobavljači')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2>Upravljanje dobavljačima</h2>
            <button class="btn-small" onclick="toggleAddForm()">+ Dodaj web shop</button>
        </div>

        @if($suppliers->isEmpty())
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <p style="font-size: 18px; margin-bottom: 20px;">🌐 Nema dodanih web shopova</p>
                <p>Dodaj link web shopa da automatski povučeš proizvode.</p>
            </div>
        @else
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 25px;">
                @foreach($suppliers as $supplier)
                    <div style="background: var(--bg-dark); border: 1px solid var(--border); border-radius: 12px; padding: 20px; transition: all 0.3s ease;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                            <div>
                                <div style="font-size: 18px; font-weight: 600; margin-bottom: 5px;">{{ $supplier->name }}</div>
                                <div style="font-size: 12px; color: var(--text-muted);">
                                    Proizvodi: <strong style="color: var(--accent);">{{ $supplier->products_count }}</strong>
                                </div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" 
                                       {{ $supplier->is_active ? 'checked' : '' }}
                                       onchange="toggleSupplier({{ $supplier->id }}, this)">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div style="margin-bottom: 12px;">
                            @if($supplier->isScrapingMethod())
                                <div style="color: var(--text-muted); font-size: 13px; margin-bottom: 5px;">
                                    🔗 {{ Str::limit($supplier->url, 40) }}
                                </div>
                                @if($supplier->css_selector)
                                    <div style="color: var(--text-muted); font-size: 11px; font-family: monospace;">
                                        Selector: {{ $supplier->css_selector }}
                                    </div>
                                @endif
                            @else
                                <div style="color: var(--text-muted); font-size: 13px;">
                                    📄 {{ basename($supplier->xml_file_path ?? 'N/A') }}
                                </div>
                            @endif
                        </div>

                        <span style="display: inline-block; padding: 4px 10px; background: rgba(53, 116, 156, 0.2); border-radius: 6px; font-size: 11px; font-weight: 500; color: var(--accent); margin-bottom: 12px;">
                            {{ $supplier->isScrapingMethod() ? '🕷️ Web Scraping' : '📄 XML Upload' }}
                        </span>

                        @if($supplier->last_synced_at)
                            <div style="color: var(--text-muted); font-size: 11px; margin-bottom: 12px;">
                                Posljednja sinhronizacija: {{ $supplier->last_synced_at->diffForHumans() }}
                            </div>
                        @endif

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 12px;">
                            <button class="btn-small" onclick="syncSupplier({{ $supplier->id }})" style="font-size: 13px; padding: 8px 12px;">
                                🔄 Sinhronizuj
                            </button>
                            <a href="{{ route('admin.products') }}?supplier={{ $supplier->id }}" class="btn-small" style="font-size: 13px; padding: 8px 12px; text-align: center; text-decoration: none;">
                                👁️ Proizvodi
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="card" id="addSupplierForm" style="display: none;">
        <div class="card-header">
            <h2>Dodaj novi web shop</h2>
        </div>
        
        <form method="POST" action="{{ route('admin.suppliers.store') }}" enctype="multipart/form-data">
            @csrf
            
            <div class="form-row">
                <div class="form-group">
                    <label>Naziv web shopa *</label>
                    <input type="text" name="name" class="form-control" placeholder="Gigatron, Emmi, WinWin..." required>
                </div>
                <div class="form-group">
                    <label>Metoda uvoza *</label>
                    <select name="import_method" class="form-control" id="importMethod" onchange="toggleImportMethod()" required>
                        <option value="scraping">🕷️ Web Scraping (Link sajta)</option>
                        <option value="xml">📄 XML Upload (Fajl)</option>
                    </select>
                </div>
            </div>

            <div id="scrapingOptions">
                <div class="form-group">
                    <label>Link web shopa *</label>
                    <input type="url" name="url" class="form-control" placeholder="https://gigatron.rs/laptop-racunari">
                    <small style="color: var(--text-muted); font-size: 13px;">
                        💡 Tip: Kopiraj link stranice sa proizvodima koje želiš da uvezеš
                    </small>
                </div>
                <div class="form-group">
                    <label>CSS Selector (opciono - napredna opcija)</label>
                    <input type="text" name="css_selector" class="form-control" placeholder=".product-item">
                    <small style="color: var(--text-muted); font-size: 13px;">
                        Ostavi prazno - sistem će automatski probati popularne selektore
                    </small>
                </div>
                <div style="padding: 15px; background: rgba(53, 116, 156, 0.1); border-radius: 8px; margin-top: 15px;">
                    <strong style="color: var(--accent);">📌 Kako testirati prije dodavanja?</strong>
                    <pre style="background: var(--bg-dark); padding: 10px; border-radius: 5px; margin-top: 8px; font-size: 12px; overflow-x: auto;">php artisan scraper:test "https://gigatron.rs/laptop-racunari"</pre>
                </div>
            </div>

            <div id="xmlOptions" style="display: none;">
                <div class="form-group">
                    <label>XML Fajl</label>
                    <input type="file" name="xml_file" class="form-control" accept=".xml">
                    <small style="color: var(--text-muted); font-size: 13px;">Maksimalna veličina: 10MB</small>
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn-small">💾 Sačuvaj web shop</button>
                <button type="button" class="btn-small" onclick="toggleAddForm()" style="background: var(--secondary);">✖ Otkaži</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    function toggleAddForm() {
        const form = document.getElementById('addSupplierForm');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
        if (form.style.display === 'block') {
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function toggleImportMethod() {
        const method = document.getElementById('importMethod').value;
        const scrapingOptions = document.getElementById('scrapingOptions');
        const xmlOptions = document.getElementById('xmlOptions');

        if (method === 'scraping') {
            scrapingOptions.style.display = 'block';
            xmlOptions.style.display = 'none';
        } else {
            scrapingOptions.style.display = 'none';
            xmlOptions.style.display = 'block';
        }
    }

    function toggleSupplier(id, checkbox) {
        fetch(`/admin/suppliers/${id}/toggle`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const status = data.is_active ? 'aktiviran' : 'deaktiviran';
                showNotification(`✓ Dobavljač uspješno ${status}!`, 'success');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            checkbox.checked = !checkbox.checked;
            showNotification('✗ Došlo je do greške.', 'error');
        });
    }

    function syncSupplier(id) {
        if (confirm('🔄 Pokrenuti sinhronizaciju proizvoda?')) {
            showNotification('⏳ Sinhronizacija u toku...', 'info');
            
            fetch(`/admin/suppliers/${id}/sync`, {
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
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showNotification(`✗ ${data.message}`, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('✗ Došlo je do greške pri sinhronizaciji.', 'error');
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