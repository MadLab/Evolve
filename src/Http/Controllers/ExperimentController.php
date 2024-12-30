<?php

namespace MadLab\Evolve\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use MadLab\Evolve\Models\Experiment;
use function PHPUnit\Framework\isNull;

class ExperimentController extends Controller
{
    public function index(Request $request)
    {
        $cookie = $request->cookie('evolve');
        if(isNull($cookie)){
            Cookie::queue('evolve', 'experiment', 10);
        }


        $experiments = Experiment::all();

        return view('evolve::experiments.index', compact('experiments'));
    }

    public function show(Experiment $experiment)
    {
        return view('evolve::experiments.show', compact('experiment'));
    }

    public function store()
    {
        // Let's assume we need to be authenticated
        // to create a new post
        if (! auth()->check()) {
            abort (403, 'Only authenticated users can create new posts.');
        }

        request()->validate([
            'title' => 'required',
            'body'  => 'required',
        ]);

        // Assume the authenticated user is the post's author
        $author = auth()->user();

        $post = $author->posts()->create([
            'title'     => request('title'),
            'body'      => request('body'),
        ]);

        return redirect(route('posts.show', $post));
    }
}
