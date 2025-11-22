<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
  <meta name="description" content="Responsive HTML Admin Dashboard Template based on Bootstrap 5">
	<meta name="author" content="NobleUI">
	<meta name="keywords" content="nobleui, bootstrap, bootstrap 5, bootstrap5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">

	<title>@yield('title', 'Dashboard') - Kelompok 5</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
  <!-- End fonts -->

	<!-- core:css -->
	<link rel="stylesheet" href="{{ asset('assets/vendors/core/core.css') }}">
	<!-- endinject -->

	<!-- Plugin css for this page -->
  <link rel="stylesheet" href="{{ asset('assets/vendors/flatpickr/flatpickr.min.css') }}">
	<!-- End plugin css for this page -->

	<!-- inject:css -->
	<link rel="stylesheet" href="{{ asset('assets/fonts/feather-font/css/iconfont.css') }}">
	<link rel="stylesheet" href="{{ asset('assets/vendors/flag-icon-css/css/flag-icon.min.css') }}">
	<!-- endinject -->

  <!-- Layout styles -->  
	<link id="theme-stylesheet" rel="stylesheet" href="{{ asset('assets/css/demo1/style.css') }}">
  <!-- End layout styles -->

  <!-- Theme Switcher styles -->
  <link rel="stylesheet" href="{{ asset('assets/css/theme-switcher.css') }}">
  <!-- End Theme Switcher styles -->

  <link rel="shortcut icon" href="{{ asset('assets/images/favicon.png') }}" />
</head>
<body>
	<div class="main-wrapper">

		<!-- partial:partials/_sidebar.html -->
		@include('partials._sidebar')
		<!-- partial -->

		<!-- partial:partials/_settings-sidebar.html -->
		@include('partials._settings-sidebar')
		<!-- partial -->
	
		<div class="page-wrapper">
					
			<!-- partial:partials/_navbar.html -->
			@include('partials._navbar')
			<!-- partial -->

			<div class="page-content">
				@yield('content')
			</div>

			<!-- partial:partials/_footer.html -->
			@include('partials._footer')
			<!-- partial -->
		
		</div>
	</div>

	<!-- core:js -->
	<script src="{{ asset('assets/vendors/core/core.js') }}"></script>
	<!-- endinject -->

	<!-- Plugin js for this page -->
  <script src="{{ asset('assets/vendors/flatpickr/flatpickr.min.js') }}"></script>
  <script src="{{ asset('assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
	<!-- End plugin js for this page -->

	<!-- inject:js -->
	<script src="{{ asset('assets/vendors/feather-icons/feather.min.js') }}"></script>
	<script src="{{ asset('assets/js/template.js') }}"></script>
	<!-- endinject -->

	<!-- Custom js for this page -->
  <script src="{{ asset('assets/js/dashboard-light.js') }}"></script>
	<!-- End custom js for this page -->

	<!-- Theme Switcher -->
	<script src="{{ asset('assets/js/theme-switcher.js') }}"></script>
	<!-- End Theme Switcher -->

	<!-- Settings Test (for debugging) -->
	<script src="{{ asset('assets/js/settings-test.js') }}"></script>
	<!-- End Settings Test -->

	<!-- Sidebar Fix -->
	<script src="{{ asset('assets/js/sidebar-fix.js') }}"></script>
	<!-- End Sidebar Fix -->

</body>
</html>
