<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        // if (is_null($this->user) || !$this->user->can('admin.user.view')) {
        //         abort(403, 'Sorry !! You are Unauthorized.');
        // }
        $data['title'] = 'User List';
        $data['users'] = User::where('user_type', 0)->orderBy('id', 'desc')->get();

        return view('admin.user.index', $data);
    }

    public function update(Request $request)
    {
        try {

            $user = User::findOrFail($request->id);

            $user->name   = $request->name;
            $user->email  = $request->email;
            $user->phone  = $request->phone;
            $user->status = $request->status;

            $user->save();

            return back()->with('success', 'User updated successfully');

        } catch (\Exception $e) {

            \Log::error('User update failed: ' . $e->getMessage());

            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    public function transactions($id)
    {

        $data['title'] = 'User Transactions History';
        $user = User::findOrFail($id);

        // assuming relation exists
        $transactions = Transaction::where('customer_id', $id)
                            ->latest()
                            ->get();

        $data['user'] = $user;
        $data['transactions'] = $transactions;


        return view('admin.user.transactions', $data);
    }

    public function activate($id)
    {
        $user = User::findOrFail($id);
        $user->status = 1;
        $user->save();

        return back()->with('success', 'User activated');
    }

    public function deactivate($id)
    {
        $user = User::findOrFail($id);
        $user->status = 0;
        $user->save();

        return back()->with('success', 'User deactivated');
    }
}
