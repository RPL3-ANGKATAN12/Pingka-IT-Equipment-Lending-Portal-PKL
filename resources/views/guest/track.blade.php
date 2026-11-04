@extends('layouts.app')

@section('title', 'Loan Tracking')

@push('styles')
<style>
    .loan-tracking {
        width: min(100% - 32px, 1000px);
        margin: 40px auto 72px;
    }

    .tracking-heading {
        margin-bottom: 24px;
    }

    .tracking-heading h2 {
        margin-bottom: 7px;
        font-size: clamp(1.6rem, 4vw, 2rem);
    }

    .tracking-heading p {
        color: var(--text-light);
        font-size: 0.95rem;
    }

    .tracking-card {
        overflow: hidden;
        border: 1px solid #e1e7da;
        border-radius: 20px;
        background: var(--white);
        box-shadow: 0 12px 32px rgba(52, 56, 47, 0.08);
    }

    .tracking-summary {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: clamp(24px, 5vw, 40px);
        background: linear-gradient(135deg, #f3f7ec, #fbfcf8);
    }

    .tracking-item-icon {
        display: grid;
        width: 60px;
        height: 60px;
        flex: 0 0 60px;
        place-items: center;
        border: 1px solid #dce8c9;
        border-radius: 18px;
        background: #fff;
        color: var(--matcha-dark);
        font-size: 23px;
        box-shadow: var(--shadow-sm);
    }

    .tracking-summary-content {
        min-width: 0;
        flex: 1;
    }

    .tracking-label {
        margin: 0 0 5px;
        color: var(--text-light);
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .tracking-summary h3 {
        margin: 0 0 7px;
        overflow-wrap: anywhere;
        font-size: clamp(1.25rem, 3vw, 1.65rem);
    }

    .tracking-description {
        margin: 0;
        color: var(--text-light);
        line-height: 1.6;
    }

    .tracking-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-top: 14px;
        padding: 7px 12px;
        border: 1px solid #dce8c9;
        border-radius: var(--radius-full);
        background: #fff;
        color: var(--matcha-dark);
        font-size: 12px;
        font-weight: 600;
    }

    .tracking-status.is-warning {
        border-color: #eadfb9;
        background: #fffdf6;
        color: #806b2c;
    }

    .tracking-status.is-danger {
        border-color: #e8cccc;
        background: #fffafa;
        color: #914b4b;
    }

    .tracking-section {
        padding: clamp(22px, 4vw, 32px);
        border-top: 1px solid #edf0e9;
    }

    .tracking-section h4 {
        margin: 0 0 18px;
        font-size: 15px;
    }

    .tracking-timeline {
        --tracking-progress: 0%;
        position: relative;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0;
        margin: 0;
        padding: 0 0 4px;
        list-style: none;
    }

    .tracking-timeline::before,
    .tracking-timeline::after {
        position: absolute;
        top: 17px;
        left: 12.5%;
        height: 2px;
        content: "";
    }

    .tracking-timeline::before {
        right: 12.5%;
        background: #e4e9dc;
    }

    .tracking-timeline::after {
        width: var(--tracking-progress);
        background: var(--matcha);
        transition: width 0.25s ease;
    }

    .tracking-step {
        min-width: 0;
        padding: 0 12px;
        text-align: center;
    }

    .tracking-step-marker {
        position: relative;
        z-index: 1;
        display: grid;
        width: 34px;
        height: 34px;
        margin: 0 auto 13px;
        place-items: center;
        border: 2px solid #d9dfd1;
        border-radius: 50%;
        background: #fff;
        color: #78816f;
        font-size: 12px;
        font-weight: 700;
        transition: background 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .tracking-step.is-complete .tracking-step-marker {
        border-color: var(--matcha);
        background: var(--matcha);
        color: #fff;
    }

    .tracking-step.is-current .tracking-step-marker {
        border-color: var(--matcha-dark);
        color: var(--matcha-dark);
        box-shadow: 0 0 0 4px var(--matcha-soft);
    }

    .tracking-step strong {
        display: block;
        margin: 0 auto 5px;
        color: var(--text-dark);
        font-size: 12px;
        line-height: 1.4;
    }

    .tracking-step span {
        display: block;
        max-width: 190px;
        margin: 0 auto;
        color: var(--text-light);
        font-size: 11px;
        line-height: 1.55;
    }

    .tracking-rejected {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 18px;
        border: 1px solid #e8cccc;
        border-radius: 14px;
        background: #fffafa;
        color: #914b4b;
        line-height: 1.6;
    }

    .tracking-rejected i {
        margin-top: 4px;
    }

    .tracking-dates {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 12px;
    }

    .tracking-date {
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 74px;
        padding: 14px 16px;
        border: 1px solid #e6e9e1;
        border-radius: 14px;
        background: #fbfcfa;
    }

    .tracking-date i {
        display: grid;
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        place-items: center;
        border-radius: 10px;
        background: #eef4e4;
        color: var(--matcha-dark);
    }

    .tracking-date span {
        display: block;
        margin-bottom: 3px;
        color: var(--text-light);
        font-size: 11px;
    }

    .tracking-date strong {
        font-size: 13px;
    }

    .tracking-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-top: 16px;
        padding: 0 4px;
        color: var(--text-light);
        font-size: 12px;
    }

    .tracking-footer a {
        color: var(--matcha-dark);
        font-weight: 600;
        text-decoration: none;
    }

    .tracking-footer a:hover {
        text-decoration: underline;
    }

    @media (max-width: 760px) {
        .tracking-timeline {
            grid-template-columns: 1fr;
            gap: 0;
            padding-left: 2px;
        }

        .tracking-step {
            display: grid;
            grid-template-columns: 36px minmax(0, 1fr);
            column-gap: 12px;
            padding: 0 0 18px;
            text-align: left;
        }

        .tracking-timeline::before,
        .tracking-timeline::after {
            top: 17px;
            bottom: 17px;
            left: 16px;
            width: 2px;
            height: auto;
        }

        .tracking-timeline::before {
            right: auto;
        }

        .tracking-timeline::after {
            display: none;
        }

        .tracking-step-marker {
            grid-row: span 2;
            margin: 0;
        }

        .tracking-step strong,
        .tracking-step span {
            max-width: none;
            margin-left: 0;
        }
    }

    @media (max-width: 560px) {
        .loan-tracking {
            width: min(100% - 24px, 1000px);
            margin-top: 24px;
            margin-bottom: 40px;
        }

        .tracking-summary {
            align-items: flex-start;
            gap: 13px;
            padding: 22px 18px;
        }

        .tracking-item-icon {
            width: 44px;
            height: 44px;
            flex-basis: 44px;
            border-radius: 12px;
            font-size: 17px;
        }

        .tracking-section {
            padding: 22px 18px;
        }

        .tracking-timeline {
            grid-template-columns: 1fr;
        }

        .tracking-footer {
            align-items: flex-start;
            flex-direction: column;
            gap: 8px;
            padding: 0 2px;
        }
    }
</style>
@endpush

@section('content')
@php
    $status = $loan->status;
    $isRejected = $status === 'ditolak';
    $isPrepared = $status === 'selesai';
    $isBorrowed = $status === 'borrowed';
    $isReturned = $loan->returned_at !== null;
    $trackingProgress = $isReturned
        ? '75%'
        : ($isBorrowed ? '75%' : ($isPrepared ? '50%' : ($status === 'process' ? '25%' : '0%')));

    $statusDetails = match (true) {
        $isRejected => [
            'label' => 'Permintaan ditolak',
            'description' => 'Permintaan peminjaman ini tidak dapat diproses.',
            'class' => 'is-danger',
            'icon' => 'fa-circle-xmark',
        ],
        $isReturned => [
            'label' => 'Barang telah dikembalikan',
            'description' => 'Pengembalian barang sudah tercatat. Terima kasih!',
            'class' => '',
            'icon' => 'fa-circle-check',
        ],
        $isPrepared => [
            'label' => 'Perangkat siap diambil',
            'description' => 'Perangkat telah disiapkan. Silakan ambil barang Anda.',
            'class' => 'is-warning',
            'icon' => 'fa-hand',
        ],
        $isBorrowed => [
            'label' => 'Peminjaman berlangsung',
            'description' => 'Admin telah mengonfirmasi bahwa perangkat sudah diambil.',
            'class' => '',
            'icon' => 'fa-laptop',
        ],
        $status === 'process' => [
            'label' => 'Sedang diproses',
            'description' => 'Permintaan disetujui. Admin sedang menyiapkan perangkat.',
            'class' => 'is-warning',
            'icon' => 'fa-hourglass-half',
        ],
        default => [
            'label' => 'Menunggu proses',
            'description' => 'Permintaan peminjaman sedang menunggu tindak lanjut admin.',
            'class' => 'is-warning',
            'icon' => 'fa-clock',
        ],
    };
@endphp

<div class="loan-tracking">
    <header class="tracking-heading">
        <h2>Tracking Peminjaman</h2>
        <p>Pantau status dan jadwal peminjaman perangkat Anda.</p>
    </header>

    <article class="tracking-card">
        <section class="tracking-summary" aria-labelledby="tracking-item-name">
            <div class="tracking-item-icon" aria-hidden="true">
                <i class="fas fa-laptop"></i>
            </div>
            <div class="tracking-summary-content">
                <p class="tracking-label">Perangkat yang dipinjam</p>
                <h3 id="tracking-item-name">{{ $loan->item->name ?? 'Perangkat tidak tersedia' }}</h3>
                <p class="tracking-description">{{ $statusDetails['description'] }}</p>
                <span class="tracking-status {{ $statusDetails['class'] }}">
                    <i class="fas {{ $statusDetails['icon'] }}" aria-hidden="true"></i>
                    {{ $statusDetails['label'] }}
                </span>
            </div>
        </section>

        <section class="tracking-section" aria-labelledby="tracking-progress-title">
            <h4 id="tracking-progress-title">Progres peminjaman</h4>

            @if($isRejected)
                <div class="tracking-rejected">
                    <i class="fas fa-circle-xmark" aria-hidden="true"></i>
                    <span>Status akhir permintaan ini adalah ditolak. Silakan hubungi admin jika membutuhkan informasi lebih lanjut.</span>
                </div>
            @else
                <ol class="tracking-timeline" style="--tracking-progress: {{ $trackingProgress }};">
                    <li class="tracking-step {{ in_array($status, ['process', 'selesai', 'borrowed'], true) || $isReturned ? 'is-complete' : 'is-current' }}">
                        <span class="tracking-step-marker" aria-hidden="true">
                            @if(in_array($status, ['process', 'selesai', 'borrowed'], true) || $isReturned)
                                <i class="fas fa-check"></i>
                            @else
                                1
                            @endif
                        </span>
                        <strong>{{ $status === 'pending' ? 'Permintaan diterima' : 'Permintaan disetujui' }}</strong>
                        <span>Permintaan masuk ke proses peminjaman.</span>
                    </li>
                    <li class="tracking-step {{ $isPrepared || $isBorrowed || $isReturned ? 'is-complete' : ($status === 'process' ? 'is-current' : '') }}">
                        <span class="tracking-step-marker" aria-hidden="true">
                            @if($isPrepared || $isBorrowed || $isReturned)
                                <i class="fas fa-check"></i>
                            @else
                                2
                            @endif
                        </span>
                        <strong>Perangkat disiapkan</strong>
                        <span>Admin menyiapkan perangkat untuk dipinjam.</span>
                    </li>
                    <li class="tracking-step {{ $isBorrowed || $isReturned ? 'is-complete' : ($isPrepared ? 'is-current' : '') }}">
                        <span class="tracking-step-marker" aria-hidden="true">
                            @if($isBorrowed || $isReturned)
                                <i class="fas fa-check"></i>
                            @else
                                3
                            @endif
                        </span>
                        <strong>Pengambilan perangkat</strong>
                        <span>{{ $isBorrowed || $isReturned ? 'Pengambilan perangkat telah dikonfirmasi admin.' : 'Silakan ambil perangkat yang telah disiapkan.' }}</span>
                    </li>
                    <li class="tracking-step {{ $isReturned ? 'is-complete' : ($isBorrowed ? 'is-current' : '') }}">
                        <span class="tracking-step-marker" aria-hidden="true">
                            @if($isReturned)
                                <i class="fas fa-check"></i>
                            @else
                                4
                            @endif
                        </span>
                        <strong>Pengembalian</strong>
                        <span>{{ $isReturned ? 'Pengembalian sudah dicatat.' : ($isBorrowed ? 'Menunggu perangkat dikembalikan.' : 'Pengembalian dimulai setelah perangkat diambil.') }}</span>
                    </li>
                </ol>
            @endif
        </section>

        <section class="tracking-section" aria-labelledby="tracking-dates-title">
            <h4 id="tracking-dates-title">Informasi tanggal</h4>
            <div class="tracking-dates">
                <div class="tracking-date">
                    <i class="fas fa-calendar-day" aria-hidden="true"></i>
                    <div>
                        <span>Tanggal mulai</span>
                        <strong>{{ $loan->start_date?->format('d M Y') ?? 'Belum ditentukan' }}</strong>
                    </div>
                </div>

                @if($loan->due_date)
                    <div class="tracking-date">
                        <i class="fas fa-calendar-check" aria-hidden="true"></i>
                        <div>
                            <span>Batas pengembalian</span>
                            <strong>{{ $loan->due_date->format('d M Y') }}</strong>
                        </div>
                    </div>
                @endif

                @if($loan->picked_up_at)
                    <div class="tracking-date">
                        <i class="fas fa-hand" aria-hidden="true"></i>
                        <div>
                            <span>Waktu diambil</span>
                            <strong>{{ $loan->picked_up_at->format('d M Y, H:i') }}</strong>
                        </div>
                    </div>
                @endif

                @if($loan->returned_at)
                    <div class="tracking-date">
                        <i class="fas fa-circle-check" aria-hidden="true"></i>
                        <div>
                            <span>Waktu dikembalikan</span>
                            <strong>{{ $loan->returned_at->format('d M Y, H:i') }}</strong>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </article>

    <footer class="tracking-footer">
        <span>Informasi status diperbarui oleh admin.</span>
        <a href="{{ route('guest.loans') }}">Lihat data peminjaman <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </footer>
</div>
@endsection
