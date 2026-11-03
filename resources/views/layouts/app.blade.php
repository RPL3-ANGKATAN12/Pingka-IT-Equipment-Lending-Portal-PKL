{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>  
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Laptop Portal')</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" >
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
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
                @if(!request()->routeIs('guest.home'))
                    <a href="{{ route('guest.home') }}" class="btn-back">
                        <i class="fas fa-home"></i>Home Page
                    </a>
                @endif
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
                    <i class="fas fa-list-ul"></i>Preparation
                </a>
                <a href="{{ route('admin.returned-items.index') }}" class="{{ request()->routeIs('admin.returned*') ? 'active' : '' }}">
                    <i class="fas fa-box-open"></i>Return
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
                <form id="requestForm" action="{{ route('loans.store') }}" method="POST">
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
                            <input id="request_email" type="email" name="email" value="{{ old('email') }}" placeholder="Enter your active email address" required>
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
                                    $availableItems = \App\Models\Item::where('status', 'tersedia')
                                        ->where('available', '>', 0)
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
                            <div style="position:relative; display:flex; align-items:center; gap:6px;">
                                <input
                                    id="request_start_date"
                                    type="text"
                                    value="{{ old('start_date') }}"
                                    placeholder="dd/mm/yyyy"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    aria-describedby="request_start_date_hint"
                                    style="flex:1; min-width:0;"
                                    required
                                >
                                <button
                                    type="button"
                                    onclick="openRequestDatePicker()"
                                    aria-label="Choose start date"
                                    style="padding:10px 12px; border:1px solid #ddd; border-radius:8px; background:white; cursor:pointer;"
                                >
                                    <i class="fas fa-calendar-alt"></i>
                                </button>
                                <input
                                    id="request_start_date_picker"
                                    type="date"
                                    value="{{ old('start_date') }}"
                                    aria-label="Choose start date"
                                    tabindex="-1"
                                    style="position:absolute; top:0; right:0; width:44px; height:100%; opacity:0; pointer-events:none;"
                                >
                            </div>
                            <input
                                id="request_start_date_iso"
                                type="hidden"
                                name="start_date"
                                value="{{ old('start_date') }}"
                            >
                            <small id="request_start_date_hint"></small>
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

        if (new URLSearchParams(window.location.search).get('request') === '1')
        {
            openRequestModal();
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

        function parseDisplayDate(value)
        {
            const match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(value.trim());
            if (!match)
                return null;

            const day = Number(match[1]);
            const month = Number(match[2]);
            const year = Number(match[3]);
            const date = new Date(year, month - 1, day);

            if (
                year < 1000 ||
                date.getFullYear() !== year ||
                date.getMonth() !== month - 1 ||
                date.getDate() !== day
            )
                return null;

            return year + '-' + match[2] + '-' + match[1];
        }

        const requestStartDate = document.getElementById('request_start_date');
        const requestStartDateIso = document.getElementById('request_start_date_iso');
        const requestStartDatePicker = document.getElementById('request_start_date_picker');

        function openRequestDatePicker()
        {
            if (!requestStartDatePicker)
                return;

            if (typeof requestStartDatePicker.showPicker === 'function')
                requestStartDatePicker.showPicker();
            else {
                requestStartDatePicker.focus();
                requestStartDatePicker.click();
            }
        }

        if (requestStartDate && requestStartDateIso && requestStartDatePicker) {
            if (/^\d{4}-\d{2}-\d{2}$/.test(requestStartDate.value)) {
                const parts = requestStartDate.value.split('-');
                requestStartDate.value = parts[2] + '/' + parts[1] + '/' + parts[0];
            }

            requestStartDatePicker.addEventListener('change', function () {
                if (!this.value)
                    return;

                const parts = this.value.split('-');
                requestStartDate.value = parts[2] + '/' + parts[1] + '/' + parts[0];
                requestStartDateIso.value = this.value;
                requestStartDate.setCustomValidity('');
            });

            document.getElementById('requestForm').addEventListener(
                'submit',
                function (event) {
                    const isoDate = parseDisplayDate(requestStartDate.value);
                    requestStartDate.setCustomValidity(
                        isoDate ? '' : 'Enter a valid date in dd/mm/yyyy format.'
                    );

                    if (!isoDate) {
                        event.preventDefault();
                        requestStartDate.reportValidity();
                        return;
                    }

                    requestStartDateIso.value = isoDate;
                }
            );

            requestStartDate.addEventListener('input', function () {
                this.setCustomValidity('');
            });
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
                title: 'Log out of your account?',
                html: 'You will be logged out<br>and returned to the home page.',
                icon: 'question',
                showCancelButton: true,
                reverseButtons: true,
                focusCancel: true,
                confirmButtonText: '<i class="fas fa-sign-out-alt"></i> Yes, Log Out',
                cancelButtonText: 'Cancel',
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