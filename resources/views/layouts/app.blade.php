{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>  
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal Laptop')</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" >
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
    .logout-popup {
        width: 390px !important;
        border-radius: 22px !important;
        padding: 32px 30px 28px !important;
        font-family: 'Poppins', sans-serif !important;
    }

    .logout-popup .swal2-icon {
        width: 70px;
        height: 70px;
        margin: 0 auto 18px;
        border-width: 3px;
    }

    .logout-title {
        font-size: 22px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        margin-bottom: 8px !important;
    }

    .logout-text {
        font-size: 13px !important;
        line-height: 1.7 !important;
        color: #64748b !important;
        margin-top: 0 !important;
    }

    .logout-confirm,
    .logout-cancel {
        border: none !important;
        border-radius: 10px !important;
        padding: 11px 19px !important;
        font-family: 'Poppins', sans-serif !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        cursor: pointer !important;
        transition: all .2s ease !important;
    }

    .logout-confirm {
        background: #ef4444 !important;
        color: #fff !important;
        box-shadow: 0 5px 14px rgba(239, 68, 68, .20) !important;
    }

    .logout-confirm:hover {
        background: #dc2626 !important;
        transform: translateY(-1px);
    }

    .logout-cancel {
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    .logout-cancel:hover {
        background: #e2e8f0 !important;
        transform: translateY(-1px);
    }
</style>
    @stack('styles')
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <a href="{{ route('guest.home') }}" class="logo-link">
                <img
                    src="{{ asset('images/logo.png') }}"
                    alt="IT Equipment Lending Portal Logo"
                    class="navbar-logo"
                >
            </a>

            <h1>IT Equipment Lending Portal</h1>
        </div>

        <div class="nav-links">
            @guest
                @if(!request()->routeIs('guest.home'))
                    <a href="{{ route('guest.home') }}" class="btn-back">
                        <i class="fas fa-home"></i>Home Page
                    </a>
                @endif
                <button type="button" class="btn-primary" onclick="openModal('admin')">
                    <i class="fas fa-user-shield"></i>Admin Login
                </button>

                <button type="button" class="btn-primary" onclick="openRequestModal()">
                    <i class="fas fa-laptop"></i>Request Items
                </button>
            @endguest

            @auth
                <a href="{{ route('guest.home') }}" class="btn-back">
                    <i class="fas fa-home"></i>Home Page
                </a>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span>{{ auth()->user()->nama ?? auth()->user()->name }}</span>
                    <small>({{ auth()->user()->role }})</small>
                </div>
                <div class="btn-logout">
                    <form id="logoutForm" action="{{ route('logout') }}" method="POST" >
                        @csrf
                        <button type="button" onclick="confirmLogout()" >
                            <i class="fas fa-sign-out-alt"></i>Logout
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </nav>

    @auth
        @if(auth()->user()->role === 'admin')
            <div class="admin-nav">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>Dashboard
                </a>
                <a href="{{ route('admin.items.index') }}" class="{{ request()->routeIs('admin.items*') ? 'active' : '' }}">
                    <i class="fas fa-laptop"></i>Items
                </a>
                <a href="{{ route('admin.requests') }}" class="{{ request()->routeIs('admin.requests*') ? 'active' : '' }}">
                    <i class="fas fa-user-plus"></i>
                    Request
                </a>
                <a href="{{ route('admin.loans') }}" class="{{ request()->routeIs('admin.loans*') ? 'active' : '' }}">
                    <i class="fas fa-list-ul"></i>Data
                </a>
                <a href="{{ route('admin.returned-items.index') }}">
                    <i class="fas fa-box-open"></i><span>Return</span>
                </a>
            </div>
        @endif
    @endauth
    <main>
        @if(session('success'))
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-error" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <ul style="margin-left:20px; margin-top:5px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
    @guest
        <div id="modal-login" class="modal">
            <div class="modal-content" style="max-width:450px;">
                <div class="modal-header">
                    <h3>
                        <i class="fas fa-user-shield"></i>Admin Login
                    </h3>
                    <button type="button" class="close-modal" onclick="closeModal()">
                        &times;
                    </button>
                </div>
                <div class="tab-content active">
                    <form method="POST" action="{{ route('login.admin') }}">
                        @csrf
                        <div class="modal-body">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-user"></i>Name
                                </label>
                                <input type="text" name="nama" value="{{ old('nama') }}" placeholder="Enter name" required>
                            </div>
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-lock"></i>Password
                                </label>
                                <input type="password" name="password" placeholder="Enter password" required>
                            </div>
                        </div>
                        <div class="modal-footer" style="justify-content:space-between;">
                            <button type="button" class="btn-outline" onclick="closeModal()">
                                Cancel
                            </button>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-sign-in-alt"></i>Admin Login
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endguest
    @guest
        <div id="modal-request" class="modal">
            <div class="modal-content" style="max-width:550px;">
                <div class="modal-header">
                    <h3>
                        <i class="fas fa-laptop"></i>Loan Request
                    </h3>
                    <button type="button" class="close-modal" onclick="closeRequestModal()">
                        &times;
                    </button>
                </div>
                <form action="{{ route('loans.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="request_name">
                                <i class="fas fa-user"></i>Name
                            </label>
                            <input id="request_name" type="text" name="name" value="{{ old('name') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="request_email">
                                <i class="fas fa-envelope"></i>
                                Email
                            </label>
                            <input id="request_email" type="email" name="email" value="{{ old('email') }}" placeholder="Masukkan email aktif" required>
                        </div>
                        <div class="form-group">
                            <label for="request_departement">
                                <i class="fas fa-building"></i>Department
                            </label>
                            <input id="request_departement" type="text" name="departement" value="{{ old('departement') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="request_item_id">
                                <i class="fas fa-laptop"></i>Item
                            </label>
                            <select id="request_item_id" name="item_id" required>
                                <option value="">-- Select Item --</option>
                                @php
                                    $availableItems = \App\Models\Item::where('available', '>', 0)
                                        ->orderBy('name')
                                        ->get();
                                @endphp

                                @foreach($availableItems as $item)
                                    <option
                                        value="{{ $item->id }}"
                                        {{ old('item_id') == $item->id ? 'selected' : '' }}
                                    >
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="request_start_date">
                                <i class="fas fa-calendar"></i>
                                Start Date
                            </label>
                            <input id="request_start_date" type="date" name="start_date" value="{{ old('start_date') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="request_others">
                                <i class="fas fa-comment"></i>Description (optional)
                            </label>
                            <textarea id="request_others" name="others" rows="4">
                                {{ old('others') }}
                            </textarea>
                        </div>
                    </div>
                    <div class="modal-footer" style="justify-content:space-between;">
                        <button type="button" class="btn-outline" onclick="closeRequestModal()">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-paper-plane"></i>Send Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endguest
    <button type="button" onclick="scrollToTop()" class="scroll-top" id="scrollTopBtn">
        <i class="fas fa-arrow-up"></i>
    </button>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function scrollToTop()
        {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        window.addEventListener(
            'scroll',
            function ()
            {
                const scrollBtn =
                    document.getElementById(
                        'scrollTopBtn'
                    );
                if (!scrollBtn)
                    return;
                if (window.pageYOffset > 300)
                {
                    scrollBtn.style.display = 'flex';
                }
                else
                {
                    scrollBtn.style.display = 'none';
                }
            }
        );


        setTimeout(
            function ()
            {
                const alerts =
                    document.querySelectorAll(
                        '.alert'
                    );
                alerts.forEach(
                    function (alert)
                    {
                        alert.style.transition =
                            'opacity 0.3s';
                        alert.style.opacity = '0';
                        setTimeout(
                            function ()
                            {
                                alert.remove();
                            },
                            300
                        );
                    }
                );
            },
            4000
        );


        function openModal(type)
        {
            const modal =
                document.getElementById(
                    'modal-login'
                );
            if (!modal)
                return;
            if (type === 'admin')
            {
                modal.style.display = 'flex';
                document.body.style.overflow =
                    'hidden';
            }
        }
    
        function closeModal()
        {
            const modal =
                document.getElementById(
                    'modal-login'
                );
            if (!modal)
                return;
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        document.addEventListener(
            'click',
            function (event)
            {
                const modal =
                    document.getElementById(
                        'modal-login'
                    );
                if (
                    modal &&
                    event.target === modal
                )
                {
                    closeModal();
                }
            }
        );

        function openRequestModal()
        {
            const modal =
                document.getElementById('modal-request');
            if (!modal)
                return;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeRequestModal()
        {
            const modal =
                document.getElementById('modal-request');
            if (!modal)
                return;
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        document.addEventListener(
            'keydown',
            function (event)
            {
                if (event.key === 'Escape')
                {
                    closeModal();
                    closeRequestModal();
                }
            }
        );

        function confirmLogout()
        {
            Swal.fire({
                title: 'Logout dari akun?',
                html: 'Kamu akan keluar dari akun<br>dan kembali ke halaman utama.',
                icon: 'question',
                showCancelButton: true,
                reverseButtons: true,
                focusCancel: true,
                confirmButtonText: '<i class="fas fa-sign-out-alt"></i> Ya, Logout',
                cancelButtonText: 'Batal',
                customClass: {
                    popup: 'logout-popup',
                    title: 'logout-title',
                    htmlContainer: 'logout-text',
                    confirmButton: 'logout-confirm',
                    cancelButton: 'logout-cancel'
                },
                buttonsStyling: false
            }).then(function(result)
            {
                if (result.isConfirmed)
                {
                    const form =
                        document.getElementById('logoutForm');

                    if (form)
                    {
                        form.submit();
                    }
                }
            });
        }

        function closeLogoutModal()
        {
            const modal =
                document.getElementById(
                    'logoutModal'
                );
            if (modal)
            {
                modal.style.display =
                    'none';
            }
        }

        function submitLogout()
        {
            const form =
                document.getElementById(
                    'logoutForm'
                );
            if (form)
            {
                form.submit();
            }
        }
    </script>
    @stack('scripts')
</body>
</html>