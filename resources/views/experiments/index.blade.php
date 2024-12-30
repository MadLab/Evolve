<h1>Showing all Experiments</h1>

@forelse ($experiments as $experiment)
    <li><a href="{{route('evolve.experiments.show', $experiment)}}">{{$experiment->name}}</a></li>
@empty
    <p> 'No Experiemnts yet' </p>
@endforelse