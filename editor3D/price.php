@extends('layouts.header')

@section('content')
<div class="pricing-container">
    <h1>🚀 Choisissez Votre Plan</h1>
    <p>Commencez gratuitement, évoluez selon vos besoins</p>

    <div class="pricing-grid">
        <!-- Plan Gratuit -->
        <div class="pricing-card {{ $currentPlan === 'free' ? 'current' : '' }}">
            <div class="plan-header">
                <h3>🎨 Free</h3>
                <div class="price">Gratuit</div>
                <div class="period">À vie</div>
            </div>
            <ul class="features">
                <li>✅ 1 projet gratuit à vie</li>
                <li>✅ Export code basique</li>
                <li>✅ Modèles de base</li>
                <li>❌ Pas de galerie publique</li>
                <li>❌ Support standard</li>
            </ul>
            @if($currentPlan === 'free')
                <button class="btn btn-secondary" disabled>Plan Actuel</button>
            @else
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">Revenir Gratuit</a>
            @endif
        </div>

        <!-- Plan Creator -->
        <div class="pricing-card {{ $currentPlan === 'creator' ? 'current' : '' }} popular">
            <div class="plan-header">
                <div class="popular-badge">Populaire</div>
                <h3>⚡ Creator</h3>
                <div class="price">9€<span>/mois</span></div>
                <div class="period">Facturation mensuelle</div>
            </div>
            <ul class="features">
                <li>✅ Projets illimités</li>
                <li>✅ Export code complet</li>
                <li>✅ Modèles premium</li>
                <li>✅ Galerie publique</li>
                <li>✅ Support prioritaire</li>
            </ul>
            @if($currentPlan === 'creator')
                <button class="btn btn-primary" disabled>Plan Actuel</button>
            @else
                <form action="{{ route('subscribe', 'creator') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">Choisir Creator</button>
                </form>
            @endif
        </div>

        <!-- Plan Agency -->
        <div class="pricing-card {{ $currentPlan === 'agency' ? 'current' : '' }}">
            <div class="plan-header">
                <h3>🏢 Agency</h3>
                <div class="price">29€<span>/mois</span></div>
                <div class="period">Facturation mensuelle</div>
            </div>
            <ul class="features">
                <li>✅ Tout Creator +</li>
                <li>✅ Collaboration équipe</li>
                <li>✅ Analytics avancés</li>
                <li>✅ Support dédié</li>
                <li>✅ Whitelabel</li>
            </ul>
            @if($currentPlan === 'agency')
                <button class="btn btn-primary" disabled>Plan Actuel</button>
            @else
                <form action="{{ route('subscribe', 'agency') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">Choisir Agency</button>
                </form>
            @endif
        </div>
    </div>
</div>

<style>
.pricing-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
    text-align: center;
}

.pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 3rem;
}

.pricing-card {
    background: #F1F1F1;
    border: 1px solid #313244;
    border-radius: 12px;
    padding: 2rem;
    position: relative;
    transition: transform 0.2s, border-color 0.2s;
}

.pricing-card:hover {
    transform: translateY(-5px);
    border-color: #cba6f7;
}

.pricing-card.current {
    border-color: #cba6f7;
    background: rgba(203, 166, 247, 0.05);
}

.pricing-card.popular {
    border-color: #f5c2e7;
    transform: scale(1.05);
}

.popular-badge {
    position: absolute;
    top: -10px;
    left: 50%;
    transform: translateX(-50%);
    background: #f5c2e7;
    color: #1e1e2e;
    padding: 0.25rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: bold;
}

.plan-header {
    margin-bottom: 1.5rem;
}

.plan-header h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.price {
    font-size: 2.5rem;
    font-weight: bold;
    color: #cba6f7;
}

.price span {
    font-size: 1rem;
    color: #a6adc8;
}

.period {
    color: #a6adc8;
    font-size: 0.9rem;
}

.features {
    list-style: none;
    padding: 0;
    margin: 2rem 0;
    text-align: left;
}

.features li {
    padding: 0.5rem 0;
    border-bottom: 1px solid #313244;
}

.features li:last-child {
    border-bottom: none;
}

.btn {
    width: 100%;
    padding: 1rem;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: #cba6f7;
    color: #1e1e2e;
}

.btn-primary:hover {
    background: #b692f6;
}

.btn-secondary {
    background: #585b70;
    color: #cdd6f4;
}

.btn-secondary:hover {
    background: #6c7086;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>
@endsection