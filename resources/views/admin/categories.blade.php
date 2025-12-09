@extends('layouts.admin')

@section('title', 'Kategorije')
@section('page-title', 'Kategorije')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2>Kategorije proizvoda</h2>
            <button class="btn-small" onclick="toggleAddForm()">+ Dodaj kategoriju</button>
        </div>

        @if($categories->isEmpty())
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <p style="font-size: 18px; margin-bottom: 20px;">📋 Nemate još nijednu kategoriju</p>
                <p>Dodajte kategorije da biste organizovali proizvode.</p>
            </div>
        @else
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 25px;">
                @foreach($categories as $category)
                    <div style="background: var(--bg-dark); border: 1px solid var(--border); border-radius: 12px; padding: 20px; transition: all 0.3s ease; cursor: pointer;">
                        <div style="font-size: 32px; margin-bottom: 10px;">
                            {{ $category->icon ?? '📦' }}
                        </div>
                        <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">
                            {{ $category->name }}
                        </div>
                        <div style="color: var(--text-muted); font-size: 14px;">
                            Proizvodi: {{ $category->products_count }}
                        </div>
                        <div style="margin-top: 12px;">
                            <span style="padding: 5px 10px; background: rgba(53, 116, 156, 0.2); border-radius: 6px; font-size: 12px; color: var(--accent);">
                                {{ $category->slug }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="card" id="addCategoryForm" style="display: none;">
        <div class="card-header">
            <h2>Dodaj novu kategoriju</h2>
        </div>
        
        <form method="POST" action="{{ route('admin.categories.store') }}">
            @csrf
            
            <div class="form-row">
                <div class="form-group">
                    <label>Naziv kategorije *</label>
                    <input type="text" name="name" class="form-control" placeholder="Laptopi" required>
                </div>
                <div class="form-group">
                    <label>Slug *</label>
                    <input type="text" name="slug" class="form-control" placeholder="laptopi" required>
                    <small style="color: var(--text-muted); font-size: 13px;">URL-friendly verzija naziva</small>
                </div>
            </div>

            <div class="form-group">
                <label>Ikonica (emoji)</label>
                <input type="text" name="icon" class="form-control" placeholder="💻" maxlength="2">
                <small style="color: var(--text-muted); font-size: 13px;">Kopirajte emoji sa emojipedia.org</small>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn-small">Sačuvaj kategoriju</button>
                <button type="button" class="btn-small" onclick="toggleAddForm()" style="background: var(--secondary);">Otkaži</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    function toggleAddForm() {
        const form = document.getElementById('addCategoryForm');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
</script>
@endpush