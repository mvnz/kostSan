<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->orderBy('name')->get();

        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        return view('roles.form', [
            'role' => new Role(),
            'isEdit' => false,
            'menuGroups' => Role::menuGroups(),
            'crudActions' => Role::crudActions(),
            'crudActionLabels' => Role::crudActionLabels(),
            'menuActionOptions' => Role::menuActionOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'menu_permissions' => ['nullable', 'array'],
        ]);

        $normalizedPermissions = Role::normalizeMenuPermissions($request->input('menu_permissions', []));
        $validated['menu_permissions'] = $normalizedPermissions;
        $validated['menu_access'] = array_keys($normalizedPermissions);

        Role::create($validated);

        return redirect()->route('roles.index')->with('success', 'Role berhasil ditambahkan.');
    }

    public function show(Role $role)
    {
        return redirect()->route('roles.edit', $role);
    }

    public function edit(Role $role)
    {
        return view('roles.form', [
            'role' => $role,
            'isEdit' => true,
            'menuGroups' => Role::menuGroups(),
            'crudActions' => Role::crudActions(),
            'crudActionLabels' => Role::crudActionLabels(),
            'menuActionOptions' => Role::menuActionOptions(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name,' . $role->id],
            'description' => ['nullable', 'string', 'max:255'],
            'menu_permissions' => ['nullable', 'array'],
        ]);

        $normalizedPermissions = Role::normalizeMenuPermissions($request->input('menu_permissions', []));
        $validated['menu_permissions'] = $normalizedPermissions;
        $validated['menu_access'] = array_keys($normalizedPermissions);

        $role->update($validated);

        return redirect()->route('roles.index')->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        if ($role->users()->exists()) {
            return back()->with('error', 'Role tidak dapat dihapus karena masih dipakai user.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role berhasil dihapus.');
    }
}
