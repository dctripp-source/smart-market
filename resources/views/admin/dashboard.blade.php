@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Pregled')

@section('content')
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Ukupno proizvoda</h3>
            <div class="stat-value">{{ $stats['total_products'] ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <h3>Aktivni dobavljači</h3>
            <div class="stat-value">{{ $stats['active_suppliers'] ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <h3>Kategorije</h3>
            <div class="stat-value">{{ $stats['total_categories'] ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <h3>Posljednje ažuriranje</h3>
            <div class="stat-value" style="font-size: 18px;">
                @if($stats['last_sync'])
                    {{ \Carbon\Carbon::parse($stats['last_sync'])->diffForHumans() }}
                @else
                    Nikad
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Brze akcije</h2>
        </div>
        <div class="form-row">
            <a href="{{ route('admin.suppliers') }}" class="btn-small">Upravljaj dobavljačima</a>
            <a href="{{ route('admin.products') }}" class="btn-small">Pregledaj proizvode</a>
            <button class="btn-small" onclick="syncAll()">Sinhronizuj sve</button>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Dobrodošli u Smart Market CMS</h2>
        </div>
        <p style="color: var(--text-muted); line-height: 1.8;">
            Ovaj sistem omogućava vam da lako upravljate proizvodima iz različitih izvora. 
            Možete dodati dobavljače koji će automatski sinhronizovati svoje proizvode putem 
            web scrapinga ili XML fajlova. Svi proizvodi će biti dostupni u vašem online shopu.
        </p>
        <div style="margin-top: 20px; padding: 20px; background: rgba(53, 116, 156, 0.1); border-radius: 10px;">
            <h3 style="margin-bottom: 10px;">📋 Sljedeći koraci:</h3>
            <ul style="padding-left: 20px; color: var(--text-muted);">
                <li>Dodajte dobavljače u sekciji "Dobavljači"</li>
                <li>Konfigurišite kategorije proizvoda</li>
                <li>Pokrenite sinhronizaciju proizvoda</li>
                <li>Prilagodite izgled vašeg shopa</li>
            </ul>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function syncAll() {
        if (confirm('Da li ste sigurni da želite sinhronizovati sve dobavljače?')) {
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
                    alert(data.message);
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Došlo je do greške pri sinhronizaciji.');
            });
        }
    }
</script>
@endpush