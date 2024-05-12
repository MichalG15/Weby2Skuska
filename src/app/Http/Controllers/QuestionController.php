<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use App\Models\OptionsHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function getAllByUserId(string $id)
    {
        $questions = Question::query()
            ->where('owner_id', $id)
            ->get();
        return $questions;
    }

    public function index()
    {
        $questions = Question::query()
            ->where('owner_id', request()->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return view('question.index', ['questions' => $questions]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $subjects = Subject::all();
        $users = User::all();
        return view('question.create', ["users" => $users, 'subjects' => $subjects]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $question = new Question;
        $validatedData = $request->validate([
            'question' => 'required|string|max:1023',
            'question_type' => 'required|string|in:multiple_choice,open_ended',
            'word_cloud' => 'required|boolean|in:0,1',
        ]);
        $question->question = $validatedData['question'];
        $question->question_type = $validatedData['question_type'];
        $question->word_cloud = $validatedData['word_cloud'];

        $dropdownValue = $request->input('ownerInput');
        if (! empty($dropdownValue) && $dropdownValue != '0') {
            $user = User::where('name', $dropdownValue)->first();
            if ($user) {
                $question->owner_id = $user->id;
            } else {
                $question->owner_id = Auth::id();
            }
        } else
            $question->owner_id = Auth::id();

        if ($request->input('subject') != 0) {
            $question->subject()->associate($request->input('subject'));
        } else {
            $subject = Subject::firstOrCreate(['subject' => $request->input('other_subject')]);
            $question->subject()->associate($subject);
        }

        $question->save();

        if ($validatedData['question_type'] === 'multiple_choice') {
            $i = 1;
            foreach ($request->all() as $key => $value) {
                if (str_starts_with($key, 'option')) {
                    $correct = $request->input('isCorrect' . $i);
                    $correct = isset($correct) ? true : false;
                    $option = $question->options()->create(['option_text' => $value, 'correct' => $correct]);
                    $optionsHistory = OptionsHistory::create(['year' => date('Y'), 'times_answered' => 0, 'option_id' => $option->id]);
                    //$option->optionsHistory()->associate($optionsHistory);
                    $option->save();
                    $i++;
                }
            }
        }
        return redirect()->route('question.index', $question);
    }

    /**
     * Display the specified resource.
     */
    public function show(Question $question)
    {
        if (! $question->active) {
            return redirect()->back();
        }
        //options budu v $question->options
        $question->load('options');
        return view('question.show', ['question' => $question]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Question $question)
    {
        return view('question.edit', ['question' => $question]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Question $question)
    {
        $validatedData = $request->validate([
            'question' => 'sometimes|required|string|max:1023',
            'question_type' => 'sometimes|required|string|in:multiple_choice,open_ended',
            'active' => 'sometimes|boolean',
        ]);

        if (! isset($validatedData['active'])) {
            $validatedData['active'] = 0;
        }

        $question->update($validatedData);

        return redirect()->route('question.index', $question);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question)
    {
        $question->delete();

        return to_route('question.index')->with('message', ('Question was destroyed'));
    }

    public function destroyAdmin(string $question_id)
    {
        $question = Question::find($question_id);
        $question->delete();
        return back();
    }
}
