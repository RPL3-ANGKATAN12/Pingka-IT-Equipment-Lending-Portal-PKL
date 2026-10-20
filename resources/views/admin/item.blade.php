@extends('layouts.app')

@section('title', 'Item Management')

@section('content')
<div class="dashboard item-management">

    {{-- HEADER --}}
    <div class="item-page-header">
        <div>
            <h2 class="page-title">
                <i class="fas fa-tags"></i> Item Management
            </h2>
            <p class="item-subtitle">Manage your IT equipment inventory.</p>
        </div>
    </div>

    {{-- SUMMARY + SEARCH --}}
    <div class="inventory-toolbar">
        <div class="inventory-summary">
            <div class="summary-icon">
                <i class="fas fa-boxes-stacked"></i>
            </div>
            <div>
                <span>Total Item Types</span>
                <strong>{{ $items->count() }}</strong>
            </div>
        </div>

        <div class="inventory-search">
            <i class="fas fa-search"></i>
            <input
                type="search"
                id="itemSearch"
                placeholder="Search item name..."
                autocomplete="off"
                oninput="filterItems()"
            >
        </div>

        <button type="button" class="btn-add-item" onclick="openAddModal()">
            <i class="fas fa-plus-circle"></i> Add Item
        </button>
    </div>

    {{-- ITEM CARDS --}}
    <div class="item-grid" id="itemGrid">
        @forelse($items as $item)
            @php
                $serialNumbers = $item->serialNumbers
                    ->map(fn ($serial) => [
                        'id' => $serial->id,
                        'serial_number' => $serial->serial_number,
                        'status' => $serial->status,
                    ])
                    ->values();

                if ($item->tracks_serial_numbers) {
                    $activeLoanSerials = \App\Models\Loans::where('status', 'process')
                        ->whereNotNull('sn')
                        ->pluck('sn')
                        ->map(fn ($sn) => mb_strtolower(trim($sn)))
                        ->all();

                    $serialNumbers = $item->serialNumbers
                        ->map(fn ($serial) => [
                            'id' => $serial->id,
                            'serial_number' => $serial->serial_number,
                            'status' => (
                                $serial->status === 'borrowed'
                                || in_array(mb_strtolower(trim($serial->serial_number)), $activeLoanSerials, true)
                            ) ? 'borrowed' : $serial->status,
                        ])
                        ->values();

                    $borrowed = $item->serialNumbers->filter(fn ($serial) =>
                        $serial->status === 'borrowed'
                        || in_array(mb_strtolower(trim($serial->serial_number)), $activeLoanSerials, true)
                    )->count();

                    $available = $item->serialNumbers->filter(fn ($serial) =>
                        $serial->status === 'available'
                        && !in_array(mb_strtolower(trim($serial->serial_number)), $activeLoanSerials, true)
                    )->count();

                    $totalStock = $item->serialNumbers->count();
                } else {
                    $available = (int) ($item->available ?? 0);
                    $borrowed = $item->loans()->where('status', 'process')->count();
                    $totalStock = $available + $borrowed;
                }
            @endphp

            <article
                class="inventory-card"
                data-name="{{ strtolower($item->name) }}"
            >
                <div class="inventory-card-top">
                    <div class="inventory-item-name" title="{{ $item->name }}">
                        {{ $item->name }}
                    </div>

                    <span class="inventory-total" title="Total stock">
                        {{ $totalStock }}
                    </span>
                </div>

                <div class="inventory-stock-info">
                    <span>
                        <i class="fas fa-hand-holding"></i>
                        In Use:
                        <strong>{{ $borrowed }}</strong>
                    </span>

                    <span>
                        Available:
                        <strong class="{{ $available <= 0 ? 'stock-empty' : 'stock-available' }}">
                            {{ $available }}
                        </strong>
                    </span>
                </div>

                <div class="inventory-stock-bar">
                    <span
                        style="width: {{ $totalStock > 0 ? min(100, ($available / $totalStock) * 100) : 0 }}%;"
                    ></span>
                </div>

                <div class="inventory-card-footer">
                    @if($item->requires_hostname)
                        <span class="hostname-tag" title="Hostname required">
                            <i class="fas fa-desktop"></i>
                        </span>
                    @else
                        <span class="hostname-tag hostname-tag-muted" title="Hostname not required">
                            <i class="fas fa-box"></i>
                        </span>
                    @endif

                    @if($item->tracks_serial_numbers)
                        <button
                            type="button"
                            class="btn-serial-item"
                            onclick="openSerialModal(
                                {{ $item->id }},
                                @js($item->name),
                                @js($serialNumbers),
                                {{ $totalStock }}
                            )"
                        >
                            <i class="fas fa-barcode"></i>
                            SN <span>{{ $item->serialNumbers->count() }}</span>
                        </button>
                    @endif

                    <button
                        type="button"
                        class="btn-view-item"
                        onclick="openDetailModal(
                            {{ $item->id }},
                            @js($item->name),
                            {{ $totalStock }},
                            {{ $borrowed }},
                            {{ $available }},
                            {{ $item->requires_hostname ? 'true' : 'false' }}
                        )"
                    >
                        View <i class="fas fa-chevron-right"></i>
                    </button>
                </div>

                {{-- DATA UNTUK EDIT MODAL --}}
                <div class="item-actions-data" hidden>
                    <button
                        type="button"
                        class="edit-item-data"
                        onclick="openEditModal(
                            {{ $item->id }},
                            @js($item->name),
                            {{ $available }},
                            {{ $item->requires_hostname ? 'true' : 'false' }},
                            {{ $item->tracks_serial_numbers ? 'true' : 'false' }}
                        )"
                    ></button>
                </div>
            </article>
        @empty
            <div class="item-empty">
                <i class="fas fa-box-open"></i>
                <h3>No items yet</h3>
                <p>Add your first item to start managing inventory.</p>
            </div>
        @endforelse
    </div>

    <div id="noItemsFound" class="item-empty" style="display:none;">
        <i class="fas fa-search"></i>
        <h3>No matching items</h3>
        <p>Try another search keyword.</p>
    </div>
</div>

{{-- ADD ITEM MODAL --}}
<div id="addItemModal" class="inventory-modal" aria-hidden="true">
    <div class="inventory-modal-content" role="dialog" aria-modal="true">
        <div class="inventory-modal-header">
            <h3><i class="fas fa-plus-circle"></i> Add New Item</h3>
            <button type="button" class="modal-x" onclick="closeAddModal()">&times;</button>
        </div>

        <form action="{{ route('admin.items.store') }}" method="POST">
            @csrf

            <div class="inventory-modal-body">
                <div class="inventory-field">
                    <label for="newItemName">Item Name</label>
                    <input
                        id="newItemName"
                        type="text"
                        name="name"
                        maxlength="255"
                        placeholder="Example: Computer"
                        required
                    >
                </div>

                <div class="inventory-field">
                    <label for="newItemAvailable">Available Stock</label>
                    <input
                        id="newItemAvailable"
                        type="number"
                        name="available"
                        min="0"
                        value="0"
                        required
                    >
                </div>

                <label class="hostname-option">
                    <input type="checkbox" name="requires_hostname" value="1">
                    <span>
                        <strong>
                            <i class="fas fa-desktop"></i> Hostname Required
                        </strong>
                        <small>Require hostname information before completing a loan.</small>
                    </span>
                </label>

                <label class="hostname-option serial-tracking-option">
                    <input type="checkbox" name="tracks_serial_numbers" value="1">
                    <span>
                        <strong><i class="fas fa-barcode"></i> Track Serial Numbers</strong>
                        <small>Track each physical unit using its own serial number.</small>
                    </span>
                </label>
            </div>

            <div class="inventory-modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeAddModal()">
                    Cancel
                </button>

                <button type="submit" class="btn-modal-save">
                    <i class="fas fa-save"></i> Save Item
                </button>
            </div>
        </form>
    </div>
</div>

{{-- EDIT ITEM MODAL --}}
<div id="editItemModal" class="inventory-modal" aria-hidden="true">
    <div class="inventory-modal-content" role="dialog" aria-modal="true">
        <div class="inventory-modal-header">
            <h3><i class="fas fa-edit"></i> Edit Item</h3>
            <button type="button" class="modal-x" onclick="closeEditModal()">&times;</button>
        </div>

        <form id="editItemForm" method="POST">
            @csrf
            @method('PUT')

            <div class="inventory-modal-body">
                <div class="inventory-field">
                    <label for="editItemName">Item Name</label>
                    <input
                        id="editItemName"
                        type="text"
                        name="name"
                        maxlength="255"
                        required
                    >
                </div>

                <div class="inventory-field">
                    <label for="editItemAvailable">Available Stock</label>
                    <input
                        id="editItemAvailable"
                        type="number"
                        name="available"
                        min="0"
                        required
                    >
                    <small>Enter the current number of available items.</small>
                </div>

                <label class="hostname-option">
                    <input
                        type="checkbox"
                        id="editItemRequiresHostname"
                        name="requires_hostname"
                        value="1"
                    >
                    <span>
                        <strong>
                            <i class="fas fa-desktop"></i> Hostname Required
                        </strong>
                        <small>Require hostname information before completing a loan.</small>
                    </span>
                </label>

                <label class="hostname-option serial-tracking-option">
                    <input
                        type="checkbox"
                        id="editItemTracksSerialNumbers"
                        name="tracks_serial_numbers"
                        value="1"
                    >
                    <span>
                        <strong><i class="fas fa-barcode"></i> Track Serial Numbers</strong>
                        <small>Manage the individual serial number for each physical unit.</small>
                    </span>
                </label>
            </div>

            <div class="inventory-modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeEditModal()">
                    Cancel
                </button>

                <button type="submit" class="btn-modal-save">
                    <i class="fas fa-save"></i> Update Item
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ITEM DETAIL MODAL --}}
<div id="detailItemModal" class="inventory-modal" aria-hidden="true">
    <div class="inventory-modal-content" role="dialog" aria-modal="true">
        <div class="inventory-modal-header">
            <h3><i class="fas fa-box"></i> Item Details</h3>
            <button type="button" class="modal-x" onclick="closeDetailModal()">&times;</button>
        </div>

        <div class="inventory-modal-body">
            <div class="detail-item-heading">
                <div class="detail-item-icon">
                    <i class="fas fa-desktop"></i>
                </div>

                <div>
                    <small>Item Name</small>
                    <h3 id="detailItemName">-</h3>
                </div>
            </div>

            <div class="detail-stock-grid">
                <div class="detail-stock-box">
                    <span>Total Stock</span>
                    <strong id="detailTotal">0</strong>
                </div>

                <div class="detail-stock-box">
                    <span>In Use</span>
                    <strong id="detailBorrowed">0</strong>
                </div>

                <div class="detail-stock-box">
                    <span>Available</span>
                    <strong id="detailAvailable">0</strong>
                </div>
            </div>

            <div class="detail-hostname">
                <i class="fas fa-desktop"></i>
                <span id="detailHostname">Hostname not required</span>
            </div>
        </div>

        <div class="inventory-modal-footer detail-footer">
            <button type="button" class="btn-modal-delete" onclick="deleteCurrentItem()">
                <i class="fas fa-trash"></i> Delete
            </button>

            <button type="button" class="btn-modal-cancel" onclick="closeDetailModal()">
                Close
            </button>

            <button type="button" class="btn-modal-save" onclick="editCurrentItem()">
                <i class="fas fa-edit"></i> Edit
            </button>
        </div>
    </div>
</div>

{{-- SERIAL NUMBERS MODAL --}}
<div id="serialNumbersModal" class="inventory-modal" aria-hidden="true">
    <div class="inventory-modal-content serial-modal-content" role="dialog" aria-modal="true">
        <div class="inventory-modal-header">
            <h3><i class="fas fa-barcode"></i> Serial Numbers</h3>
            <button type="button" class="modal-x" onclick="closeSerialModal()">&times;</button>
        </div>

        <div class="inventory-modal-body">
            <div class="serial-modal-heading">
                <span class="serial-item-label">Equipment</span>
                <h3 id="serialItemName">-</h3>
                <p>Every physical unit can have its own unique serial number.</p>
            </div>

            <div class="serial-units-summary">
                <span>Registered Units</span>
                <strong id="serialUnitsCount">0 / 0 units</strong>
            </div>

            <form id="serialNumberForm" method="POST" class="serial-add-form">
                @csrf

                <div class="inventory-field">
                    <label for="newSerialNumber">Serial Number</label>
                    <input
                        id="newSerialNumber"
                        name="serial_number"
                        maxlength="150"
                        placeholder="Example: CN0ABC123456"
                        autocomplete="off"
                        required
                    >
                </div>

                <button id="addSerialButton" type="submit" class="btn-modal-save">
                    <i class="fas fa-plus"></i> Add Serial Number
                </button>
            </form>

            <div id="serialLimitMessage" class="serial-limit-message" hidden>
                <i class="fas fa-check-circle"></i>
                All physical units have registered serial numbers.
            </div>

            <div class="serial-list-header">
                <strong>Serial Number List</strong>
                <span id="serialCountBadge">0 units</span>
            </div>

            <div id="serialNumbersList" class="serial-numbers-list"></div>
        </div>

        <div class="inventory-modal-footer">
            <button type="button" class="btn-modal-cancel" onclick="closeSerialModal()">
                Close
            </button>
        </div>
    </div>
</div>

{{-- DELETE FORM --}}
<form id="deleteItemForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let currentItemId = null;
let currentItemName = '';

let currentSerialNumbers = [];
let currentSerialLimit = 0;

/* SEARCH ITEMS */
function filterItems() {
    const search = document.getElementById('itemSearch').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.inventory-card');
    let visible = 0;

    cards.forEach(function(card) {
        const name = card.dataset.name || '';
        const match = name.includes(search);

        card.style.display = match ? '' : 'none';

        if (match) visible++;
    });

    const emptyMessage = document.getElementById('noItemsFound');

    if (emptyMessage) {
        emptyMessage.style.display = visible === 0 ? '' : 'none';
    }
}

/* OPEN AND CLOSE MODALS */
function showInventoryModal(id) {
    const modal = document.getElementById(id);

    if (!modal) return;

    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function hideInventoryModal(id) {
    const modal = document.getElementById(id);

    if (!modal) return;

    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');

    if (!document.querySelector('.inventory-modal.open')) {
        document.body.style.overflow = '';
    }
}

/* ADD ITEM */
function openAddModal() {
    showInventoryModal('addItemModal');

    setTimeout(function() {
        document.getElementById('newItemName').focus();
    }, 100);
}

function closeAddModal() {
    hideInventoryModal('addItemModal');
}

/* EDIT ITEM */
function openEditModal(id, name, available, requiresHostname, tracksSerialNumbers) {
    currentItemId = id;
    currentItemName = name;

    document.getElementById('editItemForm').action =
        "{{ url('/admin/items') }}/" + encodeURIComponent(id);

    document.getElementById('editItemName').value = name || '';
    document.getElementById('editItemAvailable').value = available ?? 0;

    document.getElementById('editItemRequiresHostname').checked =
        Boolean(requiresHostname);

    document.getElementById('editItemTracksSerialNumbers').checked =
        Boolean(tracksSerialNumbers);

    closeDetailModal();
    showInventoryModal('editItemModal');

    setTimeout(function() {
        document.getElementById('editItemName').focus();
    }, 100);
}

function closeEditModal() {
    hideInventoryModal('editItemModal');
}

/* ITEM DETAILS */
function openDetailModal(id, name, total, borrowed, available, requiresHostname) {
    currentItemId = id;
    currentItemName = name;

    document.getElementById('detailItemName').textContent = name || '-';
    document.getElementById('detailTotal').textContent = total;
    document.getElementById('detailBorrowed').textContent = borrowed;
    document.getElementById('detailAvailable').textContent = available;

    document.getElementById('detailHostname').textContent =
        requiresHostname
            ? 'Hostname is required for this item.'
            : 'Hostname is not required for this item.';

    showInventoryModal('detailItemModal');
}

function closeDetailModal() {
    hideInventoryModal('detailItemModal');
}

/* EDIT BUTTON FROM DETAILS */
function editCurrentItem() {
    if (!currentItemId) return;

    const card = document.querySelector(
        '.inventory-card .edit-item-data[onclick*="' + currentItemId + '"]'
    );

    if (card) {
        card.click();
    }
}

/* ESCAPE HTML */
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function(char) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char];
    });
}

/* SERIAL NUMBER MODAL */
function openSerialModal(itemId, itemName, serials, totalStock) {
    currentItemId = itemId;
    currentItemName = itemName;

    currentSerialNumbers = Array.isArray(serials) ? serials : [];
    currentSerialLimit = Math.max(0, Number(totalStock) || 0);

    document.getElementById('serialItemName').textContent = itemName || '-';

    document.getElementById('serialNumberForm').action =
        "{{ url('/admin/items') }}/" +
        encodeURIComponent(itemId) +
        "/serial-numbers";

    document.getElementById('newSerialNumber').value = '';

    renderSerialNumbers();
    showInventoryModal('serialNumbersModal');

    setTimeout(function() {
        const input = document.getElementById('newSerialNumber');

        if (input && !input.disabled) {
            input.focus();
        }
    }, 100);
}

/* UPDATE SERIAL NUMBER COUNTER AND LIST */
function renderSerialNumbers() {
    const list = document.getElementById('serialNumbersList');
    const countBadge = document.getElementById('serialCountBadge');
    const unitsCount = document.getElementById('serialUnitsCount');
    const addButton = document.getElementById('addSerialButton');
    const input = document.getElementById('newSerialNumber');
    const limitMessage = document.getElementById('serialLimitMessage');

    const registered = currentSerialNumbers.length;
    const limitReached = registered >= currentSerialLimit;

    unitsCount.textContent =
        registered + ' / ' + currentSerialLimit + ' units';

    countBadge.textContent =
        registered + (registered === 1 ? ' unit' : ' units');

    addButton.disabled = limitReached;
    input.disabled = limitReached;

    limitMessage.hidden = !limitReached;

    if (limitReached) {
        input.placeholder = 'Stock limit reached';
    } else {
        input.placeholder = 'Example: CN0ABC123456';
    }

    if (registered === 0) {
        list.innerHTML =
            '<div class="serial-empty">' +
                '<i class="fas fa-barcode"></i><br>' +
                'No serial numbers yet. Add the first unit above.' +
            '</div>';

        return;
    }

    list.innerHTML = currentSerialNumbers.map(function(serial) {
        const status = String(serial.status || 'available');

        let statusLabel = 'Available';
        let statusClass = '';

        if (status === 'borrowed') {
            statusLabel = 'Borrowed';
            statusClass = 'status-borrowed';
        } else if (status === 'maintenance') {
            statusLabel = 'Maintenance';
            statusClass = 'status-maintenance';
        }

        let deleteButton = '';

        if (status !== 'borrowed') {
            deleteButton =
                '<form method="POST" action="{{ url('/admin/items') }}/' +
                    encodeURIComponent(currentItemId) +
                    '/serial-numbers/' +
                    encodeURIComponent(serial.id) +
                    '" onsubmit="return confirmSerialDelete(event, this)">' +
                    '<input type="hidden" name="_token" value="{{ csrf_token() }}">' +
                    '<input type="hidden" name="_method" value="DELETE">' +
                    '<button type="submit" class="serial-delete-btn" title="Delete serial number" aria-label="Delete serial number">' +
                        '<i class="fas fa-trash"></i>' +
                    '</button>' +
                '</form>';
        }

        return (
            '<div class="serial-number-row">' +
                '<div class="serial-number-info">' +
                    '<div class="serial-number-value">' +
                        escapeHtml(serial.serial_number) +
                    '</div>' +
                    '<span class="serial-status-pill ' + statusClass + '">' +
                        escapeHtml(statusLabel) +
                    '</span>' +
                '</div>' +
                deleteButton +
            '</div>'
        );
    }).join('');
}

function closeSerialModal() {
    hideInventoryModal('serialNumbersModal');
}

/* SERIAL NUMBER DELETE CONFIRMATION */
function confirmSerialDelete(event, form) {
    event.preventDefault();

    if (typeof Swal === 'undefined') {
        if (window.confirm('Delete this serial number?')) {
            form.submit();
        }

        return false;
    }

    Swal.fire({
        title: 'Delete serial number?',
        text: 'This serial number will be removed from this item.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc6262',
        cancelButtonColor: '#64748b'
    }).then(function(result) {
        if (result.isConfirmed) {
            form.submit();
        }
    });

    return false;
}

/* DELETE ITEM */
function deleteCurrentItem() {
    if (!currentItemId) return;

    const id = currentItemId;
    const name = currentItemName;

    if (typeof Swal === 'undefined') {
        if (window.confirm('Delete item "' + name + '"?')) {
            submitDelete(id);
        }

        return;
    }

    closeDetailModal();

    Swal.fire({
        title: 'Delete this item?',
        html:
            '<div style="margin-top:8px;">' +
                'Are you sure you want to delete ' +
                '<strong>' + escapeHtml(name) + '</strong>?' +
                '<div style="' +
                    'margin-top:12px;' +
                    'padding:10px 12px;' +
                    'border-radius:9px;' +
                    'background:rgba(220,98,98,.09);' +
                    'color:#dc6262;' +
                    'font-size:12px;' +
                    'text-align:left;' +
                '">' +
                    '<i class="fas fa-exclamation-triangle"></i> ' +
                    'This action cannot be undone.' +
                '</div>' +
            '</div>',
        icon: 'warning',
        iconColor: '#d99a36',
        showCancelButton: true,
        reverseButtons: true,
        focusCancel: true,
        confirmButtonText: '<i class="fas fa-trash"></i> Yes, delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc6262',
        cancelButtonColor: '#64748b',
        buttonsStyling: true,
        allowOutsideClick: false
    }).then(function(result) {
        if (result.isConfirmed) {
            submitDelete(id);
        }
    });
}

/* SUBMIT DELETE FORM */
function submitDelete(id) {
    const form = document.getElementById('deleteItemForm');

    form.action = "{{ url('/admin/items') }}/" + encodeURIComponent(id);
    form.submit();
}

/* CLOSE MODAL WHEN CLICKING OUTSIDE */
document.querySelectorAll('.inventory-modal').forEach(function(modal) {
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            hideInventoryModal(modal.id);
        }
    });
});

/* CLOSE MODAL WITH ESCAPE */
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.inventory-modal.open').forEach(function(modal) {
            hideInventoryModal(modal.id);
        });
    }
});
</script>
@endsection