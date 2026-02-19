<!-- partial:partials/_navbar.html -->
<nav class="navbar">
  <a href="#" class="sidebar-toggler">
    <i data-feather="menu"></i>
  </a>
  <div class="navbar-content">
    <form class="search-form">
      <div class="input-group">
        <div class="input-group-text">
          <i data-feather="search" class="text-muted"></i>
        </div>
        <input type="text" class="form-control" id="navbarForm" placeholder="Search now...">
      </div>
    </form>
    <ul class="navbar-nav">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="appsDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i data-feather="grid"></i>
        </a>
        <div class="dropdown-menu p-0" aria-labelledby="appsDropdown">
          <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
            <p class="mb-0 fw-bold">Quick Access</p>
          </div>
          <div class="row g-0 p-1">
            <div class="col-6 text-center">
              <a href="{{ route('dashboard') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center wd-70 ht-70"><i data-feather="home" class="icon-lg mb-1"></i><p class="tx-12">Dashboard</p></a>
            </div>
            <div class="col-6 text-center">
              <a href="{{ route('item_master.index') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center wd-70 ht-70"><i data-feather="package" class="icon-lg mb-1"></i><p class="tx-12">Data Master</p></a>
            </div>
            <div class="col-6 text-center">
              <a href="{{ route('data_po.index') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center wd-70 ht-70"><i data-feather="file-text" class="icon-lg mb-1"></i><p class="tx-12">Data PO</p></a>
            </div>
            <div class="col-6 text-center">
              <a href="{{ route('item_minim.index') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center wd-70 ht-70"><i data-feather="alert-circle" class="icon-lg mb-1"></i><p class="tx-12">Item Minim</p></a>
            </div>
            <div class="col-6 text-center">
              <a href="{{ route('kedatangan_barang.index') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center wd-70 ht-70"><i data-feather="truck" class="icon-lg mb-1"></i><p class="tx-12">Kedatangan</p></a>
            </div>
            <div class="col-6 text-center">
              <a href="{{ route('history.index') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center wd-70 ht-70"><i data-feather="clock" class="icon-lg mb-1"></i><p class="tx-12">History</p></a>
            </div>
          </div>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          @if(auth()->check())
            <div class="wd-30 ht-30 rounded-circle d-flex align-items-center justify-content-center bg-primary text-white" style="font-size: 0.75rem; font-weight: bold;">
              {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
            </div>
          @else
            <img class="wd-30 ht-30 rounded-circle" src="{{ asset('assets/images/faces/face1.jpg') }}" alt="profile">
          @endif
        </a>
        <div class="dropdown-menu p-0" aria-labelledby="profileDropdown">
          <div class="d-flex flex-column align-items-center border-bottom px-5 py-3">
            <div class="mb-3">
              <div class="wd-80 ht-80 rounded-circle d-flex align-items-center justify-content-center bg-primary text-white" style="font-size: 2rem; font-weight: bold;">
                @if(auth()->check() && auth()->user()->name)
                  {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                @else
                  U
                @endif
              </div>
            </div>
            <div class="text-center">
              <p class="tx-16 fw-bolder mb-1">
                @if(auth()->check())
                  {{ auth()->user()->name ?? 'User' }}
                @else
                  Guest
                @endif
              </p>
              <p class="tx-12 text-muted mb-1">
                @if(auth()->check())
                  {{ auth()->user()->email ?? '-' }}
                @else
                  Not logged in
                @endif
              </p>
              @if(auth()->check() && auth()->user()->username)
                <p class="tx-11 text-muted mb-0">
                  @if(auth()->user()->username === 'guest')
                    <span class="badge bg-secondary">Guest Mode (View Only)</span>
                  @else
                    <span class="badge bg-info">{{ ucfirst(auth()->user()->username) }}</span>
                  @endif
                </p>
              @endif
            </div>
          </div>
          <ul class="list-unstyled p-1 px-2">
            @auth
            <li class="dropdown-item py-2">
              <a href="{{ route('dashboard') }}" class="text-body ms-0 d-flex align-items-center">
                <i class="me-2 icon-md" data-feather="home"></i>
                <span>Dashboard</span>
              </a>
            </li>
            <li class="dropdown-item py-2">
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-link text-body p-0 ms-0 d-flex align-items-center w-100">
                  <i class="me-2 icon-md" data-feather="log-out"></i>
                  <span>Logout</span>
                </button>
              </form>
            </li>
            @else
            <li class="dropdown-item py-2">
              <a href="{{ route('login') }}" class="text-body ms-0 d-flex align-items-center">
                <i class="me-2 icon-md" data-feather="log-in"></i>
                <span>Login</span>
              </a>
            </li>
            @endauth
          </ul>
        </div>
      </li>
    </ul>
  </div>
</nav>
