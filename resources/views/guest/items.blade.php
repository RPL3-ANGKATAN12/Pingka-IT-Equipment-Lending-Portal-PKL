@extends('layouts.app')

@section('title', 'Items')

@section('content')

<div class="items-page">

    <div class="page-header">
        <div class="page-header-container">
            <div class="items-heading">
                <span class="items-heading-icon" aria-hidden="true">
                    <i class="fas fa-laptop"></i>
                </span>
                <div>
                    <h1>IT Items</h1>
                    <p>List of IT equipment and their current availability</p>
                </div>
            </div>
        </div>
    </div>

    <div class="items-container">
        <div class="items-toolbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="search" id="itemSearch" placeholder="Search item..." aria-label="Search items" autocomplete="off">
            </div>
            <div class="item-total">
                <i class="fas fa-cubes" aria-hidden="true"></i>
                <span>{{ $items->count() }} items</span>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Item</th>
                        <th>Available</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody id="itemsTableBody">
                    @forelse($items as $item)
                        <tr class="item-row">
                            <td data-label="No">{{ $loop->iteration }}</td>
                            <td data-label="Item" class="item-name-cell">
                                <span class="item-type-icon" aria-hidden="true">
                                    <i class="fas fa-laptop"></i>
                                </span>
                                <strong>{{ $item->name }}</strong>
                            </td>
                            <td data-label="Available">
                                {{ $item->status === 'tersedia' ? $item->available : 0 }}
                            </td>
                            <td data-label="Status">
                                @if($item->status === 'tersedia' && $item->available > 0)
                                    <span class="status-badge is-available">
                                        Available
                                    </span>
                                @else
                                    <span class="status-badge is-unavailable">
                                        Unavailable
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-table">
                                <i class="fas fa-box-open"></i>
                                <p>No items available.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div id="noSearchResult" class="no-search-result" style="display: none;">
                <i class="fas fa-search"></i>
                <p>No items found.</p>
            </div>
        </div>
    </div>
</div>

<script>

    const searchInput = document.getElementById('itemSearch');
    const rows = document.querySelectorAll('.item-row');
    const noSearchResult = document.getElementById('noSearchResult');

    searchInput.addEventListener('input', function () {
        const keyword = this.value.toLowerCase().trim();
        let visibleRows = 0;
        rows.forEach(function (row) {
            const text = row.textContent.toLowerCase();
            if (text.includes(keyword)) {
                row.style.display = '';
                visibleRows++;
            } else {
                row.style.display = 'none';
            }
        });

        if (visibleRows === 0 && keyword !== '') {
            noSearchResult.style.display = 'block';
        } else {
            noSearchResult.style.display = 'none';
        }

    });
</script>
@endsection