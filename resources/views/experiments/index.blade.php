<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <!-- Link to Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Add any additional custom CSS here -->
</head>
<body class="bg-gray-100 text-emerald-950">
<!-- Navbar -->
<header class="bg-white shadow shadow-emerald-900">
    <div class="container mx-auto px-4 py-4 flex justify-between items-center">
        <a href="{{route('evolve.experiments.index')}}" class="text-xl font-bold text-emerald-700">Evolve</a>
        <nav>
            <ul class="flex space-x-4">
                <li><a href="/" class="text-gray-600 hover:text-blue-600">Return to Site</a></li>
            </ul>
        </nav>
    </div>
</header>

<!-- Main Content -->
<main class="container mx-auto px-4 py-8">
    <h2 class="text-3xl font-bold text-emerald-900">Active Experiments</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-5">
        @forelse ($copyExperiments as $experiment)
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-bold mb-4">{{$experiment->name}}</h2>
                <p class="text-gray-600">{{$experiment->variantLogs->count()}} Variants</p>
                <a href="{{route('evolve.experiments.show', $experiment)}}" class="inline-block mt-4 text-emerald-600 hover:underline">View Experiment →</a>
            </div>
        @empty
            <p> 'No Experiments yet' </p>
        @endforelse

    </div>
</main>

</body>
</html>
