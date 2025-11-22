<!-- partial:partials/_settings-sidebar.html -->
<!-- Settings Toggle Button (Outside sidebar) -->
<a href="#" class="settings-sidebar-toggler" onclick="document.body.classList.toggle('settings-open'); return false;">
  <i data-feather="settings"></i>
</a>

<nav class="settings-sidebar">
  <div class="sidebar-body">
    <h6 class="text-muted mb-2">Sidebar:</h6>
    <div class="mb-3 pb-3 border-bottom">
      <div class="form-check form-check-inline">
        <input type="radio" class="form-check-input" name="sidebarThemeSettings" id="sidebarLight" value="sidebar-light" checked 
               onclick="document.body.classList.remove('sidebar-dark'); document.body.classList.add('sidebar-light'); localStorage.setItem('sidebarTheme', 'sidebar-light'); console.log('Sidebar theme changed to light');">
        <label class="form-check-label" for="sidebarLight">
          Light
        </label>
      </div>
      <div class="form-check form-check-inline">
        <input type="radio" class="form-check-input" name="sidebarThemeSettings" id="sidebarDark" value="sidebar-dark"
               onclick="document.body.classList.remove('sidebar-light'); document.body.classList.add('sidebar-dark'); localStorage.setItem('sidebarTheme', 'sidebar-dark'); console.log('Sidebar theme changed to dark');">
        <label class="form-check-label" for="sidebarDark">
          Dark
        </label>
      </div>
    </div>
    <div class="theme-wrapper">
      <h6 class="text-muted mb-2">Light Theme:</h6>
      <a class="theme-item active" href="#" data-theme="light">
        <img src="{{ asset('assets/images/screenshots/light.jpg') }}" alt="light theme">
      </a>
    </div>
    <div class="theme-wrapper">
      <h6 class="text-muted mb-2">Dark Theme:</h6>
      <a class="theme-item" href="#" data-theme="dark">
        <img src="{{ asset('assets/images/screenshots/dark.jpg') }}" alt="dark theme">
      </a>
    </div>
  </div>
</nav>
