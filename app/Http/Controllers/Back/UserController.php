<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('developer'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $users = User::select(sprintf('%s.*', (new User)->getTable()))->withCount('userlogs');

            return DataTables::of($users)
                ->only([
                    'id',
                    'name',
                    'email',
                    'is_developer',
                    'userlogs_count',
                ])
                ->addColumn('DT_RowId', function ($row) {return $row->id;})
                ->toJson();
        }

        return view('back.users.index');
    }

    public function create()
    {
        abort_if(Gate::denies('developer'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('back.users.create');
    }

    public function store(UserStoreRequest $request)
    {
        $user = User::create($request->all());
        Password::sendResetLink($request->only(['email']));

        $notification = [
            "type" => "success",
            "title" => 'Add ...',
            "message" => 'Item added.',
        ];

        return redirect()->route('back.users.index')->with('notification', $notification);
    }

    public function edit(User $user)
    {
        abort_if(Gate::denies('developer'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('back.users.edit', compact('user'));
    }

    public function update(UserUpdateRequest $request, User $user)
    {
        if ($user->id == 1) {
            $notification = [
                "type" => "info",
                "title" => 'Edit ...',
                "message" => 'This account is read-only.',
            ];
        } else {
            $user->update($request->except(['token']));

            $notification = [
                "type" => "success",
                "title" => 'Edit ...',
                "message" => 'Item updated.',
            ];

        }

        return redirect()->route('back.users.index')->with('notification', $notification);
    }

    public function massDestroy(Request $request)
    {
        User::where('id', '>', 1)->whereIn('id', request('ids'))->delete();

        return response()->noContent();
    }

    public function getUserlogs(Request $request)
    {
        abort_if(Gate::denies('developer'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $userlogs_by_date = DB::table('v_userlogs')->orderBy('created_at', 'desc')->where('user_id', $request->id)->limit(20)->get()->groupBy('date');

        return view('back.users.get-userlogs', compact('userlogs_by_date'))->render();
    }
}
