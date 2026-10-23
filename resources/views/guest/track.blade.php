@extends('layouts.app')

@section('title', 'Loan Tracking')

@push('styles')
<style>
    .loan-tracking {
        width: min(100% - 32px, 900px);
        margin: 36px auto 64px;
    }

    .tracking-heading {
        margin-bottom: 22px;
    }

    .tracking-heading h2 {
        margin-bottom: 8px;
        font-size: clamp(1.6rem, 4vw, 2rem);
    }

    .tracking-heading p {
        color: var(--text-light);
    }

    .tracking-card {
        overflow: hidden;
        border: 1px solid var(--gray);
        border-radius: var(--radius-lg);
        background: var(--white);
        box-shadow: var(--shadow-md);
    }

    .tracking-summary {
        display: flex;
        align-items: flex-start;
        gap: 18px;
        padding: clamp(22px, 5vw, 36px);
        background: linear-gradient(135deg, #f0f5e8, #fbfcf9);
    }

    .tracking-item-icon {
        display: grid;
        width: 58px;
        height: 58px;
        flex: 0 0 58px;
        place-items: center;
        border: 1px solid #dce8c9;
        border-radius: 16px;
        background: #fff;
        color: var(--matcha-dark);
        font-size: 22px;
    }

    .tracking-summary-content {
        min-width: 0;
        flex: 1;
    }

    .tracking-label {
        margin: 0 0 5px;
        color: var(--text-light);
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .tracking-summary h3 {
        margin: 0 0 10px;
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
        margin-top: 18px;
        padding: 8px 12px;
        border: 1px solid #dce8c9;
        border-radius: var(--radius-full);
        background: #fff;
        color: var(--matcha-dark);
        font-size: 13px;
        font-weight: 600;
    }

    .tracking-status.is-warning {
        border-color: #eadfb9;
        color: #806b2c;
    }

    .tracking-status.is-danger {
        border-color: #e8cccc;
        color: #914b4b;
    }

    .tracking-section {
        padding: clamp(22px, 5vw, 34px);
        border-top: 1px solid var(--gray-light);
    }

    .tracking-section h4 {
        margin: 0 0 22px;
        font-size: 16px;
    }

    .tracking-timeline {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .tracking-step {
        position: relative;
        min-width: 0;
        padding-right: 18px;
    }

    .tracking-step:not(:last-child)::after {
        position: absolute;
        top: 15px;
        right: 0;
        left: 34px;
        height: 2px;
        background: var(--gray);
        content: "";
    }

    .tracking-step.is-complete:not(:last-child)::after {
        background: var(--matcha);
    }

    .tracking-step-marker {
        position: relative;
        z-index: 1;
        display: grid;
        width: 32px;
        height: 32px;
        margin-bottom: 12px;
        place-items: center;
        border: 2px solid var(--gray);
        border-radius: 50%;
        background: #fff;
        color: var(--gray-dark);
        font-size: 12px;
    }

    .tracking-step.is-complete .tracking-step-marker,
    .tracking-step.is-current .tracking-step-marker {
        border-color: var(--matcha);
        background: var(--matcha);
        color: #fff;
    }

    .tracking-step.is-current .tracking-step-marker {
        box-shadow: 0 0 0 5px var(--matcha-soft);
    }

    .tracking-step strong {
        display: block;
        margin-bottom: 4px;
        font-size: 13px;
    }

    .tracking-step span {
        display: block;
        color: var(--text-light);
        font-size: 12px;
        line-height: 1.5;
    }

    .tracking-rejected {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 16px;
        border: 1px solid #e8cccc;
        border-radius: var(--radius-md);
        background: #fffafa;
        color: #914b4b;
        line-height: 1.6;
    }

    .tracking-rejected i {
        margin-top: 4px;
    }

    .tracking-dates {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 14px;
    }

    .tracking-date {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 15px;
        border: 1px solid var(--gray);
        border-radius: var(--radius-md);
        background: #fff;
    }

    .tracking-date i {
        margin-top: 3px;
        color: var(--matcha-dark);
    }

    .tracking-date span {
        display: block;
        margin-bottom: 4px;
        color: var(--text-light);
        font-size: 12px;
    }

    .tracking-date strong {
        font-size: 14px;
    }

    .tracking-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-top: 18px;
        color: var(--text-light);
        font-size: 13px;
    }

    .tracking-footer a {
        color: var(--matcha-dark);
        font-weight: 600;
        text-decoration: none;
    }

    .tracking-footer a:hover {
        text-decoration: underline;
    }

    @media (max-width: 560px) {
        .loan-tracking {
            margin-top: 24px;
        }

        .tracking-summary {
            gap: 13px;
        }

        .tracking-item-icon {
            width: 46px;
            height: 46px;
            flex-basis: 46px;
            border-radius: 13px;
            font-size: 18px;
        }

        .tracking-timeline {
            grid-template-columns: 1fr;
            gap: 18px;
        }

        .tracking-step {
            display: grid;
            grid-template-columns: 34px 1fr;
            column-gap: 12px;
            padding: 0;
        }

        .tracking-step:not(:last-child)::after {
            top: 31px;
            bottom: -18px;
            left: 15px;
            width: 2px;
            height: auto;
        }

        .tracking-step-marker {
            grid-row: span 2;
            margin: 0;
        }

        .tracking-footer {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
@endpush

@section('content')
@php
    $status = $loan->status;
    $isRejected = $status === 'ditolak';
    $isPrepared = $status === 'selesai';
    $isReturned = $loan->returned_at !== null;

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
            'label' => 'Peminjaman berlangsung',
            'description' => 'Perangkat telah disiapkan dan sedang dalam masa peminjaman.',
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
                <ol class="tracking-timeline">
                    <li class="tracking-step {{ in_array($status, ['process', 'selesai'], true) || $isReturned ? 'is-complete' : 'is-current' }}">
                        <span class="tracking-step-marker" aria-hidden="true">
                            <i class="fas {{ in_array($status, ['process', 'selesai'], true) || $isReturned ? 'fa-check' : 'fa-file-lines' }}"></i>
                        </span>
                        <strong>{{ $status === 'pending' ? 'Permintaan diterima' : 'Permintaan disetujui' }}</strong>
                        <span>Permintaan masuk ke proses peminjaman.</span>
                    </li>
                    <li class="tracking-step {{ $isPrepared || $isReturned ? 'is-complete' : ($status === 'process' ? 'is-current' : '') }}">
                        <span class="tracking-step-marker" aria-hidden="true">
                            <i class="fas {{ $isPrepared || $isReturned ? 'fa-check' : 'fa-box' }}"></i>
                        </span>
                        <strong>Perangkat disiapkan</strong>
                        <span>Admin menyiapkan perangkat untuk dipinjam.</span>
                    </li>
                    <li class="tracking-step {{ $isReturned ? 'is-complete' : ($isPrepared ? 'is-current' : '') }}">
                        <span class="tracking-step-marker" aria-hidden="true">
                            <i class="fas {{ $isReturned ? 'fa-check' : 'fa-rotate-left' }}"></i>
                        </span>
                        <strong>Pengembalian</strong>
                        <span>{{ $isReturned ? 'Pengembalian sudah dicatat.' : 'Menunggu perangkat dikembalikan.' }}</span>
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
