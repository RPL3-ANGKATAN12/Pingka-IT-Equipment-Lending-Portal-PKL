{{-- resources/views/admin/item/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Item Management')

@section('content')
<div class="dashboard">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <h2 class="page-title" style="margin-bottom: 0;">
            <i class="fas fa-tags"></i> Item Management
        </h2>
        <button type="button" onclick="cekElement()" style="position: relative; z-index: 99999; background: linear-gradient(135deg, #88B04B, #6B8E34); color: white; border: none; padding: 12px 28px; border-radius: 9999px; cursor: pointer;">
            <i class="fas fa-plus-circle"></i> Add Item
        </button>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h4>{{ $items->count() }}</h4>
            <p>Total Items</p>
        </div>
    </div>
    
    @if(session('success'))
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif
    
    <div class="dashboard-card" style="padding: 0; overflow: hidden;">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">NO.</th>
                        <th>Name Item</th>
                        <th style="width: 150px;">Available</th>
                        <th style="width: 180px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $k)
                    <tr>
                        <td>{{ $loop->iteration }}</td>

                        <td>
                            <strong style="display: flex; align-items: center; gap: 8px;">
                                <span class="badge" style="background: var(--matcha-light);">
                                    <i class="fas fa-tag"></i>
                                </span>
                                {{ $k->name }}
                            </strong>
                        </td>

                        <td>
                            <span class="badge badge-info">
                                {{ $k->available }}
                            </span>
                        </td>

                        <td>
                            <div class="action-buttons">
                                <button type="button" class="btn-table btn-edit" onclick="openEditModal( {{ $k->id }}, @js($k->name), {{ $k->available ?? 0 }} )">
                                
                                    <i class="fas fa-edit"></i> Edit
                                </button>

                                <form action="{{ route('admin.items.destroy', $k->id) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn-table btn-delete"
                                        onclick="confirmDelete(event, '{{ $k->name }}', this)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    @empty
                    <tr>
                        <td colspan="4" class="empty-state">
                            <i class="fas fa-folder-open"></i>
                            <p>No items yet</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalItem" class="modal" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); justify-content: center; align-items: center;">
    <div class="modal-content" style="background: white; border-radius: 12px; width: 90%; max-width: 500px; animation: modalPop 0.3s ease-out;">
        <div class="modal-header" style="background: linear-gradient(135deg, var(--matcha), var(--strawberry)); padding: 1.2rem 1.5rem; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="color: white; margin: 0;"><i class="fas fa-plus-circle"></i> Add New Item</h3>
            <button class="close-modal" onclick="closeModal()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form action="{{ route('admin.items.store') }}" method="POST">
            @csrf
            <div class="modal-body" style="padding: 1.5rem;">
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Name Item</label>
                    <input type="text" name="name" placeholder="Example: Laptop, Computer, Printer..." required autofocus style="width: 100%; padding: 12px; border: 2px solid #e8e8ed; border-radius: 8px;">
                    <small style="color: #6B7B6B;">Name item</small>
                </div>
                <div class="form-group" style="margin-top: 1rem;">
                    <label><i class="fas fa-boxes"></i> Available</label>
                    <input type="number" name="available" min="0" value="0" required style="width: 100%; padding: 12px; border: 2px solid #e8e8ed; border-radius: 8px;">
                    <small style="color: #6B7B6B;">Enter jumlah item yang tersedia.</small>
                </div>  
            </div>
            <div class="modal-footer" style="padding: 1rem 1.5rem 1.5rem; display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn-outline" onclick="closeModal()" style="background: transparent; border: 2px solid #88B04B; padding: 10px 24px; border-radius: 9999px; cursor: pointer;">Cancel</button>
                <button type="submit" class="btn-primary" style="background: linear-gradient(135deg, #88B04B, #6B8E34); color: white; border: none; padding: 10px 24px; border-radius: 9999px; cursor: pointer;">Save</button>
            </div>
        </form>
    </div>
</div>

<div id="editItemModal" style="display: none; position: fixed; z-index: 9999999; inset: 0; background: rgba(0, 0, 0, 0.6); justify-content: center; align-items: center; padding: 20px;">
    <div style="background: white; border-radius: 12px; width: 90%; max-width: 500px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); overflow: hidden;">
        <div style="background: linear-gradient(135deg, #88B04B, #E8A3A3); padding: 1.2rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
          
            <h3 style="color: white; margin: 0;">
                <i class="fas fa-edit"></i> Edit Item
            </h3>

            <button type="button" onclick="closeEditModal()" style="background: none; border: none; color: white; font-size: 28px; cursor: pointer; line-height: 1;">
                &times;
            </button>
        </div>

        <form id="editItemForm" method="POST" style="padding: 1.5rem;">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label>
                    <i class="fas fa-tag"></i> Name Item
                </label>

                <input type="text" id="editItemName" name="name" placeholder="Example: Laptop, Computer, Printer..." required style=" width: 100%; padding: 12px; border: 2px solid #E8E8ED; border-radius: 8px; font-size: 16px; margin-top: 8px;">

                <small style="color: #6B7B6B;">
                    Enter name item yang baru.
                </small>
            </div>

            <div class="form-group" style="margin-top: 1rem;">
                <label>
                    <i class="fas fa-boxes"></i> Available
                </label>
                <input type="number" id="editItemAvailable" name="available" min="0" required style="width: 100%; padding: 12px; border: 2px solid #E8E8ED; border-radius: 8px; font-size: 16px; margin-top: 8px;">
                <small style="color: #6B7B6B;">
                    Enter jumlah item yang tersedia.
                </small>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" onclick="closeEditModal()" style=" background: transparent; border: 2px solid #88B04B; padding: 10px 24px; border-radius: 9999px; cursor: pointer;">
                    Cancel
                </button>

                <button type="submit" style=" background: linear-gradient(135deg, #88B04B, #6B8E34); color: white; border: none; padding: 10px 24px; border-radius: 9999px; cursor: pointer;">
                    <i class="fas fa-save"></i>Update
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function cekElement() {
    var existingModal = document.getElementById('modalItem');
    if(existingModal) {
        existingModal.remove(); 
    }
    
    var modalHtml = `
    <div id="modalItem" style="display: flex; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999999; justify-content: center; align-items: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 500px; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
            <div style="background: linear-gradient(135deg, #88B04B, #E8A3A3); padding: 1.2rem 1.5rem; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between;">
                <h3 style="color: white; margin: 0;"><i class="fas fa-plus-circle"></i> Add Item</h3>
                <button onclick="this.closest('#modalItem').style.display='none'" style="background: none; border: none; color: white; font-size: 28px; cursor: pointer;">&times;</button>
            </div>
            <form action="{{ route('admin.items.store') }}" method="POST" style="padding: 1.5rem;">
                @csrf
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Name Item</label>
                    <input type="text" name="name" placeholder="Example: Laptop, Computer, Printer..." required autofocus style="width: 100%; padding: 12px; border: 2px solid #e8e8ed; border-radius: 8px;">
                    <small style="color: #6B7B6B;">Name item</small>
                </div>
                <div class="form-group" style="margin-top: 1rem;">
                    <label><i class="fas fa-boxes"></i> Available</label>
                    <input type="number" name="available" min="0" value="0" required style="width: 100%; padding: 12px; border: 2px solid #e8e8ed; border-radius: 8px;">
                    <small style="color: #6B7B6B;">Enter jumlah item yang tersedia.</small>
                </div>
                <label></label>
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" onclick="this.closest('#modalItem').style.display='none'" style="background: transparent; border: 2px solid #88B04B; padding: 10px 24px; border-radius: 9999px; cursor: pointer;">Cancel</button>
                    <button type="submit" style="background: linear-gradient(135deg, #88B04B, #6B8E34); color: white; border: none; padding: 10px 24px; border-radius: 9999px; cursor: pointer;">Save</button>
                </div>
            </form>
        </div>
    </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'opacity 0.3s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        });
    }, 3000);


    function confirmDelete(event, name, el) {
        Swal.fire({
            title: 'Are you sure you want to delete?',
            text: 'Item "' + name + '" will be deleted!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#88B04B',
            cancelButtonColor: '#E8A3A3',
            confirmButtonText: 'Yes, delete!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                el.closest('form').submit();
            }
        });
    }

    function openEditModal(id, name, available) {
    const modal = document.getElementById('editItemModal');
    const form = document.getElementById('editItemForm');
    const input = document.getElementById('editItemName');
    const availableInput = document.getElementById('editItemAvailable');

    form.action = "{{ url('/admin/items') }}/" + id;

    console.log(form.action);

    input.value = name;
    availableInput.value = available;

    modal.style.display = 'flex';

    setTimeout(() => {
        input.focus();
        input.select();
    }, 100);

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

    function closeEditModal() {
        const modal = document.getElementById('editItemModal');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    window.addEventListener('click', function(event) {
        const modal = document.getElementById('editItemModal');
        if (event.target === modal) {
            closeEditModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeEditModal();
        }
    });
</script>
@endsection