<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserProfileController extends Controller
{
    public function __invoke(Request $request)
    {
        $title = 'Akun Saya';

        $vue = "<user-profile-page :title='".json_encode($title)."' />";

        return response()->view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'user_data') {
            $data = auth()->user();

            return response()->json(['models' => $data]);
        }
    }

    public function write(Request $request)
    {
        if ($request->req === 'change_password') {
            $request->validate(
                [
                    'old_password' => 'required',
                    'new_password' => 'required|min:6',
                    'confirm_password' => 'required|same:new_password',
                ],
                [
                    'old_password.required' => 'Password lama wajib diisi.',
                    'new_password.required' => 'Password baru wajib diisi.',
                    'new_password.min' => 'Password baru minimal 6 karakter.',
                    'confirm_password.required' => 'Konfirmasi password wajib diisi.',
                    'confirm_password.same' => 'Konfirmasi password tidak sama dengan password baru.',
                ],
            );

            $user = auth()->user();

            // Cek apakah old_password benar
            if (! Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Password lama salah. Harap cek kembali!!!',
                ], 422);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            return response()->json([
                'message' => 'Password berhasil diubah.',
            ]);
        } elseif ($request->req === 'change_photo') {
            $request->validate(
                [
                    'photo' => 'required|image|mimes:jpg,jpeg,png,gif,bmp,webp|max:2048',
                ],
                [
                    'photo.required' => 'Foto wajib diupload.',
                    'photo.image' => 'File harus berupa gambar.',
                    'photo.mimes' => 'Format gambar harus jpg, jpeg, png, gif, bmp, atau webp.',
                    'photo.max' => 'Ukuran gambar maksimal 2MB.',
                ]
            );

            $user = auth()->user();

            // MENANGANI FILE
            if ($request->hasFile('photo')) {
                $path = "users/{$user->id}/profile_photo";
                $user->photo = $user->storeFile($request->file('photo'), $path, 'public');
                $user->save();
            }

            return response()->json([
                'message' => 'Foto Profil berhasil diubah.',
            ]);
        }
    }
}
