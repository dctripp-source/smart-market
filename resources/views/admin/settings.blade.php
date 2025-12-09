@extends('layouts.admin')

@section('title', 'Podešavanja')
@section('page-title', 'Podešavanja')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2>Opšta podešavanja</h2>
        </div>
        
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            
            <div class="form-row">
                <div class="form-group">
                    <label>Naziv radnje *</label>
                    <input type="text" 
                           name="shop_name" 
                           class="form-control" 
                           value="{{ old('shop_name', 'Smart Market') }}" 
                           required>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" 
                           name="email" 
                           class="form-control" 
                           value="{{ old('email', 'info@smartmarket.ba') }}" 
                           required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Telefon *</label>
                    <input type="tel" 
                           name="phone" 
                           class="form-control" 
                           value="{{ old('phone', '+387 51 123 456') }}" 
                           required>
                </div>
                <div class="form-group">
                    <label>Adresa *</label>
                    <input type="text" 
                           name="address" 
                           class="form-control" 
                           value="{{ old('address', 'Banja Luka, Republika Srpska') }}" 
                           required>
                </div>
            </div>

            <button type="submit" class="btn-small">Sačuvaj izmjene</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Sistemske informacije</h2>
        </div>
        
        <div style="background: var(--bg-dark); padding: 20px; border-radius: 10px; font-family: 'Courier New', monospace; font-size: 14px;">
            <div style="margin-bottom: 10px;">
                <span style="color: var(--text-muted);">Laravel verzija:</span> 
                <span style="color: var(--success);">{{ app()->version() }}</span>
            </div>
            <div style="margin-bottom: 10px;">
                <span style="color: var(--text-muted);">PHP verzija:</span> 
                <span style="color: var(--success);">{{ phpversion() }}</span>
            </div>
            <div style="margin-bottom: 10px;">
                <span style="color: var(--text-muted);">Okruženje:</span> 
                <span style="color: var(--warning);">{{ config('app.env') }}</span>
            </div>
            <div>
                <span style="color: var(--text-muted);">Debug:</span> 
                <span style="color: {{ config('app.debug') ? 'var(--danger)' : 'var(--success)' }}">
                    {{ config('app.debug') ? 'Uključen' : 'Isključen' }}
                </span>
            </div>
        </div>
    </div>
@endsection