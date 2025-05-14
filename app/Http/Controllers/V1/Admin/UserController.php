<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\UserResource;
use App\User;
use Illuminate\Http\Request;
use App\Role;
use App\Permission;
use App\Http\Resources\RoleResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
class UserController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User:: with(["permissions", "roles"])->paginate(10);
        return $this->successResponse([
            
            "users" => UserResource::collection($users),
            "links" => UserResource::collection($users)->response()->getData()->links,
            "meta" => UserResource::collection($users)->response()->getData()->meta,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       $validator = Validator::make($request->all(), [
            "first_name" => "required|string",
            "last_name" => "required|string",
            "national_code" => "nullable|string",
            "email"=>"required|email",
            "password"=> "required|string",
            "role"=>"required|string",
            "permissions"=>"nullable",
            "permissions.*"=>"nullable|string"
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }
        
        DB::beginTransaction();
        $user = User::create([
            "first_name" => $request->first_name,
            "last_name" => $request->last_name,
            "national_code" => $request->national_code,
            "email"=>$request->email,
            "password"=> $request->password,
        ]);
        
        $user->assignRole($request->role);
        $user->givePermissionTo($request->permissions);
        DB::commit();
        return $this->successResponse($user, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return $this->successResponse((new UserResource($user->load(["roles", "permissions"]))), 200);
        
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
        dd($request->all());
        $validator = Validator::make($request->all(), [
            "first_name" => "required|string",
            "last_name" => "required|string",
            "national_code" => "nullable|string",
            "email"=>"required|email",
            "password"=> "required|string",
            "role"=>"required|string",
            "permissions"=>"nullable",
            "permissions.*"=>"nullable|string"
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }
        
        DB::beginTransaction();
        $user = User::findOrFail($id);
        $user->update([
            "first_name" => $request->first_name,
            "last_name" => $request->last_name,
            "national_code" => $request->national_code,
            "email"=>$request->email,
            "password"=> $request->password,
        ]);
        
        $user->syncRoles($request->role);
        $user->syncPermissions($request->permissions);
        DB::commit();
        return $this->successResponse($user, 200);
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
    public function getRolesAndPermissions()
    {
        $roles = Role::all();
        $permissions = Permission::all();
        return $this->successResponse(
            [
                "roles" => RoleResource::collection($roles),
                "permissions" => PermissionResource::collection($permissions)
            ],

            200
        );
    }

}
