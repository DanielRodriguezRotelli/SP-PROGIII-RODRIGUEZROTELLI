<?php
require_once './models/Usuario.php';
require_once './models/GestorCSV.php';
require_once './models/AutentificadorJWT.php';
require_once './interfaces/IApiUsable.php';

class UsuarioController extends Usuario implements IApiUsable
{

  public function CargarUno($request, $response, $args)
  {
    $parametros = $request->getParsedBody();

    $nombre = $parametros['nombre'];
    $clave = $parametros['clave'];
    $perfil = $parametros["perfil"];
    $tipoDocumento = $parametros["tipoDocumento"];
    $nroDocumento = $parametros["nroDocumento"];
    $email = $parametros["email"];
    $fechaAlta = $parametros["fechaAlta"];


    $usr = new Usuario();
    $usr->nombre = $nombre;
    $usr->clave = $clave;
    $usr->perfil= $perfil;
    $usr->tipoDocumento = $tipoDocumento;
    $usr->nroDocumento = $nroDocumento;
    $usr->email= $email;
    $usr->fechaAlta= $fechaAlta;

    if(file_exists($_FILES["fotoUsuario"]["tmp_name"]))
    {
      $usr->fotoUsuario = $this->GuardarFoto($usr);
    } 
    else 
    {
      $usr->fotoUsuario = null;
    }

    $usr->crearUsuario();
    LogController::CargarUno($request, "Alta de un usuario");   

    $payload = json_encode(array("mensaje" => "Usuario creado con exito"));
    $response->getBody()->write($payload);
    $response = $response->withStatus(200);
    return $response->withHeader('Content-Type', 'application/json');

  }
  


  public function TraerUno($request, $response, $args)
  {
    $id = $args['id'];
    $usuario = Usuario::obtenerUsuarioPorId($id);

    if($usuario)
    {
      $payload = json_encode($usuario);
      $response->getBody()->write($payload);
      $response = $response->withStatus(200);
      return $response->withHeader('Content-Type', 'application/json');           
    }
    else
    {
      $payload = json_encode(array("mensaje" => "Usuario inválido. Verifique los datos ingresados."));
      $response->getBody()->write($payload);
      $response = $response->withStatus(400);
      return $response->withHeader('Content-Type', 'application/json');
    }
  }



  public static function obtenerUsuario($nroDocumento)
  {
    $usuario = Usuario::obtenerUsuario($nroDocumento);
    if($usuario)
    {
      return $usuario;
    }
  }
  


  public function TraerTodos($request, $response, $args)
  {
    $lista = Usuario::obtenerTodos();
    $payload = json_encode(array("listaUsuarios" => $lista));
    $response->getBody()->write($payload);
    $response = $response->withStatus(200);
    return $response->withHeader('Content-Type', 'application/json');
  }


  public function TraerTodosActivos($request, $response, $args)
  {
    $lista = Usuario::obtenerTodosActivos();
    $payload = json_encode(array("listaUsuariosActivos" => $lista));
    $response->getBody()->write($payload);
    $response = $response->withStatus(200);
    return $response->withHeader('Content-Type', 'application/json');
  }


  public function ListarOperacionesPorUsuario($request, $response, $args)
   {
        if(!isset($args['idUsuario'])) 
        {
            $mensaje = "Falta el id de Usuario en la URL.";
            $response = $response->withStatus(400);
            $payload = json_encode($mensaje);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        }

        $id = $args['idUsuario'];

        $auxUsuario = Usuario::obtenerUsuarioPorId($id);

        if (empty($auxUsuario)) 
        {
            $mensaje = "Error. El usuario no existe. Verifique los datos ingresados.";
            $payload = json_encode($mensaje);
            $response = $response->withStatus(400);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        } 
        else 
        { 
        $operaciones = Usuario::TraerOperacionesPorUsuario($id);
        //ARREGLAR LOS MENSAJES DE SALIDA!!!!!
            if($operaciones)
            {
                $payload = json_encode($operaciones);
                $response->getBody()->write($payload);
                $response = $response->withStatus(200);
                return $response->withHeader('Content-Type', 'application/json');           
            }
            else
            {
                $payload = json_encode(array("mensaje" => "No hay operaciones realizados por el Usuario."));
                $response->getBody()->write($payload);
                $response = $response->withStatus(400);
                return $response->withHeader('Content-Type', 'application/json');
            }
        }
    }



  public function ModificarUno($request, $response, $args)
  {
    $datos = json_decode(file_get_contents("php://input"), true);
    $usuarioAModificar = new Usuario();
    $usuarioAModificar->id=$datos["id"]; 
    $usuarioAModificar->nombre=$datos["nombre"]; 
    $usuarioAModificar->clave=$datos["clave"]; 
    $usuarioAModificar->perfil=$datos["perfil"];
    $usuarioAModificar->tipoDocumento=$datos["tipoDocumento"]; 
    $usuarioAModificar->nroDocumento=$datos["nroDocumento"]; 
    $usuarioAModificar->email=$datos["email"];
    $usuarioAModificar->fechaAlta=$datos["fechaAlta"]; 
    if(array_key_exists("fechaAlta",$datos))
    {
      $usuarioAModificar->fechaAlta=$datos["fechaAlta"]; 
    }

    $existeUsuario = Usuario::obtenerUsuarioPorId($usuarioAModificar->id);
    if($existeUsuario == NULL)
    {
      $mensaje = "Error. El id de usuario no existe.";
      $payload = json_encode($mensaje);
      $response = $response->withStatus(400);
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }

    if (Usuario::modificarUsuario($usuarioAModificar)) 
    {        
      $mensaje = "Usuario modificado con exito";
      $payload = json_encode($mensaje);
      $response = $response->withStatus(200);
    } 
    else
    {
      $mensaje = "No se pudo modificar el usuario. Intente nuevamente";
      $payload = json_encode($mensaje);
      $response = $response->withStatus(400);
    }
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function ActivarUno($request, $response, $args)
  {
    $datos = json_decode(file_get_contents("php://input"), true);
    $usuarioAModificar = new Usuario();
    $usuarioAModificar->id=$datos["id"]; 
    $usuarioAModificar->nombre=$datos["nombre"]; 
    $usuarioAModificar->clave=$datos["clave"]; 
    $usuarioAModificar->perfil=$datos["perfil"];
    $usuarioAModificar->tipoDocumento=$datos["tipoDocumento"]; 
    $usuarioAModificar->nroDocumento=$datos["nroDocumento"]; 
    $usuarioAModificar->email=$datos["email"];
    $usuarioAModificar->fechaAlta=$datos["fechaAlta"]; 
    if(array_key_exists("fechaAlta",$datos))
    {
      $usuarioAModificar->fechaAlta=$datos["fechaAlta"]; 
    }

    $existeUsuario = Usuario::obtenerUsuarioPorIdSinBaja($usuarioAModificar->id);
    if($existeUsuario == NULL)
    {
      $mensaje = "Error. El id de usuario no existe.";
      $payload = json_encode($mensaje);
      $response = $response->withStatus(400);
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }

    if (Usuario::modificarUsuario($usuarioAModificar)) 
    {        
      $mensaje = "Usuario activo nuevamente";
      $payload = json_encode($mensaje);
      $response = $response->withStatus(200);
    } 
    else
    {
      $mensaje = "No se pudo activar el usuario. Intente nuevamente";
      $payload = json_encode($mensaje);
      $response = $response->withStatus(400);
    }
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
  }



  public function BorrarUno($request, $response, $args)
  {
    $id = $args['id'];

    $existeUsuario = Usuario::obtenerUsuarioPorId($id);

    if ($existeUsuario == NULL) 
    {
      $mensaje = "Error. El usuario no existe. Verifique los datos ingresados";
      $payload = json_encode($mensaje);
      $response = $response->withStatus(400);
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }

    Usuario::borrarUsuario($id);

    $payload = json_encode(array("mensaje" => "Usuario borrado con exito"));
    $response->getBody()->write($payload);
    $response = $response->withStatus(200);
    return $response->withHeader('Content-Type', 'application/json');
  }



  public static function GuardarFoto($usuario)
  {
    $carpetaFotos = ".".DIRECTORY_SEPARATOR."ImagenesDeCliente/2023".DIRECTORY_SEPARATOR;
    if(!file_exists($carpetaFotos))
    {
        mkdir($carpetaFotos, 0777, true);
    }
  
    $nombreDeArchivo = $usuario->nroDocumento;
    $destino = $carpetaFotos . $nombreDeArchivo . ".jpg";
    $tmpName = $_FILES["fotoUsuario"]["tmp_name"];

    if (move_uploaded_file($tmpName, $destino)) 
    {
        echo "<br>La foto se guardó correctamente.";
    } 
    else 
    {
        echo "<br>La foto no pudo guardarse.";
    }

    return $destino;
  }


 
  
}
?>