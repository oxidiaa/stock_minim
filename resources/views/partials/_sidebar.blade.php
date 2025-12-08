<!-- partial:partials/_sidebar.html -->
<nav class="sidebar">
  <div class="sidebar-header">
    <a href="{{ route('dashboard') }}" class="sidebar-brand">
      <img id="sidebar-logo" src="{{ asset('assets/images/logo.png') }}" alt="Logo"
           style="width: 165px; height: 100%; object-fit: contain; border-radius: 5px; margin-right: 8px;">
    </a>
    <div class="sidebar-toggler not-active">
      <span></span>
      <span></span>
      <span></span>
    </div>
  </div>

  <div class="sidebar-body">
    <ul class="nav">

      {{-- MAIN --}}
      <li class="nav-item nav-category">Main</li>
      <li class="nav-item">
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') || request()->routeIs('/') ? 'active' : '' }}">
          <i class="link-icon" data-feather="home"></i>
          <span class="link-title">Dashboard</span>
        </a>
      </li>

      {{-- WAREHOUSE & PURCHASING --}}
      <li class="nav-item nav-category">Warehouse & Purchasing</li>
      <li class="nav-item">
        <a href="{{ route('item_master.index') }}" class="nav-link {{ request()->routeIs('item_master.index') ? 'active' : '' }}">
          <i class="link-icon" data-feather="package"></i>
          <span class="link-title">Data Master</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="{{ route('data_po.index') }}" class="nav-link {{ request()->routeIs('data_po*') ? 'active' : '' }}">
          <i class="link-icon" data-feather="file-text"></i>
          <span class="link-title">Data PO</span>
        </a>
      </li>
      @if(auth()->check() && auth()->user()->username === 'master')
      <li class="nav-item">
        <a href="{{ route('item_outstanding.index') }}" class="nav-link {{ request()->routeIs('item_outstanding.index') ? 'active' : '' }}">
          <i class="link-icon" data-feather="package"></i>
          <span class="link-title">Item Outstanding</span>
        </a>
      </li>
      @endif
      <li class="nav-item">
        <a href="{{ route('item_minim.index') }}" class="nav-link {{ request()->routeIs('item_minim*') ? 'active' : '' }}">
          <i class="link-icon" data-feather="alert-circle"></i>
          <span class="link-title">Item Minim</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="{{ route('kedatangan_barang.index') }}" class="nav-link {{ request()->routeIs('kedatangan_barang*') ? 'active' : '' }}">
          <i class="link-icon" data-feather="truck"></i>
          <span class="link-title">Kedatangan Barang</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="{{ route('history.index') }}" class="nav-link {{ request()->routeIs('history*') ? 'active' : '' }}">
          <i class="link-icon" data-feather="clock"></i>
          <span class="link-title">History</span>
        </a>
      </li>

    </ul>
  </div>
</nav>