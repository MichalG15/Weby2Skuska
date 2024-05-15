@if (auth()->user()->isAdmin())
@section("title", __('messages.user_management'))
    <x-app-layout>
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('messages.user_management') }}
            </h2>
        </x-slot>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 m-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 ">
                    @include('admin.adminUserControl', ['users' => $users])
                </div>
            </div>
        </div>
    </x-app-layout>
@endif
