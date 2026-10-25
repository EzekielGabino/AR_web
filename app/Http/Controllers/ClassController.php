<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ClassController extends Controller
{
    /**
     * Display the form for creating a new class section.
     */
    public function create()
    {
        return redirect()->to(route('dashboard') . '?tab=classes');
    }

    /**
     * Display a specific class section roster and student details.
     * Passes the Firebase document $id to the Blade view.
     */
    public function show(string $id)
    {
        return view('classes.show', [
            'classId' => $id,
        ]);
    }
}
