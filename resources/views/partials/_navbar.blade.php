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
            <a href="javascript:;" class="text-muted">Edit</a>
          </div>
          <div class="row g-0 p-1">
            <div class="col-6 text-center">
              <a href="{{ route('item_outstanding.index') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center wd-70 ht-70"><i data-feather="package" class="icon-lg mb-1"></i><p class="tx-12">Item Outstanding</p></a>
            </div>
          </div>
          <div class="px-3 py-2 d-flex align-items-center justify-content-center border-top">
            <a href="javascript:;">View all</a>
          </div>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="messageDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i data-feather="mail"></i>
        </a>
        <div class="dropdown-menu p-0" aria-labelledby="messageDropdown">
          <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
            <p>9 New</p>
            <a href="javascript:;" class="text-muted">Clear all</a>
          </div>
          <div class="p-1">
            <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
              <div class="me-3">
                <img class="wd-40 ht-40 rounded-circle" src="{{ asset('assets/images/faces/face1.jpg') }}" alt="userr">
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-1">Amiah Burton</h6>
                <p class="text-muted tx-13 mb-0">Hey! are you busy?</p>
              </div>
            </a>
          </div>
          <div class="px-3 py-2 d-flex align-items-center justify-content-center border-top">
            <a href="javascript:;">View all</a>
          </div>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i data-feather="bell"></i>
          <div class="indicator">
            <div class="circle"></div>
          </div>
        </a>
        <div class="dropdown-menu p-0" aria-labelledby="notificationDropdown">
          <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
            <p>6 New Notifications</p>
            <a href="javascript:;" class="text-muted">Clear all</a>
          </div>
          <div class="p-1">
            <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
              <div class="wd-30 ht-30 d-flex align-items-center justify-content-center bg-primary rounded-circle me-3">
                <i class="icon-sm text-white" data-feather="gift"></i>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-1">New Order Recieved</h6>
                <p class="text-muted tx-13 mb-0">30 min ago</p>
              </div>
            </a>
          </div>
          <div class="px-3 py-2 d-flex align-items-center justify-content-center border-top">
            <a href="javascript:;">View all</a>
          </div>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <img class="wd-30 ht-30 rounded-circle" src="{{ asset('assets/images/faces/face1.jpg') }}" alt="profile">
        </a>
        <div class="dropdown-menu p-0" aria-labelledby="profileDropdown">
          <div class="d-flex flex-column align-items-center border-bottom px-5 py-3">
            <div class="mb-3">
              <img class="wd-80 ht-80 rounded-circle" src="{{ asset('assets/images/avatar.jpg') }}" alt="">
            </div>
            <div class="text-center">
              <p class="tx-16 fw-bolder">Admin User</p>
              <p class="tx-12 text-muted">admin@kelompok5.com</p>
            </div>
          </div>
          <ul class="list-unstyled p-1 px-2">
            <li class="dropdown-item py-2">
              <a href="pages/general/profile.html" class="text-body ms-0">
                <i class="me-2 icon-md" data-feather="user"></i>
                <span>Profile</span>
              </a>
            </li>
            @auth
            <li class="dropdown-item py-2">
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-link text-body p-0 ms-0 d-flex align-items-center">
                  <i class="me-2 icon-md" data-feather="log-out"></i>
                  <span>Logout</span>
                </button>
              </form>
            </li>
            @endauth
          </ul>
        </div>
      </li>
    </ul>
  </div>
</nav>
