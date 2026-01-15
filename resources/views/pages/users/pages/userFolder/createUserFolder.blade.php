{{-- resources/views/pages/users/admin/pages/users/manageUsers.blade.php --}}
@extends('pages.users.layout.structure')

@section('title', 'User Folder')
@section('header', 'Manage User Folder')

@section('content')
  @include('modules.userFolder.createUserFolder')
@endsection

@section('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    if (!sessionStorage.getItem('token') && !localStorage.getItem('token')) {
      window.location.href = '/';
    }
  });
</script>
@endsection
