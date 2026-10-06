<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminCommand extends Command
{
    protected $signature = 'kost:admin-create {email} {--name=Administrator}';

    protected $description = 'Buat admin baru dengan password tersembunyi tanpa kredensial bawaan';

    public function handle(): int
    {
        $data = ['email' => $this->argument('email'), 'name' => $this->option('name')];
        $validator = Validator::make($data, ['email' => ['required', 'email', 'unique:users,email'], 'name' => ['required', 'string', 'max:255']]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        if (! $this->input->isInteractive()) {
            $this->error('Jalankan perintah secara interaktif untuk memasukkan password tersembunyi.');

            return self::FAILURE;
        }
        $data['password'] = $this->secret('Password (minimal 12 karakter, huruf dan angka)');
        $data['password_confirmation'] = $this->secret('Ulangi password');
        $validator = Validator::make($data, ['password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()]]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        User::create(['email' => $data['email'], 'name' => $data['name'], 'password' => $data['password'], 'role_id' => null]);
        $this->info('Admin berhasil dibuat.');

        return self::SUCCESS;
    }
}
