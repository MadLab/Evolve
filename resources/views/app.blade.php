<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-emerald-950">
<div id="app" data-page="{{ json_encode($page) }}"></div>
<script src="{{ route('evolve.assets.js') }}"></script>
</body>
</html>