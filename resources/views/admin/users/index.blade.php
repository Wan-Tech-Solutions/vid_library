@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Admin Users</h2>
            <a href="{{ route('admin.users.create') }}"
                class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded shadow">
                + Add Admin
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-700 rounded shadow">
                {{ session('success') }}
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white dark:bg-gray-800 rounded-lg shadow text-sm text-center">
                <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="py-3 px-4">Name</th>
                        <th class="py-3 px-4">Email</th>
                        <th class="py-3 px-4">Role</th>
                        <th class="py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-800 dark:text-white">
                    @foreach ($users as $user)
                        <tr id="row-{{ $user->id }}"
                            class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="py-3 px-4 name">{{ $user->name }}</td>
                            <td class="py-3 px-4 email">{{ $user->email }}</td>
                            <td class="py-3 px-4 role capitalize">{{ $user->role }}</td>
                            <td class="py-3 px-4 relative text-center">
                                <div class="inline-block text-left">
                                    <button onclick="toggleDropdown('dropdown-{{ $user->id }}')"
                                        class="flex items-center gap-1 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 rounded hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none transition">
                                        Actions
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <div id="dropdown-{{ $user->id }}"
                                        class="hidden absolute left-1/2 -translate-x-1/2 mt-2 w-40 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow z-50 text-left">

                                        <button type="button" onclick="openModal('{{ $user->id }}')"
                                            class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">
                                            Edit
                                        </button>


                                        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-100 dark:hover:bg-red-700 dark:text-red-300">
                                                Deactivate
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <!-- Edit Modal -->
                        <div id="modal-{{ $user->id }}"
                            class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center">
                            <div class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-lg p-6 relative shadow-xl">
                                <button onclick="closeModal('{{ $user->id }}')"
                                    class="absolute top-2 right-3 text-gray-500 hover:text-red-600 text-2xl">
                                    &times;
                                </button>
                                <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-white">Edit User</h2>

                                <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
                                    @csrf
                                    <!-- We avoid @method('PUT') since you're using POST instead -->

                                    <div class="mb-3">
                                        <label class="block text-sm text-gray-700 dark:text-gray-200">Name</label>
                                        <input type="text" name="name" value="{{ $user->name }}" required
                                            class="w-full px-3 py-2 border rounded bg-white dark:bg-gray-800 text-gray-800 dark:text-white">
                                    </div>

                                    <div class="mb-3">
                                        <label class="block text-sm text-gray-700 dark:text-gray-200">Email</label>
                                        <input type="email" name="email" value="{{ $user->email }}" required
                                            class="w-full px-3 py-2 border rounded bg-white dark:bg-gray-800 text-gray-800 dark:text-white">
                                    </div>

                                    <div class="mb-3">
                                        <label class="block text-sm text-gray-700 dark:text-gray-200">Password (leave blank
                                            to keep current)</label>
                                        <input type="password" name="password"
                                            class="w-full px-3 py-2 border rounded bg-white dark:bg-gray-800 text-gray-800 dark:text-white">
                                    </div>

                                    <div class="mb-3">
                                        <label class="block text-sm text-gray-700 dark:text-gray-200">Role</label>
                                        <select name="role"
                                            class="w-full px-3 py-2 border rounded bg-white dark:bg-gray-800 text-gray-800 dark:text-white">
                                            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin
                                            </option>
                                            <option value="superadmin"
                                                {{ $user->role === 'superadmin' ? 'selected' : '' }}>Super Admin</option>
                                        </select>
                                    </div>

                                    <div class="flex justify-end">
                                        <button type="submit"
                                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Update</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            document.querySelectorAll('[id^="dropdown-"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            dropdown.classList.toggle('hidden');
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('td')) {
                document.querySelectorAll('[id^="dropdown-"]').forEach(el => el.classList.add('hidden'));
            }
        });

        // For modal
        function openModal(id) {
            document.getElementById('modal-' + id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById('modal-' + id).classList.add('hidden');
        }

        // Update through AJAX
        function updateUser(event, userId) {
            event.preventDefault();

            const form = document.getElementById('editForm-' + userId);
            const formData = new FormData(form);

            // Remove _method if it's included
            formData.delete('_method');

            fetch(`/admin/users/${userId}/update`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: formData,
                })
                .then(response => {
                    if (!response.ok) throw new Error('Update failed');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        closeModal(userId);
                        showToast(data.message || '✅ User updated successfully');

                        // Update the table row
                        const row = document.getElementById(`row-${userId}`);
                        row.querySelector('.name').textContent = form.querySelector('[name="name"]').value;
                        row.querySelector('.email').textContent = form.querySelector('[name="email"]').value;
                        row.querySelector('.role').textContent = form.querySelector('[name="role"]').value;

                        // Animate updated row
                        row.classList.add('bg-green-100');
                        setTimeout(() => row.classList.remove('bg-green-100'), 1500);
                    } else {
                        alert('❌ Something went wrong.');
                    }
                })
                .catch(error => {
                    console.error(error);
                    alert('❌ Update failed.');
                });
        }
    </script>
@endpush
