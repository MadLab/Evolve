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
                <li><a href="/" class="text-gray-600 hover:text-green-600">Return to Site</a></li>
            </ul>
        </nav>
    </div>
</header>

<!-- Main Content -->
<main class="container mx-auto px-4 py-8">
    <div class="bg-white shadow rounded-lg p-6">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-base font-semibold text-gray-900">{{$experiment->name}}</h1>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <form method="post">
                        <input type="hidden" name="action" value="{{$experiment->is_active?'disable':'enable'}}">
                        <button type="submit" class="block rounded-md bg-green-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">{{$experiment->is_active?'Pause':'Start'}} Experiment</button>
                    </form>
                </div>
            </div>
            <div class="mt-8 flow-root">
                <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead>
                            <tr>
                                <!-- Static Columns -->
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6 lg:pl-8">Variant</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Views</th>

                                <!-- Dynamic Conversion Columns -->
                                @foreach($experiment->conversionNames() as $conversionName)
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ ucfirst($conversionName) }}</th>
                                @endforeach
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach($experiment->variantLogs as $variant)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6 lg:pl-8 overflow-scroll">
                                        <textarea class="w-full h-full text-xs" rows="5">{{$variant->content}}</textarea>
                                        <form method="post" action="{{route('evolve.experiments.update', $experiment)}}" class="mt-2">
                                            <input type="hidden" name="action" value="deleteVariant">
                                            <input type="hidden" name="variant_id" value="{{$variant->id}}">
                                            <button type="submit" class="inline-block mt-1 text-sm text-emerald-600 hover:underline">Delete Variant</button>
                                        </form>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{$variant->view?->views}}</td>
                                    <!-- Dynamic Conversion Data -->
                                    @foreach($experiment->conversionNames() as $conversionName)
                                        <td class="whitespace-nowrap px-3 py-4 {{$experiment->maxRate($conversionName) == $variant->id?'bg-green-50':''}}">
                                            <ul class="space-y-2">
                                                <li class="font-medium text-lg flex flex-col">{{ $variant->view->conversions[$conversionName] ?? 0 }} <span class="text-xs text-gray-500">Conversions</span></li>
                                                <li class="font-medium text-lg flex flex-col">{{ $variant->view?->conversionRate($conversionName) }}% <span class="text-xs text-gray-500">Rate</span></li>
                                                <li class="text-sm flex flex-col">{{ $variant->view?->conversionRange($conversionName) }} <span class="text-xs text-gray-500">Range</span></li>
                                            </ul>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @if($experiment->variantLogs()->onlyTrashed()->count())
            <h2 class="text-2xl font-bold text-emerald-900 mt-10">Deleted Variants</h2>
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="mt-8 flow-root">
                    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8 bg-white shadow rounded-lg">
                        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead>
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Name</th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                        <span class="sr-only">Edit</span>
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                @foreach ($experiment->variantLogs()->onlyTrashed()->get() as $variant)
                                    <tr>
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">{{$variant->content}}</td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">

                                            <form method="post" action="{{route('evolve.experiments.update', $experiment)}}" class="mt-2">
                                                <input type="hidden" name="action" value="restoreVariant">
                                                <input type="hidden" name="variant_id" value="{{$variant->id}}">
                                                <button type="submit" class="inline-block mt-1 text-sm text-emerald-600 hover:underline">Restore</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</main>
</body>
</html>
