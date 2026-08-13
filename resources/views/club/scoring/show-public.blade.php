@extends('club.layouts.public')

@section('title', $title)

@push('style')
<!-- Google Fonts: Inter -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f4f7fb;
        color: #1e293b;
    }

    /* MATCH HEADER - Gradient & Premium Look */
    .match-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
        color: #ffffff;
        border: none;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        position: relative;
        overflow: hidden;
    }
    
    /* Subtle glow behind the score */
    .match-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, rgba(0,0,0,0) 60%);
        pointer-events: none;
    }

    .match-header h5 {
        color: #f8fafc;
        letter-spacing: -0.5px;
    }

    .match-header .text-muted {
        color: #94a3b8 !important;
    }

    .score-large {
        font-size: 2.5rem;
        font-weight: 700;
        letter-spacing: -1px;
        color: #ffffff;
        text-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }

    .score-large span {
        color: #cbd5e1 !important;
    }

    .match-header .text-primary {
        color: #38bdf8 !important;
    }

    /* Pulse animation for LIVE badge */
    @keyframes pulse-red {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }
    .badge.bg-danger {
        background-color: #ef4444 !important;
        animation: pulse-red 2s infinite;
        border-radius: 6px;
        padding: 0.4em 0.8em;
        font-weight: 600;
    }

    .badge {
        border-radius: 6px;
        padding: 0.4em 0.8em;
        font-weight: 600;
    }

    /* NAV TABS */
    .cric-nav-tabs {
        border-bottom: 2px solid #e2e8f0;
        margin-top: 1rem;
    }
    .cric-nav-tabs .nav-item {
        margin-bottom: -2px;
    }
    .cric-nav-tabs .nav-link {
        color: #64748b;
        font-weight: 600;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 1rem 1.5rem;
        transition: all 0.3s ease;
        background: transparent;
    }
    .cric-nav-tabs .nav-link:hover {
        color: #334155;
        border-bottom-color: #cbd5e1;
    }
    .cric-nav-tabs .nav-link.active {
        color: #4f46e5;
        border-bottom: 2px solid #4f46e5;
        background: transparent;
    }

    /* CARDS & ACCORDIONS (Glassmorphism & Soft Shadows) */
    .card, .accordion-item {
        background: #ffffff;
        border: 1px solid #f1f5f9 !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03) !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .accordion-button {
        border-radius: 12px !important;
        font-weight: 600;
        color: #334155;
        padding: 1.25rem;
    }
    .accordion-button:not(.collapsed) {
        background-color: #f8fafc;
        color: #4f46e5;
        box-shadow: inset 0 -1px 0 #e2e8f0;
    }
    .accordion-button:focus {
        box-shadow: none;
    }

    /* TABLES */
    .cric-table {
        margin-bottom: 0;
    }
    .cric-table th {
        background-color: #f8fafc;
        font-weight: 600;
        color: #64748b;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #f1f5f9;
        padding: 0.75rem 1rem;
    }
    .cric-table td {
        vertical-align: middle;
        font-size: 0.9rem;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }
    .cric-table tbody tr {
        transition: background-color 0.15s ease;
    }
    .cric-table tbody tr:hover {
        background-color: #f8fafc;
    }
    
    .cric-table td.fw-bold {
        font-weight: 600 !important;
    }
    
    .cric-table .text-primary {
        color: #4f46e5 !important;
    }

    /* TYPOGRAPHY UTILS */
    .dismissal-text {
        font-size: 0.8rem;
        color: #64748b;
    }
    .fow-text {
        font-size: 0.85rem;
        color: #475569;
        line-height: 1.6;
    }
    .section-title {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #64748b;
        font-weight: 700;
    }
</style>
@endpush

@section('content')
<main class="club-page" id="main-content" style="max-width: 900px; margin: 0 auto; padding: 1.5rem 1rem;">
    @include('club.scoring.partials.public-scorecard')
</main>

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mainContent = document.getElementById('main-content');
        
        // Poll every 15 seconds
        setInterval(() => {
            // Save state
            const activeTab = document.querySelector('.cric-nav-tabs .nav-link.active')?.id;
            const openAccordions = Array.from(document.querySelectorAll('.accordion-collapse.show')).map(el => el.id);

            fetch(window.location.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            })
            .then(response => response.text())
            .then(html => {
                if (html.trim() !== '') {
                    mainContent.innerHTML = html;
                    
                    // Restore state
                    if (activeTab) {
                        const tabTrigger = document.getElementById(activeTab);
                        if (tabTrigger && typeof bootstrap !== 'undefined') {
                            const tab = new bootstrap.Tab(tabTrigger);
                            tab.show();
                        } else if (tabTrigger) {
                            tabTrigger.click(); // Fallback
                        }
                    }
                    
                    openAccordions.forEach(id => {
                        const acc = document.getElementById(id);
                        if (acc) {
                            acc.classList.add('show');
                            const btn = document.querySelector(`[data-bs-target="#${id}"]`);
                            if (btn) {
                                btn.classList.remove('collapsed');
                                btn.setAttribute('aria-expanded', 'true');
                            }
                        }
                    });
                }
            })
            .catch(err => console.error('Error fetching score update:', err));
        }, 15000);
    });
</script>
@endpush
@endsection