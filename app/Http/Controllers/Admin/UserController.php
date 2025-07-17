<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cabang;
use App\Models\Jabatan;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index() {
        $users = User::latest()->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    public function create() {
        $jabatan = Jabatan::all(); // Ambil semua jabatan
        $cabang = Cabang::all(); // ambil semua data cabang
        return view('admin.users.create', compact('jabatan', 'cabang'));
    }

    public function store(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'role' => 'required|in:CEO,CBO,Manager,SBC,BC,Trainee,Administrator,Superadmin',
            'password' => 'required|confirmed|min:6',
            'jabatan_id' => 'required|exists:jabatan,id',
            'cabang_id' => 'required|exists:cabang,id',
            'employee_id' => 'required',
            'atasan_id' => 'required|exists:users,id',
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'role.required' => 'Role harus dipilih.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'cabang_id' => $request->cabang_id,
            'jabatan_id' => $request->jabatan_id,
            'atasan_id' => auth()->user()->id, // otomatis isi
            'password' => bcrypt($request->password),
            'employee_id' => 'EMP' . rand(1000, 9999),
            
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan!');

    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
        ]);

        $user = User::findOrFail($id);
        $user->update($request->only(['name', 'email']));

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diupdate!');

    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus.');
    }



    // Tambahkan edit, update, destroy sesuai kebutuhan
}
