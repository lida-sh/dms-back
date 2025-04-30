<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Role;
use App\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
class RoleController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $roles = Role::all();
        return  $this->successResponse($roles, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            "role_name" => "required|string",
            "role_display_name" => "required|string",
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }
        DB::beginTransaction();
        $role = Role::create([
            "name"=>$request->role_name,
            "display_name"=>$request->role_display_name,
            "guard_name"=>"api"
        ]);
        // $permissions = Permission::whereIn('name', $request->permissions)
        //                  ->where('guard_name', 'api')
        //                  ->get();
        // if (!empty($request->permissions)) {
            // foreach ($request->permissions as $permissionName) {
            //     $permission = Permission::where('name', $permissionName)
            //         ->where('guard_name', 'api')
            //         ->first();
        
            //     if ($permission) {
            //         $role->givePermissionTo($permission);
            //     } else {
            //         throw new \Exception("خطا.");
            //     }
            // }
        // }
        Permission::where('name', 'create-user')->update(['guard_name' => 'api']);
        $role->givePermissionTo($request->permissions);
        DB::commit();
        return $this->successResponse($role, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
