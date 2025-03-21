<?php
// Hemos creado dentro de la carpeta App, la carpeta Services, para añadir los servicios de cada modelo
namespace App\Services;

use App\Models\User;
use App\Models\Favorites;
use App\Models\Post;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use PhpParser\Node\Stmt\TryCatch;
use Spatie\Permission\Contracts\Role;
use Illuminate\Http\Request;

class UserService
{
    public function getAllUser() // Esta función recoge todos los datos de la tabla User
    { 
        return User::all();
    }

    public function getUserById($id)  // Devuelve el post con el ID especificado, o lanza un error 404 si no existe
    {   
        return User::findOrFail($id);
    }

    public function createUser($data)// Devuelve el usuario recién creado, la función create recibe un array y va rellenando la BBDD. 
    { 
        try {
            if (User::where('email_user', $data->email_user)->exists()) {
                return response()->json(['message' => 'El email ya esta registrado', 'mensaje' => 'malmalrequetemal'], 409); // 409, codigo de error de conflicto de datos
            }
            if (User::where('name_user', $data->name_user)->exists()) {
                return response()->json(['message' => 'El nombre de ususario ya esta registrado'], 409); // 409, codigo de error de conflicto de datos
            }
            $user = User::create([
                'name_user' => $data->name_user,
                'email_user' => $data->email_user,
                'password_user' => $data->password_user,
                // 'name_lastName' => $data->name_lastName ?? 'abc',
                'bio' => $data->bio ?? 'abc',
            ]);
            if ($user) {
                try {
                    $user->assignRole('reader');

                } catch (\Exception $e) {
                    return response()->json(["mensaje" => "Error al asignar el role", 400]);
                }
                return response()->json($user, 201);
            }
        } catch (\Exception $e) {
            return response()->json(["mensaje" => "Error al crear el usuario", 400]);
        }
    }

    public function assignRoleUser($request, $user) // Esta función, hace un shoftDelete de un usuario, devuelve mesaje OK o mensaje KO
    { 
        if ($user->hasRole('admin'))
            return (response()->json(["mensaje" => "Error no se puede modificar el rol al usuario administrador"], 400));
        if ($request->role == 'admin')
            return (response()->json(["mensaje" => "Error no se puede asignar el rol de administrador a un usuario"], 400));
        if ($user->roles()->get()->isNotEmpty()) {
            $user->roles()->detach();
        }
        $user->assignRole($request->role);
        return (response()->json(["mensaje" => "Rol asignado con exito"], 200));
    }

    public function deleteUser($user) // Esta función, hace un shoftDelete de un usuario, devuelve mesaje OK o mensaje KO
    { 
        if ($user && !$user->hasRole('admin')) {
            $user->delete();
            return (response()->json(["mensaje" => "Usuario eliminado con exito"], 200));
        } else {
            return (response()->json(["mensaje" => "Error al borrar el usuario"], 201));

        }
    }

    public function updateUser(Request $request,$data, User $user)
    {  
        $authUser = $request->user; //esto gracias al middleware creado de JWT realiza la comprobacion de que el usuario tenga el mismo token
        if (!$user) {
            return response()->json(["mensaje" => "Error al actualizar el usuario"], 404);
        }
        if (!$authUser->hasRole('admin') && $authUser->id !== $user->id) {
            return response()->json(["mensaje" => "No tienes permiso para modificar este usuario"], 403);
        }
    
        if (isset($data->img_user) && $data->hasFile('img_user')) { //manejo de la imagen
            $image = $data->file('img_user');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('avatars'), $imageName);
    
            if ($user->img_user && $user->img_user !== 'avatars/default.png') { // Borrar la imagen anterior si no es la default
                $oldImagePath = public_path($user->img_user);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
    
            $user->img_user = 'avatars/' . $imageName;
        }
    
        $user->update([// Actualizar usuario con la nueva info
            'name_user' => $data->name_user,
            'email_user' => $data->email_user,
            'bio' => $data->bio,
            'updated_at' => now(),
        ]);
        return response()->json(["mensaje" => "Usuario actualizado correctamente"], 200);
    }

    public function getInfoUser()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }
        return response()->json([
            'name_user' => $user->name_user,
            'img_user' => $user->img_user,
        ]);
    }

    public function getInfoFavUser()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        $posts = $user->posts()->withCount('favorites')->get(); //funcion automatica de laravel que hace que pueda contar la cantidad de favoritos que tiene ese post
    
        return response()->json($posts->map(function ($post) {
            return [
                'post_id' => $post->id,
                'title' => $post->title, // Opcional, si quieres mostrar el título
                'favorites_count' => $post->favorites_count,
            ];
        }));
    }

    public function getInfoViewUser()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }
        $totalViews = $user->posts()->sum('views');

        return response()->json([
            'User_id' => $user->id,
            'total_views' => $totalViews
        ]);
    }
}
?>