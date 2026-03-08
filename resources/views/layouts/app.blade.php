<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<meta name="description" content="Responsive HTML Admin Dashboard Template based on Bootstrap 5">
	<meta name="author" content="NobleUI">
	<meta name="keywords"
		content="nobleui, bootstrap, bootstrap 5, bootstrap5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<title>@yield('title', 'Dashboard') - Kelompok 5</title>

	<!-- Fonts -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

	<link rel="shortcut icon" href="{{ asset('assets/images/favicon2.png') }}" />

	<!-- Enhanced UI/UX Custom Styles -->
	<style>
		body,
		.main-wrapper .page-wrapper .page-content {
			font-family: 'Outfit', sans-serif !important;
			background-color: #f8fafc;
			color: #334155;
		}

		/* Cards Enhancement */
		.card {
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
			transition: all 0.2s ease-in-out;
		}

		.card .card-title {
			font-weight: 600;
			color: #1e293b;
			font-size: 1.15rem;
		}

		/* Buttons Enhancement */
		.btn {
			border-radius: 8px;
			font-weight: 500;
			padding: 0.5rem 1rem;
			transition: all 0.15s ease;
		}

		.btn-primary {
			background-color: #3b82f6;
			border-color: #3b82f6;
		}

		.btn-primary:hover {
			background-color: #2563eb;
			border-color: #2563eb;
			box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.4);
		}

		/* Input & Form Control Enhancement */
		.form-control,
		.form-select {
			border-radius: 8px;
			border: 1px solid #cbd5e1;
			padding: 0.5rem 0.75rem;
			font-size: 0.95rem;
			transition: all 0.2s ease;
		}

		.form-control:focus,
		.form-select:focus {
			border-color: #6366f1;
			box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
			background-color: #ffffff;
		}

		/* Base Colors Overrides */
		.text-primary {
			color: #3b82f6 !important;
		}

		.bg-primary {
			background-color: #3b82f6 !important;
		}

		/* Sub-Header / Navbar & Sidebar */
		.sidebar .sidebar-header {
			border-bottom: 1px solid #f1f5f9;
		}

		.sidebar .sidebar-body .nav .nav-item .nav-link {
			transition: all 0.2s ease;
		}

		.sidebar .sidebar-body .nav .nav-item.active .nav-link {
			color: #3b82f6;
			background-color: #eff6ff;
			border-radius: 8px;
			margin: 0 0.5rem;
			font-weight: 500;
		}

		.sidebar .sidebar-body .nav .nav-item .nav-link:hover {
			background-color: #f8fafc;
			border-radius: 8px;
			margin: 0 0.5rem;
		}

		.navbar {
			box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
			border-bottom: 1px solid #e2e8f0;
			background-color: #ffffff !important;
		}

		/* Tables Enhancement */
		.table-responsive {
			border-radius: 12px;
			border: 1px solid #cbd5e1;
			background: #ffffff;
			box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
			overflow-x: auto;
			/* Fix for horizontal scroll */
		}

		/* Custom Scrollbar for better UX */
		.table-responsive::-webkit-scrollbar {
			height: 8px;
		}

		.table-responsive::-webkit-scrollbar-thumb {
			background: #cbd5e1;
			border-radius: 4px;
		}

		.table-responsive::-webkit-scrollbar-thumb:hover {
			background: #94a3b8;
		}

		.table {
			margin-bottom: 0;
			width: 100%;
			white-space: nowrap;
			/* Preventions from cramping data */
		}

		.table thead th {
			font-weight: 600;
			text-transform: uppercase;
			font-size: 0.8rem;
			letter-spacing: 0.05em;
			color: #ffffff !important;
			background-color: #334155 !important;
			border: 1px solid #475569 !important;
			padding: 1rem;
		}

		.table tbody td {
			padding: 0.85rem 1rem;
			vertical-align: middle;
			border: 1px solid #cbd5e1;
			/* Clear borders */
			color: #1e293b;
			font-size: 0.95rem;
		}

		/* Distinct alternating colors */
		.table-striped>tbody>tr:nth-of-type(odd)>* {
			background-color: #f1f5f9 !important;
		}

		.table-striped>tbody>tr:nth-of-type(even)>* {
			background-color: #ffffff !important;
		}

		.table-striped>tbody>tr:hover>* {
			background-color: #eff6ff !important;
			/* Blue tint on hover */
		}

		/* Badges */
		.badge {
			font-weight: 500;
			padding: 0.35em 0.65em;
			border-radius: 6px;
		}

		/* Input Groups */
		.input-group-text {
			border-radius: 8px 0 0 8px;
			background-color: #f1f5f9;
			border: 1px solid #cbd5e1;
			border-right: none;
			color: #64748b;
		}

		.input-group .form-control {
			border-radius: 0 8px 8px 0;
		}

		/* Modals Focus */
		.modal-content {
			border-radius: 16px;
			border: none;
			box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
		}

		.modal-header {
			border-bottom: 1px solid #f1f5f9;
			padding: 1.25rem 1.5rem;
		}

		.modal-footer {
			border-top: 1px solid #f1f5f9;
			padding: 1.25rem 1.5rem;
		}
	</style>
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