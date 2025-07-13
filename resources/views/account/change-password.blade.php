@extends('layouts.app')

@section('content')
    <div class="max-w-xl mx-auto px-4 py-10">
        <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl shadow-lg">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">🔒 Change Password</h2>

            @if (session('success'))
                <div class="mb-4 p-4 rounded-lg bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('account.password.update') }}" class="space-y-6">
                @csrf

                {{-- Current Password --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Current Password</label>
                    <input type="password" name="current_password"
                        class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring focus:ring-green-400"
                        required>
                    @error('current_password')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- New Password --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New Password</label>
                    <input type="password" name="new_password"
                        class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring focus:ring-green-400"
                        required>
                    @error('new_password')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirm New Password --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm New
                        Password</label>
                    <input type="password" name="new_password_confirmation"
                        class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring focus:ring-green-400"
                        required>
                </div>

                <button type="submit"
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow transition">
                    Update Password
                </button>
            </form>
        </div>
    </div>
@endsection
