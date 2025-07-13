@extends('layouts.app')

@section('content')
    <div class="max-w-2xl mx-auto mt-10 bg-white dark:bg-gray-900 shadow-md rounded-lg p-8">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800 dark:text-white">➕ Add New User</h2>

        @if (session('success'))
            <div class="mb-4 p-4 rounded-md bg-green-100 border border-green-300 text-green-700 text-center">
                ✅ {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Full Name</label>
                <input type="text" name="name" value="{{ old('name') }}"
                    class="w-full mt-1 px-4 py-2 border rounded-md dark:bg-gray-800 dark:text-white dark:border-gray-600"
                    required>
                @error('name')
                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}"
                    class="w-full mt-1 px-4 py-2 border rounded-md dark:bg-gray-800 dark:text-white dark:border-gray-600"
                    required>
                @error('email')
                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Password</label>
                    <input type="password" name="password"
                        class="w-full mt-1 px-4 py-2 border rounded-md dark:bg-gray-800 dark:text-white dark:border-gray-600"
                        required>
                    @error('password')
                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Confirm Password</label>
                    <input type="password" name="password_confirmation"
                        class="w-full mt-1 px-4 py-2 border rounded-md dark:bg-gray-800 dark:text-white dark:border-gray-600"
                        required>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Select Role</label>
                <select name="role"
                    class="w-full mt-1 px-4 py-2 border rounded-md dark:bg-gray-800 dark:text-white dark:border-gray-600"
                    required>
                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="superadmin" {{ old('role') === 'superadmin' ? 'selected' : '' }}>Super Admin</option>
                </select>
                @error('role')
                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex justify-center gap-4 pt-4">
                <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded shadow transition duration-200">
                    Create Admin
                </button>
                <a href="{{ route('admin.users.index') }}"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded shadow transition duration-200">
                    🔙 Cancel
                </a>
            </div>
        </form>
    </div>

    <!-- Scroll to success -->
    @push('scripts')
        <script>
            window.onload = function() {
                const success = document.querySelector('.alert-success');
                if (success) {
                    success.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            }
        </script>
    @endpush
@endsection
