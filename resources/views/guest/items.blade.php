@extends('layouts.app')

@section('title', 'Items')

@section('content')

<div class="items-page">

    <div class="page-header">
        <div class="page-header-container">
            <h1>IT Items</h1>
            <p>List of IT equipment and their current availability</p>
        </div>
    </div>

    <div class="items-container">

        <div class="items-toolbar">

            <div class="search-box">
                <i class="fas fa-search"></i>
                <input
                    type="text"
                    id="itemSearch"
                    placeholder="Search item..."
                    autocomplete="off"
                >
            </div>

            <div class="item-total">
                {{ $items->count() }} items
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

                            <td>{{ $loop->iteration }}</td>

                            <td>
                                <strong>{{ $item->name }}</strong>
                            </td>

                            <td>
                                {{ $item->available }}
                            </td>

                            <td>
                                @if($item->available > 0)
                                    <span class="status-badge">
                                        {{ $item->available > 0 ? 'Available' : 'Borrowed' }}
                                    </span>
                                @else
                                    <span class="status-badge">
                                        Unavailable
                                    </span>
                                @endif
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="8" class="empty-table">
                                <i class="fas fa-box-open"></i>
                                <p>No items available.</p>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

            <div
                id="noSearchResult"
                class="no-search-result"
                style="display: none;"
            >
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